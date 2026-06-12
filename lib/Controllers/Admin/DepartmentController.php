<?php
/**
 * Department Admin Controller
 *
 * @package    BusinessHours\Controllers\Admin
 */

namespace BusinessHours\Controllers\Admin;

use BusinessHours\Helpers\SecurityHelper;
use BusinessHours\Helpers\ViewHelper;
use BusinessHours\Models\Department;
use BusinessHours\Repositories\DepartmentRepository;
use BusinessHours\Repositories\ScheduleRepository;
use BusinessHours\Services\TimezoneService;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class DepartmentController
{
    /** @var DepartmentRepository */
    private $repo;

    public function __construct()
    {
        $this->repo = new DepartmentRepository();
    }

    /**
     * Handle department actions
     */
    public function handle($subAction, $id, $moduleLink, $lang)
    {
        // Handle form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost($subAction, $id, $moduleLink, $lang);
            return;
        }

        switch ($subAction) {
            case 'add':
                $this->renderForm(null, $moduleLink, $lang);
                break;
            case 'edit':
                $dept = $this->repo->getById($id);
                if (!$dept) {
                    echo ViewHelper::alertError($lang['department_not_found']);
                    $this->renderList($moduleLink, $lang);
                    return;
                }
                $this->renderForm($dept, $moduleLink, $lang);
                break;
            case 'delete':
                $this->handleDelete($id, $moduleLink, $lang);
                break;
            default:
                $this->renderList($moduleLink, $lang);
        }
    }

    /**
     * Render department list
     */
    private function renderList($moduleLink, $lang)
    {
        $departments = $this->repo->getAll();
        $bootstrap = \BusinessHours\Bootstrap::getInstance();

        echo '<link rel="stylesheet" href="' . $bootstrap->getAssetUrl('css/admin.css') . '">';
        echo '<div class="bh-admin">';

        // Header
        echo '<div class="bh-admin-header">';
        echo '<h1><i class="fas fa-building"></i> ' . SecurityHelper::escape($lang['departments'] ?? 'Departments') . '</h1>';
        echo '</div>';

        echo ViewHelper::adminNav($moduleLink, 'departments');

        // Actions bar
        echo '<div class="bh-actions-bar">';
        echo '<a href="' . $moduleLink . '&action=departments&sub=add" class="btn btn-primary"><i class="fas fa-plus"></i> ' . SecurityHelper::escape($lang['add_department'] ?? 'Add Department') . '</a>';
        echo '</div>';

        // Success/Error messages
        if (isset($_GET['msg'])) {
            $msgKey = $_GET['msg'];
            if (isset($lang[$msgKey])) {
                echo ViewHelper::alertSuccess($lang[$msgKey]);
            }
        }

        // Table
        echo '<div class="panel panel-default bh-panel">';
        echo '<div class="panel-body">';

        if (empty($departments)) {
            echo ViewHelper::alertInfo('No departments configured yet. Click "Add Department" to create your first one.');
        } else {
            echo '<div class="table-responsive"><table class="table table-striped table-hover" id="bh-dept-table">';
            echo '<thead><tr>';
            echo '<th>Color</th>';
            echo '<th>Name</th>';
            echo '<th>Slug</th>';
            echo '<th>Timezone</th>';
            echo '<th>24/7</th>';
            echo '<th>Status</th>';
            echo '<th>Order</th>';
            echo '<th>Actions</th>';
            echo '</tr></thead><tbody>';

            foreach ($departments as $dept) {
                $statusClass = $dept->status === 'active' ? 'label-success' : ($dept->status === 'disabled' ? 'label-warning' : 'label-default');

                echo '<tr>';
                echo '<td><span class="bh-color-swatch" style="background:' . SecurityHelper::escape($dept->color ?: '#6b7280') . '"></span></td>';
                echo '<td>';
                if ($dept->icon) {
                    echo '<i class="fas ' . SecurityHelper::escape($dept->icon) . '"></i> ';
                }
                echo '<strong>' . SecurityHelper::escape($dept->name) . '</strong>';
                if ($dept->description) {
                    echo '<br><small class="text-muted">' . SecurityHelper::escape($dept->description) . '</small>';
                }
                echo '</td>';
                echo '<td><code>' . SecurityHelper::escape($dept->slug) . '</code></td>';
                echo '<td><small>' . SecurityHelper::escape($dept->timezone) . '</small></td>';
                echo '<td>' . ($dept->is24x7 ? '<span class="label label-info">Yes</span>' : 'No') . '</td>';
                echo '<td><span class="label ' . $statusClass . '">' . SecurityHelper::escape(ucfirst($dept->status)) . '</span></td>';
                echo '<td>' . $dept->sortOrder . '</td>';
                echo '<td class="bh-actions">';
                echo '<a href="' . $moduleLink . '&action=schedules&sub=list&dept=' . $dept->id . '" class="btn btn-xs btn-default" title="Schedules"><i class="fas fa-calendar"></i></a> ';
                echo '<a href="' . $moduleLink . '&action=departments&sub=edit&id=' . $dept->id . '" class="btn btn-xs btn-primary" title="Edit"><i class="fas fa-edit"></i></a> ';
                echo '<a href="' . $moduleLink . '&action=departments&sub=delete&id=' . $dept->id . '" class="btn btn-xs btn-danger" title="Delete" onclick="return confirm(\'' . SecurityHelper::escapeJs($lang['department_confirm_delete'] ?? 'Are you sure?') . '\')"><i class="fas fa-trash"></i></a>';
                echo '</td>';
                echo '</tr>';
            }

            echo '</tbody></table></div>';
        }

        echo '</div></div></div>';
    }

    /**
     * Render department add/edit form
     */
    private function renderForm($department, $moduleLink, $lang)
    {
        $isEdit = $department !== null;
        $tzService = new TimezoneService();
        $timezones = $tzService->getTimezoneList();
        $bootstrap = \BusinessHours\Bootstrap::getInstance();

        echo '<link rel="stylesheet" href="' . $bootstrap->getAssetUrl('css/admin.css') . '">';
        echo '<div class="bh-admin">';

        echo '<div class="bh-admin-header">';
        echo '<h1><i class="fas fa-building"></i> ' . SecurityHelper::escape($isEdit ? ($lang['edit_department'] ?? 'Edit Department') : ($lang['add_department'] ?? 'Add Department')) . '</h1>';
        echo '</div>';

        echo ViewHelper::adminNav($moduleLink, 'departments');

        echo '<div class="panel panel-default bh-panel">';
        echo '<div class="panel-body">';

        $action = $isEdit ? $moduleLink . '&action=departments&sub=edit&id=' . $department->id : $moduleLink . '&action=departments&sub=add';

        echo '<form method="post" action="' . $action . '" class="form-horizontal bh-form">';
        echo '<input type="hidden" name="token" value="' . generate_token('link') . '">';

        // Name
        echo '<div class="form-group">';
        echo '<label class="col-sm-3 control-label">Name *</label>';
        echo '<div class="col-sm-6">';
        echo '<input type="text" name="name" class="form-control" value="' . SecurityHelper::escape($isEdit ? $department->name : '') . '" required>';
        echo '</div></div>';

        // Slug
        echo '<div class="form-group">';
        echo '<label class="col-sm-3 control-label">Slug *</label>';
        echo '<div class="col-sm-6">';
        echo '<input type="text" name="slug" class="form-control" value="' . SecurityHelper::escape($isEdit ? $department->slug : '') . '" placeholder="auto-generated-from-name">';
        echo '<p class="help-block">URL-safe identifier. Leave blank to auto-generate.</p>';
        echo '</div></div>';

        // Description
        echo '<div class="form-group">';
        echo '<label class="col-sm-3 control-label">Description</label>';
        echo '<div class="col-sm-6">';
        echo '<textarea name="description" class="form-control" rows="3">' . SecurityHelper::escape($isEdit ? $department->description : '') . '</textarea>';
        echo '</div></div>';

        // Timezone
        echo '<div class="form-group">';
        echo '<label class="col-sm-3 control-label">Timezone *</label>';
        echo '<div class="col-sm-6">';
        echo '<select name="timezone" class="form-control">';
        $currentTz = $isEdit ? $department->timezone : 'America/New_York';
        foreach ($timezones as $region => $tzList) {
            echo '<optgroup label="' . SecurityHelper::escape($region) . '">';
            foreach ($tzList as $tz) {
                $selected = $tz['value'] === $currentTz ? ' selected' : '';
                echo '<option value="' . SecurityHelper::escape($tz['value']) . '"' . $selected . '>' . SecurityHelper::escape($tz['label']) . ' (' . $tz['offset'] . ')</option>';
            }
            echo '</optgroup>';
        }
        echo '</select>';
        echo '</div></div>';

        // 24/7
        echo '<div class="form-group">';
        echo '<label class="col-sm-3 control-label">24/7 Support</label>';
        echo '<div class="col-sm-6">';
        $checked = ($isEdit && $department->is24x7) ? ' checked' : '';
        echo '<div class="checkbox"><label><input type="checkbox" name="is_24x7" value="1"' . $checked . '> This department provides 24/7 support</label></div>';
        echo '</div></div>';

        // Color
        echo '<div class="form-group">';
        echo '<label class="col-sm-3 control-label">Color</label>';
        echo '<div class="col-sm-6">';
        echo '<input type="color" name="color" class="form-control bh-color-input" value="' . SecurityHelper::escape($isEdit && $department->color ? $department->color : '#3b82f6') . '">';
        echo '</div></div>';

        // Icon
        echo '<div class="form-group">';
        echo '<label class="col-sm-3 control-label">Icon</label>';
        echo '<div class="col-sm-6">';
        echo '<input type="text" name="icon" class="form-control" value="' . SecurityHelper::escape($isEdit ? $department->icon : '') . '" placeholder="e.g. fa-headset">';
        echo '<p class="help-block">FontAwesome icon class (e.g. fa-headset, fa-dollar-sign)</p>';
        echo '</div></div>';

        // Sort Order
        echo '<div class="form-group">';
        echo '<label class="col-sm-3 control-label">Sort Order</label>';
        echo '<div class="col-sm-3">';
        echo '<input type="number" name="sort_order" class="form-control" value="' . ($isEdit ? $department->sortOrder : 0) . '" min="0">';
        echo '</div></div>';

        // Status
        echo '<div class="form-group">';
        echo '<label class="col-sm-3 control-label">Status</label>';
        echo '<div class="col-sm-6">';
        echo '<select name="status" class="form-control">';
        $currentStatus = $isEdit ? $department->status : 'active';
        foreach (['active', 'disabled', 'archived'] as $s) {
            $selected = $s === $currentStatus ? ' selected' : '';
            echo '<option value="' . $s . '"' . $selected . '>' . ucfirst($s) . '</option>';
        }
        echo '</select>';
        echo '</div></div>';

        // Submit
        echo '<div class="form-group">';
        echo '<div class="col-sm-offset-3 col-sm-6">';
        echo '<button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> ' . SecurityHelper::escape($lang['save'] ?? 'Save') . '</button> ';
        echo '<a href="' . $moduleLink . '&action=departments" class="btn btn-default">' . SecurityHelper::escape($lang['cancel'] ?? 'Cancel') . '</a>';
        echo '</div></div>';

        echo '</form>';
        echo '</div></div></div>';
    }

    /**
     * Handle POST form submissions
     */
    private function handlePost($subAction, $id, $moduleLink, $lang)
    {
        $name       = SecurityHelper::getPost('name');
        $slug       = SecurityHelper::getPost('slug');
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $timezone   = SecurityHelper::getPost('timezone', 'America/New_York');
        $is24x7     = isset($_POST['is_24x7']) ? 1 : 0;
        $color      = SecurityHelper::sanitizeColor($_POST['color'] ?? '');
        $icon       = SecurityHelper::getPost('icon');
        $sortOrder  = SecurityHelper::sanitizeInt($_POST['sort_order'] ?? 0);
        $status     = SecurityHelper::validateEnum($_POST['status'] ?? 'active', ['active', 'disabled', 'archived'], 'active');

        if (empty($name)) {
            echo ViewHelper::alertError('Department name is required.');
            return;
        }

        if (empty($slug)) {
            $slug = Department::generateSlug($name);
        } else {
            $slug = SecurityHelper::sanitizeSlug($slug);
        }

        $data = [
            'name'        => $name,
            'slug'        => $slug,
            'description' => $description,
            'timezone'    => $timezone,
            'is_24x7'     => $is24x7,
            'color'       => $color,
            'icon'        => $icon,
            'sort_order'  => $sortOrder,
            'status'      => $status,
        ];

        if ($subAction === 'edit' && $id) {
            // Check slug uniqueness
            if ($this->repo->slugExists($slug, $id)) {
                echo ViewHelper::alertError('Slug "' . $slug . '" already exists. Please choose a different one.');
                return;
            }
            $this->repo->update($id, $data);
            header('Location: ' . $moduleLink . '&action=departments&msg=department_saved');
        } else {
            if ($this->repo->slugExists($slug)) {
                echo ViewHelper::alertError('Slug "' . $slug . '" already exists. Please choose a different one.');
                return;
            }
            $dept = new Department();
            foreach ($data as $key => $value) {
                $prop = lcfirst(str_replace('_', '', ucwords($key, '_')));
                if (property_exists($dept, $prop)) {
                    $dept->$prop = $value;
                }
            }
            // Manual mapping for non-standard property names
            $dept->name = $data['name'];
            $dept->slug = $data['slug'];
            $dept->description = $data['description'];
            $dept->timezone = $data['timezone'];
            $dept->is24x7 = (bool) $data['is_24x7'];
            $dept->sortOrder = $data['sort_order'];
            $dept->status = $data['status'];
            $dept->color = $data['color'];
            $dept->icon = $data['icon'];

            $this->repo->create($dept);
            header('Location: ' . $moduleLink . '&action=departments&msg=department_saved');
        }
        exit;
    }

    /**
     * Handle department deletion
     */
    private function handleDelete($id, $moduleLink, $lang)
    {
        $dept = $this->repo->getById($id);
        if (!$dept) {
            echo ViewHelper::alertError($lang['department_not_found'] ?? 'Department not found.');
            return;
        }

        // Delete associated schedules
        $schedRepo = new ScheduleRepository();
        $schedules = $schedRepo->getAll($id);
        foreach ($schedules as $sched) {
            $schedRepo->delete($sched->id);
        }

        $this->repo->delete($id);
        header('Location: ' . $moduleLink . '&action=departments&msg=department_deleted');
        exit;
    }
}
