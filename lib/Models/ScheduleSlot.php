<?php
/**
 * Schedule Slot Model
 *
 * Represents a single opening period within a schedule day.
 * Multiple slots per day allow split shifts and lunch breaks.
 *
 * @package    BusinessHours\Models
 */

namespace BusinessHours\Models;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class ScheduleSlot
{
    /** @var int */
    public $id;

    /** @var int */
    public $scheduleId;

    /** @var int 0=Sunday, 6=Saturday */
    public $dayOfWeek;

    /** @var string H:i:s */
    public $openTime;

    /** @var string H:i:s */
    public $closeTime;

    /** @var string|null Optional label (e.g. "Morning Shift") */
    public $label;

    /**
     * Day name mapping
     */
    const DAY_NAMES = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    /**
     * Short day name mapping
     */
    const DAY_SHORT_NAMES = [
        0 => 'Sun',
        1 => 'Mon',
        2 => 'Tue',
        3 => 'Wed',
        4 => 'Thu',
        5 => 'Fri',
        6 => 'Sat',
    ];

    /**
     * Create a ScheduleSlot instance from a database row
     *
     * @param object|array $data
     * @return self
     */
    public static function fromRow($data)
    {
        $data = (object) $data;
        $slot = new self();

        $slot->id         = (int) $data->id;
        $slot->scheduleId = (int) $data->schedule_id;
        $slot->dayOfWeek  = (int) $data->day_of_week;
        $slot->openTime   = (string) $data->open_time;
        $slot->closeTime  = (string) $data->close_time;
        $slot->label      = isset($data->label) ? $data->label : null;

        return $slot;
    }

    /**
     * Convert to array for database insertion
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'schedule_id' => $this->scheduleId,
            'day_of_week' => $this->dayOfWeek,
            'open_time'   => $this->openTime,
            'close_time'  => $this->closeTime,
            'label'       => $this->label,
        ];
    }

    /**
     * Convert to JSON-serializable array
     *
     * @return array
     */
    public function toJson()
    {
        return [
            'id'          => $this->id,
            'schedule_id' => $this->scheduleId,
            'day_of_week' => $this->dayOfWeek,
            'day_name'    => $this->getDayName(),
            'open_time'   => $this->openTime,
            'close_time'  => $this->closeTime,
            'label'       => $this->label,
        ];
    }

    /**
     * Get the full day name for this slot
     *
     * @return string
     */
    public function getDayName()
    {
        return isset(self::DAY_NAMES[$this->dayOfWeek])
            ? self::DAY_NAMES[$this->dayOfWeek]
            : 'Unknown';
    }

    /**
     * Get the short day name for this slot
     *
     * @return string
     */
    public function getDayShortName()
    {
        return isset(self::DAY_SHORT_NAMES[$this->dayOfWeek])
            ? self::DAY_SHORT_NAMES[$this->dayOfWeek]
            : '???';
    }

    /**
     * Check if a given time falls within this slot
     *
     * @param string $time Time to check in H:i:s or H:i format
     * @return bool
     */
    public function containsTime($time)
    {
        $check = $this->normalizeTime($time);
        $open  = $this->normalizeTime($this->openTime);
        $close = $this->normalizeTime($this->closeTime);

        return $check >= $open && $check < $close;
    }

    /**
     * Get the duration of this slot in minutes
     *
     * @return int
     */
    public function getDurationMinutes()
    {
        $open  = strtotime('1970-01-01 ' . $this->openTime);
        $close = strtotime('1970-01-01 ' . $this->closeTime);

        if ($close <= $open) {
            return 0;
        }

        return (int) (($close - $open) / 60);
    }

    /**
     * Format the open time for display
     *
     * @param bool $use24Hour Whether to use 24-hour format
     * @return string
     */
    public function formatOpenTime($use24Hour = false)
    {
        $format = $use24Hour ? 'H:i' : 'g:i A';
        return date($format, strtotime('1970-01-01 ' . $this->openTime));
    }

    /**
     * Format the close time for display
     *
     * @param bool $use24Hour Whether to use 24-hour format
     * @return string
     */
    public function formatCloseTime($use24Hour = false)
    {
        $format = $use24Hour ? 'H:i' : 'g:i A';
        return date($format, strtotime('1970-01-01 ' . $this->closeTime));
    }

    /**
     * Normalize a time string to H:i:s format
     *
     * @param string $time
     * @return string
     */
    private function normalizeTime($time)
    {
        $parts = explode(':', $time);
        $h = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
        $m = isset($parts[1]) ? str_pad($parts[1], 2, '0', STR_PAD_LEFT) : '00';
        $s = isset($parts[2]) ? str_pad($parts[2], 2, '0', STR_PAD_LEFT) : '00';
        return "{$h}:{$m}:{$s}";
    }
}
