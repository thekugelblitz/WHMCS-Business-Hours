<?php
/**
 * Timezone Service
 *
 * @package    BusinessHours\Services
 */

namespace BusinessHours\Services;

use BusinessHours\Repositories\DepartmentRepository;
use BusinessHours\Repositories\SettingsRepository;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class TimezoneService
{
    /** @var SettingsRepository */
    private $settingsRepo;

    /** @var DepartmentRepository */
    private $deptRepo;

    public function __construct()
    {
        $this->settingsRepo = new SettingsRepository();
        $this->deptRepo     = new DepartmentRepository();
    }

    /**
     * Get the primary company timezone
     *
     * @return string
     */
    public function getCompanyTimezone()
    {
        return $this->settingsRepo->get('company_timezone', 'America/New_York');
    }

    /**
     * Get the timezone for a specific department
     *
     * @param int $departmentId
     * @return string
     */
    public function getDepartmentTimezone($departmentId)
    {
        $dept = $this->deptRepo->getById($departmentId);
        if ($dept && $dept->timezone) {
            return $dept->timezone;
        }
        return $this->getCompanyTimezone();
    }

    /**
     * Convert a DateTime from one timezone to another
     *
     * @param \DateTime|string $dateTime
     * @param string $fromTz
     * @param string $toTz
     * @return \DateTime
     */
    public function convertToLocal($dateTime, $fromTz, $toTz)
    {
        if (is_string($dateTime)) {
            $dateTime = new \DateTime($dateTime, new \DateTimeZone($fromTz));
        }

        $converted = clone $dateTime;
        $converted->setTimezone(new \DateTimeZone($toTz));

        return $converted;
    }

    /**
     * Get a list of all available timezones grouped by region
     *
     * @return array
     */
    public function getTimezoneList()
    {
        $timezones = \DateTimeZone::listIdentifiers();
        $grouped = [];

        foreach ($timezones as $tz) {
            $parts = explode('/', $tz, 2);
            $region = $parts[0];
            $city = isset($parts[1]) ? str_replace('_', ' ', $parts[1]) : $tz;

            if (in_array($region, ['Africa', 'America', 'Asia', 'Atlantic', 'Australia', 'Europe', 'Indian', 'Pacific'])) {
                $grouped[$region][] = [
                    'value' => $tz,
                    'label' => $city,
                    'offset' => $this->getUtcOffset($tz),
                ];
            }
        }

        return $grouped;
    }

    /**
     * Get a flat list of timezones with UTC offsets
     *
     * @return array
     */
    public function getTimezoneFlatList()
    {
        $timezones = \DateTimeZone::listIdentifiers();
        $list = [];

        foreach ($timezones as $tz) {
            $list[] = [
                'value'  => $tz,
                'label'  => $tz . ' (' . $this->getUtcOffset($tz) . ')',
                'offset' => $this->getUtcOffset($tz),
            ];
        }

        return $list;
    }

    /**
     * Get the UTC offset string for a timezone
     *
     * @param string $timezone
     * @return string e.g. "UTC-05:00"
     */
    public function getUtcOffset($timezone)
    {
        try {
            $tz = new \DateTimeZone($timezone);
            $now = new \DateTime('now', $tz);
            $offset = $tz->getOffset($now);

            $hours = intdiv($offset, 3600);
            $minutes = abs(($offset % 3600) / 60);

            $sign = $hours >= 0 ? '+' : '-';
            return sprintf('UTC%s%02d:%02d', $sign, abs($hours), $minutes);
        } catch (\Exception $e) {
            return 'UTC';
        }
    }

    /**
     * Get the current time in a timezone formatted for display
     *
     * @param string $timezone
     * @return string
     */
    public function getCurrentTimeFormatted($timezone)
    {
        $use24h = $this->settingsRepo->get('time_format', '12h') === '24h';
        $format = $use24h ? 'H:i' : 'g:i A';

        $now = new \DateTime('now', new \DateTimeZone($timezone));
        return $now->format($format);
    }

    /**
     * Check if client timezone detection is enabled
     *
     * @return bool
     */
    public function isClientTimezoneEnabled()
    {
        return (bool) $this->settingsRepo->get('enable_client_timezone', '1');
    }
}
