<?php
/**
 * View Helper
 *
 * Assists with rendering Smarty templates and building HTML.
 *
 * @package    BusinessHours\Helpers
 */

namespace BusinessHours\Helpers;

use BusinessHours\Bootstrap;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class ViewHelper
{
    /**
     * Render a Smarty template and return the output
     *
     * @param string $template Relative template path
     * @param array $vars Template variables
     * @return string Rendered HTML
     */
    public static function render($template, array $vars = [])
    {
        $bootstrap = Bootstrap::getInstance();
        $templatePath = $bootstrap->getTemplatePath($template);

        if (!file_exists($templatePath)) {
            return '<!-- Template not found: ' . SecurityHelper::escape($template) . ' -->';
        }

        // Use output buffering with Smarty
        try {
            global $smarty;
            if ($smarty && method_exists($smarty, 'fetch')) {
                foreach ($vars as $key => $value) {
                    $smarty->assign($key, $value);
                }
                return $smarty->fetch($templatePath);
            }
        } catch (\Exception $e) {
            // Smarty not available, fall through
        }

        // Fallback: basic PHP template rendering
        extract($vars, EXTR_SKIP);
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }

    /**
     * Render an admin template directly to output
     *
     * @param string $template Relative template path within templates/admin/
     * @param array $vars
     * @return void
     */
    public static function renderAdmin($template, array $vars = [])
    {
        echo self::render('admin/' . $template, $vars);
    }

    /**
     * Render a client widget template
     *
     * @param string $widget Widget template name (without path prefix)
     * @param array $vars
     * @return string
     */
    public static function renderWidget($widget, array $vars = [])
    {
        return self::render('client/widgets/' . $widget . '.tpl', $vars);
    }

    /**
     * Build a status badge HTML
     *
     * @param string $label
     * @param string $color Hex color
     * @param bool $isOpen
     * @return string
     */
    public static function statusBadge($label, $color = null, $isOpen = false)
    {
        $class = $isOpen ? 'bh-badge-online' : 'bh-badge-offline';
        $style = $color ? ' style="background-color:' . SecurityHelper::escape($color) . '"' : '';

        return '<span class="bh-badge ' . $class . '"' . $style . '>'
            . '<span class="bh-status-dot"></span> '
            . SecurityHelper::escape($label)
            . '</span>';
    }

    /**
     * Build navigation tabs HTML for admin area
     *
     * @param string $moduleLink
     * @param string $activeTab
     * @return string
     */
    public static function adminNav($moduleLink, $activeTab = 'dashboard')
    {
        $tabs = [
            'dashboard'   => ['icon' => 'fa-tachometer-alt', 'label' => 'Dashboard', 'action' => ''],
            'departments' => ['icon' => 'fa-building', 'label' => 'Departments', 'action' => 'departments'],
            'schedules'   => ['icon' => 'fa-calendar-alt', 'label' => 'Schedules', 'action' => 'schedules'],
            'holidays'    => ['icon' => 'fa-umbrella-beach', 'label' => 'Holidays', 'action' => 'holidays'],
            'analytics'   => ['icon' => 'fa-chart-bar', 'label' => 'Analytics', 'action' => 'analytics'],
            'settings'    => ['icon' => 'fa-cog', 'label' => 'Settings', 'action' => 'settings'],
        ];

        $html = '<ul class="nav nav-tabs bh-admin-nav" role="tablist">';

        foreach ($tabs as $key => $tab) {
            $active = ($activeTab === $key) ? ' class="active"' : '';
            $href = $tab['action'] ? $moduleLink . '&action=' . $tab['action'] : $moduleLink;
            $html .= '<li' . $active . '><a href="' . $href . '"><i class="fas ' . $tab['icon'] . '"></i> ' . $tab['label'] . '</a></li>';
        }

        $html .= '</ul>';
        return $html;
    }

    /**
     * Build a success alert
     *
     * @param string $message
     * @return string
     */
    public static function alertSuccess($message)
    {
        return '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' . SecurityHelper::escape($message) . '</div>';
    }

    /**
     * Build an error alert
     *
     * @param string $message
     * @return string
     */
    public static function alertError($message)
    {
        return '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' . SecurityHelper::escape($message) . '</div>';
    }

    /**
     * Build an info alert
     *
     * @param string $message
     * @return string
     */
    public static function alertInfo($message)
    {
        return '<div class="alert alert-info"><i class="fas fa-info-circle"></i> ' . SecurityHelper::escape($message) . '</div>';
    }
}
