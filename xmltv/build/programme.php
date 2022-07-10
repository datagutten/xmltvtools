<?php


namespace datagutten\xmltv\tools\build;


use DateTime;
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

    public function title(string $title, string $lang='')
    {
        $title = str_replace('&', '&amp;', $title);
        $title = $this->xml->addChild('title', $title);
        if(!empty($this->default_lang) && empty($lang))
            $lang = $this->default_lang;
        if (!empty($lang))
            $title->addAttribute('lang', $lang);
    }

    public function sub_title(string $title, string $lang='')
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

    /**
     * The date the programme or film was finished.  This will probably
     * be the same as the copyright date.
     * @param DateTime $date
     */
    public function date(DateTime $date)
    {
        $date_string = $date->format('YmdHis O');
        $this->xml->addChild('date', $date_string);
    }

    /**
     * Type of programme, eg 'soap', 'comedy' or whatever the
     * equivalents are in your language.
     * There's no predefined set of categories, and it's okay for a programme to belong to several.
     * @param string $category Category name
     * @param ?string $lang Language code
     */
    public function category(string $category, ?string $lang = null)
    {
        $xml = $this->xml->addChild('category', $category);
        if (!empty($lang))
            $xml->addAttribute('lang', $lang);
    }

    /**
     * A URL where you can find out more about the element that contains
     * it (programme or channel).  This might be the official site, or a fan
     * page, whatever you like really.
     *
     * If multiple url elements are given, the most authoritative or official
     * (which might conflict...) sites should be listed first.
     *
     * If the URL does not define a real (i.e. clickable) link then the scheme
     * should be set to something other than 'http://' such as 'uri://'
     *
     * The system attribute may be used to identify the source or target of the
     * url, or some other useful feature of the target.
     * @param string $url URL
     * @param string $system System
     */
    public function url(string $url, string $system)
    {
        $xml = $this->xml->addChild('url', $url);
        if (!empty($system))
            $xml->addAttribute('system', $system);
    }

    /**
     * Helper method to add IMDb URL
     * @param string $url IMDb URL or id
     */
    public function url_imdb(string $url)
    {
        if (!str_starts_with($url, 'http'))
            $url = 'https://www.imdb.com/title/' . $url;
        $this->url($url, 'IMDb');
    }
}