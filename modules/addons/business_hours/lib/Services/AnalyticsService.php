<?php
/**
 * Analytics Service
 *
 * @package    BusinessHours\Services
 */

namespace BusinessHours\Services;

use BusinessHours\Models\AnalyticsEvent;
use BusinessHours\Repositories\AnalyticsRepository;
use BusinessHours\Repositories\SettingsRepository;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class AnalyticsService
{
    /** @var AnalyticsRepository */
    private $repo;

    /** @var SettingsRepository */
    private $settingsRepo;

    public function __construct()
    {
        $this->repo         = new AnalyticsRepository();
        $this->settingsRepo = new SettingsRepository();
    }

    /**
     * Track a widget view event
     *
     * @param string $widgetType
     * @param int|null $departmentId
     * @param string|null $pageUrl
     * @param int|null $clientId
     * @return void
     */
    public function trackView($widgetType, $departmentId = null, $pageUrl = null, $clientId = null)
    {
        if (!$this->isEnabled()) {
            return;
        }

        $event = new AnalyticsEvent();
        $event->eventType    = 'view';
        $event->widgetType   = $widgetType;
        $event->departmentId = $departmentId;
        $event->pageUrl      = $pageUrl ? substr($pageUrl, 0, 500) : null;
        $event->clientId     = $clientId;
        $event->ipHash       = AnalyticsEvent::hashIp($this->getClientIp());

        try {
            $this->repo->record($event);
        } catch (\Exception $e) {
            // Silently fail — analytics should never break the page
        }
    }

    /**
     * Track an interaction event
     *
     * @param string $widgetType
     * @param array|null $eventData
     * @return void
     */
    public function trackInteraction($widgetType, $eventData = null)
    {
        if (!$this->isEnabled()) {
            return;
        }

        $event = new AnalyticsEvent();
        $event->eventType  = 'interaction';
        $event->widgetType = $widgetType;
        $event->eventData  = $eventData;
        $event->ipHash     = AnalyticsEvent::hashIp($this->getClientIp());

        try {
            $this->repo->record($event);
        } catch (\Exception $e) {
            // Silently fail
        }
    }

    /**
     * Get dashboard analytics data
     *
     * @param string $period '7days', '30days', '90days'
     * @return array
     */
    public function getDashboardData($period = '30days')
    {
        $endDate = date('Y-m-d');
        switch ($period) {
            case '7days':
                $startDate = date('Y-m-d', strtotime('-7 days'));
                break;
            case '90days':
                $startDate = date('Y-m-d', strtotime('-90 days'));
                break;
            default:
                $startDate = date('Y-m-d', strtotime('-30 days'));
        }

        return [
            'period'            => $period,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'total_events'      => $this->repo->getTotalCount($startDate, $endDate),
            'by_type'           => $this->repo->getCountsByType($startDate, $endDate),
            'by_widget'         => $this->repo->getCountsByWidget($startDate, $endDate),
            'by_department'     => $this->repo->getCountsByDepartment($startDate, $endDate),
            'daily_views'       => $this->repo->getDailyCounts($startDate, $endDate, 'view'),
            'daily_interactions' => $this->repo->getDailyCounts($startDate, $endDate, 'interaction'),
        ];
    }

    /**
     * Prune old analytics records
     *
     * @return int Number of deleted records
     */
    public function prune()
    {
        $retention = (int) $this->settingsRepo->get('analytics_retention', '90');
        return $this->repo->deleteOld($retention);
    }

    /**
     * Check if analytics is enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        return (bool) $this->settingsRepo->get('analytics_enabled', '1');
    }

    /**
     * Get the client's IP address
     *
     * @return string
     */
    private function getClientIp()
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
