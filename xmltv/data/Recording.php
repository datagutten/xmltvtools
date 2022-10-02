<?php


namespace datagutten\xmltv\tools\data;

use datagutten\dreambox;
use datagutten\video_tools\exceptions as video_exceptions;
use datagutten\xmltv\tools\exceptions as xmltv_exceptions;
use datagutten\xmltv\tools\exceptions\XMLTVException;
use datagutten\xmltv\tools\parse;
use DependencyFailedException;
use FileNotFoundException;
use InvalidArgumentException;

class Recording extends RecordingFile
{
    /**
     * @var dreambox\recording_info
     */
    public dreambox\recording_info $dreambox;

    /**
     * @var string Channel name from file name
     */
    public string $channel_name;

    /**
     * @var Program
     */
    public Program $eit;

    /**
     * @var parse\merger
     */
    public parse\merger $xmltv_parser;

    /**
     * recording constructor.
     * @param string $file Recording file
     * @param string $xmltv_path XMLTV root path
     * @param array $xmltv_sub_folders Sub folders of each channel to load data from
     * @param bool $ignore_file_names Ignore errors for file names that can not be parsed
     * @throws FileNotFoundException
     * @throws xmltv_exceptions\InvalidFileNameException
     * @throws XMLTVException
     */
    public function __construct(string $file, string $xmltv_path = '', array $xmltv_sub_folders = ['xmltv'], bool $ignore_file_names = false, bool $require_eit = true)
    {
        parent::__construct($file);

        $this->dreambox = new dreambox\recording_info();

        try {
            $this->duration = $this->duration();
        } catch (DependencyFailedException|video_exceptions\DurationNotFoundException $e) {
            trigger_error('Unable to get duration: ' . $e->getMessage());
        }

        try {
            $info = $this->dreambox->parse_file_name($file);
            $this->parseStartEnd($info['datetime']); //Date and time from file name
            if(!empty($this->duration))
                $this->calcEnd();
            $this->channel_name = $info['channel']; //Channel from file name
        } catch (xmltv_exceptions\InvalidFileNameException $e) {
            if (!$ignore_file_names)
                throw $e;
        }

        try {
            $this->eit = $this->eitInfo();
        } catch (FileNotFoundException $e) {
            if ($require_eit)
                throw $e;
        }

        if(!empty($xmltv_path))
            $this->xmltv_parser = new parse\merger($xmltv_path, $xmltv_sub_folders);
    }

    /**
     * Get XMLTV channel id
     * @return string XMLTV channel id
     * @throws xmltv_exceptions\ChannelNotFoundException Channel not found
     */
    public function channelId(): string
    {
        return $this->dreambox->channels->name_to_id($this->channel_name);
    }

    /**
     * Get programs in the recording
     * @return Program[]
     * @throws xmltv_exceptions\ProgramNotFoundException Program not found
     * @throws xmltv_exceptions\ChannelNotFoundException Channel not found
     */
    public function programs(): array
    {
        if(empty($this->xmltv_parser))
            throw new InvalidArgumentException('XMLTV path not specified');
        if(empty($this->start_obj))
            throw new xmltv_exceptions\ProgramNotFoundException('Start time not set, unable to search programs');

        $channel = $this->channelId();

        $first_program_xml = $this->xmltv_parser->find_program($this->start_obj->getTimestamp(), $channel, 'nearest', true);
        $first_program = Program::fromXMLTV($first_program_xml);
        $info_array = [$first_program];
        $end_timestamp = $first_program->end_timestamp;
        if(empty($end_timestamp))
            throw new xmltv_exceptions\ProgramNotFoundException('Invalid end time');

        while($end_timestamp<$this->end_timestamp) {
            try {
                $program_xml = $this->xmltv_parser->find_program($end_timestamp, $channel, 'next', true);
            }
            catch (xmltv_exceptions\ProgramNotFoundException $e)
            {
                break;
            }
            if ((string)$program_xml->attributes()->{'start'} == (string)$program_xml->attributes()->{'stop'})
                break;
            $info_array[] = Program::fromXMLTV($program_xml);
            $end_timestamp = strtotime($program_xml->attributes()->{'stop'});
        }
        return $info_array;
    }

    /**
     * Find the nearest program start in the recording
     * @return Program
     * @throws xmltv_exceptions\ProgramNotFoundException Program not found
     * @throws xmltv_exceptions\ChannelNotFoundException Channel not found
     */
    public function nearestProgram(): Program
    {
        if(empty($this->xmltv_parser))
            throw new InvalidArgumentException('XMLTV path not specified');
        if(empty($this->start_obj))
            throw new xmltv_exceptions\ProgramNotFoundException('Start time not set, unable to search programs');

        $xmltv = $this->xmltv_parser->find_program($this->start_timestamp, $this->channelId(), 'nearest');
        return Program::fromXMLTV($xmltv);
    }

    public function time(): string
    {
        if (!empty($this->end_obj))
            return sprintf('%s-%s', $this->start_obj->format('H:i'), $this->end_obj->format('H:i'));
        else
            return $this->start_obj->format('H:i');
    }

    public function eitTime(): string
    {
        return sprintf('%s-%s', $this->eit->start_obj->format('H:i'), $this->eit->end_obj->format('H:i'));
    }

    /**
     * Get information from EIT file
     * @return Program
     * @throws FileNotFoundException EIT file not found
     */
    public function eitInfo(): Program
    {
        $eit_file = sprintf('%s/%s.eit', $this->pathinfo['dirname'], $this->pathinfo['filename']);
        return Program::fromEIT($eit_file);
    }
}
