<?php
/**
 * Business Hours & Support Availability - WHMCS Addon Module
 *
 * Main module entry point file.
 * Defines all required WHMCS addon module functions.
 *
 * @package    BusinessHours
 * @author     WHMCS Custom Code
 * @copyright  2026
 * @version    1.0.0
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

use Illuminate\Database\Capsule\Manager as Capsule;

// Initialize the module autoloader
require_once __DIR__ . '/lib/Bootstrap.php';
\BusinessHours\Bootstrap::getInstance()->init();

/**
 * Module Configuration
 *
 * @return array
 */
function business_hours_config()
{
    return [
        'name' => 'Business Hours & Support Availability',
        'description' => 'Display company support availability, business hours, office status, holiday schedules, response expectations, and contact availability throughout the WHMCS client area.',
        'version' => \BusinessHours\Bootstrap::VERSION,
        'author' => 'WHMCS Custom Code',
        'language' => 'english',
        'fields' => [],
    ];
}

/**
 * Module Activation
 *
 * Called when the module is activated in the admin area.
 * Creates all required database tables.
 *
 * @return array
 */
function business_hours_activate()
{
    try {
        // Table: departments
        if (!Capsule::schema()->hasTable('mod_business_hours_departments')) {
            Capsule::schema()->create('mod_business_hours_departments', function ($table) {
                $table->increments('id');
                $table->string('name', 255);
                $table->string('slug', 100)->unique();
                $table->text('description')->nullable();
                $table->string('timezone', 100)->default('America/New_York');
                $table->tinyInteger('is_24x7')->default(0);
                $table->integer('sort_order')->default(0);
                $table->enum('status', ['active', 'disabled', 'archived'])->default('active');
                $table->string('color', 7)->nullable();
                $table->string('icon', 50)->nullable();
                $table->timestamps();
            });
        }

        // Table: schedules
        if (!Capsule::schema()->hasTable('mod_business_hours_schedules')) {
            Capsule::schema()->create('mod_business_hours_schedules', function ($table) {
                $table->increments('id');
                $table->unsignedInteger('department_id');
                $table->string('name', 255);
                $table->enum('type', ['regular', 'seasonal', 'event', 'temporary'])->default('regular');
                $table->date('effective_from')->nullable();
                $table->date('effective_to')->nullable();
                $table->integer('priority')->default(0);
                $table->tinyInteger('is_active')->default(1);
                $table->timestamps();

                $table->index('department_id');
                $table->index(['is_active', 'priority']);
            });
        }

        // Table: slots (time slots within schedules)
        if (!Capsule::schema()->hasTable('mod_business_hours_slots')) {
            Capsule::schema()->create('mod_business_hours_slots', function ($table) {
                $table->increments('id');
                $table->unsignedInteger('schedule_id');
                $table->tinyInteger('day_of_week'); // 0=Sun, 6=Sat
                $table->time('open_time');
                $table->time('close_time');
                $table->string('label', 100)->nullable();

                $table->index('schedule_id');
                $table->index(['schedule_id', 'day_of_week']);
            });
        }

        // Table: holidays
        if (!Capsule::schema()->hasTable('mod_business_hours_holidays')) {
            Capsule::schema()->create('mod_business_hours_holidays', function ($table) {
                $table->increments('id');
                $table->string('name', 255);
                $table->text('description')->nullable();
                $table->date('start_date');
                $table->date('end_date');
                $table->tinyInteger('is_recurring')->default(0);
                $table->tinyInteger('is_partial_day')->default(0);
                $table->time('partial_open_time')->nullable();
                $table->time('partial_close_time')->nullable();
                $table->enum('type', ['company', 'regional', 'global', 'emergency'])->default('company');
                $table->string('region', 100)->nullable();
                $table->text('applies_to_departments')->nullable(); // JSON
                $table->text('reopen_message')->nullable();
                $table->enum('status', ['active', 'disabled'])->default('active');
                $table->timestamps();

                $table->index(['start_date', 'end_date']);
                $table->index('status');
            });
        }

        // Table: overrides (one-time date-specific overrides)
        if (!Capsule::schema()->hasTable('mod_business_hours_overrides')) {
            Capsule::schema()->create('mod_business_hours_overrides', function ($table) {
                $table->increments('id');
                $table->unsignedInteger('department_id')->nullable();
                $table->date('override_date');
                $table->tinyInteger('is_closed')->default(0);
                $table->time('open_time')->nullable();
                $table->time('close_time')->nullable();
                $table->string('reason', 255)->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index('override_date');
                $table->index(['override_date', 'department_id']);
            });
        }

        // Table: response_times
        if (!Capsule::schema()->hasTable('mod_business_hours_response_times')) {
            Capsule::schema()->create('mod_business_hours_response_times', function ($table) {
                $table->increments('id');
                $table->unsignedInteger('department_id')->nullable();
                $table->enum('context', ['business_hours', 'after_hours', 'holiday', 'emergency'])->default('business_hours');
                $table->text('message');
                $table->integer('estimated_minutes')->nullable();
                $table->integer('sort_order')->default(0);
                $table->tinyInteger('is_active')->default(1);

                $table->index('department_id');
                $table->index('context');
            });
        }

        // Table: settings
        if (!Capsule::schema()->hasTable('mod_business_hours_settings')) {
            Capsule::schema()->create('mod_business_hours_settings', function ($table) {
                $table->increments('id');
                $table->string('setting_key', 255)->unique();
                $table->text('setting_value')->nullable();
                $table->string('setting_group', 100)->default('general');
            });
        }

        // Table: analytics
        if (!Capsule::schema()->hasTable('mod_business_hours_analytics')) {
            Capsule::schema()->create('mod_business_hours_analytics', function ($table) {
                $table->increments('id');
                $table->string('event_type', 50);
                $table->string('widget_type', 50)->nullable();
                $table->unsignedInteger('department_id')->nullable();
                $table->string('page_url', 500)->nullable();
                $table->unsignedInteger('client_id')->nullable();
                $table->string('ip_hash', 64)->nullable();
                $table->text('event_data')->nullable(); // JSON
                $table->timestamp('created_at')->useCurrent();

                $table->index('event_type');
                $table->index('created_at');
                $table->index(['event_type', 'created_at']);
            });
        }

        // Install default settings
        $settingsRepo = new \BusinessHours\Repositories\SettingsRepository();
        $settingsRepo->installDefaults();

        // Seed sample data
        business_hours_seed_sample_data();

        return [
            'status' => 'success',
            'description' => 'Business Hours & Support Availability module has been activated successfully. Navigate to Addons > Business Hours to configure your support schedules.',
        ];
    } catch (\Exception $e) {
        return [
            'status' => 'error',
            'description' => 'Failed to activate module: ' . $e->getMessage(),
        ];
    }
}

/**
 * Seed sample data during activation
 *
 * @return void
 */
function business_hours_seed_sample_data()
{
    $deptRepo = new \BusinessHours\Repositories\DepartmentRepository();
    $schedRepo = new \BusinessHours\Repositories\ScheduleRepository();
    $rtRepo = new \BusinessHours\Repositories\SettingsRepository();

    // Only seed if no departments exist
    if ($deptRepo->getActiveCount() > 0) {
        return;
    }

    // Create sample departments
    $salesDept = new \BusinessHours\Models\Department();
    $salesDept->name = 'Sales';
    $salesDept->slug = 'sales';
    $salesDept->description = 'Pre-sales inquiries and new orders';
    $salesDept->timezone = 'America/New_York';
    $salesDept->is24x7 = false;
    $salesDept->sortOrder = 1;
    $salesDept->status = 'active';
    $salesDept->color = '#3b82f6';
    $salesDept->icon = 'fa-dollar-sign';
    $salesId = $deptRepo->create($salesDept);

    $techDept = new \BusinessHours\Models\Department();
    $techDept->name = 'Technical Support';
    $techDept->slug = 'technical-support';
    $techDept->description = 'Technical assistance and troubleshooting';
    $techDept->timezone = 'America/New_York';
    $techDept->is24x7 = true;
    $techDept->sortOrder = 2;
    $techDept->status = 'active';
    $techDept->color = '#22c55e';
    $techDept->icon = 'fa-headset';
    $techId = $deptRepo->create($techDept);

    $billingDept = new \BusinessHours\Models\Department();
    $billingDept->name = 'Billing';
    $billingDept->slug = 'billing';
    $billingDept->description = 'Billing inquiries and payment issues';
    $billingDept->timezone = 'America/New_York';
    $billingDept->is24x7 = false;
    $billingDept->sortOrder = 3;
    $billingDept->status = 'active';
    $billingDept->color = '#f59e0b';
    $billingDept->icon = 'fa-file-invoice-dollar';
    $billingId = $deptRepo->create($billingDept);

    // Create sales schedule (Mon-Fri 9-18)
    $salesSchedule = new \BusinessHours\Models\Schedule();
    $salesSchedule->departmentId = $salesId;
    $salesSchedule->name = 'Sales Regular Hours';
    $salesSchedule->type = 'regular';
    $salesSchedule->priority = 0;
    $salesSchedule->isActive = true;
    $salesSchedId = $schedRepo->create($salesSchedule);

    // Add Mon-Fri slots for sales
    for ($day = 1; $day <= 5; $day++) {
        $slot = new \BusinessHours\Models\ScheduleSlot();
        $slot->scheduleId = $salesSchedId;
        $slot->dayOfWeek = $day;
        $slot->openTime = '09:00:00';
        $slot->closeTime = '18:00:00';
        $schedRepo->createSlot($slot);
    }

    // Create billing schedule (Mon-Fri 10-17)
    $billingSchedule = new \BusinessHours\Models\Schedule();
    $billingSchedule->departmentId = $billingId;
    $billingSchedule->name = 'Billing Regular Hours';
    $billingSchedule->type = 'regular';
    $billingSchedule->priority = 0;
    $billingSchedule->isActive = true;
    $billingSchedId = $schedRepo->create($billingSchedule);

    for ($day = 1; $day <= 5; $day++) {
        $slot = new \BusinessHours\Models\ScheduleSlot();
        $slot->scheduleId = $billingSchedId;
        $slot->dayOfWeek = $day;
        $slot->openTime = '10:00:00';
        $slot->closeTime = '17:00:00';
        $schedRepo->createSlot($slot);
    }

    // Create sample response times
    $responseTable = \BusinessHours\Bootstrap::TABLE_PREFIX . 'response_times';

    Capsule::table($responseTable)->insert([
        [
            'department_id' => null,
            'context' => 'business_hours',
            'message' => 'Tickets submitted during business hours are typically answered within 30 minutes.',
            'estimated_minutes' => 30,
            'sort_order' => 1,
            'is_active' => 1,
        ],
        [
            'department_id' => null,
            'context' => 'after_hours',
            'message' => 'Tickets submitted outside business hours are typically answered within 8 hours on the next business day.',
            'estimated_minutes' => 480,
            'sort_order' => 2,
            'is_active' => 1,
        ],
        [
            'department_id' => null,
            'context' => 'holiday',
            'message' => 'We are currently observing a holiday. Tickets will be responded to when we return.',
            'estimated_minutes' => null,
            'sort_order' => 3,
            'is_active' => 1,
        ],
        [
            'department_id' => null,
            'context' => 'emergency',
            'message' => 'Emergency tickets receive priority handling and are addressed as quickly as possible.',
            'estimated_minutes' => 15,
            'sort_order' => 4,
            'is_active' => 1,
        ],
    ]);
}

/**
 * Module Deactivation
 *
 * Called when the module is deactivated.
 * Tables are preserved by default for data safety.
 *
 * @return array
 */
function business_hours_deactivate()
{
    try {
        // Intentionally NOT dropping tables to preserve data.
        // Tables can be manually removed if needed.

        return [
            'status' => 'success',
            'description' => 'Business Hours module has been deactivated. Your data has been preserved and will be available if you reactivate the module.',
        ];
    } catch (\Exception $e) {
        return [
            'status' => 'error',
            'description' => 'Error during deactivation: ' . $e->getMessage(),
        ];
    }
}

/**
 * Module Upgrade
 *
 * Called when the version in config is higher than the stored version.
 *
 * @param array $vars Including 'version' - the currently installed version
 * @return void
 */
function business_hours_upgrade($vars)
{
    $currentVersion = $vars['version'];

    // Future upgrade migrations go here
    // Example:
    // if (version_compare($currentVersion, '1.1.0', '<')) {
    //     Capsule::schema()->table('mod_business_hours_departments', function ($table) {
    //         $table->string('new_column')->nullable()->after('icon');
    //     });
    // }
}

/**
 * Module Admin Area Output
 *
 * Renders the admin area interface for the module.
 *
 * @param array $vars Module configuration parameters
 * @return void
 */
function business_hours_output($vars)
{
    $bootstrap = \BusinessHours\Bootstrap::getInstance();
    $moduleDir = $bootstrap->getModuleDir();

    // Load language
    $lang = business_hours_load_language();

    // Determine the current action/page
    $action = isset($_GET['action']) ? trim($_GET['action']) : 'dashboard';
    $subAction = isset($_GET['sub']) ? trim($_GET['sub']) : '';
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    // CSRF token for forms
    $csrfToken = generate_token('link');

    // Common variables for all pages
    $moduleLink = $vars['modulelink'];

    // Route to appropriate controller
    try {
        switch ($action) {
            case 'schedules':
                $controller = new \BusinessHours\Controllers\Admin\ScheduleController();
                $controller->handle($subAction, $id, $moduleLink, $lang);
                break;

            case 'holidays':
                $controller = new \BusinessHours\Controllers\Admin\HolidayController();
                $controller->handle($subAction, $id, $moduleLink, $lang);
                break;

            case 'departments':
                $controller = new \BusinessHours\Controllers\Admin\DepartmentController();
                $controller->handle($subAction, $id, $moduleLink, $lang);
                break;

            case 'settings':
                $controller = new \BusinessHours\Controllers\Admin\SettingsController();
                $controller->handle($subAction, $id, $moduleLink, $lang);
                break;

            case 'analytics':
                $controller = new \BusinessHours\Controllers\Admin\AnalyticsController();
                $controller->handle($subAction, $id, $moduleLink, $lang);
                break;

            case 'ajax':
                // Handle admin AJAX requests
                header('Content-Type: application/json');
                $controller = new \BusinessHours\Controllers\Admin\ScheduleController();
                $controller->handleAjax($subAction, $moduleLink, $lang);
                exit;

            case 'dashboard':
            default:
                business_hours_render_dashboard($moduleLink, $lang);
                break;
        }
    } catch (\Exception $e) {
        echo '<div class="alert alert-danger">';
        echo '<strong>Error:</strong> ' . htmlspecialchars($e->getMessage());
        echo '</div>';
    }
}

/**
 * Render the admin dashboard page
 *
 * @param string $moduleLink
 * @param array $lang
 * @return void
 */
function business_hours_render_dashboard($moduleLink, $lang)
{
    $bootstrap = \BusinessHours\Bootstrap::getInstance();
    $deptRepo = new \BusinessHours\Repositories\DepartmentRepository();
    $holidayRepo = new \BusinessHours\Repositories\HolidayRepository();
    $settingsRepo = new \BusinessHours\Repositories\SettingsRepository();

    $departments = $deptRepo->getAll(true);
    $upcomingHolidays = $holidayRepo->getUpcoming(5);
    $settings = $settingsRepo->getAll();

    // Build status data for each department
    $availabilityService = new \BusinessHours\Services\AvailabilityService();
    $departmentStatuses = [];
    foreach ($departments as $dept) {
        $departmentStatuses[] = [
            'department' => $dept,
            'status' => $availabilityService->getCurrentStatus($dept->id),
        ];
    }

    // Include admin CSS
    $assetUrl = $bootstrap->getAssetUrl('css/admin.css');

    // Render the dashboard
    echo '<link rel="stylesheet" href="' . htmlspecialchars($assetUrl) . '">';
    echo '<div class="bh-admin">';

    // Header
    echo '<div class="bh-admin-header">';
    echo '<h1><i class="fas fa-clock"></i> ' . htmlspecialchars($lang['module_title'] ?? 'Business Hours & Support Availability') . '</h1>';
    echo '<p class="bh-admin-subtitle">' . htmlspecialchars($lang['dashboard_subtitle'] ?? 'Manage your support schedules, holidays, and availability settings.') . '</p>';
    echo '</div>';

    // Navigation tabs
    echo '<ul class="nav nav-tabs bh-admin-nav" role="tablist">';
    echo '<li class="active"><a href="' . $moduleLink . '"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>';
    echo '<li><a href="' . $moduleLink . '&action=departments"><i class="fas fa-building"></i> Departments</a></li>';
    echo '<li><a href="' . $moduleLink . '&action=schedules"><i class="fas fa-calendar-alt"></i> Schedules</a></li>';
    echo '<li><a href="' . $moduleLink . '&action=holidays"><i class="fas fa-umbrella-beach"></i> Holidays</a></li>';
    echo '<li><a href="' . $moduleLink . '&action=analytics"><i class="fas fa-chart-bar"></i> Analytics</a></li>';
    echo '<li><a href="' . $moduleLink . '&action=settings"><i class="fas fa-cog"></i> Settings</a></li>';
    echo '</ul>';

    // Stats cards row
    echo '<div class="row bh-stats-row">';

    // Active Departments card
    echo '<div class="col-md-3">';
    echo '<div class="bh-stat-card bh-stat-departments">';
    echo '<div class="bh-stat-icon"><i class="fas fa-building"></i></div>';
    echo '<div class="bh-stat-content">';
    echo '<div class="bh-stat-value">' . count($departments) . '</div>';
    echo '<div class="bh-stat-label">Active Departments</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    // Currently Open card
    $openCount = 0;
    foreach ($departmentStatuses as $ds) {
        if ($ds['status']['is_open']) {
            $openCount++;
        }
    }
    echo '<div class="col-md-3">';
    echo '<div class="bh-stat-card bh-stat-open">';
    echo '<div class="bh-stat-icon"><i class="fas fa-door-open"></i></div>';
    echo '<div class="bh-stat-content">';
    echo '<div class="bh-stat-value">' . $openCount . '/' . count($departments) . '</div>';
    echo '<div class="bh-stat-label">Currently Open</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    // Upcoming Holidays card
    echo '<div class="col-md-3">';
    echo '<div class="bh-stat-card bh-stat-holidays">';
    echo '<div class="bh-stat-icon"><i class="fas fa-umbrella-beach"></i></div>';
    echo '<div class="bh-stat-content">';
    echo '<div class="bh-stat-value">' . count($upcomingHolidays) . '</div>';
    echo '<div class="bh-stat-label">Upcoming Holidays</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    // Server Time card
    $companyTz = $settings['company_timezone'] ?? 'America/New_York';
    $now = new \DateTime('now', new \DateTimeZone($companyTz));
    echo '<div class="col-md-3">';
    echo '<div class="bh-stat-card bh-stat-time">';
    echo '<div class="bh-stat-icon"><i class="fas fa-clock"></i></div>';
    echo '<div class="bh-stat-content">';
    echo '<div class="bh-stat-value">' . $now->format('g:i A') . '</div>';
    echo '<div class="bh-stat-label">' . htmlspecialchars($companyTz) . '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    echo '</div>'; // .row

    // Department Status cards
    echo '<div class="row">';
    echo '<div class="col-md-8">';
    echo '<div class="panel panel-default bh-panel">';
    echo '<div class="panel-heading"><h3 class="panel-title"><i class="fas fa-signal"></i> Department Status</h3></div>';
    echo '<div class="panel-body">';

    if (empty($departmentStatuses)) {
        echo '<div class="alert alert-info">No departments configured. <a href="' . $moduleLink . '&action=departments&sub=add">Add your first department</a>.</div>';
    } else {
        echo '<div class="table-responsive"><table class="table table-striped bh-status-table">';
        echo '<thead><tr><th>Department</th><th>Status</th><th>Hours Today</th><th>Next Change</th><th>Actions</th></tr></thead>';
        echo '<tbody>';

        foreach ($departmentStatuses as $ds) {
            $dept = $ds['department'];
            $status = $ds['status'];

            $statusClass = $status['is_open'] ? 'bh-badge-online' : 'bh-badge-offline';
            $statusLabel = $status['label'];

            echo '<tr>';
            echo '<td>';
            if ($dept->color) {
                echo '<span class="bh-dept-color" style="background:' . htmlspecialchars($dept->color) . '"></span> ';
            }
            if ($dept->icon) {
                echo '<i class="fas ' . htmlspecialchars($dept->icon) . '"></i> ';
            }
            echo htmlspecialchars($dept->name);
            echo '</td>';
            echo '<td><span class="bh-badge ' . $statusClass . '">' . htmlspecialchars($statusLabel) . '</span></td>';
            echo '<td>' . htmlspecialchars($status['today_hours'] ?? 'N/A') . '</td>';
            echo '<td>' . htmlspecialchars($status['next_change'] ?? 'N/A') . '</td>';
            echo '<td>';
            echo '<a href="' . $moduleLink . '&action=schedules&sub=list&dept=' . $dept->id . '" class="btn btn-xs btn-default" title="View Schedules"><i class="fas fa-calendar"></i></a> ';
            echo '<a href="' . $moduleLink . '&action=departments&sub=edit&id=' . $dept->id . '" class="btn btn-xs btn-default" title="Edit"><i class="fas fa-edit"></i></a>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table></div>';
    }

    echo '</div></div></div>';

    // Upcoming Holidays sidebar
    echo '<div class="col-md-4">';
    echo '<div class="panel panel-default bh-panel">';
    echo '<div class="panel-heading"><h3 class="panel-title"><i class="fas fa-calendar-check"></i> Upcoming Holidays</h3></div>';
    echo '<div class="panel-body">';

    if (empty($upcomingHolidays)) {
        echo '<p class="text-muted">No upcoming holidays configured.</p>';
    } else {
        echo '<ul class="bh-holiday-list">';
        foreach ($upcomingHolidays as $holiday) {
            $icon = $holiday->type === 'emergency' ? 'fa-exclamation-triangle' : 'fa-calendar-day';
            echo '<li>';
            echo '<i class="fas ' . $icon . '"></i> ';
            echo '<strong>' . htmlspecialchars($holiday->name) . '</strong><br>';
            echo '<small class="text-muted">';
            echo htmlspecialchars($holiday->startDate);
            if ($holiday->isMultiDay()) {
                echo ' &mdash; ' . htmlspecialchars($holiday->endDate);
            }
            echo '</small>';
            echo '</li>';
        }
        echo '</ul>';
    }

    echo '<a href="' . $moduleLink . '&action=holidays" class="btn btn-sm btn-default btn-block"><i class="fas fa-plus"></i> Manage Holidays</a>';
    echo '</div></div>';

    // Quick Actions panel
    echo '<div class="panel panel-default bh-panel">';
    echo '<div class="panel-heading"><h3 class="panel-title"><i class="fas fa-bolt"></i> Quick Actions</h3></div>';
    echo '<div class="panel-body bh-quick-actions">';
    echo '<a href="' . $moduleLink . '&action=departments&sub=add" class="btn btn-sm btn-primary btn-block"><i class="fas fa-plus"></i> Add Department</a>';
    echo '<a href="' . $moduleLink . '&action=schedules&sub=add" class="btn btn-sm btn-success btn-block"><i class="fas fa-plus"></i> Add Schedule</a>';
    echo '<a href="' . $moduleLink . '&action=holidays&sub=add" class="btn btn-sm btn-info btn-block"><i class="fas fa-plus"></i> Add Holiday</a>';
    echo '<a href="' . $moduleLink . '&action=settings" class="btn btn-sm btn-default btn-block"><i class="fas fa-cog"></i> Settings</a>';
    echo '</div></div>';

    echo '</div>'; // .col-md-4
    echo '</div>'; // .row
    echo '</div>'; // .bh-admin

    // Include admin JS
    $jsUrl = $bootstrap->getAssetUrl('js/admin.js');
    echo '<script src="' . htmlspecialchars($jsUrl) . '"></script>';
}

/**
 * Client Area Output
 *
 * Defines the client area page for the module.
 *
 * @param array $vars
 * @return array
 */
function business_hours_clientarea($vars)
{
    $bootstrap = \BusinessHours\Bootstrap::getInstance();
    $action = isset($_GET['action']) ? trim($_GET['action']) : 'view';

    // Handle AJAX requests
    if ($action === 'status') {
        $controller = new \BusinessHours\Controllers\Client\AjaxController();
        $controller->handleStatus();
        exit;
    }

    if ($action === 'schedule') {
        $controller = new \BusinessHours\Controllers\Client\AjaxController();
        $controller->handleSchedule();
        exit;
    }

    if ($action === 'track') {
        $controller = new \BusinessHours\Controllers\Client\AjaxController();
        $controller->handleTrack();
        exit;
    }

    // Full page view
    $widgetService = new \BusinessHours\Services\WidgetService();
    $pageData = $widgetService->getWidgetData('full-schedule', []);

    return [
        'pagetitle' => 'Support Hours',
        'breadcrumb' => ['index.php?m=business_hours' => 'Support Hours'],
        'templatefile' => 'templates/client/page',
        'vars' => $pageData,
    ];
}

/**
 * Load the appropriate language file
 *
 * @return array
 */
function business_hours_load_language()
{
    $moduleDir = dirname(__FILE__);
    $language = isset($_SESSION['Language']) ? $_SESSION['Language'] : 'english';
    $langFile = $moduleDir . '/lang/' . $language . '.php';

    if (!file_exists($langFile)) {
        $langFile = $moduleDir . '/lang/english.php';
    }

    if (file_exists($langFile)) {
        $_LANG = [];
        require $langFile;
        return $_LANG;
    }

    return [];
}
