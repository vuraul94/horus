<?php
/**
 * Plugin Name: Horus - Tailwind CSS for Elementor
 * Plugin URI: https://github.com/yourusername/horus
 * Description: Integrates Tailwind CSS with Elementor, enabling real-time Tailwind classes in the editor with JIT compilation and automatic purging.
 * Version: 1.0.0
 * Author: Raúl Venegas Ugalde
 * Author URI: https://yourdomain.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: horus
 * Domain Path: /languages
 * Requires at least: 5.9
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define('HORUS_VERSION', '1.0.0');
define('HORUS_FILE', __FILE__);
define('HORUS_PATH', plugin_dir_path(__FILE__));
define('HORUS_URL', plugin_dir_url(__FILE__));
define('HORUS_BASENAME', plugin_basename(__FILE__));

/**
 * Main Horus Class
 */
final class Horus {

    /**
     * Instance
     * @var Horus
     */
    private static $_instance = null;

    /**
     * Get Instance
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
        $this->includes();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'check_dependencies'));
        add_action('admin_notices', array($this, 'admin_notices'));
    }

    /**
     * Include required files
     */
    private function includes() {
        require_once HORUS_PATH . 'includes/tailwind-integration.php';
        require_once HORUS_PATH . 'includes/elementor-integration.php';
        require_once HORUS_PATH . 'includes/css-generator.php';

        // Initialize components
        Horus_Tailwind_Integration::instance();
        Horus_Elementor_Integration::instance();
        Horus_CSS_Generator::instance();
    }

    /**
     * Check plugin dependencies
     */
    public function check_dependencies() {
        if (!did_action('elementor/loaded')) {
            add_action('admin_notices', array($this, 'missing_elementor_notice'));
            return;
        }

        // Check Elementor version
        if (!version_compare(ELEMENTOR_VERSION, '3.0.0', '>=')) {
            add_action('admin_notices', array($this, 'minimum_elementor_version_notice'));
            return;
        }
    }

    /**
     * Admin notices
     */
    public function admin_notices() {
        // Placeholder for future notices
    }

    /**
     * Missing Elementor notice
     */
    public function missing_elementor_notice() {
        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" to be installed and activated.', 'horus'),
            '<strong>' . esc_html__('Horus - Tailwind CSS for Elementor', 'horus') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'horus') . '</strong>'
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }

    /**
     * Minimum Elementor version notice
     */
    public function minimum_elementor_version_notice() {
        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'horus'),
            '<strong>' . esc_html__('Horus - Tailwind CSS for Elementor', 'horus') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'horus') . '</strong>',
            '3.0.0'
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }
}

/**
 * Initialize Horus
 */
function horus() {
    return Horus::instance();
}

// Kickoff
horus();
