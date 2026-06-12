<?php
/**
 * Holiday Service
 *
 * @package    BusinessHours\Services
 */

namespace BusinessHours\Services;

use BusinessHours\Helpers\TimeHelper;
use BusinessHours\Repositories\HolidayRepository;
use BusinessHours\Repositories\SettingsRepository;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class HolidayService
{
    /** @var HolidayRepository */
    private $holidayRepo;

    /** @var SettingsRepository */
    private $settingsRepo;

    public function __construct()
    {
        $this->holidayRepo  = new HolidayRepository();
        $this->settingsRepo = new SettingsRepository();
    }

    /**
     * Check if a given date is a holiday
     *
     * @param string|null $date Y-m-d, defaults to today
     * @param int|null $departmentId
     * @return bool
     */
    public function isHoliday($date = null, $departmentId = null)
    {
        $tz = $this->settingsRepo->get('company_timezone', 'America/New_York');
        $date = $date ?: TimeHelper::getCurrentDate($tz);

        $holidays = $this->holidayRepo->getActiveForDate($date, $departmentId);
        return !empty($holidays);
    }

    /**
     * Get the active holiday for a department
     *
     * @param int|null $departmentId
     * @return \BusinessHours\Models\Holiday|null
     */
    public function getActiveHoliday($departmentId = null)
    {
        $tz = $this->settingsRepo->get('company_timezone', 'America/New_York');
        $date = TimeHelper::getCurrentDate($tz);

        $holidays = $this->holidayRepo->getActiveForDate($date, $departmentId);
        return !empty($holidays) ? $holidays[0] : null;
    }

    /**
     * Get upcoming holidays
     *
     * @param int $limit
     * @return \BusinessHours\Models\Holiday[]
     */
    public function getUpcomingHolidays($limit = 5)
    {
        return $this->holidayRepo->getUpcoming($limit);
    }

    /**
     * Get the reopening date after a holiday
     *
     * @param int $holidayId
     * @return string|null
     */
    public function getReopenDate($holidayId)
    {
        $holiday = $this->holidayRepo->getById($holidayId);
        if (!$holiday) return null;

        return $holiday->getReopenDate();
    }

    /**
     * Get holiday info formatted for display
     *
     * @param \BusinessHours\Models\Holiday $holiday
     * @return array
     */
    public function getDisplayInfo($holiday)
    {
        $dateFormat = $this->settingsRepo->get('date_format', 'M j, Y');
        $use24h = $this->settingsRepo->get('time_format', '12h') === '24h';

        $info = [
            'name'          => $holiday->name,
            'description'   => $holiday->description,
            'type'          => $holiday->type,
            'start_date'    => TimeHelper::formatDate($holiday->startDate, $dateFormat),
            'end_date'      => TimeHelper::formatDate($holiday->endDate, $dateFormat),
            'is_multi_day'  => $holiday->isMultiDay(),
            'duration_days' => $holiday->getDurationDays(),
            'is_recurring'  => $holiday->isRecurring,
            'reopen_date'   => TimeHelper::formatDate($holiday->getReopenDate(), $dateFormat),
            'reopen_message' => $holiday->reopenMessage,
        ];

        if ($holiday->isPartialDay) {
            $info['is_partial']     = true;
            $info['partial_hours']  = TimeHelper::formatTime($holiday->partialOpenTime, $use24h)
                . ' - '
                . TimeHelper::formatTime($holiday->partialCloseTime, $use24h);
        }

        return $info;
    }
}
