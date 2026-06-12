<?php
/**
 * Holiday Repository
 *
 * @package    BusinessHours\Repositories
 */

namespace BusinessHours\Repositories;

use BusinessHours\Bootstrap;
use BusinessHours\Models\Holiday;
use Illuminate\Database\Capsule\Manager as Capsule;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class HolidayRepository
{
    /** @var string */
    private $table;

    public function __construct()
    {
        $tables = Bootstrap::getTableNames();
        $this->table = $tables['holidays'];
    }

    /**
     * Get all holidays
     *
     * @param bool $activeOnly
     * @return Holiday[]
     */
    public function getAll($activeOnly = false)
    {
        $query = Capsule::table($this->table)->orderBy('start_date', 'asc');

        if ($activeOnly) {
            $query->where('status', 'active');
        }

        $results = $query->get();
        $holidays = [];
        foreach ($results as $row) {
            $holidays[] = Holiday::fromRow($row);
        }
        return $holidays;
    }

    /**
     * Get a holiday by ID
     *
     * @param int $id
     * @return Holiday|null
     */
    public function getById($id)
    {
        $row = Capsule::table($this->table)->where('id', (int) $id)->first();
        return $row ? Holiday::fromRow($row) : null;
    }

    /**
     * Get active holidays for a specific date
     *
     * @param string $date Y-m-d
     * @param int|null $departmentId
     * @return Holiday[]
     */
    public function getActiveForDate($date, $departmentId = null)
    {
        // Get non-recurring holidays for this exact date range
        $nonRecurring = Capsule::table($this->table)
            ->where('status', 'active')
            ->where('is_recurring', 0)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->get();

        // Get recurring holidays (need to check month-day)
        $recurring = Capsule::table($this->table)
            ->where('status', 'active')
            ->where('is_recurring', 1)
            ->get();

        $holidays = [];

        foreach ($nonRecurring as $row) {
            $holiday = Holiday::fromRow($row);
            if ($departmentId === null || $holiday->appliesToDepartment($departmentId)) {
                $holidays[] = $holiday;
            }
        }

        $checkMD = substr($date, 5); // "MM-DD"
        foreach ($recurring as $row) {
            $holiday = Holiday::fromRow($row);
            $startMD = substr($holiday->startDate, 5);
            $endMD   = substr($holiday->endDate, 5);

            $isActive = false;
            if ($startMD <= $endMD) {
                $isActive = ($checkMD >= $startMD && $checkMD <= $endMD);
            } else {
                // Year-spanning range (e.g., Dec 24 - Jan 2)
                $isActive = ($checkMD >= $startMD || $checkMD <= $endMD);
            }

            if ($isActive && ($departmentId === null || $holiday->appliesToDepartment($departmentId))) {
                $holidays[] = $holiday;
            }
        }

        return $holidays;
    }

    /**
     * Get upcoming holidays (future start dates)
     *
     * @param int $limit
     * @return Holiday[]
     */
    public function getUpcoming($limit = 5)
    {
        $today = date('Y-m-d');

        // Non-recurring upcoming
        $nonRecurring = Capsule::table($this->table)
            ->where('status', 'active')
            ->where('is_recurring', 0)
            ->where('start_date', '>=', $today)
            ->orderBy('start_date', 'asc')
            ->limit($limit)
            ->get();

        $holidays = [];
        foreach ($nonRecurring as $row) {
            $holidays[] = Holiday::fromRow($row);
        }

        // Also include recurring holidays with their next occurrence
        $recurring = Capsule::table($this->table)
            ->where('status', 'active')
            ->where('is_recurring', 1)
            ->get();

        $currentYear = (int) date('Y');
        foreach ($recurring as $row) {
            $holiday = Holiday::fromRow($row);
            // Calculate next occurrence
            $startMD = substr($holiday->startDate, 5);
            $nextDate = $currentYear . '-' . $startMD;
            if ($nextDate < $today) {
                $nextDate = ($currentYear + 1) . '-' . $startMD;
            }
            // Temporarily store the next occurrence date for sorting
            $holiday->startDate = $nextDate;
            $endMD = substr($holiday->endDate, 5);
            $holiday->endDate = substr($nextDate, 0, 4) . '-' . $endMD;
            $holidays[] = $holiday;
        }

        // Sort by start date and limit
        usort($holidays, function ($a, $b) {
            return strcmp($a->startDate, $b->startDate);
        });

        return array_slice($holidays, 0, $limit);
    }

    /**
     * Create a new holiday
     *
     * @param Holiday $holiday
     * @return int
     */
    public function create(Holiday $holiday)
    {
        $data = $holiday->toArray();
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        return Capsule::table($this->table)->insertGetId($data);
    }

    /**
     * Update an existing holiday
     *
     * @param int $id
     * @param array $data
     * @return int
     */
    public function update($id, array $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        return Capsule::table($this->table)
            ->where('id', (int) $id)
            ->update($data);
    }

    /**
     * Delete a holiday
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
     * Get the total count of holidays
     *
     * @param bool $activeOnly
     * @return int
     */
    public function getCount($activeOnly = false)
    {
        $query = Capsule::table($this->table);
        if ($activeOnly) {
            $query->where('status', 'active');
        }
        return $query->count();
    }
}
