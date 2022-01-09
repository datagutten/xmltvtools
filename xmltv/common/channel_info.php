<?php


namespace datagutten\xmltv\tools\common;


use datagutten\xmltv\tools\exceptions\ChannelNotFoundException;
use SimpleXMLElement;

class channel_info
{
    /**
     * @var SimpleXMLElement
     */
    public $xml;

    /**
     * channel_info constructor.
     */
    public function __construct()
    {
        $this->xml = simplexml_load_file(__DIR__.'/channel_mappings.xml');
    }

    /**
     * Find channel name from xmltv id
     * @param string $id Channel id
     * @param bool $multiple Return an array with all possible channel names
     * @return string|string[] Channel name
     * @throws ChannelNotFoundException
     */
    public function id_to_name(string $id, bool $multiple = false)
    {
        $result = $this->xml->xpath(sprintf('/mappings/channel[@id="%s"]/name', $id));
        if(empty($result))
            throw new ChannelNotFoundException($id);
        if(!$multiple)
            return (string)$result[0];
        else
            return array_map('strval', $result);
    }

    /**
     * Find xmltv id from channel name
     * @param $name
     * @return string
     * @throws ChannelNotFoundException
     */
    public function name_to_id($name)
    {
        $result = $this->xml->xpath(sprintf('/mappings/channel/name[.="%s"]/../@id', trim($name)));
        if(empty($result))
            throw new ChannelNotFoundException($name);
        return (string)$result[0];
    }
}