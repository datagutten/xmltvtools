<?php


namespace datagutten\xmltv\tools\data;

use datagutten\dreambox\eit_parser;
use datagutten\xmltv\tools\exceptions\XMLTVException;
use datagutten\xmltv\tools\parse\parser;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use FileNotFoundException;
use InvalidArgumentException;
use SimpleXMLElement;

/**
 * Class to represent a XMLTV program
 */
class Program
{
    /**
     * @var ?int Season
     */
    public ?int $season;
    /**
     * @var ?int Episode
     */
    public ?int $episode;
    /**
     * @var string Title
     */
    public $title;
    /**
     * @var int Start timestamp
     */
    public int $start_timestamp;
    /**
     * @var ?int End timestamp
     */
    public ?int $end_timestamp = null;
    /**
     * @var string Formatted start time
     */
    public string $start;
    /**
     * @var string Formatted end time
     */
    public string $end;

    /**
     * @var DateTimeImmutable Program start
     */
    public DateTimeImmutable $start_obj;

    /**
     * @var DateTimeImmutable Program end
     */
    public DateTimeImmutable $end_obj;

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
     * @throws Exception Unable to parse date
     */
    public static function fromXMLTV(SimpleXMLElement $xml)
    {
        $program = new self();

        $program->generator = (string)$xml->xpath('/tv/@generator-info-name')[0];
        $program->parseStartEnd($xml->attributes()->{'start'}, $xml->attributes()->{'stop'} ?? null);

        if(isset($xml->title)) //Get the title
            $program->title=(string)$xml->title;

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
    public static function fromEIT(string $file)
    {
        if (!file_exists($file))
            throw new FileNotFoundException($file);
        $eit = eit_parser::parse(file_get_contents($file));

        $program = new self();
        try
        {
            $start = new DateTime('now', new DateTimeZone('GMT')); //Time zone from EIT is always GMT
            $start->setDate($eit['date'][0], $eit['date'][1], $eit['date'][2]);
            $start->setTime($eit['time'][0], $eit['time'][1], $eit['time'][2]);
            $start->setTimezone(new DateTimeZone(date_default_timezone_get())); //Convert the time to the local timezone

            $program->start_obj = DateTimeImmutable::createFromMutable($start);
            $program->end_obj = $program->start_obj->add(new DateInterval(sprintf('PT%dH%dM%dS', $eit['duration'][0], $eit['duration'][1], $eit['duration'][2])));
            $program->parseStartEnd();
        }
        catch (Exception $e)
        {//Unable to parse time
        }

        $program->title = preg_replace('#\s?\(R\)$#', '', $eit['name']);
        list(, $program->season, $program->episode) = array_values(eit_parser::season_episode($eit['short_description']));

        if(empty($eit['description']) && !empty($eit['short_description']))
            $program->description = $eit['short_description'];
        elseif(!empty($eit['description']))
            $program->description = $eit['description'];

        return $program;
    }

    public function formatEpisode()
    {
        return sprintf('S%02dE%02d', $this->season, $this->episode);
    }

    /**
     * Parse and format start and end time
     * Arguments are optional if start_obj and end_obj properties is set
     * @param string|null $start Start time string
     * @param string|null $end End time string
     * @throws XMLTVException Unable to parse time
     */
    public function parseStartEnd(?string $start = null, ?string $end = null)
    {
        if (!empty($start))
        {
            try
            {
                $this->start_obj = new DateTimeImmutable($start);
            }
            catch (Exception $e)
            {
                throw new XMLTVException('Unable to parse start time', $e->getCode(), $e);
            }
        }
        elseif (!isset($this->start_obj))
            throw new InvalidArgumentException('start_obj must be set if start argument is not provided');

        $this->start_timestamp = $this->start_obj->getTimestamp();
        $this->start = $this->start_obj->format('H:i');

        if (!empty($end))
            try
            {
                $this->end_obj = new DateTimeImmutable($end);
            }
            catch (Exception $e)
            {
                throw new XMLTVException('Unable to parse end time', $e->getCode(), $e);
            }
        if (!empty($this->end_obj))
        {
            $this->end_timestamp = $this->end_obj->getTimestamp();
            $this->end = $this->end_obj->format('H:i');

            if ($this->end_timestamp < $this->start_timestamp)
                $this->end_timestamp = null;
        }
    }

    public function header()
    {
        $header = sprintf('%s-%s %s', $this->start, $this->end, $this->title);
        if(!empty($this->episode))
            $header.= ' '.$this->formatEpisode();
        return $header;
    }
}
