<?php


namespace datagutten\xmltv\tools\common;


use datagutten\tools\files\files as file_tools;
use DateTimeInterface;

class filename
{
    /**
     * Generate folder path
     * @param string $channel XMLTV channel id to use as base folder name
     * @param string $sub_folder Sub folder of the channel folder
     * @param int|DateTimeInterface $timestamp Timestamp to get year
     * @return string Folder path
     */
    public static function folder(string $channel, string $sub_folder, int|DateTimeInterface $timestamp): string
    {
        if (is_object($timestamp))
            $year = $timestamp->format('Y');
        else
            $year = date('Y', $timestamp);
        return file_tools::path_join($channel, $sub_folder, $year);
    }

    /**
     * Generate file name
     * @param string $channel XMLTV channel
     * @param int|DateTimeInterface $timestamp Timestamp to get date
     * @param string $extension File extension
     * @return string File name
     */
    public static function filename(string $channel, int|DateTimeInterface $timestamp, string $extension): string
    {
        if (is_object($timestamp))
            return sprintf('%s_%s.%s', $channel, $timestamp->format('Y-m-d'), $extension);
        else
            return $channel . '_' . date('Y-m-d', $timestamp) . '.' . $extension;
    }

    /**
     * Generate file and folder path
     * @param string $channel XMLTV channel
     * @param string $sub_folder Sub folder of the channel folder
     * @param int|DateTimeInterface $timestamp Timestamp to get date
     * @param string $extension File extension
     * @return string File name
     */
    public static function file_path(string $channel, string $sub_folder, int|DateTimeInterface $timestamp, string $extension): string
    {
        $folder = self::folder($channel,$sub_folder,$timestamp);
        $file = self::filename($channel,$timestamp,$extension);
        return file_tools::path_join($folder, $file);
    }
}