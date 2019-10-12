<?php
/**
 * Created by PhpStorm.
 * User: abi
 * Date: 04.10.2019
 * Time: 15:21
 */

namespace datagutten\dreambox;


use datagutten\xmltv\tools\common\channel_info;
use datagutten\xmltv\tools\exceptions\ChannelNotFoundException;
use datagutten\xmltv\tools\parse\parser;
use datagutten\xmltv\tools\exceptions\ProgramNotFoundException;
use FileNotFoundException;
use InvalidArgumentException;
use SimpleXMLElement;

class recording_info
{
    /**
     * @var channel_info
     */
    public $channels;

    /**
     * @var parser
     */
    public $xmltv;

    /**
     * recording_info constructor.
     */
    function __construct()
    {
        $this->channels = new channel_info();
        $this->xmltv = new parser();
    }

    /**
     * @param string $input File name
     * @return array Date, time and channel
     * @throws InvalidArgumentException File name could not be parsed
     */
    public static function parse_file_name($input)
    {
        if(!preg_match('^([0-9]{8} [0-9]{4}) - (.*) - (.*)\.ts^U',$input,$result))
        {
            throw new InvalidArgumentException('Could not parse file name');
        }
        else
            return array('datetime'=>$result[1],'channel'=>$result[2]);
    }

    /**
     * Find information about a recorded file
     * @param $filename
     * @return SimpleXMLElement
     * @throws ProgramNotFoundException
     * @throws ChannelNotFoundException
     */
    public function recording_info($filename)
    {
        $info=$this->parse_file_name($filename);
        $timestamp=strtotime($info['datetime']);

        $channel_name=$info['channel'];
        $channel_id = $this->channels->name_to_id($channel_name);

        return $this->xmltv->find_program($timestamp,$channel_id,'nearest'); //Find the program start nearest to the search time
    }

    /**
     * @param string $eit_file eit file to be parsed
     * @param string $mode Return only the title or an array with season, episode and title
     * @return string|array
     * @throws FileNotFoundException
     */
    public static function parse_eit($eit_file, $mode = 'title')
    {
        if(!file_exists($eit_file))
            throw new FileNotFoundException($eit_file);
        $eit_file = file_get_contents($eit_file);
        $info = eit_parser::parse($eit_file);
        $info['title'] = $info['name'];
        $info['season_episode'] = eit_parser::season_episode($info['short_description']);

        if ($mode == 'array')
            return $info;
        else
            return $info['title'];
    }
}