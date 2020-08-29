<?php


namespace datagutten\xmltv\tools\data;


use datagutten\dreambox;
use datagutten\video_tools\exceptions as video_exceptions;
use datagutten\xmltv\tools\exceptions as xmltv_exceptions;
use datagutten\xmltv\tools\exceptions\XMLTVException;
use datagutten\xmltv\tools\parse;
use DependencyFailedException;
use FileNotFoundException;
use InvalidArgumentException;

class recording extends recording_file
{
    /**
     * @var dreambox\recording_info
     */
    public $dreambox;

    /**
     * @var string Date and time for the recording start
     */
    public $start_datetime;

    /**
     * @var int Recording start timestamp
     */
    public $start_timestamp;

    /**
     * @var int Recording end timestamp
     */
    public $end_timestamp;

    /**
     * @var string Channel name from file name
     */
    public $channel_name;

    /**
     * @var program|array
     */
    public $eit;

    /**
     * @var parse\merger
     */
    public $xmltv_parser;

    /**
     * recording constructor.
     * @param string $file Recording file
     * @param string $xmltv_path XMLTV root path
     * @param array $xmltv_sub_folders Sub folders of each channel to load data from
     * @param bool $ignore_file_names Ignore errors for file names that can not be parsed
     * @throws FileNotFoundException
     * @throws xmltv_exceptions\InvalidFileNameException
     * @throws XMLTVException
     */
    function __construct($file, $xmltv_path = '', $xmltv_sub_folders = ['xmltv'], $ignore_file_names = False)
    {
        parent::__construct($file);

        $this->dreambox = new dreambox\recording_info();

        try {
            $this->duration = $this->duration();
        }
        catch (DependencyFailedException|video_exceptions\DurationNotFoundException $e) {
            trigger_error('Unable to get duration: '.$e->getMessage());
        }

        try {
            $info = $this->dreambox->parse_file_name($file);
            $this->start_datetime = $info['datetime']; //Date and time from file name
            $this->channel_name = $info['channel']; //Channel from file name
            $this->start_timestamp = strtotime($info['datetime']);
            $this->end_timestamp = $this->start_timestamp + $this->duration;
        } catch (xmltv_exceptions\InvalidFileNameException $e) {
            if (!$ignore_file_names)
                throw $e;
        }

        try {
            $this->eit = $this->eit_info();
        } catch (FileNotFoundException $e) {
            if (!$ignore_file_names)
                throw $e;
        }

        if(!empty($xmltv_path))
            $this->xmltv_parser = new parse\merger($xmltv_path, $xmltv_sub_folders);
    }

    /**
     * Get XMLTV channel id
     * @return string XMLTV channel id
     * @throws xmltv_exceptions\ChannelNotFoundException Channel not found
     */
    function channel_id() {
        return $this->dreambox->channels->name_to_id($this->channel_name);
    }

    /**
     * Get programs in the recording
     * @return array
     * @throws xmltv_exceptions\ProgramNotFoundException Program not found
     * @throws xmltv_exceptions\ChannelNotFoundException Channel not found
     */
    function programs()
    {
        if(empty($this->xmltv_parser))
            throw new InvalidArgumentException('XMLTV path not specified');

        $channel = $this->channel_id();
        $info_array = [];
        $end_timestamp = $this->start_timestamp;

        while($end_timestamp<$this->start_timestamp+$this->duration) {
            $program_xml = $this->xmltv_parser->find_program($end_timestamp, $channel);
            $end_timestamp = strtotime($program_xml->attributes()->{'stop'});
            $info_array[] = program::from_xmltv($program_xml);
        }
        return $info_array;
    }

    /**
     * Find the nearest program start in the recording
     * @return program
     * @throws xmltv_exceptions\ProgramNotFoundException Program not found
     * @throws xmltv_exceptions\ChannelNotFoundException Channel not found
     */
    function program_nearest()
    {
        if(empty($this->xmltv_parser))
            throw new InvalidArgumentException('XMLTV path not specified');

        $xmltv = $this->xmltv_parser->find_program($this->start_timestamp,$this->channel_id(),'nearest');
        return program::from_xmltv($xmltv);
    }

    function time()
    {
        return sprintf('%s-%s',date('H:i',$this->start_timestamp),date('H:i',$this->end_timestamp));
    }
    function eit_time()
    {
        return sprintf('%s-%s',date('H:i',$this->eit->start_timestamp),date('H:i',$this->eit->end_timestamp));
    }

    /**
     * Get information from EIT file
     * @return program
     * @throws FileNotFoundException EIT file not found
     */
    function eit_info()
    {
        $eit_file = sprintf('%s/%s.eit', $this->pathinfo['dirname'], $this->pathinfo['filename']);
        return program::from_eit($eit_file);
    }
}