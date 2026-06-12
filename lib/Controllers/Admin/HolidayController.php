<?php
/**
 * Holiday Admin Controller
 *
 * @package    BusinessHours\Controllers\Admin
 */

namespace BusinessHours\Controllers\Admin;

use BusinessHours\Helpers\SecurityHelper;
use BusinessHours\Helpers\ViewHelper;
use BusinessHours\Models\Holiday;
use BusinessHours\Repositories\HolidayRepository;
use BusinessHours\Repositories\DepartmentRepository;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class HolidayController
{
    /** @var HolidayRepository */
    private $repo;

    public function __construct()
    {
        $this->repo = new HolidayRepository();
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
                $holiday = $this->repo->getById($id);
                if (!$holiday) {
                    echo ViewHelper::alertError($lang['holiday_not_found'] ?? 'Holiday not found.');
                    $this->renderList($moduleLink, $lang);
                    return;
                }
                $this->renderForm($holiday, $moduleLink, $lang);
                break;
            case 'delete':
                $this->repo->delete($id);
                header('Location: ' . $moduleLink . '&action=holidays&msg=holiday_deleted');
                exit;
            default:
                $this->renderList($moduleLink, $lang);
        }
    }

    private function renderList($moduleLink, $lang)
    {
        $holidays = $this->repo->getAll();
        $bootstrap = \BusinessHours\Bootstrap::getInstance();

        echo '<link rel="stylesheet" href="' . $bootstrap->getAssetUrl('css/admin.css') . '">';
        echo '<div class="bh-admin">';
        echo '<div class="bh-admin-header"><h1><i class="fas fa-umbrella-beach"></i> ' . SecurityHelper::escape($lang['holidays'] ?? 'Holidays') . '</h1></div>';
        echo ViewHelper::adminNav($moduleLink, 'holidays');

        echo '<div class="bh-actions-bar">';
        echo '<a href="' . $moduleLink . '&action=holidays&sub=add" class="btn btn-primary"><i class="fas fa-plus"></i> ' . SecurityHelper::escape($lang['add_holiday'] ?? 'Add Holiday') . '</a>';
        echo '</div>';

        if (isset($_GET['msg']) && isset($lang[$_GET['msg']])) {
            echo ViewHelper::alertSuccess($lang[$_GET['msg']]);
        }

        echo '<div class="panel panel-default bh-panel"><div class="panel-body">';

        if (empty($holidays)) {
            echo ViewHelper::alertInfo('No holidays configured yet.');
        } else {
            echo '<div class="table-responsive"><table class="table table-striped">';
            echo '<thead><tr><th>Name</th><th>Dates</th><th>Type</th><th>Recurring</th><th>Partial</th><th>Departments</th><th>Status</th><th>Actions</th></tr></thead><tbody>';

            foreach ($holidays as $h) {
                $typeLabels = [
                    'company'   => '<span class="label label-primary">Company</span>',
                    'regional'  => '<span class="label label-info">Regional</span>',
                    'global'    => '<span class="label label-success">Global</span>',
                    'emergency' => '<span class="label label-danger">Emergency</span>',
                ];

                echo '<tr>';
                echo '<td><strong>' . SecurityHelper::escape($h->name) . '</strong>';
                if ($h->description) echo '<br><small class="text-muted">' . SecurityHelper::escape(substr($h->description, 0, 60)) . '</small>';
                echo '</td>';
                echo '<td>' . SecurityHelper::escape($h->startDate);
                if ($h->isMultiDay()) echo ' &mdash; ' . SecurityHelper::escape($h->endDate);
                echo '</td>';
                echo '<td>' . ($typeLabels[$h->type] ?? $h->type) . '</td>';
                echo '<td>' . ($h->isRecurring ? '<i class="fas fa-redo text-success"></i> Yes' : 'No') . '</td>';
                echo '<td>' . ($h->isPartialDay ? 'Yes' : 'No') . '</td>';
                echo '<td>' . ($h->appliesToDepartments === null ? 'All' : count($h->appliesToDepartments) . ' dept(s)') . '</td>';
                echo '<td><span class="label ' . ($h->status === 'active' ? 'label-success' : 'label-default') . '">' . ucfirst($h->status) . '</span></td>';
                echo '<td class="bh-actions">';
                echo '<a href="' . $moduleLink . '&action=holidays&sub=edit&id=' . $h->id . '" class="btn btn-xs btn-primary"><i class="fas fa-edit"></i></a> ';
                echo '<a href="' . $moduleLink . '&action=holidays&sub=delete&id=' . $h->id . '" class="btn btn-xs btn-danger" onclick="return confirm(\'Delete this holiday?\')"><i class="fas fa-trash"></i></a>';
                echo '</td>';
                echo '</tr>';
            }

            echo '</tbody></table></div>';
        }

        echo '</div></div></div>';
    }

    private function renderForm($holiday, $moduleLink, $lang)
    {
        $isEdit = $holiday !== null;
        $deptRepo = new DepartmentRepository();
        $departments = $deptRepo->getAll();
        $bootstrap = \BusinessHours\Bootstrap::getInstance();

        echo '<link rel="stylesheet" href="' . $bootstrap->getAssetUrl('css/admin.css') . '">';
        echo '<div class="bh-admin">';
        echo '<div class="bh-admin-header"><h1><i class="fas fa-umbrella-beach"></i> ' . SecurityHelper::escape($isEdit ? ($lang['edit_holiday'] ?? 'Edit Holiday') : ($lang['add_holiday'] ?? 'Add Holiday')) . '</h1></div>';
        echo ViewHelper::adminNav($moduleLink, 'holidays');

        echo '<div class="panel panel-default bh-panel"><div class="panel-body">';

        $action = $isEdit ? $moduleLink . '&action=holidays&sub=edit&id=' . $holiday->id : $moduleLink . '&action=holidays&sub=add';

        echo '<form method="post" action="' . $action . '" class="form-horizontal bh-form">';
        echo '<input type="hidden" name="token" value="' . generate_token('link') . '">';

        // Name
        echo '<div class="form-group"><label class="col-sm-3 control-label">Name *</label><div class="col-sm-6">';
        echo '<input type="text" name="name" class="form-control" value="' . SecurityHelper::escape($isEdit ? $holiday->name : '') . '" required>';
        echo '</div></div>';

        // Description
        echo '<div class="form-group"><label class="col-sm-3 control-label">Description</label><div class="col-sm-6">';
        echo '<textarea name="description" class="form-control" rows="2">' . SecurityHelper::escape($isEdit ? $holiday->description : '') . '</textarea>';
        echo '</div></div>';

        // Dates
        echo '<div class="form-group"><label class="col-sm-3 control-label">Start Date *</label><div class="col-sm-4">';
        echo '<input type="date" name="start_date" class="form-control" value="' . SecurityHelper::escape($isEdit ? $holiday->startDate : '') . '" required>';
        echo '</div></div>';

        echo '<div class="form-group"><label class="col-sm-3 control-label">End Date *</label><div class="col-sm-4">';
        echo '<input type="date" name="end_date" class="form-control" value="' . SecurityHelper::escape($isEdit ? $holiday->endDate : '') . '" required>';
        echo '</div></div>';

        // Type
        echo '<div class="form-group"><label class="col-sm-3 control-label">Type</label><div class="col-sm-6">';
        echo '<select name="type" class="form-control">';
        $types = ['company' => 'Company Holiday', 'regional' => 'Regional Holiday', 'global' => 'Global Holiday', 'emergency' => 'Emergency Closure'];
        $currentType = $isEdit ? $holiday->type : 'company';
        foreach ($types as $val => $label) {
            echo '<option value="' . $val . '"' . ($val === $currentType ? ' selected' : '') . '>' . $label . '</option>';
        }
        echo '</select></div></div>';

        // Recurring
        echo '<div class="form-group"><label class="col-sm-3 control-label">Recurring Annually</label><div class="col-sm-6">';
        $checked = ($isEdit && $holiday->isRecurring) ? ' checked' : '';
        echo '<div class="checkbox"><label><input type="checkbox" name="is_recurring" value="1"' . $checked . '> Repeats every year</label></div>';
        echo '</div></div>';

        // Partial day
        echo '<div class="form-group"><label class="col-sm-3 control-label">Partial Day</label><div class="col-sm-6">';
        $checked = ($isEdit && $holiday->isPartialDay) ? ' checked' : '';
        echo '<div class="checkbox"><label><input type="checkbox" name="is_partial_day" value="1"' . $checked . ' id="bh-partial-toggle"> Partial day closure (reduced hours)</label></div>';
        echo '</div></div>';

        // Partial day times
        echo '<div class="bh-partial-fields" style="' . ($isEdit && $holiday->isPartialDay ? '' : 'display:none') . '">';
        echo '<div class="form-group"><label class="col-sm-3 control-label">Open Time</label><div class="col-sm-3">';
        echo '<input type="time" name="partial_open_time" class="form-control" value="' . SecurityHelper::escape($isEdit && $holiday->partialOpenTime ? substr($holiday->partialOpenTime, 0, 5) : '') . '">';
        echo '</div></div>';
        echo '<div class="form-group"><label class="col-sm-3 control-label">Close Time</label><div class="col-sm-3">';
        echo '<input type="time" name="partial_close_time" class="form-control" value="' . SecurityHelper::escape($isEdit && $holiday->partialCloseTime ? substr($holiday->partialCloseTime, 0, 5) : '') . '">';
        echo '</div></div>';
        echo '</div>';

        // Region
        echo '<div class="form-group"><label class="col-sm-3 control-label">Region</label><div class="col-sm-4">';
        echo '<input type="text" name="region" class="form-control" value="' . SecurityHelper::escape($isEdit ? $holiday->region : '') . '" placeholder="e.g. US, EU">';
        echo '</div></div>';

        // Departments
        echo '<div class="form-group"><label class="col-sm-3 control-label">Applies To</label><div class="col-sm-6">';
        $selectedDepts = ($isEdit && $holiday->appliesToDepartments !== null) ? $holiday->appliesToDepartments : [];
        $allDepts = ($isEdit && $holiday->appliesToDepartments === null) || !$isEdit;
        echo '<div class="checkbox"><label><input type="checkbox" name="all_departments" value="1"' . ($allDepts ? ' checked' : '') . ' id="bh-all-depts"> All Departments</label></div>';
        foreach ($departments as $d) {
            $checked = in_array($d->id, $selectedDepts) ? ' checked' : '';
            echo '<div class="checkbox bh-dept-checkbox" style="' . ($allDepts ? 'display:none' : '') . '"><label><input type="checkbox" name="department_ids[]" value="' . $d->id . '"' . $checked . '> ' . SecurityHelper::escape($d->name) . '</label></div>';
        }
        echo '</div></div>';

        // Reopen message
        echo '<div class="form-group"><label class="col-sm-3 control-label">Reopen Message</label><div class="col-sm-6">';
        echo '<textarea name="reopen_message" class="form-control" rows="2" placeholder="e.g. We will resume normal operations on...">' . SecurityHelper::escape($isEdit ? $holiday->reopenMessage : '') . '</textarea>';
        echo '</div></div>';

        // Status
        echo '<div class="form-group"><label class="col-sm-3 control-label">Status</label><div class="col-sm-4">';
        echo '<select name="status" class="form-control">';
        $currentStatus = $isEdit ? $holiday->status : 'active';
        echo '<option value="active"' . ($currentStatus === 'active' ? ' selected' : '') . '>Active</option>';
        echo '<option value="disabled"' . ($currentStatus === 'disabled' ? ' selected' : '') . '>Disabled</option>';
        echo '</select></div></div>';

        // Submit
        echo '<hr>';
        echo '<div class="form-group"><div class="col-sm-offset-3 col-sm-6">';
        echo '<button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button> ';
        echo '<a href="' . $moduleLink . '&action=holidays" class="btn btn-default">Cancel</a>';
        echo '</div></div>';

        echo '</form>';
        echo '</div></div></div>';

        // Toggle partial fields JS
        echo '<script>
        document.getElementById("bh-partial-toggle").addEventListener("change", function() {
            document.querySelector(".bh-partial-fields").style.display = this.checked ? "" : "none";
        });
        document.getElementById("bh-all-depts").addEventListener("change", function() {
            var boxes = document.querySelectorAll(".bh-dept-checkbox");
            for (var i = 0; i < boxes.length; i++) {
                boxes[i].style.display = this.checked ? "none" : "";
            }
        });
        </script>';
    }

    private function handlePost($subAction, $id, $moduleLink, $lang)
    {
        $name = SecurityHelper::getPost('name');
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $startDate = SecurityHelper::sanitizeDate($_POST['start_date'] ?? '');
        $endDate = SecurityHelper::sanitizeDate($_POST['end_date'] ?? '');
        $type = SecurityHelper::validateEnum($_POST['type'] ?? 'company', ['company', 'regional', 'global', 'emergency'], 'company');
        $isRecurring = isset($_POST['is_recurring']) ? 1 : 0;
        $isPartialDay = isset($_POST['is_partial_day']) ? 1 : 0;
        $partialOpen = $isPartialDay ? SecurityHelper::sanitizeTime($_POST['partial_open_time'] ?? '') : null;
        $partialClose = $isPartialDay ? SecurityHelper::sanitizeTime($_POST['partial_close_time'] ?? '') : null;
        $region = SecurityHelper::getPost('region');
        $reopenMessage = isset($_POST['reopen_message']) ? trim($_POST['reopen_message']) : '';
        $status = SecurityHelper::validateEnum($_POST['status'] ?? 'active', ['active', 'disabled'], 'active');

        // Handle departments
        $allDepts = isset($_POST['all_departments']);
        $departmentIds = null;
        if (!$allDepts && isset($_POST['department_ids'])) {
            $departmentIds = array_map('intval', $_POST['department_ids']);
        }

        if (empty($name) || !$startDate || !$endDate) {
            echo ViewHelper::alertError('Name, Start Date, and End Date are required.');
            return;
        }

        $data = [
            'name'                    => $name,
            'description'             => $description ?: null,
            'start_date'              => $startDate,
            'end_date'                => $endDate,
            'is_recurring'            => $isRecurring,
            'is_partial_day'          => $isPartialDay,
            'partial_open_time'       => $partialOpen,
            'partial_close_time'      => $partialClose,
            'type'                    => $type,
            'region'                  => $region ?: null,
            'applies_to_departments'  => $departmentIds !== null ? json_encode($departmentIds) : null,
            'reopen_message'          => $reopenMessage ?: null,
            'status'                  => $status,
        ];

        if ($subAction === 'edit' && $id) {
            $this->repo->update($id, $data);
        } else {
            $holiday = new Holiday();
            $holiday->name = $data['name'];
            $holiday->description = $data['description'];
            $holiday->startDate = $data['start_date'];
            $holiday->endDate = $data['end_date'];
            $holiday->isRecurring = (bool) $data['is_recurring'];
            $holiday->isPartialDay = (bool) $data['is_partial_day'];
            $holiday->partialOpenTime = $data['partial_open_time'];
            $holiday->partialCloseTime = $data['partial_close_time'];
            $holiday->type = $data['type'];
            $holiday->region = $data['region'];
            $holiday->appliesToDepartments = $departmentIds;
            $holiday->reopenMessage = $data['reopen_message'];
            $holiday->status = $data['status'];
            $this->repo->create($holiday);
        }

        header('Location: ' . $moduleLink . '&action=holidays&msg=holiday_saved');
        exit;
    }
}
