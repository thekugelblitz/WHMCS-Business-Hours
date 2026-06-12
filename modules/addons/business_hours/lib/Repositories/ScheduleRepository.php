<?php
/**
 * Schedule Repository
 *
 * @package    BusinessHours\Repositories
 */

namespace BusinessHours\Repositories;

use BusinessHours\Bootstrap;
use BusinessHours\Models\Schedule;
use BusinessHours\Models\ScheduleSlot;
use Illuminate\Database\Capsule\Manager as Capsule;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class ScheduleRepository
{
    /** @var string */
    private $scheduleTable;

    /** @var string */
    private $slotTable;

    public function __construct()
    {
        $tables = Bootstrap::getTableNames();
        $this->scheduleTable = $tables['schedules'];
        $this->slotTable     = $tables['slots'];
    }

    /**
     * Get all schedules, optionally filtered by department
     *
     * @param int|null $departmentId
     * @param bool $activeOnly
     * @return Schedule[]
     */
    public function getAll($departmentId = null, $activeOnly = false)
    {
        $query = Capsule::table($this->scheduleTable)
            ->orderBy('priority', 'desc')
            ->orderBy('name', 'asc');

        if ($departmentId !== null) {
            $query->where('department_id', (int) $departmentId);
        }

        if ($activeOnly) {
            $query->where('is_active', 1);
        }

        $results = $query->get();
        $schedules = [];

        foreach ($results as $row) {
            $schedules[] = Schedule::fromRow($row);
        }

        return $schedules;
    }

    /**
     * Get a schedule by ID
     *
     * @param int $id
     * @return Schedule|null
     */
    public function getById($id)
    {
        $row = Capsule::table($this->scheduleTable)
            ->where('id', (int) $id)
            ->first();

        return $row ? Schedule::fromRow($row) : null;
    }

    /**
     * Get a schedule by ID with its slots loaded
     *
     * @param int $id
     * @return Schedule|null
     */
    public function getByIdWithSlots($id)
    {
        $schedule = $this->getById($id);
        if ($schedule) {
            $schedule->slots = $this->getSlots($id);
        }
        return $schedule;
    }

    /**
     * Get the effective schedule for a department on a given date
     *
     * Priority resolution: highest priority schedule whose date range covers the given date.
     *
     * @param int $departmentId
     * @param string $date Y-m-d
     * @return Schedule|null
     */
    public function getEffectiveSchedule($departmentId, $date)
    {
        $schedules = Capsule::table($this->scheduleTable)
            ->where('department_id', (int) $departmentId)
            ->where('is_active', 1)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_from')
                  ->orWhere('effective_from', '<=', $date);
            })
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $date);
            })
            ->orderBy('priority', 'desc')
            ->first();

        return $schedules ? Schedule::fromRow($schedules) : null;
    }

    /**
     * Create a new schedule
     *
     * @param Schedule $schedule
     * @return int Inserted ID
     */
    public function create(Schedule $schedule)
    {
        $data = $schedule->toArray();
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        return Capsule::table($this->scheduleTable)->insertGetId($data);
    }

    /**
     * Update an existing schedule
     *
     * @param int $id
     * @param array $data
     * @return int
     */
    public function update($id, array $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        return Capsule::table($this->scheduleTable)
            ->where('id', (int) $id)
            ->update($data);
    }

    /**
     * Delete a schedule and its associated slots
     *
     * @param int $id
     * @return void
     */
    public function delete($id)
    {
        $this->deleteSlotsBySchedule($id);
        Capsule::table($this->scheduleTable)->where('id', (int) $id)->delete();
    }

    /**
     * Clone a schedule with its slots
     *
     * @param int $id
     * @param string $newName
     * @return int New schedule ID
     */
    public function cloneSchedule($id, $newName)
    {
        $original = $this->getByIdWithSlots($id);
        if (!$original) {
            throw new \InvalidArgumentException("Schedule not found: {$id}");
        }

        $clone = new Schedule();
        $clone->departmentId  = $original->departmentId;
        $clone->name          = $newName;
        $clone->type          = $original->type;
        $clone->effectiveFrom = $original->effectiveFrom;
        $clone->effectiveTo   = $original->effectiveTo;
        $clone->priority      = $original->priority;
        $clone->isActive      = false; // Clones start inactive

        $newId = $this->create($clone);

        // Clone slots
        foreach ($original->slots as $slot) {
            $newSlot = new ScheduleSlot();
            $newSlot->scheduleId = $newId;
            $newSlot->dayOfWeek  = $slot->dayOfWeek;
            $newSlot->openTime   = $slot->openTime;
            $newSlot->closeTime  = $slot->closeTime;
            $newSlot->label      = $slot->label;
            $this->createSlot($newSlot);
        }

        return $newId;
    }

    // ---- Slot Methods ----

    /**
     * Get all slots for a schedule
     *
     * @param int $scheduleId
     * @return ScheduleSlot[]
     */
    public function getSlots($scheduleId)
    {
        $results = Capsule::table($this->slotTable)
            ->where('schedule_id', (int) $scheduleId)
            ->orderBy('day_of_week', 'asc')
            ->orderBy('open_time', 'asc')
            ->get();

        $slots = [];
        foreach ($results as $row) {
            $slots[] = ScheduleSlot::fromRow($row);
        }
        return $slots;
    }

    /**
     * Get slots for a specific day of the week
     *
     * @param int $scheduleId
     * @param int $dayOfWeek 0=Sunday, 6=Saturday
     * @return ScheduleSlot[]
     */
    public function getSlotsForDay($scheduleId, $dayOfWeek)
    {
        $results = Capsule::table($this->slotTable)
            ->where('schedule_id', (int) $scheduleId)
            ->where('day_of_week', (int) $dayOfWeek)
            ->orderBy('open_time', 'asc')
            ->get();

        $slots = [];
        foreach ($results as $row) {
            $slots[] = ScheduleSlot::fromRow($row);
        }
        return $slots;
    }

    /**
     * Create a new slot
     *
     * @param ScheduleSlot $slot
     * @return int
     */
    public function createSlot(ScheduleSlot $slot)
    {
        return Capsule::table($this->slotTable)->insertGetId($slot->toArray());
    }

    /**
     * Update a slot
     *
     * @param int $id
     * @param array $data
     * @return int
     */
    public function updateSlot($id, array $data)
    {
        return Capsule::table($this->slotTable)
            ->where('id', (int) $id)
            ->update($data);
    }

    /**
     * Delete a single slot
     *
     * @param int $id
     * @return int
     */
    public function deleteSlot($id)
    {
        return Capsule::table($this->slotTable)
            ->where('id', (int) $id)
            ->delete();
    }

    /**
     * Delete all slots for a schedule
     *
     * @param int $scheduleId
     * @return int
     */
    public function deleteSlotsBySchedule($scheduleId)
    {
        return Capsule::table($this->slotTable)
            ->where('schedule_id', (int) $scheduleId)
            ->delete();
    }

    /**
     * Replace all slots for a schedule (atomic update)
     *
     * @param int $scheduleId
     * @param array $slotsData Array of slot arrays
     * @return void
     */
    public function replaceSlots($scheduleId, array $slotsData)
    {
        Capsule::connection()->transaction(function () use ($scheduleId, $slotsData) {
            $this->deleteSlotsBySchedule($scheduleId);

            foreach ($slotsData as $slotData) {
                $slotData['schedule_id'] = $scheduleId;
                Capsule::table($this->slotTable)->insert($slotData);
            }
        });
    }

    /**
     * Get the count of schedules for a department
     *
     * @param int $departmentId
     * @return int
     */
    public function getCountByDepartment($departmentId)
    {
        return Capsule::table($this->scheduleTable)
            ->where('department_id', (int) $departmentId)
            ->count();
    }
}
