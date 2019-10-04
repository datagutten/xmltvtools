<?php


namespace datagutten\xmltv\tools\build;


use DOMDocument;
use FileNotFoundException;
use SimpleXMLElement;
use Symfony\Component\Filesystem\Filesystem;

class tv
{
    /**
     * @var SimpleXMLElement
     */
    public $xml;

    public $channel;

    public $language;

    public $folder;

    public $generator;

    /**
     * tv constructor.
     * @param $folder
     * @param string $generator
     * @throws FileNotFoundException
     */
    function __construct($folder, $generator = 'php-xmltv-grabber')
    {
        $this->folder = $folder;
        $this->generator = $generator;
        if(!file_exists($folder))
            throw new FileNotFoundException($folder);
        $this->init_xml();
    }

    function init_xml()
    {
        $this->xml = new SimpleXMLElement(file_get_contents(__DIR__.'/template.xml'));
        $this->xml->addAttribute('generator-info-name', $this->generator);
        $this->xml->addAttribute('generator-info-url', 'https://github.com/datagutten/xmltvgrabber');
    }

    function format_output()
    {
        if(empty($this->xml->programme))
            return null;
        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($this->xml->asXML());
        return $dom->saveXML();
    }

    function save_file($timestamp)
    {
        $filesystem = new Filesystem();
        //TODO: Add sub folder as argument or something
        $folder = $this->folder.'/'.file::folder($this->channel, 'xmltv_quad', $timestamp);
        $filesystem->mkdir($folder);
        $filename = file::filename($this->channel, $timestamp, 'xml');
        $file = $folder.'/'.$filename;
        //$xml_string = $this->xml->asXML();
        $xml_string = $this->format_output();
        $filesystem->dumpFile($file, $xml_string);
        return $file;
    }
}