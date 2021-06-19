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
     * programme constructor.
     * @param int $start Start time as unix timestamp
     * @param tv TV class instance the program should be added to
     */
    public function __construct(int $start, tv $tv)
    {
        $this->xml = $tv->xml->addChild('programme');
        $this->xml->addAttribute('start', date('YmdHis O', $start));
        $this->xml->addAttribute('channel', $tv->channel);
        $this->default_lang = $tv->language;
    }

    public function stop(int $stop)
    {
        $this->xml->addAttribute('stop', date('YmdHis O', $stop));
    }

    public function title(string $title, $lang='')
    {
        $title = str_replace('&', '&amp;', $title);
        $title = $this->xml->addChild('title', $title);
        if(!empty($this->default_lang) && empty($lang))
            $lang = $this->default_lang;
        if (!empty($lang))
            $title->addAttribute('lang', $lang);
    }

    public function sub_title($title, $lang='')
    {
        $title = str_replace('&', '&amp;', $title);
        $title = $this->xml->addChild('sub-title', $title);
        if(!empty($this->default_lang) && empty($lang))
            $lang = $this->default_lang;
        if (!empty($lang))
            $title->addAttribute('lang', $lang);
    }

    public function description(string $description, $lang='')
    {
        $description=str_replace('&', '&amp;', $description);
        $desc=$this->xml->addChild('desc', $description);
        if(!empty($this->default_lang) && empty($lang))
            $lang = $this->default_lang;
        if(!empty($lang))
            $desc->addAttribute('lang', $lang);
    }

    /**
     * @param int $episode Episode number
     * @param int $season Season number
     * @param int $total_episodes Total episodes
     * @param string $onscreen Season and episode formatted for screen
     */
    public function series(int $episode, $season=0, $total_episodes=0, $onscreen='')
    {
        $xmltv_ns='';
        if(!empty($season))
            $xmltv_ns.=$season-1;
        $xmltv_ns.='.';
        $xmltv_ns.=$episode-1;
        if(!empty($total_episodes))
            $xmltv_ns.='/'.$total_episodes;
        $xmltv_ns.='.';

        $episode_num=$this->xml->addChild('episode-num', $xmltv_ns);
        $episode_num->addAttribute('system', 'xmltv_ns');
        if(!empty($onscreen))
            $this->onscreen($onscreen);
    }

    public function onscreen(string $onscreen)
    {
        $episode_num=$this->xml->addChild('episode-num', $onscreen);
        $episode_num->addAttribute('system', 'onscreen');
    }
}