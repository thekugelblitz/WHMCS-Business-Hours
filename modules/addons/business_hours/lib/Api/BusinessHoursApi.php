<?php
/**
 * Developer API
 *
 * Provides a clean interface for theme developers and other modules
 * to access business hours data without knowing internal service details.
 *
 * @package    BusinessHours\Api
 */

namespace BusinessHours\Api;

use BusinessHours\Services\AvailabilityService;
use BusinessHours\Services\ScheduleService;
use BusinessHours\Services\HolidayService;
use BusinessHours\Services\WidgetService;
use BusinessHours\Services\ResponseTimeService;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class BusinessHoursApi
{
    /**
     * Check if a department (or the overall company) is currently open
     *
     * @param int|null $departmentId NULL for overall company status
     * @return bool
     */
    public static function isOpen($departmentId = null)
    {
        $service = new AvailabilityService();
        return $service->isOpen($departmentId);
    }

    /**
     * Get the current status details
     *
     * @param int|null $departmentId
     * @return array
     */
    public static function getStatus($departmentId = null)
    {
        $service = new AvailabilityService();
        return $service->getCurrentStatus($departmentId);
    }

    /**
     * Get today's schedule for a department
     *
     * @param int $departmentId
     * @return array
     */
    public static function getTodaySchedule($departmentId)
    {
        $service = new ScheduleService();
        return $service->getTodaySchedule($departmentId);
    }

    /**
     * Get the full weekly schedule for a department
     *
     * @param int $departmentId
     * @return array
     */
    public static function getWeeklySchedule($departmentId)
    {
        $service = new ScheduleService();
        return $service->getWeeklySchedule($departmentId);
    }

    /**
     * Check if today is a holiday
     *
     * @param int|null $departmentId
     * @return bool
     */
    public static function isHoliday($departmentId = null)
    {
        $service = new HolidayService();
        return $service->isHoliday(null, $departmentId);
    }

    /**
     * Get the expected response time message
     *
     * @param int|null $departmentId
     * @return string|null
     */
    public static function getExpectedResponseTime($departmentId = null)
    {
        $service = new ResponseTimeService();
        $msg = $service->getMessage($departmentId);
        return $msg ? $msg['message'] : null;
    }

    /**
     * Render a widget to HTML string
     *
     * @param string $type Widget type (compact, sidebar, full-schedule, status-indicator, etc.)
     * @param array $options Widget options (e.g., ['department' => 'sales'])
     * @return string HTML
     */
    public static function renderWidget($type, array $options = [])
    {
        $service = new WidgetService();
        return $service->renderWidget($type, $options);
    }
}
