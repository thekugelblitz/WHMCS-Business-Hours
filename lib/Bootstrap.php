<?php
/**
 * Business Hours & Support Availability Module
 * Bootstrap & Autoloader
 *
 * @package    BusinessHours
 * @author     WHMCS Custom Code
 * @copyright  2024
 * @version    1.0.0
 */

namespace BusinessHours;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class Bootstrap
{
    /**
     * @var Bootstrap|null Singleton instance
     */
    private static $instance = null;

    /**
     * @var string Module root directory
     */
    private $moduleDir;

    /**
     * @var bool Whether the autoloader has been registered
     */
    private $autoloaderRegistered = false;

    /**
     * @var array Cached settings
     */
    private $settings = [];

    /**
     * @var bool Whether settings have been loaded
     */
    private $settingsLoaded = false;

    /**
     * Module version constant
     */
    const VERSION = '1.0.0';

    /**
     * Module name constant
     */
    const MODULE_NAME = 'business_hours';

    /**
     * Database table prefix
     */
    const TABLE_PREFIX = 'mod_business_hours_';

    /**
     * Private constructor for singleton pattern
     */
    private function __construct()
    {
        $this->moduleDir = dirname(__DIR__);
    }

    /**
     * Get singleton instance
     *
     * @return Bootstrap
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the module
     *
     * @return void
     */
    public function init()
    {
        $this->registerAutoloader();
    }

    /**
     * Register the PSR-4 autoloader for the BusinessHours namespace
     *
     * @return void
     */
    public function registerAutoloader()
    {
        if ($this->autoloaderRegistered) {
            return;
        }

        spl_autoload_register(function ($class) {
            $prefix = 'BusinessHours\\';
            $baseDir = $this->moduleDir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR;

            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }

            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

            if (file_exists($file)) {
                require_once $file;
            }
        });

        $this->autoloaderRegistered = true;
    }

    /**
     * Get the module root directory
     *
     * @return string
     */
    public function getModuleDir()
    {
        return $this->moduleDir;
    }

    /**
     * Get path to a template file
     *
     * @param string $template Relative template path
     * @return string
     */
    public function getTemplatePath($template)
    {
        return $this->moduleDir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $template;
    }

    /**
     * Get path to an asset file
     *
     * @param string $asset Relative asset path
     * @return string
     */
    public function getAssetPath($asset)
    {
        return $this->moduleDir . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . $asset;
    }

    /**
     * Get the web-accessible URL for an asset
     *
     * @param string $asset Relative asset path
     * @return string
     */
    public function getAssetUrl($asset)
    {
        $systemUrl = '';
        if (class_exists('\WHMCS\Utility\Environment\WebHelper')) {
            $systemUrl = \WHMCS\Utility\Environment\WebHelper::getBaseUrl();
        } else {
            $systemUrl = isset($GLOBALS['CONFIG']['SystemURL']) ? $GLOBALS['CONFIG']['SystemURL'] : '';
        }
        
        $systemUrl = rtrim($systemUrl, '/');
        
        // Fallback if SystemURL is empty or relative
        if (empty($systemUrl) || strpos($systemUrl, 'http') !== 0) {
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http');
            $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
            $dir = dirname($_SERVER['PHP_SELF']);
            if ($dir === '/' || $dir === '\\') {
                $dir = '';
            }
            $systemUrl = $protocol . '://' . $host . $dir;
        }
        
        return $systemUrl . '/modules/addons/' . self::MODULE_NAME . '/assets/' . $asset;
    }

    /**
     * Get a module setting value
     *
     * @param string $key Setting key
     * @param mixed $default Default value if setting not found
     * @return mixed
     */
    public function getSetting($key, $default = null)
    {
        $this->loadSettings();
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }

    /**
     * Load all settings from database (cached per request)
     *
     * @return void
     */
    private function loadSettings()
    {
        if ($this->settingsLoaded) {
            return;
        }

        try {
            $results = \Illuminate\Database\Capsule\Manager::table(self::TABLE_PREFIX . 'settings')
                ->get();

            foreach ($results as $row) {
                $this->settings[$row->setting_key] = $row->setting_value;
            }
        } catch (\Exception $e) {
            // Table may not exist yet during activation
        }

        $this->settingsLoaded = true;
    }

    /**
     * Clear the settings cache (useful after saving settings)
     *
     * @return void
     */
    public function clearSettingsCache()
    {
        $this->settings = [];
        $this->settingsLoaded = false;
    }

    /**
     * Get all database table names used by the module
     *
     * @return array
     */
    public static function getTableNames()
    {
        return [
            'departments'    => self::TABLE_PREFIX . 'departments',
            'schedules'      => self::TABLE_PREFIX . 'schedules',
            'slots'          => self::TABLE_PREFIX . 'slots',
            'holidays'       => self::TABLE_PREFIX . 'holidays',
            'overrides'      => self::TABLE_PREFIX . 'overrides',
            'response_times' => self::TABLE_PREFIX . 'response_times',
            'settings'       => self::TABLE_PREFIX . 'settings',
            'analytics'      => self::TABLE_PREFIX . 'analytics',
        ];
    }

    /**
     * Check if the current WHMCS theme is Lagom
     *
     * @return bool
     */
    public function isLagomTheme()
    {
        try {
            $template = \WHMCS\Config\Setting::getValue('Template');
            return stripos($template, 'lagom') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the detected theme type
     *
     * @return string 'lagom', 'twenty-one', 'six', or 'unknown'
     */
    public function getThemeType()
    {
        try {
            $template = \WHMCS\Config\Setting::getValue('Template');
            if (stripos($template, 'lagom') !== false) {
                return 'lagom';
            }
            if (stripos($template, 'twenty-one') !== false) {
                return 'twenty-one';
            }
            if (stripos($template, 'six') !== false) {
                return 'six';
            }
        } catch (\Exception $e) {
            // Ignore
        }
        return 'unknown';
    }

    /**
     * Prevent cloning of singleton
     */
    private function __clone() {}

    /**
     * Prevent unserialization of singleton
     */
    public function __wakeup()
    {
        throw new \Exception('Cannot unserialize singleton');
    }
}
