<?php
/**
 * Time Helper
 *
 * @package    BusinessHours\Helpers
 */

namespace BusinessHours\Helpers;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class TimeHelper
{
    /**
     * Format a time string for display
     *
     * @param string $time H:i:s or H:i format
     * @param bool $use24Hour
     * @return string
     */
    public static function formatTime($time, $use24Hour = false)
    {
        if (empty($time)) {
            return '';
        }

        $format = $use24Hour ? 'H:i' : 'g:i A';
        $ts = strtotime('1970-01-01 ' . $time);

        return $ts !== false ? date($format, $ts) : $time;
    }

    /**
     * Format a date for display
     *
     * @param string $date Y-m-d
     * @param string $format
     * @return string
     */
    public static function formatDate($date, $format = 'M j, Y')
    {
        $ts = strtotime($date);
        return $ts !== false ? date($format, $ts) : $date;
    }

    /**
     * Format a countdown duration
     *
     * @param int $seconds
     * @return string Human-readable countdown
     */
    public static function formatCountdown($seconds)
    {
        if ($seconds < 0) {
            return 'now';
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours . ($hours === 1 ? ' hour' : ' hours');
        }

        if ($minutes > 0) {
            $parts[] = $minutes . ($minutes === 1 ? ' minute' : ' minutes');
        }

        if (empty($parts)) {
            return 'less than a minute';
        }

        return implode(' ', $parts);
    }

    /**
     * Check if a time is within a time range
     *
     * @param string $time H:i:s or H:i
     * @param string $rangeStart H:i:s or H:i
     * @param string $rangeEnd H:i:s or H:i
     * @return bool
     */
    public static function isWithinRange($time, $rangeStart, $rangeEnd)
    {
        $t = self::timeToSeconds($time);
        $s = self::timeToSeconds($rangeStart);
        $e = self::timeToSeconds($rangeEnd);

        return $t >= $s && $t < $e;
    }

    /**
     * Convert a time string to seconds since midnight
     *
     * @param string $time
     * @return int
     */
    public static function timeToSeconds($time)
    {
        $parts = explode(':', $time);
        $h = (int) ($parts[0] ?? 0);
        $m = (int) ($parts[1] ?? 0);
        $s = (int) ($parts[2] ?? 0);
        return ($h * 3600) + ($m * 60) + $s;
    }

    /**
     * Get the number of seconds between two times
     *
     * @param string $from H:i:s
     * @param string $to H:i:s
     * @return int
     */
    public static function secondsBetween($from, $to)
    {
        return self::timeToSeconds($to) - self::timeToSeconds($from);
    }

    /**
     * Get the current day of week (0=Sunday, 6=Saturday) in a timezone
     *
     * @param string $timezone
     * @return int
     */
    public static function getCurrentDayOfWeek($timezone = 'UTC')
    {
        $now = new \DateTime('now', new \DateTimeZone($timezone));
        return (int) $now->format('w');
    }

    /**
     * Get the current time (H:i:s) in a timezone
     *
     * @param string $timezone
     * @return string
     */
    public static function getCurrentTime($timezone = 'UTC')
    {
        $now = new \DateTime('now', new \DateTimeZone($timezone));
        return $now->format('H:i:s');
    }

    /**
     * Get the current date (Y-m-d) in a timezone
     *
     * @param string $timezone
     * @return string
     */
    public static function getCurrentDate($timezone = 'UTC')
    {
        $now = new \DateTime('now', new \DateTimeZone($timezone));
        return $now->format('Y-m-d');
    }

    /**
     * Get the current DateTime in a timezone
     *
     * @param string $timezone
     * @return \DateTime
     */
    public static function now($timezone = 'UTC')
    {
        return new \DateTime('now', new \DateTimeZone($timezone));
    }

    /**
     * Get the next occurrence of a specific day of week
     *
     * @param int $targetDay 0-6
     * @param string $timezone
     * @return \DateTime
     */
    public static function getNextDayOfWeek($targetDay, $timezone = 'UTC')
    {
        $now = self::now($timezone);
        $currentDay = (int) $now->format('w');

        $daysUntil = $targetDay - $currentDay;
        if ($daysUntil <= 0) {
            $daysUntil += 7;
        }

        $next = clone $now;
        $next->modify("+{$daysUntil} days");
        $next->setTime(0, 0, 0);

        return $next;
    }

    /**
     * Get the day name from day number
     *
     * @param int $dayOfWeek 0-6
     * @param bool $short
     * @return string
     */
    public static function getDayName($dayOfWeek, $short = false)
    {
        $days = [
            0 => ['Sunday', 'Sun'],
            1 => ['Monday', 'Mon'],
            2 => ['Tuesday', 'Tue'],
            3 => ['Wednesday', 'Wed'],
            4 => ['Thursday', 'Thu'],
            5 => ['Friday', 'Fri'],
            6 => ['Saturday', 'Sat'],
        ];

        if (!isset($days[$dayOfWeek])) {
            return 'Unknown';
        }

        return $short ? $days[$dayOfWeek][1] : $days[$dayOfWeek][0];
    }
}
