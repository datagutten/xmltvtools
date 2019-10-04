<?php


namespace datagutten\xmltv\tools\build;


use SimpleXMLElement;

class programme
{
    /**
     * @var SimpleXMLElement
     */
    public $xml;

    /**
     * @var string Default language used in title, sub-title and description elements
     */
    public $default_lang;

    /**
     * @var string Channel id
     */
    public $channel;

    /**
     * @var tv TV class instance
     */
    public $tv;

    /*
     *      * @param string $channel
     * @param string $default_lang Default language used in title, sub-title and description elements
     */

    /**
     * programme constructor.
     * @param int $start Start time as unix timestamp
     * @param tv TV class instance the program should be added to
     */
    function __construct($start, $tv)
    {
        $this->xml = $tv->xml->addChild('programme');
        //$this->xml = new SimpleXMLElement('<programme></programme>');
        $this->xml->addAttribute('start', date('YmdHis O', $start));
        $this->xml->addAttribute('channel', $tv->channel);
        $this->default_lang = $tv->language;
        //$tv->xml->addChild($this->xml);
    }

    function stop($stop)
    {
        $this->xml->addAttribute('stop', date('YmdHis O', $stop));
    }

    function title($title, $lang=null)
    {
        $title = str_replace('&', '&amp;', $title);
        $title = $this->xml->addChild('title', $title);
        if(!empty($this->default_lang) && empty($lang))
            $lang = $this->default_lang;
        if ($lang)
            $title->addAttribute('lang', $lang);
    }

    function sub_title($title, $lang=null)
    {
        $title = str_replace('&', '&amp;', $title);
        $title = $this->xml->addChild('sub-title', $title);
        if(!empty($this->default_lang) && empty($lang))
            $lang = $this->default_lang;
        if ($lang)
            $title->addAttribute('lang', $lang);
    }

    function description($description, $lang=null)
    {
        $description=str_replace('&','&amp;', $description);
        $desc=$this->xml->addChild('desc', $description);
        if(!empty($this->default_lang) && empty($lang))
            $lang = $this->default_lang;
        if($lang)
            $desc->addAttribute('lang', $lang);
    }

    /**
     * @param int $season
     * @param int $episode
     * @param int $total_episodes
     * @param string $onscreen
     */
    function series($episode, $season=null, $total_episodes=null, $onscreen=null)
    {
        $xmltv_ns='';
        if(!empty($season))
            $xmltv_ns.=$season-1;
        $xmltv_ns.='.';
        $xmltv_ns.=$episode-1;
        if(!empty($total_episodes))
            $xmltv_ns.='/'.$total_episodes;
        $xmltv_ns.='.';

        $episode_num=$this->xml->addChild('episode-num',$xmltv_ns);
        $episode_num->addAttribute('system','xmltv_ns');
    }

    function onscreen($onscreen)
    {
        $episode_num=$this->xml->addChild('episode-num',$onscreen);
        $episode_num->addAttribute('system','onscreen');
    }
}