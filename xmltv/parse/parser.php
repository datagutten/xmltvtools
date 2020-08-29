<?php
/**
 * Created by PhpStorm.
 * User: abi
 * Date: 04.10.2019
 * Time: 10:54
 */

namespace datagutten\xmltv\tools\parse;


use datagutten\xmltv\tools\common\files;
use datagutten\xmltv\tools\exceptions\InvalidXMLFileException;
use datagutten\xmltv\tools\exceptions\ProgramNotFoundException;
use FileNotFoundException;
use InvalidArgumentException;
use RuntimeException;
use SimpleXMLElement;

class parser
{
    public $debug = false;
    /**
     * @var files
     */
    public $files;

    /**
     * parser constructor.
     * @param string $xmltv_path XMLTV root path
     * @param array $sub_folders Sub folders of each channel to load data from
     * @throws FileNotFoundException XMLTV path not found
     */
    function __construct($xmltv_path, $sub_folders)
    {
        $this->files = new files($xmltv_path, $sub_folders);
    }

    /**
     * @var bool Ignore timezone in XMLTV data
     */
    public $ignore_timezone = false;

    /**
     * Run strtotime on a xmltv date and time, removing timezone if $this->ignore_timezone is set to true
     * @param string $time Date and time to be converted
     * @return int Unix timestamp
     */
    function strtotime($time)
    {
        if(!preg_match('/([0-9]{14})\s\+[0-9]+/', $time, $matches))
            throw new InvalidArgumentException('Not a valid xmltv date and time');

        if($this->ignore_timezone) {
            return strtotime($matches[1]);
        }
        else
            return strtotime($time);
    }
    /**
     * Combine schedule for multiple days to get all programs for the specified date
     * @param SimpleXMLElement[] $days Array with schedules
     * @param string $date Date to get
     * @return SimpleXMLElement[] Array with programs
     */
    public static function combine_days($days, $date=null)
    {
        $programs = [];
        foreach($days as $day)
        {
            foreach($day as $program)
            {
                if(!empty($date) && $date!=substr($program->attributes()->{'start'},0,8)) //Wrong date
                    continue;
                $programs[]=$program;
            }
        }
        return $programs;
    }

    /**
     * Get programs
     * @param string $channel XMLTV channel id
     * @param int $timestamp Timestamp to search
     * @param bool $multiple_days Combine data for current day and previous day to get all programs for the current day
     * @return array|SimpleXMLElement
     * @throws FileNotFoundException XMLTV file not found
     * @throws InvalidXMLFileException XMLTV file not valid
     */
    public function get_programs($channel,$timestamp = null,$multiple_days = null)
    {
        $xml_current_day=$this->files->load_file($channel,$timestamp);

        $first_start_time=$xml_current_day->{'programme'}->attributes()['start'];
        $first_start_hour=(int)substr($first_start_time,8,2);
        if($multiple_days===null)
        {
            //If the first program in the file starts before or on 01:59 the file contains a complete day
            if($first_start_hour>1)
                $multiple_days=true;
            elseif($first_start_hour<=1)
                $multiple_days=false;
        }
        if($multiple_days)
        {
            $days=array();
            try {
                $xml_previous_day = $this->files->load_file($channel, $timestamp - 86400);
                $days[] = $xml_previous_day;
            }
            catch (FileNotFoundException|InvalidXMLFileException $e) {
                // @codeCoverageIgnoreStart
                if ($this->debug)
                    echo $e->getMessage();
                // @codeCoverageIgnoreEnd
            }

            $days[]=$xml_current_day;

            try {
                $xml_next_day=$this->files->load_file($channel,$timestamp+86400);
                $days[]=$xml_next_day;
            }
            catch (FileNotFoundException|InvalidXMLFileException $e) {
                // @codeCoverageIgnoreStart
                if ($this->debug)
                    echo $e->getMessage();
                // @codeCoverageIgnoreEnd
            }
        }
        else
            $days=array($xml_current_day);

        return $this->combine_days($days, date('Ymd',$timestamp));
    }


    /**
     * Get program running at the given time or the next starting program
     * @param int $search_time Program timestamp
     * @param string $channel Channel id
     * @param string $mode now (running program at search time), next (next starting program) or nearest (program start with lowest difference to search time)
     * @return SimpleXMLElement
     * @throws ProgramNotFoundException
     */
    public function find_program($search_time, $channel, $mode='nearest')
    {
        try {
            $programs_xml = $this->get_programs($channel, $search_time);
        }
        catch (FileNotFoundException|InvalidXMLFileException $e)
        {
            throw new ProgramNotFoundException($e->getMessage(), 0, $e);
        }

        foreach($programs_xml as $key=>$program) //Loop through the programs
        {
            $program_start=$this->strtotime($program->attributes()->{'start'}); //Get program start

            if($key==0 && $this->debug)
                echo sprintf("First program start: %s date: %s\n",(string)$program->attributes()->{'start'},date('c',$program_start));

            $time_to_start[$key]=$program_start-$search_time; //How long is there until the program starts?

            if($this->debug)
                echo sprintf("Time to start: %s (%s seconds) Program starts: XML: %s date: %s Timestamp: %s\n",date('H:i',$time_to_start[$key]),$time_to_start[$key],$program->attributes()->{'start'},date('H:i',$program_start),$program_start);

            if($key==0 && $time_to_start[$key]>0) //First program has not started
            {
                if($mode=='next' || $mode=='nearest')
                    return $program;
                elseif($mode=='now')
                {
                    throw new ProgramNotFoundException('Nothing on air at given time');
                }
            }

            if($mode=='next' && $time_to_start[$key]>=0) //Find first program which has not started
                return $program;
            elseif($mode=='now')
            {
                if($time_to_start[$key]>0) //Current program has not started, return the previous (running now)
                    return $programs_xml[$key-1];
            }
            elseif($mode=='nearest' && $key>0) //Get the nearest start
            {
                $time_to_start_previous=$time_to_start[$key-1];
                $time_to_start_current=$time_to_start[$key];

                if($time_to_start_previous<0)
                    $time_to_start_previous=-$time_to_start_previous;
                if($time_to_start_current<0)
                    $time_to_start_current=-$time_to_start_current;
                if($this->debug)
                    echo sprintf("%s<%s\n",$time_to_start_previous,$time_to_start_current);
                if($time_to_start_previous<$time_to_start_current) //Previous diff was lower
                    return $programs_xml[$key-1];
                if(!isset($programs_xml[$key+1])) //If we are on the last program and haven't returned yet, return the current program
                {
                    if($this->debug)
                        echo "Returning last program\n";
                    return $program;
                }
            }
        }
        // @codeCoverageIgnoreStart
        throw new RuntimeException('Loop did not return');
        // @codeCoverageIgnoreEnd
    }

    /**
     * Parse a season and episode in xmltv notation
     * @param SimpleXMLElement $program
     * @param bool $string Set false to return array instead of formatted string
     * @return array|string
     */
    public static function season_episode($program,$string=true)
    {
        foreach($program->{'episode-num'} as $num) {
            if (preg_match('^([0-9]+)\s?\.\s?([0-9]+)/([0-9]+)^', $num, $matches) ||
                preg_match('^([0-9]+)\s?\.\s?([0-9]+)^', $num, $matches))
            {
                if ($string) {
                    $season = str_pad($matches[1] + 1, 2, '0', STR_PAD_LEFT);
                    $episode = str_pad($matches[2] + 1, 2, '0', STR_PAD_LEFT);
                    return "S{$season}E$episode";
                }
                else
                    return array('season' => $matches[1] + 1, 'episode' => $matches[2] + 1);
            }
            elseif(preg_match('^\.\s?([0-9]+)/([0-9]+)\s?\.^', $num, $matches)) //One shot series
            {
                if ($string)
                    return "EP" . str_pad($matches[1] + 1, 2, '0', STR_PAD_LEFT);
                else
                    return array('season' => 0, 'episode' => $matches[1] + 1);
            }
        }
        return null;
    }

    /**
     * @param $programs
     * @param $program_filter
     * @return array
     */
    public static function filter_programs($programs, $program_filter)
    {
        $programs_filtered = [];
        foreach($programs as $program)
        {
            if(stripos($program->{'title'}, $program_filter)===false)
                continue;
            $programs_filtered[] = $program;
        }
        return $programs_filtered;
    }
}