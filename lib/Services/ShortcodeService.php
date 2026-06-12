<?php
/**
 * Shortcode Service
 *
 * Parses {supporthours} shortcodes and renders the appropriate widget.
 *
 * @package    BusinessHours\Services
 */

namespace BusinessHours\Services;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class ShortcodeService
{
    /** @var WidgetService */
    private $widgetService;

    public function __construct()
    {
        $this->widgetService = new WidgetService();
    }

    /**
     * Process all shortcodes in a content string
     *
     * @param string $content
     * @return string Content with shortcodes replaced by rendered HTML
     */
    public function process($content)
    {
        // Match {supporthours} and {supporthours key="value" key2="value2"}
        $pattern = '/\{supporthours(?:\s+([^}]*))?\}/i';

        return preg_replace_callback($pattern, function ($matches) {
            $attributes = [];

            if (isset($matches[1]) && !empty(trim($matches[1]))) {
                $attributes = $this->parseAttributes($matches[1]);
            }

            return $this->render($attributes);
        }, $content);
    }

    /**
     * Render a shortcode with given attributes
     *
     * @param array $attributes
     * @return string HTML
     */
    public function render(array $attributes = [])
    {
        $type = isset($attributes['type']) ? $attributes['type'] : null;
        $department = isset($attributes['department']) ? $attributes['department'] : null;
        $compact = isset($attributes['compact']) && ($attributes['compact'] === 'true' || $attributes['compact'] === '1');
        $showCountdown = isset($attributes['show_countdown']) ? $attributes['show_countdown'] !== 'false' : true;
        $showHolidays = isset($attributes['show_holidays']) ? $attributes['show_holidays'] !== 'false' : true;

        // Determine widget type
        if ($compact) {
            $widgetType = 'compact';
        } elseif ($type) {
            $widgetType = $type;
        } elseif ($department) {
            $widgetType = 'sidebar';
        } else {
            $widgetType = 'department-cards';
        }

        $options = [];
        if ($department) {
            $options['department'] = $department;
        }
        $options['show_countdown'] = $showCountdown;
        $options['show_holidays'] = $showHolidays;

        try {
            return $this->widgetService->renderWidget($widgetType, $options);
        } catch (\Exception $e) {
            return '<!-- Business Hours widget error: ' . htmlspecialchars($e->getMessage()) . ' -->';
        }
    }

    /**
     * Parse shortcode attributes from a string
     *
     * @param string $attrString e.g. 'department="sales" compact="true"'
     * @return array
     */
    private function parseAttributes($attrString)
    {
        $attributes = [];
        // Match key="value" or key='value' patterns
        $pattern = '/(\w+)\s*=\s*["\']([^"\']*?)["\']/';

        if (preg_match_all($pattern, $attrString, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $attributes[strtolower($match[1])] = $match[2];
            }
        }

        return $attributes;
    }
}
