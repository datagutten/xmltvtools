<?php


namespace datagutten\xmltv\tools\common;


use datagutten\xmltv\tools\exceptions\InvalidXMLFileException;
use FileNotFoundException;
use InvalidArgumentException;
use SimpleXMLElement;
use Symfony\Component\Filesystem\Filesystem;

class files
{
    public $xmltv_path;
    /**
     * @var array Sub folders in order
     */
    public $sub_folders;
    /**
     * @var Filesystem
     */
    public $filesystem;

    /**
     * files constructor.
     * @param string $xmltv_path XMLTV root path
     * @param array $sub_folders Sub folders of each channel to load data from
     * @throws FileNotFoundException XMLTV path not found
     */
    function __construct($xmltv_path, $sub_folders)
    {
        libxml_use_internal_errors(true);
        if(empty($xmltv_path))
            throw new InvalidArgumentException('Empty argument: xmltv_path');
        $this->xmltv_path = $xmltv_path;

        if(!file_exists($this->xmltv_path))
            throw new FileNotFoundException($this->xmltv_path);
        if(!empty($sub_folders))
            $this->sub_folders = $sub_folders;
        else
            throw new InvalidArgumentException('Empty argument: sub_folders');

        $this->filesystem = new Filesystem();
    }

    /**
     * @param string $channel
     * @param int $timestamp
     * @param string $sub_folder
     * @param string $extension
     * @param bool $create Create folder
     * @return string File name
     */
    function file($channel,$timestamp = null,$sub_folder = null, $extension = 'xml', $create = false)
    {
        if (!preg_match('/[a-z0-9]+\.[a-z]+/', $channel)) {
            throw new InvalidArgumentException('Invalid channel id: ' . $channel);
        }
        if(empty($timestamp))
            $timestamp=strtotime('midnight');
        if(empty($sub_folder))
            $sub_folder = $this->sub_folders[0];

        $folder = $this->xmltv_path.'/'.filename::folder($channel, $sub_folder, $timestamp);
        if($create)
            $this->filesystem->mkdir($folder);
        $file = $folder.'/'.filename::filename($channel, $timestamp, $extension);
        if(file_exists($file))
            return realpath($file);
        else
            return $file;
    }

    /**
     * @param string $channel
     * @param int $timestamp
     * @param string $sub_folder
     * @return SimpleXMLElement
     * @throws FileNotFoundException XML file not found
     * @throws InvalidXMLFileException XML file has no <programme> element
     */
    function load_file($channel, $timestamp = null, $sub_folder = null)
    {
        if(!empty($sub_folder))
            $folders = [$sub_folder];
        else
            $folders = $this->sub_folders;

        $error = new InvalidXMLFileException('No files found');
        foreach ($folders as $sub_folder)
        {
            $file = $this->file($channel, $timestamp, $sub_folder);
            if(file_exists($file))
            {
                $xml = simplexml_load_file($file);
                if(false===$xml)
                {
                    $error = libxml_get_last_error();
                    $error = new InvalidXMLFileException($error->message);
                }
                elseif(empty($xml->programme))
                    $error = new InvalidXMLFileException('Invalid XML file: '.$file);
                else
                    return $xml;
            }
            else
                $error = new FileNotFoundException($file);
        }

        throw $error;
    }
}