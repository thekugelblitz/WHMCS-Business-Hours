<?php
/**
 * Override Repository
 *
 * @package    BusinessHours\Repositories
 */

namespace BusinessHours\Repositories;

use BusinessHours\Bootstrap;
use BusinessHours\Models\ScheduleOverride;
use Illuminate\Database\Capsule\Manager as Capsule;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class OverrideRepository
{
    /** @var string */
    private $table;

    public function __construct()
    {
        $tables = Bootstrap::getTableNames();
        $this->table = $tables['overrides'];
    }

    /**
     * Get all overrides
     *
     * @return ScheduleOverride[]
     */
    public function getAll()
    {
        $results = Capsule::table($this->table)
            ->orderBy('override_date', 'asc')
            ->get();

        $overrides = [];
        foreach ($results as $row) {
            $overrides[] = ScheduleOverride::fromRow($row);
        }
        return $overrides;
    }

    /**
     * Get an override by ID
     *
     * @param int $id
     * @return ScheduleOverride|null
     */
    public function getById($id)
    {
        $row = Capsule::table($this->table)->where('id', (int) $id)->first();
        return $row ? ScheduleOverride::fromRow($row) : null;
    }

    /**
     * Get overrides for a specific date
     *
     * @param string $date Y-m-d
     * @param int|null $departmentId
     * @return ScheduleOverride[]
     */
    public function getForDate($date, $departmentId = null)
    {
        $query = Capsule::table($this->table)
            ->where('override_date', $date);

        if ($departmentId !== null) {
            $query->where(function ($q) use ($departmentId) {
                $q->whereNull('department_id')
                  ->orWhere('department_id', (int) $departmentId);
            });
        }

        $results = $query->get();
        $overrides = [];
        foreach ($results as $row) {
            $overrides[] = ScheduleOverride::fromRow($row);
        }
        return $overrides;
    }

    /**
     * Get upcoming overrides (future dates)
     *
     * @param int $limit
     * @return ScheduleOverride[]
     */
    public function getUpcoming($limit = 10)
    {
        $today = date('Y-m-d');

        $results = Capsule::table($this->table)
            ->where('override_date', '>=', $today)
            ->orderBy('override_date', 'asc')
            ->limit($limit)
            ->get();

        $overrides = [];
        foreach ($results as $row) {
            $overrides[] = ScheduleOverride::fromRow($row);
        }
        return $overrides;
    }

    /**
     * Create a new override
     *
     * @param ScheduleOverride $override
     * @return int
     */
    public function create(ScheduleOverride $override)
    {
        $data = $override->toArray();
        $data['created_at'] = date('Y-m-d H:i:s');

        return Capsule::table($this->table)->insertGetId($data);
    }

    /**
     * Update an existing override
     *
     * @param int $id
     * @param array $data
     * @return int
     */
    public function update($id, array $data)
    {
        return Capsule::table($this->table)
            ->where('id', (int) $id)
            ->update($data);
    }

    /**
     * Delete an override
     *
     * @param int $id
     * @return int
     */
    public function delete($id)
    {
        return Capsule::table($this->table)
            ->where('id', (int) $id)
            ->delete();
    }

    /**
     * Delete old overrides (cleanup)
     *
     * @param int $daysOld Delete overrides older than this many days
     * @return int Number of deleted rows
     */
    public function deleteOld($daysOld = 90)
    {
        $cutoff = date('Y-m-d', strtotime("-{$daysOld} days"));

        return Capsule::table($this->table)
            ->where('override_date', '<', $cutoff)
            ->delete();
    }
}
