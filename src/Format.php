<?php


namespace SitPHP\Helpers;


class Format
{
    /**
     * Returns a human readable elapsed time
     *
     * @param float $microtime
     * @param string $format The format to display
     * @param int $round
     * @return string
     */
    static function readableTime(float $microtime, int $round = 3, string $format = '%time%%unit%')
    {

        if($microtime >= 3600 * 24 * 365) {
            $unit = 'years';
            $time = round($microtime / 3600, $round);
        }
        else if($microtime >= 3600 * 24) {
            $unit = 'days';
            $time = round($microtime / 3600, $round);
        }
        else if($microtime >= 3600){
            $unit = 'hrs';
            $time = round($microtime / 3600, $round);
        }
        else if($microtime >= 60){
            $unit = 'min';
            $time = round($microtime / 60, $round);
        }
        else if ($microtime >= 1) {
            $unit = 's';
            $time = round($microtime, $round);
        } else {
            $unit = 'ms';
            $time = round($microtime * 1000);
        }

        return strtr($format, ['%time%' => $time, '%unit%' => $unit]);
    }

    /**
     * Returns a human readable size
     *
     * @param int $size
     * @param string $format The format to display
     * @param int $round
     * @return  string
     */
    static function readableSize($size, int $round = 3, string $format = '%size%%unit%')
    {
        $mod = 1024;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $size >= $mod; $i++) {
            $size /= $mod;
        }
        if($i === 0){
            $size = round($size);
        } else {
            $size = round($size, $round);
        }
        $unit = $units[$i];
        return strtr($format, ['%size%' => $size, '%unit%' => $unit]);
    }
}