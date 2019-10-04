<?php


namespace datagutten\xmltv\tools\common;


class file
{
    public static function folder($channel, $sub_folder='xmltv_php', $timestamp=null)
    {
        if(empty($timestamp))
            $timestamp=strtotime('midnight');

        return sprintf('%s/%s/%s', $channel, $sub_folder, date('Y',$timestamp));
    }
    public static function filename($channel,$timestamp,$extension)
    {
        return $channel.'_'.date('Y-m-d',$timestamp).'.'.$extension;
    }
    public static function file_path($channel,$sub_folder,$timestamp,$extension)
    {
        $folder = self::folder($channel,$sub_folder,$timestamp);
        $file = self::filename($channel,$timestamp,$extension);
        return $folder.'/'.$file;
    }
}