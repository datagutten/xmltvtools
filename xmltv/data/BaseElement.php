<?php

namespace datagutten\xmltv\tools\data;

use datagutten\xmltv\tools\exceptions\XMLTVException;
use DateInterval;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use RuntimeException;

abstract class BaseElement
{
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
     * @var int Recording duration in seconds
     */
    public int $duration;

    /**
     * @var DateTimeImmutable Start time
     */
    public DateTimeImmutable $start_obj;

    /**
     * @var DateTimeImmutable End time
     */
    public DateTimeImmutable $end_obj;

    /**
     * @var DateInterval Duration
     */
    public DateInterval $duration_obj;

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

        if (!empty($end))
        {
            try
            {
                $this->end_obj = new DateTimeImmutable($end);
            }
            catch (Exception $e)
            {
                throw new XMLTVException('Unable to parse end time', $e->getCode(), $e);
            }
            $this->calcDuration();
        }

        $this->convertTimes();
    }

    /**
     * Convert datetime objects to timestamp and string
     */
    protected function convertTimes()
    {
        if (!empty($this->start_obj))
        {
            $this->start_timestamp = $this->start_obj->getTimestamp();
            $this->start = $this->start_obj->format('H:i');
        }

        if (!empty($this->end_obj))
        {
            $this->end_timestamp = $this->end_obj->getTimestamp();
            $this->end = $this->end_obj->format('H:i');

            if ($this->end_timestamp < $this->start_timestamp)
                $this->end_timestamp = null;
        }

        /*if(!empty($this->duration_obj))
            $this->duration = $this->duration_obj->s; //TODO: Check if this works with seconds above 60*/
    }

    /**
     * Set duration and calculate end time if needed
     * @param int $duration
     */
    public function setDuration(int $duration)
    {
        $this->duration_obj = new DateInterval(sprintf('PT%dS', $duration));
        if (empty($this->end_obj) && !empty($this->start_obj))
            $this->calcEnd();

        $this->convertTimes();
    }

    /**
     * Calculate end time from start time and duration
     */
    public function calcEnd(): DateTimeImmutable
    {
        $this->end_obj = $this->start_obj->add($this->duration_obj);
        $this->convertTimes();
        return $this->end_obj;
    }

    /**
     * Calculate duration from start and end time
     * @return DateInterval
     */
    public function calcDuration(): DateInterval
    {
        if (empty($this->end_obj))
            throw new RuntimeException('End not set, unable to calculate duration');

        $this->duration_obj = $this->end_obj->diff($this->start_obj);
        $this->convertTimes();
        return $this->duration_obj;
    }
}