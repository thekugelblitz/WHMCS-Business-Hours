<?php
/**
 * Client Area AJAX Controller
 *
 * Handles AJAX requests from the client area widgets.
 *
 * @package    BusinessHours\Controllers\Client
 */

namespace BusinessHours\Controllers\Client;

use BusinessHours\Services\AvailabilityService;
use BusinessHours\Services\ScheduleService;
use BusinessHours\Services\HolidayService;
use BusinessHours\Services\AnalyticsService;
use BusinessHours\Repositories\DepartmentRepository;
use BusinessHours\Repositories\SettingsRepository;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class AjaxController
{
    /**
     * Handle status request — returns current status for all departments
     */
    public function handleStatus()
    {
        header('Content-Type: application/json');
        header('Cache-Control: public, max-age=30');

        $availService = new AvailabilityService();
        $settingsRepo = new SettingsRepository();
        $deptRepo = new DepartmentRepository();

        $departmentId = isset($_GET['department']) ? (int) $_GET['department'] : null;
        $departmentSlug = isset($_GET['dept']) ? trim($_GET['dept']) : null;

        // Resolve slug
        if ($departmentSlug && !$departmentId) {
            $dept = $deptRepo->getBySlug($departmentSlug);
            if ($dept) {
                $departmentId = $dept->id;
            }
        }

        if ($departmentId) {
            $status = $availService->getCurrentStatus($departmentId);
            $response = [
                'success'   => true,
                'status'    => $this->sanitizeStatus($status),
                'timestamp' => time(),
            ];
        } else {
            // All departments
            $allStatuses = $availService->getAllDepartmentStatuses();
            $sanitized = [];
            foreach ($allStatuses as $id => $data) {
                $sanitized[$id] = [
                    'department' => $data['department'],
                    'status'     => $this->sanitizeStatus($data['status']),
                ];
            }

            $response = [
                'success'      => true,
                'departments'  => $sanitized,
                'aggregate'    => $this->sanitizeStatus($availService->getCurrentStatus()),
                'timestamp'    => time(),
                'interval'     => (int) $settingsRepo->get('ajax_interval', '60'),
            ];
        }

        echo json_encode($response);
    }

    /**
     * Handle schedule request — returns schedule data for a department
     */
    public function handleSchedule()
    {
        header('Content-Type: application/json');
        header('Cache-Control: public, max-age=300');

        $schedService = new ScheduleService();
        $deptRepo = new DepartmentRepository();

        $departmentId = isset($_GET['department']) ? (int) $_GET['department'] : null;
        $departmentSlug = isset($_GET['dept']) ? trim($_GET['dept']) : null;

        if ($departmentSlug && !$departmentId) {
            $dept = $deptRepo->getBySlug($departmentSlug);
            if ($dept) {
                $departmentId = $dept->id;
            }
        }

        if (!$departmentId) {
            echo json_encode(['success' => false, 'error' => 'Department ID required']);
            return;
        }

        $weekly = $schedService->getWeeklySchedule($departmentId);
        $today = $schedService->getTodaySchedule($departmentId);
        $tomorrow = $schedService->getTomorrowSchedule($departmentId);

        $response = [
            'success'   => true,
            'weekly'    => $this->sanitizeWeekly($weekly),
            'today'     => $today,
            'tomorrow'  => $tomorrow,
            'timestamp' => time(),
        ];

        echo json_encode($response);
    }

    /**
     * Handle analytics tracking
     */
    public function handleTrack()
    {
        header('Content-Type: application/json');
        header('Cache-Control: no-cache');

        $analyticsService = new AnalyticsService();

        $widgetType = isset($_POST['widget']) ? trim($_POST['widget']) : (isset($_GET['widget']) ? trim($_GET['widget']) : null);
        $departmentId = isset($_POST['department_id']) ? (int) $_POST['department_id'] : (isset($_GET['department_id']) ? (int) $_GET['department_id'] : null);
        $pageUrl = isset($_POST['page']) ? trim($_POST['page']) : (isset($_GET['page']) ? trim($_GET['page']) : null);
        $clientId = isset($_SESSION['uid']) ? (int) $_SESSION['uid'] : null;

        if ($widgetType) {
            $analyticsService->trackView($widgetType, $departmentId, $pageUrl, $clientId);
        }

        echo json_encode(['success' => true]);
    }

    /**
     * Sanitize status data for JSON output (remove internal objects)
     */
    private function sanitizeStatus($status)
    {
        // Remove non-serializable objects
        $clean = [
            'is_open'     => $status['is_open'] ?? false,
            'label'       => $status['label'] ?? 'Unknown',
            'next_change' => $status['next_change'] ?? null,
            'reason'      => $status['reason'] ?? null,
            'source'      => $status['source'] ?? 'unknown',
            'today_hours' => $status['today_hours'] ?? 'N/A',
            'timestamp'   => $status['timestamp'] ?? time(),
        ];

        // Include holiday info if present
        if (isset($status['holiday']) && is_object($status['holiday'])) {
            $clean['holiday'] = [
                'name'           => $status['holiday']->name,
                'description'    => $status['holiday']->description,
                'reopen_message' => $status['holiday']->reopenMessage ?? $status['reopen_message'] ?? null,
            ];
        }

        return $clean;
    }

    /**
     * Sanitize weekly schedule for JSON (remove slot objects)
     */
    private function sanitizeWeekly($weekly)
    {
        $clean = [];
        foreach ($weekly as $day => $data) {
            $clean[$day] = [
                'day_name'  => $data['day_name'] ?? '',
                'day_short' => $data['day_short'] ?? '',
                'closed'    => $data['closed'] ?? true,
                'display'   => $data['display'] ?? 'Closed',
                'is_24x7'   => $data['is_24x7'] ?? false,
            ];
        }
        return $clean;
    }
}
