<?php


namespace datagutten\xmltv\tools\data;


use datagutten\video_tools\exceptions as video_exceptions;
use datagutten\video_tools\video;
use DependencyFailedException;
use FileNotFoundException;

class recording_file
{
    /**
     * @var string Full path to recording file
     */
    public $file;

    /**
     * @var array Result from pathinfo()
     */
    public $pathinfo;

    /**
     * @var int Recording duration in seconds
     */
    public $duration = 0;

    /**
     * recording_file constructor.
     * @param $file
     * @throws FileNotFoundException
     */
    function __construct($file)
    {
        if(!file_exists($file))
            throw new FileNotFoundException($file);
        $this->file = $file;
        $this->pathinfo = pathinfo($file);
    }

    function basename()
    {
        return $this->pathinfo['basename'];
    }

    /**
     * Get recording duration
     * Requires package datagutten/video-tools
     * @return float Duration
     * @throws video_exceptions\DurationNotFoundException
     * @throws DependencyFailedException
     */
    function get_duration()
    {
        return video::duration($this->file);
    }

    /**
     * @return false|float|string
     * @throws video_exceptions\DurationNotFoundException
     * @throws DependencyFailedException
     */
    function duration()
    {
        $file = $this->file.'.duration';
        if(!file_exists($file))
        {
            $duration = $this->get_duration();
            file_put_contents($file, $duration);
            return $duration;
        }
        else
        {
            $duration = file_get_contents($file);
            if(empty($duration)) {
                unlink($file);
                return $this->duration();
            }
            else
                return $duration;

        }
    }

    /**
     * Get duration as a hours:minutes:seconds string
     * @throws video_exceptions\DurationNotFoundException|DependencyFailedException
     */
    function duration_hms()
    {
        return video::seconds_to_time($this->duration());
    }
}