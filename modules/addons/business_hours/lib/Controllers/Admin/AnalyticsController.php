<?php
/**
 * Analytics Admin Controller
 *
 * @package    BusinessHours\Controllers\Admin
 */

namespace BusinessHours\Controllers\Admin;

use BusinessHours\Helpers\SecurityHelper;
use BusinessHours\Helpers\ViewHelper;
use BusinessHours\Services\AnalyticsService;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class AnalyticsController
{
    public function handle($subAction, $id, $moduleLink, $lang)
    {
        $this->renderDashboard($moduleLink, $lang);
    }

    private function renderDashboard($moduleLink, $lang)
    {
        $period = isset($_GET['period']) ? $_GET['period'] : '30days';
        $analyticsService = new AnalyticsService();
        $data = $analyticsService->getDashboardData($period);
        $bootstrap = \BusinessHours\Bootstrap::getInstance();

        echo '<link rel="stylesheet" href="' . $bootstrap->getAssetUrl('css/admin.css') . '">';
        echo '<div class="bh-admin">';
        echo '<div class="bh-admin-header"><h1><i class="fas fa-chart-bar"></i> ' . SecurityHelper::escape($lang['analytics'] ?? 'Analytics') . '</h1></div>';
        echo ViewHelper::adminNav($moduleLink, 'analytics');

        // Period selector
        echo '<div class="bh-actions-bar">';
        $periods = ['7days' => 'Last 7 Days', '30days' => 'Last 30 Days', '90days' => 'Last 90 Days'];
        foreach ($periods as $key => $label) {
            $active = ($period === $key) ? ' btn-primary' : ' btn-default';
            echo '<a href="' . $moduleLink . '&action=analytics&period=' . $key . '" class="btn' . $active . '">' . $label . '</a> ';
        }
        echo '</div>';

        // Stats cards
        echo '<div class="row bh-stats-row">';

        echo '<div class="col-md-3"><div class="bh-stat-card">';
        echo '<div class="bh-stat-icon"><i class="fas fa-eye"></i></div>';
        echo '<div class="bh-stat-content"><div class="bh-stat-value">' . number_format($data['total_events']) . '</div>';
        echo '<div class="bh-stat-label">Total Events</div></div></div></div>';

        // Count widget views
        $viewCount = 0;
        foreach ($data['by_type'] as $item) {
            $item = (object) $item;
            if ($item->event_type === 'view') {
                $viewCount = $item->count;
            }
        }
        echo '<div class="col-md-3"><div class="bh-stat-card">';
        echo '<div class="bh-stat-icon"><i class="fas fa-desktop"></i></div>';
        echo '<div class="bh-stat-content"><div class="bh-stat-value">' . number_format($viewCount) . '</div>';
        echo '<div class="bh-stat-label">Widget Views</div></div></div></div>';

        // Interaction count
        $interactionCount = 0;
        foreach ($data['by_type'] as $item) {
            $item = (object) $item;
            if ($item->event_type === 'interaction') {
                $interactionCount = $item->count;
            }
        }
        echo '<div class="col-md-3"><div class="bh-stat-card">';
        echo '<div class="bh-stat-icon"><i class="fas fa-mouse-pointer"></i></div>';
        echo '<div class="bh-stat-content"><div class="bh-stat-value">' . number_format($interactionCount) . '</div>';
        echo '<div class="bh-stat-label">Interactions</div></div></div></div>';

        // Average daily
        $days = max(1, (strtotime($data['end_date']) - strtotime($data['start_date'])) / 86400);
        $avgDaily = round($data['total_events'] / $days, 1);
        echo '<div class="col-md-3"><div class="bh-stat-card">';
        echo '<div class="bh-stat-icon"><i class="fas fa-chart-line"></i></div>';
        echo '<div class="bh-stat-content"><div class="bh-stat-value">' . $avgDaily . '</div>';
        echo '<div class="bh-stat-label">Avg. Daily Events</div></div></div></div>';

        echo '</div>';

        // Views by Widget Type
        echo '<div class="row">';
        echo '<div class="col-md-6"><div class="panel panel-default bh-panel">';
        echo '<div class="panel-heading"><h3 class="panel-title">Views by Widget Type</h3></div>';
        echo '<div class="panel-body">';

        if (empty($data['by_widget'])) {
            echo '<p class="text-muted">No data yet.</p>';
        } else {
            echo '<table class="table table-striped"><thead><tr><th>Widget</th><th>Views</th></tr></thead><tbody>';
            foreach ($data['by_widget'] as $item) {
                $item = (object) $item;
                echo '<tr><td>' . SecurityHelper::escape($item->widget_type) . '</td><td>' . number_format($item->count) . '</td></tr>';
            }
            echo '</tbody></table>';
        }

        echo '</div></div></div>';

        // Views by Department
        echo '<div class="col-md-6"><div class="panel panel-default bh-panel">';
        echo '<div class="panel-heading"><h3 class="panel-title">Views by Department</h3></div>';
        echo '<div class="panel-body">';

        if (empty($data['by_department'])) {
            echo '<p class="text-muted">No data yet.</p>';
        } else {
            $deptRepo = new \BusinessHours\Repositories\DepartmentRepository();
            echo '<table class="table table-striped"><thead><tr><th>Department</th><th>Views</th></tr></thead><tbody>';
            foreach ($data['by_department'] as $item) {
                $item = (object) $item;
                $dept = $deptRepo->getById($item->department_id);
                $deptName = $dept ? $dept->name : 'ID: ' . $item->department_id;
                echo '<tr><td>' . SecurityHelper::escape($deptName) . '</td><td>' . number_format($item->count) . '</td></tr>';
            }
            echo '</tbody></table>';
        }

        echo '</div></div></div>';
        echo '</div>'; // row

        // Daily chart data (rendered as a simple table for now)
        echo '<div class="panel panel-default bh-panel">';
        echo '<div class="panel-heading"><h3 class="panel-title">Daily Activity</h3></div>';
        echo '<div class="panel-body">';

        if (empty($data['daily_views'])) {
            echo '<p class="text-muted">No data for this period.</p>';
        } else {
            // Simple bar chart using CSS
            $maxCount = 1;
            foreach ($data['daily_views'] as $d) {
                $d = (object) $d;
                if ($d->count > $maxCount) $maxCount = $d->count;
            }

            echo '<div class="bh-chart">';
            foreach ($data['daily_views'] as $d) {
                $d = (object) $d;
                $pct = round(($d->count / $maxCount) * 100);
                echo '<div class="bh-chart-bar">';
                echo '<div class="bh-chart-fill" style="width:' . $pct . '%"></div>';
                echo '<span class="bh-chart-label">' . date('M j', strtotime($d->date)) . '</span>';
                echo '<span class="bh-chart-value">' . $d->count . '</span>';
                echo '</div>';
            }
            echo '</div>';
        }

        echo '</div></div>';
        echo '</div>';
    }
}
