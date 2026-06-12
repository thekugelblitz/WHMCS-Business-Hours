<?php
/**
 * Schedule Service
 *
 * @package    BusinessHours\Services
 */

namespace BusinessHours\Services;

use BusinessHours\Helpers\TimeHelper;
use BusinessHours\Repositories\ScheduleRepository;
use BusinessHours\Repositories\DepartmentRepository;
use BusinessHours\Repositories\SettingsRepository;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class ScheduleService
{
    /** @var ScheduleRepository */
    private $schedRepo;

    /** @var DepartmentRepository */
    private $deptRepo;

    /** @var SettingsRepository */
    private $settingsRepo;

    public function __construct()
    {
        $this->schedRepo    = new ScheduleRepository();
        $this->deptRepo     = new DepartmentRepository();
        $this->settingsRepo = new SettingsRepository();
    }

    /**
     * Get the active schedule for a department on a given date
     *
     * @param int $departmentId
     * @param string|null $date Y-m-d, defaults to today
     * @return \BusinessHours\Models\Schedule|null
     */
    public function getActiveSchedule($departmentId, $date = null)
    {
        $dept = $this->deptRepo->getById($departmentId);
        if (!$dept) return null;

        $tz = $dept->timezone ?: $this->settingsRepo->get('company_timezone', 'America/New_York');
        $date = $date ?: TimeHelper::getCurrentDate($tz);

        return $this->schedRepo->getEffectiveSchedule($departmentId, $date);
    }

    /**
     * Get today's schedule for a department
     *
     * @param int $departmentId
     * @return array
     */
    public function getTodaySchedule($departmentId)
    {
        $dept = $this->deptRepo->getById($departmentId);
        if (!$dept) return ['closed' => true, 'slots' => []];

        if ($dept->is24x7) {
            return ['closed' => false, 'is_24x7' => true, 'display' => '24/7 Support', 'slots' => []];
        }

        $tz = $dept->timezone ?: $this->settingsRepo->get('company_timezone', 'America/New_York');
        $date = TimeHelper::getCurrentDate($tz);
        $dayOfWeek = TimeHelper::getCurrentDayOfWeek($tz);

        $schedule = $this->schedRepo->getEffectiveSchedule($departmentId, $date);
        if (!$schedule) {
            return ['closed' => true, 'slots' => [], 'display' => 'Closed Today'];
        }

        $slots = $this->schedRepo->getSlotsForDay($schedule->id, $dayOfWeek);
        if (empty($slots)) {
            return ['closed' => true, 'slots' => [], 'display' => 'Closed Today'];
        }

        $use24h = $this->settingsRepo->get('time_format', '12h') === '24h';
        $display = [];
        foreach ($slots as $slot) {
            $display[] = TimeHelper::formatTime($slot->openTime, $use24h)
                . ' - '
                . TimeHelper::formatTime($slot->closeTime, $use24h);
        }

        return [
            'closed'  => false,
            'slots'   => $slots,
            'display' => implode(', ', $display),
        ];
    }

    /**
     * Get tomorrow's schedule for a department
     *
     * @param int $departmentId
     * @return array
     */
    public function getTomorrowSchedule($departmentId)
    {
        $dept = $this->deptRepo->getById($departmentId);
        if (!$dept) return ['closed' => true, 'slots' => []];

        if ($dept->is24x7) {
            return ['closed' => false, 'is_24x7' => true, 'display' => '24/7 Support', 'slots' => []];
        }

        $tz = $dept->timezone ?: $this->settingsRepo->get('company_timezone', 'America/New_York');
        $tomorrow = new \DateTime('tomorrow', new \DateTimeZone($tz));
        $date = $tomorrow->format('Y-m-d');
        $dayOfWeek = (int) $tomorrow->format('w');

        $schedule = $this->schedRepo->getEffectiveSchedule($departmentId, $date);
        if (!$schedule) {
            return ['closed' => true, 'slots' => [], 'display' => 'Closed'];
        }

        $slots = $this->schedRepo->getSlotsForDay($schedule->id, $dayOfWeek);
        if (empty($slots)) {
            return ['closed' => true, 'slots' => [], 'display' => 'Closed'];
        }

        $use24h = $this->settingsRepo->get('time_format', '12h') === '24h';
        $display = [];
        foreach ($slots as $slot) {
            $display[] = TimeHelper::formatTime($slot->openTime, $use24h)
                . ' - '
                . TimeHelper::formatTime($slot->closeTime, $use24h);
        }

        return [
            'closed'   => false,
            'slots'    => $slots,
            'display'  => implode(', ', $display),
            'day_name' => TimeHelper::getDayName($dayOfWeek),
        ];
    }

    /**
     * Get the full weekly schedule for a department
     *
     * @param int $departmentId
     * @return array
     */
    public function getWeeklySchedule($departmentId)
    {
        $dept = $this->deptRepo->getById($departmentId);
        if (!$dept) return [];

        if ($dept->is24x7) {
            $weekly = [];
            for ($day = 0; $day <= 6; $day++) {
                $weekly[$day] = [
                    'day_name' => TimeHelper::getDayName($day),
                    'day_short' => TimeHelper::getDayName($day, true),
                    'is_24x7'  => true,
                    'display'  => '24/7',
                    'slots'    => [],
                ];
            }
            return $weekly;
        }

        $tz = $dept->timezone ?: $this->settingsRepo->get('company_timezone', 'America/New_York');
        $date = TimeHelper::getCurrentDate($tz);

        $schedule = $this->schedRepo->getEffectiveSchedule($departmentId, $date);
        if (!$schedule) {
            $weekly = [];
            for ($day = 0; $day <= 6; $day++) {
                $weekly[$day] = [
                    'day_name'  => TimeHelper::getDayName($day),
                    'day_short' => TimeHelper::getDayName($day, true),
                    'closed'    => true,
                    'display'   => 'Closed',
                    'slots'     => [],
                ];
            }
            return $weekly;
        }

        $allSlots = $this->schedRepo->getSlots($schedule->id);
        $use24h = $this->settingsRepo->get('time_format', '12h') === '24h';

        // Group slots by day
        $slotsByDay = [];
        foreach ($allSlots as $slot) {
            $slotsByDay[$slot->dayOfWeek][] = $slot;
        }

        $weekly = [];
        for ($day = 0; $day <= 6; $day++) {
            $daySlots = isset($slotsByDay[$day]) ? $slotsByDay[$day] : [];

            if (empty($daySlots)) {
                $weekly[$day] = [
                    'day_name'  => TimeHelper::getDayName($day),
                    'day_short' => TimeHelper::getDayName($day, true),
                    'closed'    => true,
                    'display'   => 'Closed',
                    'slots'     => [],
                ];
            } else {
                $display = [];
                foreach ($daySlots as $slot) {
                    $display[] = TimeHelper::formatTime($slot->openTime, $use24h)
                        . ' - '
                        . TimeHelper::formatTime($slot->closeTime, $use24h);
                }

                $weekly[$day] = [
                    'day_name'  => TimeHelper::getDayName($day),
                    'day_short' => TimeHelper::getDayName($day, true),
                    'closed'    => false,
                    'display'   => implode(', ', $display),
                    'slots'     => $daySlots,
                ];
            }
        }

        return $weekly;
    }
}
