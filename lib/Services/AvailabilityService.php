<?php
/**
 * Availability Service
 *
 * The core engine that determines real-time support availability.
 * Resolution order: Override → Holiday → Seasonal Schedule → Regular Schedule → 24/7 flag
 *
 * @package    BusinessHours\Services
 */

namespace BusinessHours\Services;

use BusinessHours\Bootstrap;
use BusinessHours\Helpers\TimeHelper;
use BusinessHours\Repositories\DepartmentRepository;
use BusinessHours\Repositories\ScheduleRepository;
use BusinessHours\Repositories\HolidayRepository;
use BusinessHours\Repositories\OverrideRepository;
use BusinessHours\Repositories\SettingsRepository;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class AvailabilityService
{
    /** @var DepartmentRepository */
    private $deptRepo;

    /** @var ScheduleRepository */
    private $schedRepo;

    /** @var HolidayRepository */
    private $holidayRepo;

    /** @var OverrideRepository */
    private $overrideRepo;

    /** @var SettingsRepository */
    private $settingsRepo;

    /** @var array Per-request cache */
    private $statusCache = [];

    public function __construct()
    {
        $this->deptRepo     = new DepartmentRepository();
        $this->schedRepo    = new ScheduleRepository();
        $this->holidayRepo  = new HolidayRepository();
        $this->overrideRepo = new OverrideRepository();
        $this->settingsRepo = new SettingsRepository();
    }

    /**
     * Get the current status for a department or all departments
     *
     * @param int|null $departmentId NULL for the overall/primary status
     * @return array Status data
     */
    public function getCurrentStatus($departmentId = null)
    {
        $cacheKey = 'status_' . ($departmentId ?? 'all');
        if (isset($this->statusCache[$cacheKey])) {
            return $this->statusCache[$cacheKey];
        }

        if ($departmentId === null) {
            // Return aggregate status across all active departments
            $status = $this->getAggregateStatus();
        } else {
            $status = $this->getDepartmentStatus($departmentId);
        }

        $this->statusCache[$cacheKey] = $status;
        return $status;
    }

    /**
     * Get detailed status for a specific department
     *
     * @param int $departmentId
     * @return array
     */
    public function getDepartmentStatus($departmentId)
    {
        $department = $this->deptRepo->getById($departmentId);
        if (!$department || !$department->isActive()) {
            return $this->buildStatus(false, 'Unavailable', 'N/A', 'N/A');
        }

        $timezone = $department->timezone ?: $this->settingsRepo->get('company_timezone', 'America/New_York');
        $now      = TimeHelper::now($timezone);
        $today    = $now->format('Y-m-d');
        $time     = $now->format('H:i:s');
        $dayOfWeek = (int) $now->format('w');

        // 1. Check overrides first (highest priority)
        $overrides = $this->overrideRepo->getForDate($today, $departmentId);
        foreach ($overrides as $override) {
            if ($override->isClosed) {
                $label = $this->settingsRepo->get('label_closed', 'Closed');
                $reason = $override->reason ?: 'Schedule override';
                return $this->buildStatus(false, $label, $this->getNextOpeningAfterOverride($department, $now), $reason, 'override', [
                    'today_hours'    => 'Closed (' . $reason . ')',
                    'department'     => $department,
                    'timezone'       => $timezone,
                ]);
            }
            // Partial override — check adjusted times
            if ($override->openTime && $override->closeTime) {
                $isOpen = TimeHelper::isWithinRange($time, $override->openTime, $override->closeTime);
                $label = $isOpen
                    ? $this->settingsRepo->get('label_open', 'Open')
                    : $this->settingsRepo->get('label_closed', 'Closed');

                $use24h = $this->settingsRepo->get('time_format', '12h') === '24h';
                $todayHours = TimeHelper::formatTime($override->openTime, $use24h)
                    . ' - '
                    . TimeHelper::formatTime($override->closeTime, $use24h);

                return $this->buildStatus($isOpen, $label, $this->computeNextChange($isOpen, $time, [['open_time' => $override->openTime, 'close_time' => $override->closeTime]], $timezone), $override->reason, 'override', [
                    'today_hours'    => $todayHours . ' (Modified)',
                    'department'     => $department,
                    'timezone'       => $timezone,
                ]);
            }
        }

        // 2. Check holidays
        $holidays = $this->holidayRepo->getActiveForDate($today, $departmentId);
        if (!empty($holidays)) {
            $holiday = $holidays[0]; // Take the first matching holiday

            if ($holiday->isPartialDay && $holiday->partialOpenTime && $holiday->partialCloseTime) {
                // Partial day holiday
                $isOpen = TimeHelper::isWithinRange($time, $holiday->partialOpenTime, $holiday->partialCloseTime);
                $label = $isOpen
                    ? $this->settingsRepo->get('label_holiday', 'Holiday Hours')
                    : $this->settingsRepo->get('label_closed', 'Closed');

                $use24h = $this->settingsRepo->get('time_format', '12h') === '24h';
                $todayHours = TimeHelper::formatTime($holiday->partialOpenTime, $use24h)
                    . ' - '
                    . TimeHelper::formatTime($holiday->partialCloseTime, $use24h);

                return $this->buildStatus($isOpen, $label, null, $holiday->name, 'holiday', [
                    'today_hours' => $todayHours . ' (Holiday)',
                    'holiday'     => $holiday,
                    'department'  => $department,
                    'timezone'    => $timezone,
                ]);
            }

            // Full day holiday — closed
            $label = $this->settingsRepo->get('label_closed', 'Closed');
            $reopenDate = $holiday->getReopenDate();
            $reopenFormatted = TimeHelper::formatDate($reopenDate, $this->settingsRepo->get('date_format', 'M j, Y'));

            return $this->buildStatus(false, $label, 'Reopens ' . $reopenFormatted, $holiday->name, 'holiday', [
                'today_hours'     => 'Closed - ' . $holiday->name,
                'holiday'         => $holiday,
                'reopen_message'  => $holiday->reopenMessage,
                'department'      => $department,
                'timezone'        => $timezone,
            ]);
        }

        // 3. Check if 24/7 department
        if ($department->is24x7) {
            $label = $this->settingsRepo->get('label_online', 'Online');
            return $this->buildStatus(true, $label, null, null, '24x7', [
                'today_hours' => '24/7 Support',
                'department'  => $department,
                'timezone'    => $timezone,
            ]);
        }

        // 4. Check scheduled hours
        $schedule = $this->schedRepo->getEffectiveSchedule($departmentId, $today);
        if (!$schedule) {
            $label = $this->settingsRepo->get('label_closed', 'Closed');
            return $this->buildStatus(false, $label, 'No schedule configured', null, 'no_schedule', [
                'today_hours' => 'No schedule',
                'department'  => $department,
                'timezone'    => $timezone,
            ]);
        }

        $slots = $this->schedRepo->getSlotsForDay($schedule->id, $dayOfWeek);

        if (empty($slots)) {
            // No slots for today — closed
            $label = $this->settingsRepo->get('label_closed', 'Closed');
            $nextOpening = $this->findNextOpeningFromSchedule($schedule->id, $dayOfWeek, $timezone);

            return $this->buildStatus(false, $label, $nextOpening, null, 'schedule', [
                'today_hours' => 'Closed Today',
                'department'  => $department,
                'timezone'    => $timezone,
            ]);
        }

        // Check if current time is within any slot
        $isOpen = false;
        $currentSlot = null;
        $use24h = $this->settingsRepo->get('time_format', '12h') === '24h';

        foreach ($slots as $slot) {
            if ($slot->containsTime($time)) {
                $isOpen = true;
                $currentSlot = $slot;
                break;
            }
        }

        // Build today's hours string
        $hoursStrings = [];
        foreach ($slots as $slot) {
            $hoursStrings[] = TimeHelper::formatTime($slot->openTime, $use24h)
                . ' - '
                . TimeHelper::formatTime($slot->closeTime, $use24h);
        }
        $todayHours = implode(', ', $hoursStrings);

        if ($isOpen) {
            $label = $this->settingsRepo->get('label_open', 'Open');
            $closingIn = TimeHelper::secondsBetween($time, $currentSlot->closeTime);
            $nextChange = 'Closes in ' . TimeHelper::formatCountdown($closingIn);

            return $this->buildStatus(true, $label, $nextChange, null, 'schedule', [
                'today_hours'    => $todayHours,
                'closing_in'     => $closingIn,
                'current_slot'   => $currentSlot,
                'department'     => $department,
                'timezone'       => $timezone,
            ]);
        } else {
            $label = $this->settingsRepo->get('label_closed', 'Closed');

            // Find next opening today or on future days
            $nextOpening = null;
            foreach ($slots as $slot) {
                if (TimeHelper::timeToSeconds($slot->openTime) > TimeHelper::timeToSeconds($time)) {
                    $openingIn = TimeHelper::secondsBetween($time, $slot->openTime);
                    $nextOpening = 'Opens in ' . TimeHelper::formatCountdown($openingIn);
                    break;
                }
            }

            if ($nextOpening === null) {
                $nextOpening = $this->findNextOpeningFromSchedule($schedule->id, $dayOfWeek, $timezone);
            }

            return $this->buildStatus(false, $label, $nextOpening, null, 'schedule', [
                'today_hours' => $todayHours,
                'department'  => $department,
                'timezone'    => $timezone,
            ]);
        }
    }

    /**
     * Get aggregate status across all active departments
     *
     * @return array
     */
    private function getAggregateStatus()
    {
        $departments = $this->deptRepo->getAll(true);
        $anyOpen = false;
        $statuses = [];

        foreach ($departments as $dept) {
            $status = $this->getDepartmentStatus($dept->id);
            $statuses[$dept->id] = $status;
            if ($status['is_open']) {
                $anyOpen = true;
            }
        }

        if ($anyOpen) {
            $label = $this->settingsRepo->get('label_available', 'Available');
        } else {
            $label = $this->settingsRepo->get('label_offline', 'Offline');
        }

        return $this->buildStatus($anyOpen, $label, null, null, 'aggregate', [
            'department_statuses' => $statuses,
        ]);
    }

    /**
     * Check if a department is currently open
     *
     * @param int|null $departmentId
     * @return bool
     */
    public function isOpen($departmentId = null)
    {
        $status = $this->getCurrentStatus($departmentId);
        return $status['is_open'];
    }

    /**
     * Get the next status transition (open/close)
     *
     * @param int|null $departmentId
     * @return array|null
     */
    public function getNextTransition($departmentId = null)
    {
        $status = $this->getCurrentStatus($departmentId);
        return [
            'currently_open' => $status['is_open'],
            'next_change'    => $status['next_change'],
        ];
    }

    /**
     * Get the status label for a department
     *
     * @param int|null $departmentId
     * @return string
     */
    public function getStatusLabel($departmentId = null)
    {
        $status = $this->getCurrentStatus($departmentId);
        return $status['label'];
    }

    /**
     * Get status data for all active departments
     *
     * @return array Department statuses indexed by ID
     */
    public function getAllDepartmentStatuses()
    {
        $departments = $this->deptRepo->getAll(true);
        $statuses = [];

        foreach ($departments as $dept) {
            $statuses[$dept->id] = [
                'department' => $dept->toJson(),
                'status'     => $this->getDepartmentStatus($dept->id),
            ];
        }

        return $statuses;
    }

    /**
     * Build a standardized status array
     *
     * @param bool $isOpen
     * @param string $label
     * @param string|null $nextChange
     * @param string|null $reason
     * @param string $source
     * @param array $extra
     * @return array
     */
    private function buildStatus($isOpen, $label, $nextChange = null, $reason = null, $source = 'unknown', $extra = [])
    {
        $status = [
            'is_open'     => $isOpen,
            'label'       => $label,
            'next_change' => $nextChange,
            'reason'      => $reason,
            'source'      => $source,
            'today_hours' => $extra['today_hours'] ?? 'N/A',
            'timestamp'   => time(),
        ];

        return array_merge($status, $extra);
    }

    /**
     * Find the next opening time searching future days in a schedule
     *
     * @param int $scheduleId
     * @param int $currentDayOfWeek
     * @param string $timezone
     * @return string|null
     */
    private function findNextOpeningFromSchedule($scheduleId, $currentDayOfWeek, $timezone)
    {
        $use24h = $this->settingsRepo->get('time_format', '12h') === '24h';

        for ($offset = 1; $offset <= 7; $offset++) {
            $nextDay = ($currentDayOfWeek + $offset) % 7;
            $slots = $this->schedRepo->getSlotsForDay($scheduleId, $nextDay);

            if (!empty($slots)) {
                $dayName = TimeHelper::getDayName($nextDay);
                $openTime = TimeHelper::formatTime($slots[0]->openTime, $use24h);
                return "Opens {$dayName} at {$openTime}";
            }
        }

        return null;
    }

    /**
     * Compute the next status change for given slots
     *
     * @param bool $isOpen
     * @param string $currentTime H:i:s
     * @param array $slotsData Array of ['open_time' => ..., 'close_time' => ...]
     * @param string $timezone
     * @return string|null
     */
    private function computeNextChange($isOpen, $currentTime, $slotsData, $timezone)
    {
        if ($isOpen) {
            // Find the closing time of the current slot
            foreach ($slotsData as $slot) {
                if (TimeHelper::isWithinRange($currentTime, $slot['open_time'], $slot['close_time'])) {
                    $seconds = TimeHelper::secondsBetween($currentTime, $slot['close_time']);
                    return 'Closes in ' . TimeHelper::formatCountdown($seconds);
                }
            }
        } else {
            // Find the next opening time
            $currentSeconds = TimeHelper::timeToSeconds($currentTime);
            foreach ($slotsData as $slot) {
                $openSeconds = TimeHelper::timeToSeconds($slot['open_time']);
                if ($openSeconds > $currentSeconds) {
                    $seconds = $openSeconds - $currentSeconds;
                    return 'Opens in ' . TimeHelper::formatCountdown($seconds);
                }
            }
        }

        return null;
    }

    /**
     * Get the next opening time after an override closure
     *
     * @param \BusinessHours\Models\Department $department
     * @param \DateTime $now
     * @return string|null
     */
    private function getNextOpeningAfterOverride($department, $now)
    {
        // Check tomorrow's schedule
        $tomorrow = clone $now;
        $tomorrow->modify('+1 day');
        $tomorrowDate = $tomorrow->format('Y-m-d');
        $tomorrowDay = (int) $tomorrow->format('w');

        // Check if there's also an override tomorrow
        $tomorrowOverrides = $this->overrideRepo->getForDate($tomorrowDate, $department->id);
        foreach ($tomorrowOverrides as $ov) {
            if ($ov->isClosed) {
                // Also closed tomorrow
                return 'Reopening date TBD';
            }
        }

        $schedule = $this->schedRepo->getEffectiveSchedule($department->id, $tomorrowDate);
        if ($schedule) {
            $slots = $this->schedRepo->getSlotsForDay($schedule->id, $tomorrowDay);
            if (!empty($slots)) {
                $use24h = $this->settingsRepo->get('time_format', '12h') === '24h';
                $dayName = TimeHelper::getDayName($tomorrowDay);
                return "Opens {$dayName} at " . TimeHelper::formatTime($slots[0]->openTime, $use24h);
            }
        }

        return 'Reopening soon';
    }

    /**
     * Clear the per-request cache
     *
     * @return void
     */
    public function clearCache()
    {
        $this->statusCache = [];
    }
}
