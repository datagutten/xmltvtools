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
    function __construct()
    {
        $this->xml = simplexml_load_file(__DIR__.'/channel_mappings.xml');
    }

    /**
     * Find channel name from xmltv id
     * @param $id
     * @return string
     * @throws ChannelNotFoundException
     */
    function id_to_name($id)
    {
        $result = $this->xml->xpath(sprintf('/mappings/channel[@id="%s"]/name', $id));
        if(empty($result))
            throw new ChannelNotFoundException($id);
        return (string)$result[0];

    }

    /**
     * Find xmltv id from channel name
     * @param $name
     * @return string
     * @throws ChannelNotFoundException
     */
    function name_to_id($name)
    {
        $result = $this->xml->xpath(sprintf('/mappings/channel/name[.="%s"]/../@id', $name));
        if(empty($result))
            throw new ChannelNotFoundException($name);
        return (string)$result[0];
    }
}