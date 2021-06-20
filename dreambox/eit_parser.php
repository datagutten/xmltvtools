<?php


namespace datagutten\dreambox;

function int($int)
{
    return (int)$int;
}

/**
 * Class eit_parser
 * @package datagutten\dreambox
 * https://de.wikipedia.org/wiki/Event_Information_Table
 * Based on https://github.com/betonme/e2openplugin-EnhancedMovieCenter/blob/master/src/EitSupport.py
 */
class eit_parser
{
    public static function parseMJD($MJD)
    {
        # Parse 16 bit unsigned int containing Modified Julian Date,
        # as per DVB-SI spec
        # returning year,month,day
        $YY = int(($MJD - 15078.2) / 365.25);
        $MM = int(($MJD - 14956.1 - int($YY * 365.25)) / 30.6001);
        $D = $MJD - 14956 - int($YY * 365.25) - int($MM * 30.6001);
        $K = 0;
        if ($MM == 14 || $MM == 15)
            $K = 1;

        return [(1900 + $YY + $K), ($MM - 1 - $K * 12), $D];
    }

    public static function unBCD($byte)
    {
        return ($byte >> 4) * 10 + ($byte & 0xf);
    }

    public static function parse_header($data)
    {
        $string = substr($data, 0, 12);
        //$data = unpack('S/asdf/S/C/C/C/C/C/C/S', $string);
        $data = unpack('nid/ndate/CtimeH/CtimeM/CtimeS/CdurationH/CdurationM/CdurationS/nstatus', $string);

        $date = self::parseMJD($data['date']);
        $duration = [self::unBCD($data['durationH']), self::unBCD($data['durationM']), self::unBCD($data['durationS'])];
        $time = [self::unBCD($data['timeH']), self::unBCD($data['timeM']), self::unBCD($data['timeS'])];

        return ['date'=>$date, 'duration'=>$duration, 'time'=>$time];
    }

    public static function parse($data)
    {
        if (strlen($data) < 12)
            die('Invalid file');
        //0-12 = event_id date, time, duration, running_status, free_ca_mode, descriptors_len
        $eit = self::parse_header($data);

        $pos = 0;
        $pos = $pos + 12;
        $end_pos = strlen($data);
        while ($pos < $end_pos)
        {
            $rec = ord($data[$pos]);
            if ($pos + 1 >= $end_pos)
                break;
            $length = ord($data[$pos + 1]) + 2;

            if ($rec == 0x4D) {
                $descriptor_tag = $data[$pos + 1];
                $descriptor_length = $data[$pos + 2];
                $ISO_639_language_code = strtoupper(substr($data, $pos + 2, 3));
                $event_name_length = ord($data[$pos + 5]);

                $name_event_description = self::get_string($data, $pos + 6, $pos + 6 + $event_name_length);
                $eit['name'] = $name_event_description;

                $short_event_description = self::get_string($data, $pos + 7 + $event_name_length, $pos + $length);
                $eit['short_description'] = $short_event_description;

            } elseif ($rec == 0x4E) {
                $extended_event_description = self::get_string($data, $pos + 8, $pos + $length);
                $eit['description'] = $extended_event_description;
            }
            $pos += $length;
        }
        return $eit;
    }

    /**
     * Get codepage
     * @param int $code
     * @return string
     */
    public static function get_codepage(int $code): ?string
    {
        if ($code == 1)
            return 'iso-8859-5';
        elseif ($code == 2)
            return 'iso-8859-6';
        elseif ($code == 3)
            return 'iso-8859-7';
        elseif ($code == 4)
            return 'iso-8859-8';
        elseif ($code == 5)
            return 'iso-8859-9';
        elseif ($code == 6)
            return 'iso-8859-10';
        elseif ($code == 7)
            return 'iso-8859-11';
        elseif ($code == 9)
            return 'iso-8859-13';
        elseif ($code == 10)
            return 'iso-8859-14';
        elseif ($code == 11)
            return 'iso-8859-15';
        elseif ($code == 21)
            return 'utf-8';
        else
            return null;
    }

    public static function get_string($data, $start, $end): string
    {
        if($start===$end)
            return '';
        $codepage = self::get_codepage(ord($data[$start])); //First byte in string is codepage
        $string = '';
        for ($i = $start + 1; $i < $end; $i++)
        {
            $string .= $data[$i];
        }
        if ($codepage !== 'utf-8')
            return utf8_encode($string);
        else
            return $string;
    }

    public static function season_episode($short_event_description)
    {
        if(preg_match('#\(([0-9]+)(?::([0-9]+))?(?:/s([0-9]+))?\)#',$short_event_description,$season_episode))
        {
            $info['raw_season_episode_string']=$season_episode[0];

            if(empty($season_episode[3]))
            {
                $info['season']=0;
                $info['episode']=(int)$season_episode[1];
            }
            else
            {
                $info['season']=(int)$season_episode[3];
                $info['episode']=(int)$season_episode[1];
            }
            return $info;
        }
        else
            return null;
    }
}