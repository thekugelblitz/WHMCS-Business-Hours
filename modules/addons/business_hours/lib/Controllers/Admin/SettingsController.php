<?php
/**
 * Settings Admin Controller
 *
 * @package    BusinessHours\Controllers\Admin
 */

namespace BusinessHours\Controllers\Admin;

use BusinessHours\Helpers\SecurityHelper;
use BusinessHours\Helpers\ViewHelper;
use BusinessHours\Repositories\SettingsRepository;
use BusinessHours\Services\TimezoneService;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class SettingsController
{
    /** @var SettingsRepository */
    private $repo;

    public function __construct()
    {
        $this->repo = new SettingsRepository();
    }

    public function handle($subAction, $id, $moduleLink, $lang)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost($moduleLink, $lang);
            return;
        }

        $this->renderSettings($moduleLink, $lang);
    }

    private function renderSettings($moduleLink, $lang)
    {
        $settings = $this->repo->getAll();
        $tzService = new TimezoneService();
        $timezones = $tzService->getTimezoneList();
        $bootstrap = \BusinessHours\Bootstrap::getInstance();

        echo '<link rel="stylesheet" href="' . $bootstrap->getAssetUrl('css/admin.css') . '">';
        echo '<div class="bh-admin">';
        echo '<div class="bh-admin-header"><h1><i class="fas fa-cog"></i> Settings</h1></div>';
        echo ViewHelper::adminNav($moduleLink, 'settings');

        if (isset($_GET['msg']) && isset($lang[$_GET['msg']])) {
            echo ViewHelper::alertSuccess($lang[$_GET['msg']]);
        }

        echo '<form method="post" action="' . $moduleLink . '&action=settings" class="bh-form">';
        echo '<input type="hidden" name="token" value="' . generate_token('link') . '">';

        // Tabs
        echo '<ul class="nav nav-tabs bh-settings-tabs" role="tablist">';
        echo '<li class="active"><a href="#general" data-toggle="tab">General</a></li>';
        echo '<li><a href="#display" data-toggle="tab">Display</a></li>';
        echo '<li><a href="#labels" data-toggle="tab">Labels</a></li>';
        echo '<li><a href="#colors" data-toggle="tab">Colors</a></li>';
        echo '<li><a href="#ajax-tab" data-toggle="tab">Live Updates</a></li>';
        echo '<li><a href="#advanced" data-toggle="tab">Advanced</a></li>';
        echo '</ul>';

        echo '<div class="tab-content bh-tab-content">';

        // General Tab
        echo '<div class="tab-pane active" id="general">';
        echo '<div class="panel panel-default bh-panel"><div class="panel-body">';

        // Timezone
        echo '<div class="form-group"><label class="col-sm-3 control-label">Company Timezone</label><div class="col-sm-6">';
        echo '<select name="company_timezone" class="form-control">';
        foreach ($timezones as $region => $tzList) {
            echo '<optgroup label="' . SecurityHelper::escape($region) . '">';
            foreach ($tzList as $tz) {
                $sel = $tz['value'] === ($settings['company_timezone'] ?? '') ? ' selected' : '';
                echo '<option value="' . SecurityHelper::escape($tz['value']) . '"' . $sel . '>' . SecurityHelper::escape($tz['label']) . ' (' . $tz['offset'] . ')</option>';
            }
            echo '</optgroup>';
        }
        echo '</select></div></div>';

        // Time format
        echo '<div class="form-group"><label class="col-sm-3 control-label">Time Format</label><div class="col-sm-4">';
        echo '<select name="time_format" class="form-control">';
        echo '<option value="12h"' . (($settings['time_format'] ?? '12h') === '12h' ? ' selected' : '') . '>12-hour (1:00 PM)</option>';
        echo '<option value="24h"' . (($settings['time_format'] ?? '') === '24h' ? ' selected' : '') . '>24-hour (13:00)</option>';
        echo '</select></div></div>';

        // Show timezone
        echo '<div class="form-group"><label class="col-sm-3 control-label">Show Timezone</label><div class="col-sm-6">';
        echo '<div class="checkbox"><label><input type="checkbox" name="show_timezone" value="1"' . (($settings['show_timezone'] ?? '1') === '1' ? ' checked' : '') . '> Show timezone to visitors</label></div>';
        echo '</div></div>';

        // Client timezone
        echo '<div class="form-group"><label class="col-sm-3 control-label">Client Timezone</label><div class="col-sm-6">';
        echo '<div class="checkbox"><label><input type="checkbox" name="enable_client_timezone" value="1"' . (($settings['enable_client_timezone'] ?? '1') === '1' ? ' checked' : '') . '> Allow visitors to view in their local timezone</label></div>';
        echo '</div></div>';

        echo '</div></div></div>';

        // Display Tab
        echo '<div class="tab-pane" id="display">';
        echo '<div class="panel panel-default bh-panel"><div class="panel-body">';

        $displaySettings = [
            'show_on_homepage'      => 'Show on Homepage',
            'show_on_tickets'       => 'Show on Ticket Pages',
            'show_on_knowledgebase' => 'Show on Knowledgebase',
            'show_on_announcements' => 'Show on Announcements',
            'show_on_contact'       => 'Show on Contact Page',
            'show_sidebar'          => 'Show Sidebar Widget',
            'show_floating'         => 'Show Floating Indicator',
            'show_footer'           => 'Show Footer Widget',
            'show_banner'           => 'Show Holiday Banner',
            'show_dashboard'        => 'Show Dashboard Block',
            'show_countdown'        => 'Show Countdown Timer',
            'show_response_times'   => 'Show Response Times',
            'show_upcoming_holidays' => 'Show Upcoming Holidays',
        ];

        foreach ($displaySettings as $key => $label) {
            $checked = (($settings[$key] ?? '0') === '1') ? ' checked' : '';
            echo '<div class="checkbox"><label><input type="checkbox" name="' . $key . '" value="1"' . $checked . '> ' . SecurityHelper::escape($label) . '</label></div>';
        }

        echo '<div class="form-group" style="margin-top:15px"><label>Upcoming Holidays Count</label>';
        echo '<input type="number" name="upcoming_holidays_count" class="form-control" style="width:100px" value="' . SecurityHelper::escape($settings['upcoming_holidays_count'] ?? '3') . '" min="1" max="10">';
        echo '</div>';

        echo '</div></div></div>';

        // Labels Tab
        echo '<div class="tab-pane" id="labels">';
        echo '<div class="panel panel-default bh-panel"><div class="panel-body">';

        $labelSettings = [
            'label_online'    => 'Online Label',
            'label_available' => 'Available Label',
            'label_open'      => 'Open Label',
            'label_offline'   => 'Offline Label',
            'label_closed'    => 'Closed Label',
            'label_limited'   => 'Limited Support Label',
            'label_emergency' => 'Emergency Label',
            'label_holiday'   => 'Holiday Label',
        ];

        foreach ($labelSettings as $key => $label) {
            echo '<div class="form-group"><label class="col-sm-3 control-label">' . SecurityHelper::escape($label) . '</label>';
            echo '<div class="col-sm-4"><input type="text" name="' . $key . '" class="form-control" value="' . SecurityHelper::escape($settings[$key] ?? '') . '"></div></div>';
        }

        echo '</div></div></div>';

        // Colors Tab
        echo '<div class="tab-pane" id="colors">';
        echo '<div class="panel panel-default bh-panel"><div class="panel-body">';

        $colorSettings = [
            'color_online'    => 'Online Status Color',
            'color_offline'   => 'Offline Status Color',
            'color_limited'   => 'Limited Status Color',
            'color_holiday'   => 'Holiday Status Color',
            'color_emergency' => 'Emergency Status Color',
            'color_accent'    => 'Accent Color',
        ];

        foreach ($colorSettings as $key => $label) {
            echo '<div class="form-group"><label class="col-sm-3 control-label">' . SecurityHelper::escape($label) . '</label>';
            echo '<div class="col-sm-3"><input type="color" name="' . $key . '" class="form-control bh-color-input" value="' . SecurityHelper::escape($settings[$key] ?? '#000000') . '"></div></div>';
        }

        echo '</div></div></div>';

        // Live Updates Tab
        echo '<div class="tab-pane" id="ajax-tab">';
        echo '<div class="panel panel-default bh-panel"><div class="panel-body">';

        echo '<div class="checkbox"><label><input type="checkbox" name="ajax_enabled" value="1"' . (($settings['ajax_enabled'] ?? '1') === '1' ? ' checked' : '') . '> Enable live status updates (AJAX polling)</label></div>';

        echo '<div class="form-group" style="margin-top:15px"><label>Update Interval (seconds)</label>';
        echo '<input type="number" name="ajax_interval" class="form-control" style="width:120px" value="' . SecurityHelper::escape($settings['ajax_interval'] ?? '60') . '" min="10" max="600">';
        echo '<p class="help-block">How often to check for status changes. Lower = more responsive, higher = less server load.</p>';
        echo '</div>';

        echo '</div></div></div>';

        // Advanced Tab
        echo '<div class="tab-pane" id="advanced">';
        echo '<div class="panel panel-default bh-panel"><div class="panel-body">';

        echo '<div class="form-group"><label>Custom CSS</label>';
        echo '<textarea name="custom_css" class="form-control" rows="6" style="font-family:monospace">' . SecurityHelper::escape($settings['custom_css'] ?? '') . '</textarea>';
        echo '<p class="help-block">Custom CSS will be injected into the client area. Use .bh-widget scoping.</p>';
        echo '</div>';

        echo '<div class="checkbox"><label><input type="checkbox" name="analytics_enabled" value="1"' . (($settings['analytics_enabled'] ?? '1') === '1' ? ' checked' : '') . '> Enable widget analytics</label></div>';

        echo '<div class="form-group" style="margin-top:15px"><label>Analytics Retention (days)</label>';
        echo '<input type="number" name="analytics_retention" class="form-control" style="width:120px" value="' . SecurityHelper::escape($settings['analytics_retention'] ?? '90') . '" min="7" max="365">';
        echo '</div>';

        echo '</div></div></div>';

        echo '</div>'; // tab-content

        // Submit
        echo '<div class="bh-settings-submit">';
        echo '<button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Save Settings</button>';
        echo '</div>';

        echo '</form></div>';
    }

    private function handlePost($moduleLink, $lang)
    {
        $settingsToSave = [];

        // General
        $settingsToSave['company_timezone'] = SecurityHelper::getPost('company_timezone', 'America/New_York');
        $settingsToSave['time_format'] = SecurityHelper::validateEnum($_POST['time_format'] ?? '12h', ['12h', '24h'], '12h');
        $settingsToSave['show_timezone'] = isset($_POST['show_timezone']) ? '1' : '0';
        $settingsToSave['enable_client_timezone'] = isset($_POST['enable_client_timezone']) ? '1' : '0';

        // Display
        $displayKeys = ['show_on_homepage', 'show_on_tickets', 'show_on_knowledgebase', 'show_on_announcements', 'show_on_contact', 'show_sidebar', 'show_floating', 'show_footer', 'show_banner', 'show_dashboard', 'show_countdown', 'show_response_times', 'show_upcoming_holidays'];
        foreach ($displayKeys as $key) {
            $settingsToSave[$key] = isset($_POST[$key]) ? '1' : '0';
        }
        $settingsToSave['upcoming_holidays_count'] = SecurityHelper::sanitizeInt($_POST['upcoming_holidays_count'] ?? 3, 3);

        // Labels
        $labelKeys = ['label_online', 'label_available', 'label_open', 'label_offline', 'label_closed', 'label_limited', 'label_emergency', 'label_holiday'];
        foreach ($labelKeys as $key) {
            if (isset($_POST[$key])) {
                $settingsToSave[$key] = SecurityHelper::getPost($key);
            }
        }

        // Colors
        $colorKeys = ['color_online', 'color_offline', 'color_limited', 'color_holiday', 'color_emergency', 'color_accent'];
        foreach ($colorKeys as $key) {
            $color = SecurityHelper::sanitizeColor($_POST[$key] ?? '');
            if ($color) {
                $settingsToSave[$key] = $color;
            }
        }

        // AJAX
        $settingsToSave['ajax_enabled'] = isset($_POST['ajax_enabled']) ? '1' : '0';
        $settingsToSave['ajax_interval'] = max(10, min(600, SecurityHelper::sanitizeInt($_POST['ajax_interval'] ?? 60, 60)));

        // Advanced
        $settingsToSave['custom_css'] = isset($_POST['custom_css']) ? trim($_POST['custom_css']) : '';
        $settingsToSave['analytics_enabled'] = isset($_POST['analytics_enabled']) ? '1' : '0';
        $settingsToSave['analytics_retention'] = max(7, min(365, SecurityHelper::sanitizeInt($_POST['analytics_retention'] ?? 90, 90)));

        $this->repo->saveMultiple($settingsToSave);

        // Clear settings cache
        \BusinessHours\Bootstrap::getInstance()->clearSettingsCache();

        header('Location: ' . $moduleLink . '&action=settings&msg=settings_saved');
        exit;
    }
}
