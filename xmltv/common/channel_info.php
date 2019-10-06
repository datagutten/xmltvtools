<?php


namespace datagutten\xmltv\tools\common;


use InvalidArgumentException;
use SimpleXMLElement;

class channel_info
{
    /**
     * @var SimpleXMLElement
     */
    public $xml;
    function __construct()
    {
        $this->xml = simplexml_load_file(__DIR__.'/channel_mappings.xml');
    }
    function id_to_name($id)
    {
        $result = $this->xml->xpath(sprintf('/mappings/channel[@id="%s"]/name', $id));
        if(empty($result))
            throw new InvalidArgumentException($id.' not found');
        return (string)$result[0];

    }
    function name_to_id($name)
    {
        $result = $this->xml->xpath(sprintf('/mappings/channel/name[.="%s"]/../@id', $name));
        if(empty($result))
            throw new InvalidArgumentException($name.' not found');
        return (string)$result[0];
    }
}