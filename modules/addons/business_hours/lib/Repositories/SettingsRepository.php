<?php
/**
 * Settings Repository
 *
 * @package    BusinessHours\Repositories
 */

namespace BusinessHours\Repositories;

use BusinessHours\Bootstrap;
use Illuminate\Database\Capsule\Manager as Capsule;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class SettingsRepository
{
    /** @var string */
    private $table;

    /**
     * Default settings with their default values
     */
    const DEFAULTS = [
        // General
        'company_timezone'       => 'America/New_York',
        'time_format'            => '12h',
        'date_format'            => 'M j, Y',
        'show_timezone'          => '1',
        'enable_client_timezone' => '1',

        // Status Labels
        'label_online'           => 'Online',
        'label_available'        => 'Available',
        'label_open'             => 'Open',
        'label_offline'          => 'Offline',
        'label_closed'           => 'Closed',
        'label_limited'          => 'Limited Support',
        'label_emergency'        => 'Emergency Support Only',
        'label_holiday'          => 'Holiday Hours',

        // Display
        'default_widget'         => 'sidebar',
        'show_on_homepage'       => '1',
        'show_on_tickets'        => '1',
        'show_on_knowledgebase'  => '0',
        'show_on_announcements'  => '0',
        'show_on_contact'        => '1',
        'show_sidebar'           => '1',
        'show_floating'          => '0',
        'show_footer'            => '0',
        'show_banner'            => '1',
        'show_dashboard'         => '1',
        'show_countdown'         => '1',
        'show_response_times'    => '1',
        'show_upcoming_holidays' => '1',
        'upcoming_holidays_count' => '3',

        // AJAX
        'ajax_enabled'           => '1',
        'ajax_interval'          => '60',

        // Colors
        'color_online'           => '#22c55e',
        'color_offline'          => '#ef4444',
        'color_limited'          => '#f59e0b',
        'color_holiday'          => '#8b5cf6',
        'color_emergency'        => '#ef4444',
        'color_bg_primary'       => '',
        'color_bg_secondary'     => '',
        'color_text_primary'     => '',
        'color_text_secondary'   => '',
        'color_accent'           => '#3b82f6',
        'color_border'           => '',

        // Advanced
        'custom_css'             => '',
        'analytics_enabled'      => '1',
        'analytics_retention'    => '90',
        'cache_ttl'              => '30',
    ];

    public function __construct()
    {
        $tables = Bootstrap::getTableNames();
        $this->table = $tables['settings'];
    }

    /**
     * Get a setting value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $row = Capsule::table($this->table)
            ->where('setting_key', $key)
            ->first();

        if ($row) {
            return $row->setting_value;
        }

        // Return the default from our defaults array, or the provided default
        if ($default === null && isset(self::DEFAULTS[$key])) {
            return self::DEFAULTS[$key];
        }

        return $default;
    }

    /**
     * Set a setting value
     *
     * @param string $key
     * @param mixed $value
     * @param string $group
     * @return void
     */
    public function set($key, $value, $group = 'general')
    {
        $exists = Capsule::table($this->table)
            ->where('setting_key', $key)
            ->exists();

        if ($exists) {
            Capsule::table($this->table)
                ->where('setting_key', $key)
                ->update([
                    'setting_value' => $value,
                    'setting_group' => $group,
                ]);
        } else {
            Capsule::table($this->table)->insert([
                'setting_key'   => $key,
                'setting_value' => $value,
                'setting_group' => $group,
            ]);
        }
    }

    /**
     * Get all settings in a group
     *
     * @param string $group
     * @return array Key-value pairs
     */
    public function getGroup($group)
    {
        $results = Capsule::table($this->table)
            ->where('setting_group', $group)
            ->get();

        $settings = [];
        foreach ($results as $row) {
            $settings[$row->setting_key] = $row->setting_value;
        }

        // Merge with defaults for this group
        foreach (self::DEFAULTS as $key => $defaultValue) {
            if (!isset($settings[$key])) {
                $settings[$key] = $defaultValue;
            }
        }

        return $settings;
    }

    /**
     * Get all settings merged with defaults
     *
     * @return array
     */
    public function getAll()
    {
        $results = Capsule::table($this->table)->get();

        $settings = self::DEFAULTS;
        foreach ($results as $row) {
            $settings[$row->setting_key] = $row->setting_value;
        }

        return $settings;
    }

    /**
     * Save multiple settings at once
     *
     * @param array $settings Key-value pairs
     * @param string $group
     * @return void
     */
    public function saveMultiple(array $settings, $group = 'general')
    {
        foreach ($settings as $key => $value) {
            $this->set($key, $value, $group);
        }
    }

    /**
     * Delete a setting
     *
     * @param string $key
     * @return int
     */
    public function delete($key)
    {
        return Capsule::table($this->table)
            ->where('setting_key', $key)
            ->delete();
    }

    /**
     * Install default settings
     *
     * @return void
     */
    public function installDefaults()
    {
        foreach (self::DEFAULTS as $key => $value) {
            $exists = Capsule::table($this->table)
                ->where('setting_key', $key)
                ->exists();

            if (!$exists) {
                $group = $this->getGroupForKey($key);
                Capsule::table($this->table)->insert([
                    'setting_key'   => $key,
                    'setting_value' => $value,
                    'setting_group' => $group,
                ]);
            }
        }
    }

    /**
     * Determine the group for a settings key based on naming convention
     *
     * @param string $key
     * @return string
     */
    private function getGroupForKey($key)
    {
        if (strpos($key, 'color_') === 0) return 'colors';
        if (strpos($key, 'label_') === 0) return 'labels';
        if (strpos($key, 'show_') === 0) return 'display';
        if (strpos($key, 'ajax_') === 0) return 'ajax';
        if (strpos($key, 'analytics_') === 0) return 'analytics';
        return 'general';
    }
}
