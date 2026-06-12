<?php
/**
 * Business Hours & Support Availability - WHMCS Hook Registrations
 *
 * Loaded automatically by WHMCS when the module is active.
 *
 * @package    BusinessHours
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

// Initialize module autoloader
require_once __DIR__ . '/lib/Bootstrap.php';
\BusinessHours\Bootstrap::getInstance()->init();

use BusinessHours\Bootstrap;
use BusinessHours\Services\WidgetService;
use BusinessHours\Services\ShortcodeService;
use BusinessHours\Services\AvailabilityService;
use BusinessHours\Repositories\SettingsRepository;

/**
 * Inject CSS into client area <head>
 */
add_hook('ClientAreaHeadOutput', 1, function ($vars) {
    $bootstrap = Bootstrap::getInstance();
    $settingsRepo = new SettingsRepository();

    $css = '<link rel="stylesheet" href="' . $bootstrap->getAssetUrl('css/widgets.css') . '">' . "\n";
    $css .= '<link rel="stylesheet" href="' . $bootstrap->getAssetUrl('css/widgets-dark.css') . '">' . "\n";

    // Load Lagom compatibility CSS if Lagom theme is detected
    if ($bootstrap->isLagomTheme()) {
        $css .= '<link rel="stylesheet" href="' . $bootstrap->getAssetUrl('css/lagom-compat.css') . '">' . "\n";
    }

    // Inject custom CSS if configured
    $customCss = $settingsRepo->get('custom_css', '');
    if (!empty($customCss)) {
        $css .= '<style>' . $customCss . '</style>' . "\n";
    }

    // Inject color custom properties from settings
    $colors = [
        'color_online'    => '--bh-color-online',
        'color_offline'   => '--bh-color-offline',
        'color_limited'   => '--bh-color-limited',
        'color_holiday'   => '--bh-color-holiday',
        'color_emergency' => '--bh-color-emergency',
        'color_accent'    => '--bh-color-accent',
    ];

    $cssVars = [];
    foreach ($colors as $settingKey => $cssVar) {
        $val = $settingsRepo->get($settingKey);
        if ($val) {
            $cssVars[] = $cssVar . ':' . htmlspecialchars($val);
        }
    }

    if (!empty($cssVars)) {
        $css .= '<style>.bh-widget{' . implode(';', $cssVars) . '}</style>' . "\n";
    }

    return $css;
});

/**
 * Inject JavaScript into client area footer
 */
add_hook('ClientAreaFooterOutput', 1, function ($vars) {
    $bootstrap = Bootstrap::getInstance();
    $settingsRepo = new SettingsRepository();

    $js = '<script src="' . $bootstrap->getAssetUrl('js/widgets.js') . '"></script>' . "\n";

    // Add AJAX live updates if enabled
    $ajaxEnabled = $settingsRepo->get('ajax_enabled', '1');
    if ($ajaxEnabled === '1') {
        $interval = $settingsRepo->get('ajax_interval', '60');
        $js .= '<div id="bh-live-config" data-interval="' . (int) $interval . '" data-endpoint="index.php?m=business_hours&action=status" style="display:none"></div>' . "\n";
        $js .= '<script src="' . $bootstrap->getAssetUrl('js/live-update.js') . '"></script>' . "\n";
    }

    return $js;
});

/**
 * Show holiday announcement banner on client area pages
 */
add_hook('ClientAreaPage', 1, function ($vars) {
    try {
        $settingsRepo = new SettingsRepository();

        $availService = new AvailabilityService();
        $status = $availService->getCurrentStatus();

        return [
            'bh_status'       => $status,
            'bh_is_open'      => $status['is_open'],
            'bh_status_label' => $status['label'],
            'bh_today_hours'  => $status['today_hours'] ?? 'N/A',
        ];
    } catch (\Exception $e) {
        return [];
    }
});

/**
 * Show widget on support ticket submission page and inject department status
 */
add_hook('ClientAreaPageSubmitTicket', 1, function ($vars) {
    try {
        $settingsRepo = new SettingsRepository();
        $showOnTickets = $settingsRepo->get('show_on_tickets', '1') === '1';

        $availService = new AvailabilityService();
        $status = $availService->getCurrentStatus();

        $returnVars = [
            'bh_show_widget'  => $showOnTickets,
            'bh_status'       => $status,
            'bh_is_open'      => $status['is_open'],
            'bh_status_label' => $status['label'],
        ];

        // Native injection into the $departments array using standard Bootstrap classes
        if (isset($vars['departments']) && is_array($vars['departments'])) {
            $myDepts = \Illuminate\Database\Capsule\Manager::table(Bootstrap::TABLE_PREFIX . 'departments')
                ->whereNotNull('whmcs_dept_id')
                ->where('status', 'active')
                ->get(['id', 'whmcs_dept_id', 'timezone']);

            $deptMap = [];
            foreach ($myDepts as $md) {
                $deptMap[$md->whmcs_dept_id] = [
                    'id'       => $md->id,
                    'timezone' => $md->timezone
                ];
            }

            foreach ($vars['departments'] as &$dept) {
                if (isset($deptMap[$dept['id']])) {
                    $myDeptId = $deptMap[$dept['id']]['id'];
                    $tz       = $deptMap[$dept['id']]['timezone'];

                    $deptStatus = $availService->getCurrentStatus($myDeptId);
                    $isOpen = $deptStatus['is_open'] ?? false;
                    $label  = $deptStatus['label'] ?? 'Unknown';
                    $hours  = $deptStatus['today_hours'] ?? '';

                    $badgeClass = $isOpen ? 'label label-success' : 'label label-danger';
                    if (isset($deptStatus['source']) && $deptStatus['source'] === 'holiday') {
                        $badgeClass = 'label label-info';
                    }

                    $html = '<div style="margin-top: 10px;">';
                    $html .= '<span class="' . $badgeClass . '">' . htmlspecialchars($label) . '</span>';
                    
                    if ($hours) {
                        $html .= ' <span class="text-muted small" style="margin-left: 8px;">' . htmlspecialchars($hours) . ' (' . htmlspecialchars($tz) . ')</span>';
                    }
                    $html .= '</div>';

                    $dept['description'] .= $html;
                }
            }
            $returnVars['departments'] = $vars['departments'];
        }

        return $returnVars;
    } catch (\Exception $e) {
        return [];
    }
});

/**
 * Recursive function to inject status into menu items
 */
function bh_process_menu_item(\WHMCS\View\Menu\Item $item, $availService, $deptMap) {
    $uri = $item->getUri();
    if (strpos($uri, 'submitticket.php?step=2&deptid=') !== false) {
        if (preg_match('/deptid=(\d+)/', $uri, $matches)) {
            $whmcsDeptId = (int)$matches[1];
            if (isset($deptMap[$whmcsDeptId])) {
                $myDeptId = $deptMap[$whmcsDeptId]['id'];
                $tz = $deptMap[$whmcsDeptId]['timezone'];
                
                $deptStatus = $availService->getCurrentStatus($myDeptId);
                $isOpen = $deptStatus['is_open'] ?? false;
                $label = $deptStatus['label'] ?? 'Unknown';
                $hours = $deptStatus['today_hours'] ?? '';
                
                $badgeClass = $isOpen ? 'label label-success' : 'label label-danger';
                if (isset($deptStatus['source']) && $deptStatus['source'] === 'holiday') {
                    $badgeClass = 'label label-info';
                }
                
                $htmlLabel = $item->getLabel();
                $htmlLabel .= '<div style="margin-top: 4px; line-height: 1.4;">';
                $htmlLabel .= '<span class="' . $badgeClass . '" style="font-size: 10px; padding: 2px 6px;">' . htmlspecialchars($label) . '</span>';
                if ($hours) {
                    $htmlLabel .= '<br><span class="text-muted small" style="font-size: 11px;">' . htmlspecialchars($hours) . ' (' . htmlspecialchars($tz) . ')</span>';
                }
                $htmlLabel .= '</div>';
                
                $item->setLabel($htmlLabel);
                $item->setExtras(array_merge($item->getExtras(), ['html' => true]));
            }
        }
    }
    
    foreach ($item->getChildren() as $child) {
        bh_process_menu_item($child, $availService, $deptMap);
    }
}

/**
 * Hook into ClientAreaPrimaryNavbar to add Support Hours dropdown and modify existing links
 */
add_hook('ClientAreaPrimaryNavbar', 1, function (\WHMCS\View\Menu\Item $navbar) {
    try {
        $availService = new AvailabilityService();
        
        // 1. Add the main Support Hours dropdown to the header
        $overallStatus = $availService->getCurrentStatus();
        $isOpen = $overallStatus['is_open'] ?? false;
        $label  = $overallStatus['label'] ?? 'Unknown';
        
        $badgeClass = $isOpen ? 'label label-success' : 'label label-danger';
        if (isset($overallStatus['source']) && $overallStatus['source'] === 'holiday') {
            $badgeClass = 'label label-info';
        }
        
        $supportNav = $navbar->addChild('SupportHoursHeader', [
            'label' => 'Support: <span class="' . $badgeClass . '">' . htmlspecialchars($label) . '</span>',
            'uri' => '#',
            'order' => 99,
        ]);
        $supportNav->setExtras(['html' => true]);

        // Fetch all active departments to build the dropdown children
        $myDepts = \Illuminate\Database\Capsule\Manager::table(Bootstrap::TABLE_PREFIX . 'departments')
            ->whereNotNull('whmcs_dept_id')
            ->where('status', 'active')
            ->get(['id', 'whmcs_dept_id', 'timezone']);

        $deptMap = [];
        foreach ($myDepts as $md) {
            $deptMap[$md->whmcs_dept_id] = [
                'id' => $md->id,
                'timezone' => $md->timezone
            ];
            
            // Fetch status for this specific department
            $deptStatus = $availService->getCurrentStatus($md->id);
            $dIsOpen = $deptStatus['is_open'] ?? false;
            $dLabel  = $deptStatus['label'] ?? 'Unknown';
            $dHours  = $deptStatus['today_hours'] ?? '';
            
            $dBadgeClass = $dIsOpen ? 'label label-success' : 'label label-danger';
            if (isset($deptStatus['source']) && $deptStatus['source'] === 'holiday') {
                $dBadgeClass = 'label label-info';
            }
            
            // Get WHMCS department name natively
            $whmcsDept = \Illuminate\Database\Capsule\Manager::table('tblticketdepartments')
                ->where('id', $md->whmcs_dept_id)
                ->first(['name']);
            $deptName = $whmcsDept ? $whmcsDept->name : 'Department';

            $childHtml = '<div style="line-height: 1.4; min-width: 250px; white-space: normal; padding: 5px 0;">';
            $childHtml .= '<strong>' . htmlspecialchars($deptName) . '</strong> <span class="' . $dBadgeClass . '" style="float: right; font-size: 10px; padding: 2px 6px;">' . htmlspecialchars($dLabel) . '</span>';
            if ($dHours) {
                $childHtml .= '<br><span class="text-muted small" style="font-size: 11px;">' . htmlspecialchars($dHours) . ' (' . htmlspecialchars($md->timezone) . ')</span>';
            }
            $childHtml .= '</div>';
            
            $supportNav->addChild('dept_' . $md->id, [
                'label' => $childHtml,
                'uri' => 'submitticket.php?step=2&deptid=' . $md->whmcs_dept_id,
            ])->setExtras(['html' => true]);
        }
        
        // 2. Modify any existing links in the navbar recursively
        foreach ($navbar->getChildren() as $child) {
            if ($child->getName() !== 'SupportHoursHeader') {
                bh_process_menu_item($child, $availService, $deptMap);
            }
        }
    } catch (\Exception $e) {}
});

add_hook('ClientAreaSecondaryNavbar', 1, function (\WHMCS\View\Menu\Item $navbar) {
    try {
        $availService = new AvailabilityService();
        $deptMap = [];
        $myDepts = \Illuminate\Database\Capsule\Manager::table(Bootstrap::TABLE_PREFIX . 'departments')
            ->whereNotNull('whmcs_dept_id')
            ->where('status', 'active')
            ->get(['id', 'whmcs_dept_id', 'timezone']);
        foreach ($myDepts as $md) {
            $deptMap[$md->whmcs_dept_id] = [
                'id' => $md->id,
                'timezone' => $md->timezone
            ];
        }
        foreach ($navbar->getChildren() as $child) {
            bh_process_menu_item($child, $availService, $deptMap);
        }
    } catch (\Exception $e) {}
});

/**
 * Show widget on support ticket view page
 */
add_hook('ClientAreaPageViewTicket', 1, function ($vars) {
    try {
        $settingsRepo = new SettingsRepository();
        if ($settingsRepo->get('show_on_tickets', '1') !== '1') {
            return [];
        }

        $availService = new AvailabilityService();
        return ['bh_status' => $availService->getCurrentStatus()];
    } catch (\Exception $e) {
        return [];
    }
});

/**
 * Show widget on contact page
 */
add_hook('ClientAreaPageContact', 1, function ($vars) {
    try {
        $settingsRepo = new SettingsRepository();
        if ($settingsRepo->get('show_on_contact', '1') !== '1') {
            return [];
        }

        $availService = new AvailabilityService();
        return ['bh_status' => $availService->getCurrentStatus()];
    } catch (\Exception $e) {
        return [];
    }
});

/**
 * Inject admin CSS into admin area
 */
add_hook('AdminAreaHeadOutput', 1, function ($vars) {
    // Only load on the business hours addon page
    if (isset($_GET['module']) && $_GET['module'] === 'business_hours') {
        $bootstrap = Bootstrap::getInstance();
        return '<link rel="stylesheet" href="' . $bootstrap->getAssetUrl('css/admin.css') . '">';
    }
    return '';
});

/**
 * Build sidebar widget HTML
 *
 * @param array $data Widget data from WidgetService
 * @return string HTML
 */
function buildSidebarWidget($data)
{
    $status = $data['status'] ?? [];
    $isOpen = $status['is_open'] ?? false;
    $label  = $status['label'] ?? 'Unknown';
    $todayHours = $status['today_hours'] ?? 'N/A';
    $nextChange = $status['next_change'] ?? '';

    $badgeClass = $isOpen ? 'label label-success' : 'label label-danger';

    if (isset($status['source']) && $status['source'] === 'holiday') {
        $badgeClass = 'label label-info';
    }

    $html = '<div class="bh-widget" data-bh-widget="sidebar">';

    // Status
    $html .= '<div class="bh-sidebar__row" style="display:flex; justify-content:space-between; margin-bottom:10px;">';
    $html .= '<span class="bh-sidebar__label">Status</span>';
    $html .= '<span class="' . $badgeClass . '" data-bh-status="all">';
    $html .= '<span class="bh-status-label">' . htmlspecialchars($label) . '</span>';
    $html .= '</span>';
    $html .= '</div>';

    // Today's Hours
    $html .= '<div class="bh-sidebar__row" style="display:flex; justify-content:space-between; margin-bottom:10px;">';
    $html .= '<span class="bh-sidebar__label">Today</span>';
    $html .= '<span class="bh-sidebar__value" data-bh-hours="all">' . htmlspecialchars($todayHours) . '</span>';
    $html .= '</div>';

    // Next Change
    if ($nextChange) {
        $html .= '<div class="bh-sidebar__row">';
        $html .= '<span class="bh-sidebar__label">Next</span>';
        $html .= '<span class="bh-sidebar__value" data-bh-next="all">' . htmlspecialchars($nextChange) . '</span>';
        $html .= '</div>';
    }

    // Current Time & Timezone
    if (!empty($data['current_time']) && !empty($data['show_timezone'])) {
        $html .= '<div class="bh-timezone">';
        $html .= htmlspecialchars($data['current_time']) . ' ' . htmlspecialchars($data['company_timezone'] ?? '');
        $html .= '</div>';
    }

    // Response Time
    if (!empty($data['show_response_times']) && isset($data['response_time']['message'])) {
        $html .= '<div class="bh-response-time">';
        $html .= htmlspecialchars($data['response_time']['message']);
        $html .= '</div>';
    }

    // Upcoming Holidays
    if (!empty($data['show_holidays']) && !empty($data['upcoming_holidays'])) {
        $html .= '<div class="bh-holidays">';
        $html .= '<div class="bh-holidays__title">Upcoming Holidays</div>';
        foreach ($data['upcoming_holidays'] as $h) {
            $html .= '<div class="bh-holiday-item">';
            $html .= '<span class="bh-holiday-item__icon"><i class="fas fa-calendar-day"></i></span>';
            $html .= '<span class="bh-holiday-item__name">' . htmlspecialchars($h['name']) . '</span>';
            $html .= '<span class="bh-holiday-item__date">' . htmlspecialchars($h['start_date']) . '</span>';
            $html .= '</div>';
        }
        $html .= '</div>';
    }

    // View Full Schedule link
    $html .= '<a href="index.php?m=business_hours" class="bh-view-full">View Full Schedule &rarr;</a>';

    $html .= '</div>';

    return $html;
}
