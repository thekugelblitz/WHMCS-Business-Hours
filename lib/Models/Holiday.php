<?php
/**
 * Holiday Model
 *
 * @package    BusinessHours\Models
 */

namespace BusinessHours\Models;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class Holiday
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var string|null */
    public $description;

    /** @var string Y-m-d */
    public $startDate;

    /** @var string Y-m-d */
    public $endDate;

    /** @var bool */
    public $isRecurring;

    /** @var bool */
    public $isPartialDay;

    /** @var string|null H:i:s */
    public $partialOpenTime;

    /** @var string|null H:i:s */
    public $partialCloseTime;

    /** @var string company|regional|global|emergency */
    public $type;

    /** @var string|null */
    public $region;

    /** @var array|null Department IDs this holiday applies to (null = all) */
    public $appliesToDepartments;

    /** @var string|null */
    public $reopenMessage;

    /** @var string active|disabled */
    public $status;

    /** @var string */
    public $createdAt;

    /** @var string */
    public $updatedAt;

    /**
     * Create a Holiday instance from a database row
     *
     * @param object|array $data
     * @return self
     */
    public static function fromRow($data)
    {
        $data = (object) $data;
        $holiday = new self();

        $holiday->id                    = (int) $data->id;
        $holiday->name                  = (string) $data->name;
        $holiday->description           = isset($data->description) ? $data->description : null;
        $holiday->startDate             = (string) $data->start_date;
        $holiday->endDate               = (string) $data->end_date;
        $holiday->isRecurring           = (bool) $data->is_recurring;
        $holiday->isPartialDay          = (bool) $data->is_partial_day;
        $holiday->partialOpenTime       = isset($data->partial_open_time) ? $data->partial_open_time : null;
        $holiday->partialCloseTime      = isset($data->partial_close_time) ? $data->partial_close_time : null;
        $holiday->type                  = (string) $data->type;
        $holiday->region                = isset($data->region) ? $data->region : null;
        $holiday->reopenMessage         = isset($data->reopen_message) ? $data->reopen_message : null;
        $holiday->status                = (string) $data->status;
        $holiday->createdAt             = isset($data->created_at) ? (string) $data->created_at : '';
        $holiday->updatedAt             = isset($data->updated_at) ? (string) $data->updated_at : '';

        // Parse JSON department list
        if (isset($data->applies_to_departments) && $data->applies_to_departments !== null) {
            $decoded = json_decode($data->applies_to_departments, true);
            $holiday->appliesToDepartments = is_array($decoded) ? $decoded : null;
        } else {
            $holiday->appliesToDepartments = null;
        }

        return $holiday;
    }

    /**
     * Convert to array for database insertion
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'name'                    => $this->name,
            'description'             => $this->description,
            'start_date'              => $this->startDate,
            'end_date'                => $this->endDate,
            'is_recurring'            => $this->isRecurring ? 1 : 0,
            'is_partial_day'          => $this->isPartialDay ? 1 : 0,
            'partial_open_time'       => $this->partialOpenTime,
            'partial_close_time'      => $this->partialCloseTime,
            'type'                    => $this->type,
            'region'                  => $this->region,
            'applies_to_departments'  => $this->appliesToDepartments !== null
                ? json_encode($this->appliesToDepartments)
                : null,
            'reopen_message'          => $this->reopenMessage,
            'status'                  => $this->status,
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
            'id'                      => $this->id,
            'name'                    => $this->name,
            'description'             => $this->description,
            'start_date'              => $this->startDate,
            'end_date'                => $this->endDate,
            'is_recurring'            => $this->isRecurring,
            'is_partial_day'          => $this->isPartialDay,
            'partial_open_time'       => $this->partialOpenTime,
            'partial_close_time'      => $this->partialCloseTime,
            'type'                    => $this->type,
            'region'                  => $this->region,
            'applies_to_departments'  => $this->appliesToDepartments,
            'reopen_message'          => $this->reopenMessage,
            'status'                  => $this->status,
            'created_at'              => $this->createdAt,
            'updated_at'              => $this->updatedAt,
        ];
    }

    /**
     * Check if this holiday is currently active
     *
     * @param string|null $date Date to check (Y-m-d), defaults to today
     * @return bool
     */
    public function isActiveOn($date = null)
    {
        if ($this->status !== 'active') {
            return false;
        }

        $checkDate = $date ?: date('Y-m-d');

        // For recurring holidays, compare month and day only
        if ($this->isRecurring) {
            $checkMD  = substr($checkDate, 5); // "MM-DD"
            $startMD  = substr($this->startDate, 5);
            $endMD    = substr($this->endDate, 5);

            // Handle same-year ranges
            if ($startMD <= $endMD) {
                return $checkMD >= $startMD && $checkMD <= $endMD;
            }

            // Handle year-spanning ranges (e.g., Dec 24 - Jan 2)
            return $checkMD >= $startMD || $checkMD <= $endMD;
        }

        return $checkDate >= $this->startDate && $checkDate <= $this->endDate;
    }

    /**
     * Check if this holiday applies to a specific department
     *
     * @param int $departmentId
     * @return bool
     */
    public function appliesToDepartment($departmentId)
    {
        // Null means applies to all departments
        if ($this->appliesToDepartments === null) {
            return true;
        }

        return in_array($departmentId, $this->appliesToDepartments);
    }

    /**
     * Check if this is a multi-day holiday
     *
     * @return bool
     */
    public function isMultiDay()
    {
        return $this->startDate !== $this->endDate;
    }

    /**
     * Get the number of days for this holiday
     *
     * @return int
     */
    public function getDurationDays()
    {
        $start = new \DateTime($this->startDate);
        $end   = new \DateTime($this->endDate);
        $diff  = $start->diff($end);
        return $diff->days + 1;
    }

    /**
     * Get the reopening date (day after end date)
     *
     * @return string Y-m-d
     */
    public function getReopenDate()
    {
        $end = new \DateTime($this->endDate);
        $end->modify('+1 day');
        return $end->format('Y-m-d');
    }
}
