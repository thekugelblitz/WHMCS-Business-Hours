<?php
/**
 * Schedule Admin Controller
 *
 * @package    BusinessHours\Controllers\Admin
 */

namespace BusinessHours\Controllers\Admin;

use BusinessHours\Helpers\SecurityHelper;
use BusinessHours\Helpers\ViewHelper;
use BusinessHours\Helpers\TimeHelper;
use BusinessHours\Models\Schedule;
use BusinessHours\Models\ScheduleSlot;
use BusinessHours\Repositories\ScheduleRepository;
use BusinessHours\Repositories\DepartmentRepository;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class ScheduleController
{
    /** @var ScheduleRepository */
    private $repo;

    /** @var DepartmentRepository */
    private $deptRepo;

    public function __construct()
    {
        $this->repo = new ScheduleRepository();
        $this->deptRepo = new DepartmentRepository();
    }

    public function handle($subAction, $id, $moduleLink, $lang)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost($subAction, $id, $moduleLink, $lang);
            return;
        }

        switch ($subAction) {
            case 'add':
                $this->renderForm(null, $moduleLink, $lang);
                break;
            case 'edit':
                $schedule = $this->repo->getByIdWithSlots($id);
                if (!$schedule) {
                    echo ViewHelper::alertError($lang['schedule_not_found'] ?? 'Schedule not found.');
                    $this->renderList($moduleLink, $lang);
                    return;
                }
                $this->renderForm($schedule, $moduleLink, $lang);
                break;
            case 'clone':
                $this->handleClone($id, $moduleLink, $lang);
                break;
            case 'delete':
                $this->handleDelete($id, $moduleLink, $lang);
                break;
            case 'calendar':
                $this->renderCalendar($moduleLink, $lang);
                break;
            default:
                $this->renderList($moduleLink, $lang);
        }
    }

    /**
     * Handle admin AJAX requests
     */
    public function handleAjax($subAction, $moduleLink, $lang)
    {
        switch ($subAction) {
            case 'save-slots':
                $this->ajaxSaveSlots();
                break;
            default:
                echo json_encode(['error' => 'Unknown action']);
        }
    }

    private function renderList($moduleLink, $lang)
    {
        $deptFilter = isset($_GET['dept']) ? (int) $_GET['dept'] : null;
        $schedules = $this->repo->getAll($deptFilter);
        $departments = $this->deptRepo->getAll();
        $deptMap = [];
        foreach ($departments as $d) {
            $deptMap[$d->id] = $d;
        }

        $bootstrap = \BusinessHours\Bootstrap::getInstance();
        echo '<link rel="stylesheet" href="' . $bootstrap->getAssetUrl('css/admin.css') . '">';
        echo '<div class="bh-admin">';

        echo '<div class="bh-admin-header">';
        echo '<h1><i class="fas fa-calendar-alt"></i> ' . SecurityHelper::escape($lang['schedules'] ?? 'Schedules') . '</h1>';
        echo '</div>';

        echo ViewHelper::adminNav($moduleLink, 'schedules');

        // Actions bar
        echo '<div class="bh-actions-bar">';
        echo '<a href="' . $moduleLink . '&action=schedules&sub=add" class="btn btn-primary"><i class="fas fa-plus"></i> ' . SecurityHelper::escape($lang['add_schedule'] ?? 'Add Schedule') . '</a> ';
        echo '<a href="' . $moduleLink . '&action=schedules&sub=calendar" class="btn btn-default"><i class="fas fa-calendar-week"></i> Calendar View</a>';

        // Department filter
        if (!empty($departments)) {
            echo ' <select class="form-control bh-inline-select" onchange="window.location=this.value">';
            echo '<option value="' . $moduleLink . '&action=schedules">All Departments</option>';
            foreach ($departments as $d) {
                $selected = ($deptFilter === $d->id) ? ' selected' : '';
                echo '<option value="' . $moduleLink . '&action=schedules&sub=list&dept=' . $d->id . '"' . $selected . '>' . SecurityHelper::escape($d->name) . '</option>';
            }
            echo '</select>';
        }
        echo '</div>';

        if (isset($_GET['msg']) && isset($lang[$_GET['msg']])) {
            echo ViewHelper::alertSuccess($lang[$_GET['msg']]);
        }

        // Table
        echo '<div class="panel panel-default bh-panel"><div class="panel-body">';

        if (empty($schedules)) {
            echo ViewHelper::alertInfo('No schedules configured yet.');
        } else {
            echo '<div class="table-responsive"><table class="table table-striped">';
            echo '<thead><tr><th>Name</th><th>Department</th><th>Type</th><th>Effective Dates</th><th>Priority</th><th>Active</th><th>Slots</th><th>Actions</th></tr></thead><tbody>';

            foreach ($schedules as $sched) {
                $deptName = isset($deptMap[$sched->departmentId]) ? $deptMap[$sched->departmentId]->name : 'Unknown';
                $slots = $this->repo->getSlots($sched->id);
                $slotCount = count($slots);

                $typeLabel = [
                    'regular'   => '<span class="label label-primary">Regular</span>',
                    'seasonal'  => '<span class="label label-info">Seasonal</span>',
                    'event'     => '<span class="label label-warning">Event</span>',
                    'temporary' => '<span class="label label-danger">Temporary</span>',
                ];

                echo '<tr>';
                echo '<td><strong>' . SecurityHelper::escape($sched->name) . '</strong></td>';
                echo '<td>' . SecurityHelper::escape($deptName) . '</td>';
                echo '<td>' . ($typeLabel[$sched->type] ?? $sched->type) . '</td>';
                echo '<td>';
                if ($sched->effectiveFrom || $sched->effectiveTo) {
                    echo ($sched->effectiveFrom ?: 'Start') . ' &mdash; ' . ($sched->effectiveTo ?: 'Ongoing');
                } else {
                    echo '<span class="text-muted">Always</span>';
                }
                echo '</td>';
                echo '<td>' . $sched->priority . '</td>';
                echo '<td>' . ($sched->isActive ? '<span class="label label-success">Yes</span>' : '<span class="label label-default">No</span>') . '</td>';
                echo '<td>' . $slotCount . ' slots</td>';
                echo '<td class="bh-actions">';
                echo '<a href="' . $moduleLink . '&action=schedules&sub=edit&id=' . $sched->id . '" class="btn btn-xs btn-primary" title="Edit"><i class="fas fa-edit"></i></a> ';
                echo '<a href="' . $moduleLink . '&action=schedules&sub=clone&id=' . $sched->id . '" class="btn btn-xs btn-info" title="Clone"><i class="fas fa-clone"></i></a> ';
                echo '<a href="' . $moduleLink . '&action=schedules&sub=delete&id=' . $sched->id . '" class="btn btn-xs btn-danger" title="Delete" onclick="return confirm(\'Delete this schedule?\')"><i class="fas fa-trash"></i></a>';
                echo '</td>';
                echo '</tr>';
            }

            echo '</tbody></table></div>';
        }

        echo '</div></div></div>';
    }

    private function renderForm($schedule, $moduleLink, $lang)
    {
        $isEdit = $schedule !== null;
        $departments = $this->deptRepo->getAll();
        $bootstrap = \BusinessHours\Bootstrap::getInstance();

        echo '<link rel="stylesheet" href="' . $bootstrap->getAssetUrl('css/admin.css') . '">';
        echo '<div class="bh-admin">';

        echo '<div class="bh-admin-header">';
        echo '<h1><i class="fas fa-calendar-alt"></i> ' . SecurityHelper::escape($isEdit ? ($lang['edit_schedule'] ?? 'Edit Schedule') : ($lang['add_schedule'] ?? 'Add Schedule')) . '</h1>';
        echo '</div>';

        echo ViewHelper::adminNav($moduleLink, 'schedules');

        echo '<div class="panel panel-default bh-panel"><div class="panel-body">';

        $action = $isEdit
            ? $moduleLink . '&action=schedules&sub=edit&id=' . $schedule->id
            : $moduleLink . '&action=schedules&sub=add';

        echo '<form method="post" action="' . $action . '" class="form-horizontal bh-form" id="bh-schedule-form">';
        echo '<input type="hidden" name="token" value="' . generate_token('link') . '">';

        // Department
        echo '<div class="form-group"><label class="col-sm-3 control-label">Department *</label><div class="col-sm-6">';
        echo '<select name="department_id" class="form-control" required>';
        $preselect = $isEdit ? $schedule->departmentId : (isset($_GET['dept']) ? (int) $_GET['dept'] : 0);
        foreach ($departments as $d) {
            $sel = ($d->id === $preselect) ? ' selected' : '';
            echo '<option value="' . $d->id . '"' . $sel . '>' . SecurityHelper::escape($d->name) . '</option>';
        }
        echo '</select></div></div>';

        // Name
        echo '<div class="form-group"><label class="col-sm-3 control-label">Name *</label><div class="col-sm-6">';
        echo '<input type="text" name="name" class="form-control" value="' . SecurityHelper::escape($isEdit ? $schedule->name : '') . '" required>';
        echo '</div></div>';

        // Type
        echo '<div class="form-group"><label class="col-sm-3 control-label">Type</label><div class="col-sm-6">';
        echo '<select name="type" class="form-control">';
        $types = ['regular' => 'Regular', 'seasonal' => 'Seasonal', 'event' => 'Special Event', 'temporary' => 'Temporary'];
        $currentType = $isEdit ? $schedule->type : 'regular';
        foreach ($types as $val => $label) {
            $sel = ($val === $currentType) ? ' selected' : '';
            echo '<option value="' . $val . '"' . $sel . '>' . $label . '</option>';
        }
        echo '</select></div></div>';

        // Effective dates
        echo '<div class="form-group"><label class="col-sm-3 control-label">Effective From</label><div class="col-sm-4">';
        echo '<input type="date" name="effective_from" class="form-control" value="' . SecurityHelper::escape($isEdit ? $schedule->effectiveFrom : '') . '">';
        echo '</div></div>';

        echo '<div class="form-group"><label class="col-sm-3 control-label">Effective To</label><div class="col-sm-4">';
        echo '<input type="date" name="effective_to" class="form-control" value="' . SecurityHelper::escape($isEdit ? $schedule->effectiveTo : '') . '">';
        echo '</div></div>';

        // Priority
        echo '<div class="form-group"><label class="col-sm-3 control-label">Priority</label><div class="col-sm-3">';
        echo '<input type="number" name="priority" class="form-control" value="' . ($isEdit ? $schedule->priority : 0) . '" min="0">';
        echo '<p class="help-block">Higher priority overrides lower. Regular=0, Seasonal=10, Temporary=20</p>';
        echo '</div></div>';

        // Active
        echo '<div class="form-group"><label class="col-sm-3 control-label">Active</label><div class="col-sm-6">';
        $checked = ($isEdit ? $schedule->isActive : true) ? ' checked' : '';
        echo '<div class="checkbox"><label><input type="checkbox" name="is_active" value="1"' . $checked . '> Schedule is active</label></div>';
        echo '</div></div>';

        // Time Slots section
        echo '<hr><h3><i class="fas fa-clock"></i> Time Slots</h3>';
        echo '<p class="text-muted">Define opening hours for each day. Add multiple slots per day for split shifts.</p>';

        echo '<div id="bh-slots-container">';
        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        // Group existing slots by day
        $slotsByDay = [];
        if ($isEdit && !empty($schedule->slots)) {
            foreach ($schedule->slots as $slot) {
                $slotsByDay[$slot->dayOfWeek][] = $slot;
            }
        }

        for ($day = 0; $day <= 6; $day++) {
            $daySlots = isset($slotsByDay[$day]) ? $slotsByDay[$day] : [];
            $hasSlots = !empty($daySlots);

            echo '<div class="bh-day-row" data-day="' . $day . '">';
            echo '<div class="bh-day-header">';
            echo '<strong>' . $dayNames[$day] . '</strong>';
            echo '<button type="button" class="btn btn-xs btn-success bh-add-slot" data-day="' . $day . '"><i class="fas fa-plus"></i> Add Slot</button>';
            echo '</div>';
            echo '<div class="bh-day-slots" id="bh-day-' . $day . '-slots">';

            if ($hasSlots) {
                foreach ($daySlots as $idx => $slot) {
                    echo '<div class="bh-slot-row">';
                    echo '<input type="time" name="slots[' . $day . '][' . $idx . '][open]" value="' . substr($slot->openTime, 0, 5) . '" class="form-control bh-time-input">';
                    echo '<span class="bh-slot-dash">—</span>';
                    echo '<input type="time" name="slots[' . $day . '][' . $idx . '][close]" value="' . substr($slot->closeTime, 0, 5) . '" class="form-control bh-time-input">';
                    echo '<input type="text" name="slots[' . $day . '][' . $idx . '][label]" value="' . SecurityHelper::escape($slot->label ?: '') . '" class="form-control bh-slot-label" placeholder="Label (optional)">';
                    echo '<button type="button" class="btn btn-xs btn-danger bh-remove-slot"><i class="fas fa-times"></i></button>';
                    echo '</div>';
                }
            }

            echo '</div></div>';
        }

        echo '</div>';

        // Submit
        echo '<hr>';
        echo '<div class="form-group"><div class="col-sm-offset-3 col-sm-6">';
        echo '<button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Schedule</button> ';
        echo '<a href="' . $moduleLink . '&action=schedules" class="btn btn-default">Cancel</a>';
        echo '</div></div>';

        echo '</form>';
        echo '</div></div></div>';

        // JS for dynamic slot management
        echo '<script src="' . $bootstrap->getAssetUrl('js/admin.js') . '"></script>';
    }

    private function renderCalendar($moduleLink, $lang)
    {
        $departments = $this->deptRepo->getAll(true);
        $bootstrap = \BusinessHours\Bootstrap::getInstance();

        echo '<link rel="stylesheet" href="' . $bootstrap->getAssetUrl('css/admin.css') . '">';
        echo '<div class="bh-admin">';
        echo '<div class="bh-admin-header"><h1><i class="fas fa-calendar-week"></i> Visual Calendar</h1></div>';
        echo ViewHelper::adminNav($moduleLink, 'schedules');

        echo '<div class="bh-actions-bar">';
        echo '<a href="' . $moduleLink . '&action=schedules" class="btn btn-default"><i class="fas fa-list"></i> List View</a>';
        echo '</div>';

        // Calendar grid for each department
        foreach ($departments as $dept) {
            $schedule = $this->repo->getEffectiveSchedule($dept->id, date('Y-m-d'));
            $slots = $schedule ? $this->repo->getSlots($schedule->id) : [];

            echo '<div class="panel panel-default bh-panel">';
            echo '<div class="panel-heading"><h3 class="panel-title">';
            if ($dept->color) {
                echo '<span class="bh-dept-color" style="background:' . SecurityHelper::escape($dept->color) . '"></span> ';
            }
            echo SecurityHelper::escape($dept->name);
            if ($dept->is24x7) {
                echo ' <span class="label label-info">24/7</span>';
            }
            echo '</h3></div>';
            echo '<div class="panel-body">';

            echo '<div class="bh-calendar-grid">';
            $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

            // Header
            echo '<div class="bh-cal-header">';
            echo '<div class="bh-cal-time-col"></div>';
            foreach ($dayNames as $dn) {
                echo '<div class="bh-cal-day-col">' . $dn . '</div>';
            }
            echo '</div>';

            // Time grid (6 AM to 10 PM in 2-hour blocks)
            for ($hour = 6; $hour <= 22; $hour += 2) {
                echo '<div class="bh-cal-row">';
                echo '<div class="bh-cal-time-col">' . date('g A', mktime($hour)) . '</div>';

                for ($day = 0; $day <= 6; $day++) {
                    $hasSlot = false;
                    foreach ($slots as $slot) {
                        if ($slot->dayOfWeek === $day) {
                            $openH = (int) substr($slot->openTime, 0, 2);
                            $closeH = (int) substr($slot->closeTime, 0, 2);
                            if ($hour >= $openH && $hour < $closeH) {
                                $hasSlot = true;
                                break;
                            }
                        }
                    }
                    $cellClass = $hasSlot ? 'bh-cal-active' : 'bh-cal-empty';
                    if ($dept->is24x7) $cellClass = 'bh-cal-active';
                    echo '<div class="bh-cal-cell ' . $cellClass . '" style="' . ($hasSlot && $dept->color ? 'background:' . SecurityHelper::escape($dept->color) . '20;border-color:' . SecurityHelper::escape($dept->color) : '') . '"></div>';
                }
                echo '</div>';
            }

            echo '</div>'; // calendar grid
            echo '</div></div>'; // panel
        }

        echo '</div>';
    }

    private function handlePost($subAction, $id, $moduleLink, $lang)
    {
        $departmentId = SecurityHelper::sanitizeInt($_POST['department_id'] ?? 0);
        $name = SecurityHelper::getPost('name');
        $type = SecurityHelper::validateEnum($_POST['type'] ?? 'regular', ['regular', 'seasonal', 'event', 'temporary'], 'regular');
        $effectiveFrom = SecurityHelper::sanitizeDate($_POST['effective_from'] ?? '');
        $effectiveTo = SecurityHelper::sanitizeDate($_POST['effective_to'] ?? '');
        $priority = SecurityHelper::sanitizeInt($_POST['priority'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name) || !$departmentId) {
            echo ViewHelper::alertError('Name and Department are required.');
            return;
        }

        $data = [
            'department_id'  => $departmentId,
            'name'           => $name,
            'type'           => $type,
            'effective_from' => $effectiveFrom,
            'effective_to'   => $effectiveTo,
            'priority'       => $priority,
            'is_active'      => $isActive,
        ];

        if ($subAction === 'edit' && $id) {
            $this->repo->update($id, $data);
            $scheduleId = $id;
        } else {
            $sched = new Schedule();
            $sched->departmentId = $departmentId;
            $sched->name = $name;
            $sched->type = $type;
            $sched->effectiveFrom = $effectiveFrom;
            $sched->effectiveTo = $effectiveTo;
            $sched->priority = $priority;
            $sched->isActive = (bool) $isActive;
            $scheduleId = $this->repo->create($sched);
        }

        // Process time slots
        $slotsData = [];
        if (isset($_POST['slots']) && is_array($_POST['slots'])) {
            foreach ($_POST['slots'] as $day => $daySlots) {
                foreach ($daySlots as $slotInfo) {
                    $open = SecurityHelper::sanitizeTime($slotInfo['open'] ?? '');
                    $close = SecurityHelper::sanitizeTime($slotInfo['close'] ?? '');
                    if ($open && $close && $open < $close) {
                        $slotsData[] = [
                            'day_of_week' => (int) $day,
                            'open_time'   => $open,
                            'close_time'  => $close,
                            'label'       => isset($slotInfo['label']) ? trim($slotInfo['label']) : null,
                        ];
                    }
                }
            }
        }

        $this->repo->replaceSlots($scheduleId, $slotsData);

        header('Location: ' . $moduleLink . '&action=schedules&msg=schedule_saved');
        exit;
    }

    private function handleClone($id, $moduleLink, $lang)
    {
        try {
            $original = $this->repo->getById($id);
            $newName = ($original ? $original->name : 'Schedule') . ' (Copy)';
            $this->repo->cloneSchedule($id, $newName);
            header('Location: ' . $moduleLink . '&action=schedules&msg=schedule_cloned');
        } catch (\Exception $e) {
            echo ViewHelper::alertError('Clone failed: ' . $e->getMessage());
            return;
        }
        exit;
    }

    private function handleDelete($id, $moduleLink, $lang)
    {
        $this->repo->delete($id);
        header('Location: ' . $moduleLink . '&action=schedules&msg=schedule_deleted');
        exit;
    }

    private function ajaxSaveSlots()
    {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok']);
    }
}
