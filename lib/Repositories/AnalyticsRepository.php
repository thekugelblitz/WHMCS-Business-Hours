<?php
/**
 * Analytics Repository
 *
 * @package    BusinessHours\Repositories
 */

namespace BusinessHours\Repositories;

use BusinessHours\Bootstrap;
use BusinessHours\Models\AnalyticsEvent;
use Illuminate\Database\Capsule\Manager as Capsule;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class AnalyticsRepository
{
    /** @var string */
    private $table;

    public function __construct()
    {
        $tables = Bootstrap::getTableNames();
        $this->table = $tables['analytics'];
    }

    /**
     * Record an analytics event
     *
     * @param AnalyticsEvent $event
     * @return int
     */
    public function record(AnalyticsEvent $event)
    {
        $data = $event->toArray();
        $data['created_at'] = date('Y-m-d H:i:s');

        return Capsule::table($this->table)->insertGetId($data);
    }

    /**
     * Get event counts grouped by event type for a date range
     *
     * @param string $startDate Y-m-d
     * @param string $endDate Y-m-d
     * @return array
     */
    public function getCountsByType($startDate, $endDate)
    {
        return Capsule::table($this->table)
            ->select(Capsule::raw('event_type, COUNT(*) as count'))
            ->where('created_at', '>=', $startDate . ' 00:00:00')
            ->where('created_at', '<=', $endDate . ' 23:59:59')
            ->groupBy('event_type')
            ->get()
            ->toArray();
    }

    /**
     * Get event counts grouped by widget type for a date range
     *
     * @param string $startDate Y-m-d
     * @param string $endDate Y-m-d
     * @return array
     */
    public function getCountsByWidget($startDate, $endDate)
    {
        return Capsule::table($this->table)
            ->select(Capsule::raw('widget_type, COUNT(*) as count'))
            ->where('created_at', '>=', $startDate . ' 00:00:00')
            ->where('created_at', '<=', $endDate . ' 23:59:59')
            ->whereNotNull('widget_type')
            ->groupBy('widget_type')
            ->get()
            ->toArray();
    }

    /**
     * Get event counts grouped by department for a date range
     *
     * @param string $startDate Y-m-d
     * @param string $endDate Y-m-d
     * @return array
     */
    public function getCountsByDepartment($startDate, $endDate)
    {
        return Capsule::table($this->table)
            ->select(Capsule::raw('department_id, COUNT(*) as count'))
            ->where('created_at', '>=', $startDate . ' 00:00:00')
            ->where('created_at', '<=', $endDate . ' 23:59:59')
            ->whereNotNull('department_id')
            ->groupBy('department_id')
            ->get()
            ->toArray();
    }

    /**
     * Get daily event counts for a date range
     *
     * @param string $startDate Y-m-d
     * @param string $endDate Y-m-d
     * @param string|null $eventType Filter by event type
     * @return array
     */
    public function getDailyCounts($startDate, $endDate, $eventType = null)
    {
        $query = Capsule::table($this->table)
            ->select(Capsule::raw('DATE(created_at) as date, COUNT(*) as count'))
            ->where('created_at', '>=', $startDate . ' 00:00:00')
            ->where('created_at', '<=', $endDate . ' 23:59:59');

        if ($eventType !== null) {
            $query->where('event_type', $eventType);
        }

        return $query->groupBy(Capsule::raw('DATE(created_at)'))
            ->orderBy('date', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Get total event count for a date range
     *
     * @param string $startDate Y-m-d
     * @param string $endDate Y-m-d
     * @return int
     */
    public function getTotalCount($startDate, $endDate)
    {
        return Capsule::table($this->table)
            ->where('created_at', '>=', $startDate . ' 00:00:00')
            ->where('created_at', '<=', $endDate . ' 23:59:59')
            ->count();
    }

    /**
     * Delete old analytics records
     *
     * @param int $daysOld
     * @return int Number of deleted rows
     */
    public function deleteOld($daysOld = 90)
    {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));

        return Capsule::table($this->table)
            ->where('created_at', '<', $cutoff)
            ->delete();
    }

    /**
     * Get total record count in the analytics table
     *
     * @return int
     */
    public function getTotalRecords()
    {
        return Capsule::table($this->table)->count();
    }
}
