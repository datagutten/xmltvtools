<?php


namespace datagutten\xmltv\tools\data;

use datagutten\dreambox\recording_info as dreambox_info;
use datagutten\video_tools\video;
use datagutten\xmltv\tools\parse\parser;
use FileNotFoundException;
use SimpleXMLElement;

/**
 * Class to represent a XMLTV program
 * @package datagutten\renamevideo
 */
class Program
{
    /**
     * @var int Season
     */
    public $season;
    /**
     * @var int Episode
     */
    public $episode;
    /**
     * @var string Title
     */
    public $title;
    /**
     * @var int Start timestamp
     */
    public $start_timestamp;
    /**
     * @var int End timestamp
     */
    public $end_timestamp;
    /**
     * @var string Formatted start time
     */
    public $start;
    /**
     * @var string Formatted end time
     */
    public $end;
    /**
     * @var string Sub title
     */
    public $sub_title;
    /**
     * @var string Description
     */
    public $description;
    /**
     * @var string Generator
     */
    public $generator;
    /**
     * @var array Categories
     */
    public $categories = [];

    /**
     * Get program from XMLTV
     * @param SimpleXMLElement $xml
     * @return Program program
     */
    public static function fromXMLTV($xml)
    {
        $program = new self();

        $program->generator = (string)$xml->xpath('/tv/@generator-info-name')[0];
        $program->start_timestamp = strtotime($xml->attributes()->{'start'});

        if(isset($xml->title)) //Get the title
            $program->title=(string)$xml->title;

        if(isset($xml->attributes()->{'stop'}))
            $program->end_timestamp = strtotime($xml->attributes()->{'stop'});

        $program->formatStartEnd();

        //Get the category
        if(isset($xml->{'category'})) {
            if(count($xml->{'category'}) == 1)
                $program->categories=[(string)$xml->{'category'}];
            else {
                foreach ($xml->{'category'} as $category) {
                    $program->categories[] = (string)$category;
                }
            }
        }

        if(isset($xml->{'sub-title'})) //Get the sub-title
            $program->sub_title=(string)$xml->{'sub-title'};

        if(isset($xml->desc)) //Get the description
            $program->description=(string)$xml->desc;

        //Get the episode-num string and convert it to season and episode
        $episode = parser::season_episode($xml, false);
        if(isset($xml->{'episode-num'}) && !empty($episode)) {
            $program->season = $episode['season'];
            $program->episode = $episode['episode'];
        }

        return $program;
    }

    /**
     * @param string $file EIT file
     * @return Program
     * @throws FileNotFoundException EIT file not found
     */
    public static function fromEIT($file)
    {
        $program = new self();
        $eit = dreambox_info::parse_eit($file, 'array');

        $program->title = $eit['title'];
        $program->start_timestamp = $eit['start_timestamp'];
        if(class_exists('datagutten\video_tools\video')) {
            $duration = video::time_to_seconds(implode(':', $eit['duration']));
            $program->end_timestamp = $program->start_timestamp + $duration;
        }
        $program->formatStartEnd();

        if(empty($eit['description']) && !empty($eit['short_description']))
            $program->description = $eit['short_description'];
        elseif(!empty($eit['description']))
            $program->description = $eit['description'];

        if(!empty($eit['season_episode'])) {
            $program->season = $eit['season_episode']['season'];
            $program->episode = $eit['season_episode']['episode'];
        }

        return $program;
    }

    public function formatEpisode()
    {
        return sprintf('S%02dE%02d', $this->season, $this->episode);
    }

    /**
     * @param int $timestamp
     * @return string
     */
    public static function formatTime($timestamp)
    {
        return date('H:i', $timestamp);
    }

    /**
     * Format start and end timestamp
     */
    public function formatStartEnd()
    {
        $this->start = self::formatTime($this->start_timestamp);
        if(!empty($this->end_timestamp))
            $this->end = self::formatTime($this->end_timestamp);
    }

    public function header()
    {
        $header = sprintf('%s-%s %s', $this->start, $this->end, $this->title);
        if(!empty($this->episode))
            $header.= ' '.$this->formatEpisode();
        return $header;
    }
}
