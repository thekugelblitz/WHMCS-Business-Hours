<?php
/**
 * Analytics Event Model
 *
 * @package    BusinessHours\Models
 */

namespace BusinessHours\Models;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class AnalyticsEvent
{
    /** @var int */
    public $id;

    /** @var string */
    public $eventType;

    /** @var string|null */
    public $widgetType;

    /** @var int|null */
    public $departmentId;

    /** @var string|null */
    public $pageUrl;

    /** @var int|null */
    public $clientId;

    /** @var string|null */
    public $ipHash;

    /** @var array|null */
    public $eventData;

    /** @var string */
    public $createdAt;

    /**
     * Create an AnalyticsEvent from a database row
     *
     * @param object|array $data
     * @return self
     */
    public static function fromRow($data)
    {
        $data = (object) $data;
        $event = new self();

        $event->id            = (int) $data->id;
        $event->eventType     = (string) $data->event_type;
        $event->widgetType    = isset($data->widget_type) ? $data->widget_type : null;
        $event->departmentId  = isset($data->department_id) ? (int) $data->department_id : null;
        $event->pageUrl       = isset($data->page_url) ? $data->page_url : null;
        $event->clientId      = isset($data->client_id) ? (int) $data->client_id : null;
        $event->ipHash        = isset($data->ip_hash) ? $data->ip_hash : null;
        $event->createdAt     = isset($data->created_at) ? (string) $data->created_at : '';

        if (isset($data->event_data) && $data->event_data !== null) {
            $decoded = json_decode($data->event_data, true);
            $event->eventData = is_array($decoded) ? $decoded : null;
        } else {
            $event->eventData = null;
        }

        return $event;
    }

    /**
     * Convert to array for database insertion
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'event_type'    => $this->eventType,
            'widget_type'   => $this->widgetType,
            'department_id' => $this->departmentId,
            'page_url'      => $this->pageUrl,
            'client_id'     => $this->clientId,
            'ip_hash'       => $this->ipHash,
            'event_data'    => $this->eventData !== null ? json_encode($this->eventData) : null,
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
            'id'            => $this->id,
            'event_type'    => $this->eventType,
            'widget_type'   => $this->widgetType,
            'department_id' => $this->departmentId,
            'page_url'      => $this->pageUrl,
            'client_id'     => $this->clientId,
            'event_data'    => $this->eventData,
            'created_at'    => $this->createdAt,
        ];
    }

    /**
     * Create an anonymized IP hash
     *
     * @param string $ip
     * @return string
     */
    public static function hashIp($ip)
    {
        return hash('sha256', $ip . date('Y-m'));
    }
}
