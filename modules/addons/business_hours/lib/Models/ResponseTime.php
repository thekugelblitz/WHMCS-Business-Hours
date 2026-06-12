<?php
/**
 * Response Time Model
 *
 * @package    BusinessHours\Models
 */

namespace BusinessHours\Models;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class ResponseTime
{
    /** @var int */
    public $id;

    /** @var int|null NULL = global default */
    public $departmentId;

    /** @var string business_hours|after_hours|holiday|emergency */
    public $context;

    /** @var string */
    public $message;

    /** @var int|null Estimated response time in minutes */
    public $estimatedMinutes;

    /** @var int */
    public $sortOrder;

    /** @var bool */
    public $isActive;

    /**
     * Create a ResponseTime instance from a database row
     *
     * @param object|array $data
     * @return self
     */
    public static function fromRow($data)
    {
        $data = (object) $data;
        $rt = new self();

        $rt->id               = (int) $data->id;
        $rt->departmentId     = isset($data->department_id) ? (int) $data->department_id : null;
        $rt->context          = (string) $data->context;
        $rt->message          = (string) $data->message;
        $rt->estimatedMinutes = isset($data->estimated_minutes) ? (int) $data->estimated_minutes : null;
        $rt->sortOrder        = (int) $data->sort_order;
        $rt->isActive         = (bool) $data->is_active;

        return $rt;
    }

    /**
     * Convert to array for database insertion
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'department_id'     => $this->departmentId,
            'context'           => $this->context,
            'message'           => $this->message,
            'estimated_minutes' => $this->estimatedMinutes,
            'sort_order'        => $this->sortOrder,
            'is_active'         => $this->isActive ? 1 : 0,
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
            'id'                => $this->id,
            'department_id'     => $this->departmentId,
            'context'           => $this->context,
            'message'           => $this->message,
            'estimated_minutes' => $this->estimatedMinutes,
            'sort_order'        => $this->sortOrder,
            'is_active'         => $this->isActive,
        ];
    }

    /**
     * Get human-readable estimated time
     *
     * @return string
     */
    public function getFormattedEstimate()
    {
        if ($this->estimatedMinutes === null) {
            return '';
        }

        if ($this->estimatedMinutes < 60) {
            return $this->estimatedMinutes . ' minutes';
        }

        $hours = floor($this->estimatedMinutes / 60);
        $mins  = $this->estimatedMinutes % 60;

        if ($mins === 0) {
            return $hours . ($hours === 1 ? ' hour' : ' hours');
        }

        return $hours . ($hours === 1 ? ' hour ' : ' hours ') . $mins . ' minutes';
    }
}
