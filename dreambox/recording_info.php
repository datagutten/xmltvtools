<?php
/**
 * Created by PhpStorm.
 * User: abi
 * Date: 04.10.2019
 * Time: 15:21
 */

namespace datagutten\dreambox;


use datagutten\xmltv\tools\common\channel_info;
use datagutten\xmltv\tools\parse\parser;
use datagutten\xmltv\tools\parse\ProgramNotFoundException;
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

    function __construct()
    {
        $this->channels = new channel_info();
        $this->xmltv = new parser();
    }

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
     */
    public function recording_info($filename)
    {
        $info=$this->parse_file_name($filename);
        $timestamp=strtotime($info['datetime']);

        $channel_name=$info['channel'];
        $channel_id = $this->channels->name_to_id($channel_name);

        return $this->xmltv->find_program($timestamp,$channel_id,'nearest'); //Find the program start nearest to the search time
    }
}