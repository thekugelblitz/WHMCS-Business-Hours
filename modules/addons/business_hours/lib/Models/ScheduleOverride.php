<?php
/**
 * Schedule Override Model
 *
 * Represents a one-time override for a specific date, such as
 * emergency closures or adjusted hours.
 *
 * @package    BusinessHours\Models
 */

namespace BusinessHours\Models;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class ScheduleOverride
{
    /** @var int */
    public $id;

    /** @var int|null NULL = applies to all departments */
    public $departmentId;

    /** @var string Y-m-d */
    public $overrideDate;

    /** @var bool */
    public $isClosed;

    /** @var string|null H:i:s */
    public $openTime;

    /** @var string|null H:i:s */
    public $closeTime;

    /** @var string|null */
    public $reason;

    /** @var string */
    public $createdAt;

    /**
     * Create a ScheduleOverride instance from a database row
     *
     * @param object|array $data
     * @return self
     */
    public static function fromRow($data)
    {
        $data = (object) $data;
        $override = new self();

        $override->id            = (int) $data->id;
        $override->departmentId  = isset($data->department_id) ? (int) $data->department_id : null;
        $override->overrideDate  = (string) $data->override_date;
        $override->isClosed      = (bool) $data->is_closed;
        $override->openTime      = isset($data->open_time) ? $data->open_time : null;
        $override->closeTime     = isset($data->close_time) ? $data->close_time : null;
        $override->reason        = isset($data->reason) ? $data->reason : null;
        $override->createdAt     = isset($data->created_at) ? (string) $data->created_at : '';

        return $override;
    }

    /**
     * Convert to array for database insertion
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'department_id' => $this->departmentId,
            'override_date' => $this->overrideDate,
            'is_closed'     => $this->isClosed ? 1 : 0,
            'open_time'     => $this->openTime,
            'close_time'    => $this->closeTime,
            'reason'        => $this->reason,
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
            'id'             => $this->id,
            'department_id'  => $this->departmentId,
            'override_date'  => $this->overrideDate,
            'is_closed'      => $this->isClosed,
            'open_time'      => $this->openTime,
            'close_time'     => $this->closeTime,
            'reason'         => $this->reason,
            'created_at'     => $this->createdAt,
        ];
    }

    /**
     * Check if this override applies to a specific department
     *
     * @param int $departmentId
     * @return bool
     */
    public function appliesToDepartment($departmentId)
    {
        // NULL department_id means applies to all
        return $this->departmentId === null || $this->departmentId === $departmentId;
    }
}
