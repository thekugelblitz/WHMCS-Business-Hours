<?php
/**
 * Widget Service
 *
 * Central data provider for all frontend widgets.
 *
 * @package    BusinessHours\Services
 */

namespace BusinessHours\Services;

use BusinessHours\Bootstrap;
use BusinessHours\Helpers\TimeHelper;
use BusinessHours\Helpers\ViewHelper;
use BusinessHours\Repositories\DepartmentRepository;
use BusinessHours\Repositories\SettingsRepository;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class WidgetService
{
    /** @var AvailabilityService */
    private $availService;

    /** @var ScheduleService */
    private $schedService;

    /** @var HolidayService */
    private $holidayService;

    /** @var TimezoneService */
    private $tzService;

    /** @var ResponseTimeService */
    private $rtService;

    /** @var DepartmentRepository */
    private $deptRepo;

    /** @var SettingsRepository */
    private $settingsRepo;

    /** @var array Cached widget data */
    private $cache = [];

    public function __construct()
    {
        $this->availService   = new AvailabilityService();
        $this->schedService   = new ScheduleService();
        $this->holidayService = new HolidayService();
        $this->tzService      = new TimezoneService();
        $this->rtService      = new ResponseTimeService();
        $this->deptRepo       = new DepartmentRepository();
        $this->settingsRepo   = new SettingsRepository();
    }

    /**
     * Get assembled data for a widget type
     *
     * @param string $type Widget type
     * @param array $options Options (department, compact, etc.)
     * @return array
     */
    public function getWidgetData($type, array $options = [])
    {
        $cacheKey = $type . '_' . md5(json_encode($options));
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $departmentId = isset($options['department_id']) ? (int) $options['department_id'] : null;
        $departmentSlug = isset($options['department']) ? $options['department'] : null;

        // Resolve department slug to ID
        if ($departmentSlug && !$departmentId) {
            $dept = $this->deptRepo->getBySlug($departmentSlug);
            if ($dept) {
                $departmentId = $dept->id;
            }
        }

        $settings = $this->settingsRepo->getAll();
        $companyTz = $settings['company_timezone'] ?? 'America/New_York';
        $use24h = ($settings['time_format'] ?? '12h') === '24h';

        // Common data for all widgets
        $data = [
            'settings'           => $settings,
            'company_timezone'   => $companyTz,
            'current_time'       => $this->tzService->getCurrentTimeFormatted($companyTz),
            'use_24h'            => $use24h,
            'show_timezone'      => (bool) ($settings['show_timezone'] ?? true),
            'show_countdown'     => (bool) ($settings['show_countdown'] ?? true),
            'show_response_times' => (bool) ($settings['show_response_times'] ?? true),
            'show_holidays'      => (bool) ($settings['show_upcoming_holidays'] ?? true),
            'ajax_enabled'       => (bool) ($settings['ajax_enabled'] ?? true),
            'ajax_interval'      => (int) ($settings['ajax_interval'] ?? 60),
            'module_url'         => 'index.php?m=business_hours',
            'asset_url'          => Bootstrap::getInstance()->getAssetUrl(''),
        ];

        // Color customization
        $data['colors'] = [
            'online'     => $settings['color_online'] ?? '#22c55e',
            'offline'    => $settings['color_offline'] ?? '#ef4444',
            'limited'    => $settings['color_limited'] ?? '#f59e0b',
            'holiday'    => $settings['color_holiday'] ?? '#8b5cf6',
            'emergency'  => $settings['color_emergency'] ?? '#ef4444',
            'accent'     => $settings['color_accent'] ?? '#3b82f6',
        ];

        // Widget type-specific data
        switch ($type) {
            case 'status-indicator':
                $data = array_merge($data, $this->getStatusIndicatorData($departmentId));
                break;

            case 'compact':
                $data = array_merge($data, $this->getCompactData($departmentId));
                break;

            case 'sidebar':
                $data = array_merge($data, $this->getSidebarData($departmentId));
                break;

            case 'full-schedule':
                $data = array_merge($data, $this->getFullScheduleData());
                break;

            case 'department-cards':
                $data = array_merge($data, $this->getDepartmentCardsData());
                break;

            case 'floating':
                $data = array_merge($data, $this->getStatusIndicatorData($departmentId));
                break;

            case 'banner':
                $data = array_merge($data, $this->getBannerData());
                break;

            case 'footer':
                $data = array_merge($data, $this->getFooterData());
                break;

            case 'dashboard':
                $data = array_merge($data, $this->getDashboardData());
                break;
        }

        $this->cache[$cacheKey] = $data;
        return $data;
    }

    /**
     * Render a widget to HTML
     *
     * @param string $type Widget type
     * @param array $options
     * @return string
     */
    public function renderWidget($type, array $options = [])
    {
        $data = $this->getWidgetData($type, $options);
        $templateMap = [
            'status-indicator'  => 'status-indicator',
            'compact'           => 'compact-widget',
            'sidebar'           => 'sidebar-widget',
            'full-schedule'     => 'full-schedule',
            'department-cards'  => 'department-cards',
            'floating'          => 'floating-indicator',
            'banner'            => 'announcement-banner',
            'footer'            => 'footer-widget',
            'dashboard'         => 'dashboard-block',
        ];

        $template = isset($templateMap[$type]) ? $templateMap[$type] : 'status-indicator';
        return ViewHelper::renderWidget($template, $data);
    }

    // ---- Private data builders ----

    private function getStatusIndicatorData($departmentId = null)
    {
        $status = $this->availService->getCurrentStatus($departmentId);
        return [
            'status'      => $status,
            'widget_type' => 'status-indicator',
        ];
    }

    private function getCompactData($departmentId = null)
    {
        $status = $this->availService->getCurrentStatus($departmentId);
        $responseTime = $this->rtService->getMessage($departmentId);

        return [
            'status'        => $status,
            'response_time' => $responseTime,
            'widget_type'   => 'compact',
        ];
    }

    private function getSidebarData($departmentId = null)
    {
        $status = $this->availService->getCurrentStatus($departmentId);
        $responseTime = $this->rtService->getMessage($departmentId);

        // Get today's and tomorrow's schedule
        $todaySchedule = null;
        $tomorrowSchedule = null;

        if ($departmentId) {
            $todaySchedule = $this->schedService->getTodaySchedule($departmentId);
            $tomorrowSchedule = $this->schedService->getTomorrowSchedule($departmentId);
        } else {
            // Use the first active department
            $depts = $this->deptRepo->getAll(true);
            if (!empty($depts)) {
                $todaySchedule = $this->schedService->getTodaySchedule($depts[0]->id);
                $tomorrowSchedule = $this->schedService->getTomorrowSchedule($depts[0]->id);
            }
        }

        // Upcoming holidays
        $limit = (int) ($this->settingsRepo->get('upcoming_holidays_count', '3'));
        $upcomingHolidays = $this->holidayService->getUpcomingHolidays($limit);
        $holidayDisplay = [];
        foreach ($upcomingHolidays as $h) {
            $holidayDisplay[] = $this->holidayService->getDisplayInfo($h);
        }

        return [
            'status'            => $status,
            'response_time'     => $responseTime,
            'today_schedule'    => $todaySchedule,
            'tomorrow_schedule' => $tomorrowSchedule,
            'upcoming_holidays' => $holidayDisplay,
            'widget_type'       => 'sidebar',
        ];
    }

    private function getFullScheduleData()
    {
        $departments = $this->deptRepo->getAll(true);
        $schedules = [];

        foreach ($departments as $dept) {
            $status = $this->availService->getDepartmentStatus($dept->id);
            $weekly = $this->schedService->getWeeklySchedule($dept->id);

            $schedules[] = [
                'department' => $dept,
                'status'     => $status,
                'weekly'     => $weekly,
                'timezone'   => $this->tzService->getDepartmentTimezone($dept->id),
            ];
        }

        return [
            'schedules'   => $schedules,
            'widget_type' => 'full-schedule',
        ];
    }

    private function getDepartmentCardsData()
    {
        $departments = $this->deptRepo->getAll(true);
        $cards = [];

        foreach ($departments as $dept) {
            $status = $this->availService->getDepartmentStatus($dept->id);
            $todaySchedule = $this->schedService->getTodaySchedule($dept->id);
            $responseTime = $this->rtService->getMessage($dept->id);

            $cards[] = [
                'department'     => $dept,
                'status'         => $status,
                'today_schedule' => $todaySchedule,
                'response_time'  => $responseTime,
            ];
        }

        return [
            'cards'       => $cards,
            'widget_type' => 'department-cards',
        ];
    }

    private function getBannerData()
    {
        $activeHoliday = $this->holidayService->getActiveHoliday();
        $hasBanner = $activeHoliday !== null;

        return [
            'has_banner'    => $hasBanner,
            'holiday'       => $activeHoliday ? $this->holidayService->getDisplayInfo($activeHoliday) : null,
            'widget_type'   => 'banner',
        ];
    }

    private function getFooterData()
    {
        $departments = $this->deptRepo->getAll(true);
        $footerItems = [];

        foreach ($departments as $dept) {
            $status = $this->availService->getDepartmentStatus($dept->id);
            $todaySchedule = $this->schedService->getTodaySchedule($dept->id);

            $footerItems[] = [
                'name'     => $dept->name,
                'is_open'  => $status['is_open'],
                'hours'    => $todaySchedule['display'] ?? 'N/A',
            ];
        }

        return [
            'footer_items' => $footerItems,
            'widget_type'  => 'footer',
        ];
    }

    private function getDashboardData()
    {
        return array_merge(
            $this->getDepartmentCardsData(),
            ['widget_type' => 'dashboard']
        );
    }
}
