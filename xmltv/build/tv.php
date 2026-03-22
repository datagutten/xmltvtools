<?php


namespace datagutten\xmltv\tools\build;


use DOMDocument;
use SimpleXMLElement;

class tv
{
    /**
     * @var SimpleXMLElement
     */
    public $xml;

    public $channel;

    public $language;

    public $generator;

    /**
     * tv constructor.
     * @param string $channel Channel id
     * @param string $language Language
     * @param string $generator
     */
    function __construct(string $channel, string $language, $generator = 'php-xmltv-grabber')
    {
        $this->channel = $channel;
        $this->language = $language;
        $this->generator = $generator;
        $this->xml = new SimpleXMLElement(file_get_contents(__DIR__ . '/template.xml'));
        if (!empty($generator))
            $this->generator('php-xmltv-grabber', 'https://github.com/datagutten/xmltvgrabber');
    }

    public function generator(?string $name = null, ?string $url = null): void
    {
        if (!empty($name))
            $this->xml->addAttribute('generator-info-name', $name);
        if (!empty($url))
            $this->xml->addAttribute('generator-info-url', $url);
    }

    public function source(string $source): void
    {
        $this->xml->addAttribute('source-info-url', $source);
    }

    function format_output()
    {
        if(empty($this->xml->{'programme'}))
            return null;
        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($this->xml->asXML());
        return $dom->saveXML();
    }
}