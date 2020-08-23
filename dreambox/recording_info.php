<?php
/**
 * Created by PhpStorm.
 * User: abi
 * Date: 04.10.2019
 * Time: 15:21
 */

namespace datagutten\dreambox;


use datagutten\xmltv\tools\common\channel_info;
use datagutten\xmltv\tools\exceptions;
use FileNotFoundException;

class recording_info
{
    /**
     * @var channel_info
     */
    public $channels;

    /**
     * recording_info constructor.
     */
    function __construct()
    {
        $this->channels = new channel_info();
    }

    /**
     * @param string $input File name
     * @return array Date, time and channel
     * @throws exceptions\InvalidFileNameException File name could not be parsed
     */
    public static function parse_file_name($input)
    {
        if(!preg_match('^([0-9]{8} [0-9]{4}) - (.*) - (.*)\.ts^U',$input,$result))
        {
            throw new exceptions\InvalidFileNameException($input);
        }
        else
            return array('datetime'=>$result[1],'channel'=>$result[2]);
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
        $info['name'] = preg_replace('#\s?\(R\)$#', '', $info['name']);
        $info['title'] = $info['name'];
        $info['season_episode'] = eit_parser::season_episode($info['short_description']);
        $start = sprintf('%d-%02d-%02d %02d:%02d:%02d +00:00',$info['date'][0], $info['date'][1], $info['date'][2], $info['time'][0], $info['time'][1], $info['time'][2]);
        $info['start_timestamp'] = strtotime($start);

        if ($mode == 'array')
            return $info;
        else
            return $info['title'];
    }
}