<?php
/**
 * Schedule Model
 *
 * @package    BusinessHours\Models
 */

namespace BusinessHours\Models;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class Schedule
{
    /** @var int */
    public $id;

    /** @var int */
    public $departmentId;

    /** @var string */
    public $name;

    /** @var string regular|seasonal|event|temporary */
    public $type;

    /** @var string|null Y-m-d */
    public $effectiveFrom;

    /** @var string|null Y-m-d */
    public $effectiveTo;

    /** @var int */
    public $priority;

    /** @var bool */
    public $isActive;

    /** @var string */
    public $createdAt;

    /** @var string */
    public $updatedAt;

    /** @var ScheduleSlot[] Loaded slots */
    public $slots = [];

    /**
     * Create a Schedule instance from a database row
     *
     * @param object|array $data
     * @return self
     */
    public static function fromRow($data)
    {
        $data = (object) $data;
        $schedule = new self();

        $schedule->id             = (int) $data->id;
        $schedule->departmentId   = (int) $data->department_id;
        $schedule->name           = (string) $data->name;
        $schedule->type           = (string) $data->type;
        $schedule->effectiveFrom  = isset($data->effective_from) ? $data->effective_from : null;
        $schedule->effectiveTo    = isset($data->effective_to) ? $data->effective_to : null;
        $schedule->priority       = (int) $data->priority;
        $schedule->isActive       = (bool) $data->is_active;
        $schedule->createdAt      = isset($data->created_at) ? (string) $data->created_at : '';
        $schedule->updatedAt      = isset($data->updated_at) ? (string) $data->updated_at : '';

        return $schedule;
    }

    /**
     * Convert to array for database insertion
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'department_id'  => $this->departmentId,
            'name'           => $this->name,
            'type'           => $this->type,
            'effective_from' => $this->effectiveFrom,
            'effective_to'   => $this->effectiveTo,
            'priority'       => $this->priority,
            'is_active'      => $this->isActive ? 1 : 0,
        ];
    }

    /**
     * Convert to JSON-serializable array
     *
     * @return array
     */
    public function toJson()
    {
        $json = [
            'id'             => $this->id,
            'department_id'  => $this->departmentId,
            'name'           => $this->name,
            'type'           => $this->type,
            'effective_from' => $this->effectiveFrom,
            'effective_to'   => $this->effectiveTo,
            'priority'       => $this->priority,
            'is_active'      => $this->isActive,
            'created_at'     => $this->createdAt,
            'updated_at'     => $this->updatedAt,
        ];

        if (!empty($this->slots)) {
            $json['slots'] = array_map(function ($slot) {
                return $slot->toJson();
            }, $this->slots);
        }

        return $json;
    }

    /**
     * Check if this schedule is currently within its effective date range
     *
     * @param string|null $date Date to check (Y-m-d), defaults to today
     * @return bool
     */
    public function isEffective($date = null)
    {
        if (!$this->isActive) {
            return false;
        }

        $checkDate = $date ?: date('Y-m-d');

        if ($this->effectiveFrom !== null && $checkDate < $this->effectiveFrom) {
            return false;
        }

        if ($this->effectiveTo !== null && $checkDate > $this->effectiveTo) {
            return false;
        }

        return true;
    }

    /**
     * Check if this is a regular (non-seasonal/temporary) schedule
     *
     * @return bool
     */
    public function isRegular()
    {
        return $this->type === 'regular';
    }
}
