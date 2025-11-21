<?php
/**
 * CSS Generator Class
 *
 * @package Horus
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Horus_CSS_Generator
 */
class Horus_CSS_Generator {

    /**
     * Instance
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
        add_action('horus_regenerate_css', array($this, 'regenerate_css'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_horus_regenerate_css', array($this, 'handle_manual_regeneration'));
        add_action('wp_ajax_horus_generate_page_css', array($this, 'ajax_generate_page_css'));
    }

    /**
     * Regenerate CSS for a specific page
     */
    public function regenerate_css_for_page($post_id) {
        // Get Elementor integration instance
        $elementor_integration = Horus_Elementor_Integration::instance();

        // Get classes for this page only
        $classes = $elementor_integration->get_page_tailwind_classes($post_id);

        if (empty($classes)) {
            return false;
        }

        // Generate CSS file for this page
        $css_filename = "tailwind-page-{$post_id}.css";

        // Create temporary tailwind config for this page with safelist
        $this->create_page_tailwind_config($post_id, $classes);

        // Build CSS for this page
        $result = $this->build_page_css($post_id, $css_filename);

        // Clean up temp config file
        @unlink(HORUS_PATH . "tailwind.config.page-{$post_id}.js");

        return $result;
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Add top-level menu for Horus
        add_menu_page(
            __('Horus - Tailwind CSS', 'horus'),
            __('Horus', 'horus'),
            'manage_options',
            'horus',
            array($this, 'render_settings_page'),
            'dashicons-admin-customizer',
            59
        );

        // Add Settings submenu (same as parent, so it replaces the main item)
        add_submenu_page(
            'horus',
            __('Horus - Settings', 'horus'),
            __('Settings', 'horus'),
            'manage_options',
            'horus',
            array($this, 'render_settings_page')
        );

        // Add Diagnostics submenu
        add_submenu_page(
            'horus',
            __('Horus - Diagnostics', 'horus'),
            __('Diagnostics', 'horus'),
            'manage_options',
            'horus-diagnostics',
            array($this, 'render_diagnostics_page')
        );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        $generated_css_exists = file_exists(HORUS_PATH . 'assets/css/tailwind-generated.css');
        $last_generated = $generated_css_exists ? date('Y-m-d H:i:s', filemtime(HORUS_PATH . 'assets/css/tailwind-generated.css')) : __('Never', 'horus');

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="card">
                <h2><?php _e('CSS Generation', 'horus'); ?></h2>
                <p><?php _e('Horus automatically generates optimized Tailwind CSS containing only the classes you use in Elementor.', 'horus'); ?></p>

                <table class="form-table">
                    <tr>
                        <th><?php _e('Status:', 'horus'); ?></th>
                        <td>
                            <?php if ($generated_css_exists) : ?>
                                <span style="color: green;">✓ <?php _e('CSS Generated', 'horus'); ?></span>
                            <?php else : ?>
                                <span style="color: orange;">⚠ <?php _e('Using CDN (No generated CSS)', 'horus'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Last Generated:', 'horus'); ?></th>
                        <td><?php echo esc_html($last_generated); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Mode:', 'horus'); ?></th>
                        <td>
                            <strong><?php _e('Elementor Editor:', 'horus'); ?></strong> Tailwind Play CDN (JIT enabled)<br>
                            <strong><?php _e('Frontend:', 'horus'); ?></strong>
                            <?php echo $generated_css_exists ? __('Optimized Generated CSS', 'horus') : __('Tailwind Play CDN (Fallback)', 'horus'); ?>
                        </td>
                    </tr>
                </table>

                <h3><?php _e('Manual Regeneration', 'horus'); ?></h3>
                <p><?php _e('Click the button below to manually regenerate the optimized CSS file. This will scan all Elementor pages and generate CSS for the Tailwind classes in use.', 'horus'); ?></p>

                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <input type="hidden" name="action" value="horus_regenerate_css">
                    <?php wp_nonce_field('horus_regenerate_css', 'horus_nonce'); ?>
                    <?php submit_button(__('Regenerate CSS Now', 'horus'), 'primary', 'submit', false); ?>
                </form>

                <hr>

                <h3><?php _e('How It Works', 'horus'); ?></h3>
                <ul>
                    <li><?php _e('<strong>In Elementor Editor:</strong> Uses Tailwind Play CDN with JIT compilation for instant class rendering', 'horus'); ?></li>
                    <li><?php _e('<strong>On Frontend:</strong> Uses optimized, purged CSS containing only the classes you use', 'horus'); ?></li>
                    <li><?php _e('<strong>Automatic Updates:</strong> CSS regenerates automatically when you save Elementor pages', 'horus'); ?></li>
                    <li><?php _e('<strong>Manual Control:</strong> You can manually regenerate CSS anytime using the button above', 'horus'); ?></li>
                </ul>

                <hr>

                <h3><?php _e('Advanced: CLI CSS Generation', 'horus'); ?></h3>
                <p><?php _e('For optimal performance, you can use the Tailwind CLI to generate CSS. This requires Node.js and the Tailwind CLI installed.', 'horus'); ?></p>

                <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace;">
                    <strong><?php _e('Setup Instructions:', 'horus'); ?></strong><br><br>
                    1. <?php _e('Navigate to plugin directory:', 'horus'); ?><br>
                    <code>cd <?php echo esc_html(HORUS_PATH); ?></code><br><br>

                    2. <?php _e('Install Tailwind CLI:', 'horus'); ?><br>
                    <code>npm install -D tailwindcss</code><br><br>

                    3. <?php _e('Generate CSS:', 'horus'); ?><br>
                    <code>npx tailwindcss -o assets/css/tailwind-generated.css --minify</code>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render diagnostics page
     */
    public function render_diagnostics_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized access', 'horus'));
        }

        // Get all Elementor pages
        global $wpdb;
        $elementor_pages = $wpdb->get_results(
            "SELECT p.ID, p.post_title, p.post_status, p.post_modified
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE pm.meta_key = '_elementor_edit_mode' AND pm.meta_value = 'builder'
            AND p.post_status IN ('publish', 'draft')
            ORDER BY p.post_modified DESC"
        );

        ?>
        <style>
            .horus-diagnostics-page {
                max-width: 100% !important;
                margin-right: 20px;
            }
            .horus-diagnostics-page .card {
                max-width: none !important;
            }
            .horus-diagnostics-page table.wp-list-table {
                font-size: 13px;
                width: 100%;
            }
            .horus-diagnostics-page table.wp-list-table th,
            .horus-diagnostics-page table.wp-list-table td {
                padding: 10px 8px;
            }
            .horus-diagnostics-page .column-classes {
                max-width: 400px;
            }
            .horus-diagnostics-page .column-classes small {
                display: block;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .horus-diagnostics-page .column-id {
                width: 60px;
            }
            .horus-diagnostics-page .column-status {
                width: 100px;
            }
            .horus-diagnostics-page .column-file {
                width: 180px;
            }
            .horus-diagnostics-page .column-size {
                width: 100px;
            }
            .horus-diagnostics-page .column-actions {
                width: 150px;
            }
        </style>
        <div class="wrap horus-diagnostics-page">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="card">
                <h2><?php _e('Per-Page CSS System Diagnostics', 'horus'); ?></h2>
                <p><?php _e('This page shows the status of CSS generation for each Elementor page.', 'horus'); ?></p>

                <?php if (empty($elementor_pages)) : ?>
                    <div class="notice notice-warning">
                        <p><?php _e('No Elementor pages found.', 'horus'); ?></p>
                    </div>
                <?php else : ?>
                    <p><strong><?php printf(__('Found %d Elementor pages:', 'horus'), count($elementor_pages)); ?></strong></p>

                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th class="column-id"><?php _e('Page ID', 'horus'); ?></th>
                                <th class="column-title"><?php _e('Page Title', 'horus'); ?></th>
                                <th class="column-status"><?php _e('Status', 'horus'); ?></th>
                                <th class="column-classes"><?php _e('Classes Found', 'horus'); ?></th>
                                <th class="column-file"><?php _e('CSS File', 'horus'); ?></th>
                                <th class="column-size"><?php _e('File Size', 'horus'); ?></th>
                                <th class="column-actions"><?php _e('Actions', 'horus'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $elementor_integration = Horus_Elementor_Integration::instance();

                            foreach ($elementor_pages as $page) :
                                $page_id = $page->ID;
                                $classes = $elementor_integration->get_page_tailwind_classes($page_id);
                                $class_count = count($classes);

                                $css_path = $this->get_page_css_path($page_id);
                                $css_url = $this->get_page_css_url($page_id);
                                $css_exists = file_exists($css_path);
                                $css_size = $css_exists ? filesize($css_path) : 0;
                                $css_size_kb = $css_exists ? round($css_size / 1024, 2) : 0;

                                $status_class = $css_exists && $css_size > 500 ? 'notice-success' : 'notice-warning';
                                $status_text = $css_exists && $css_size > 500 ? __('Generated', 'horus') : __('Not Generated', 'horus');
                            ?>
                                <tr>
                                    <td><?php echo esc_html($page_id); ?></td>
                                    <td>
                                        <strong><?php echo esc_html($page->post_title); ?></strong><br>
                                        <small>
                                            <a href="<?php echo get_permalink($page_id); ?>" target="_blank"><?php _e('View', 'horus'); ?></a> |
                                            <a href="<?php echo admin_url('post.php?post=' . $page_id . '&action=elementor'); ?>" target="_blank"><?php _e('Edit', 'horus'); ?></a>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="<?php echo esc_attr($status_class); ?>" style="display:inline-block;padding:2px 8px;border-radius:3px;">
                                            <?php echo esc_html($status_text); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($class_count > 0) : ?>
                                            <strong><?php echo esc_html($class_count); ?></strong> classes
                                            <br><small style="color:#666;"><?php echo esc_html(implode(', ', array_slice($classes, 0, 5))); ?><?php echo $class_count > 5 ? '...' : ''; ?></small>
                                        <?php else : ?>
                                            <span style="color:#999;">0 classes</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($css_exists) : ?>
                                            <a href="<?php echo esc_url($css_url); ?>" target="_blank">
                                                <code><?php echo esc_html(basename($css_path)); ?></code>
                                            </a>
                                        <?php else : ?>
                                            <span style="color:#999;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($css_exists) : ?>
                                            <strong><?php echo esc_html($css_size_kb); ?> KB</strong>
                                            <br><small style="color:#666;"><?php echo esc_html($css_size); ?> bytes</small>
                                        <?php else : ?>
                                            <span style="color:#999;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button
                                            class="button button-small horus-generate-css"
                                            data-page-id="<?php echo esc_attr($page_id); ?>"
                                            data-page-title="<?php echo esc_attr($page->post_title); ?>">
                                            <?php $css_exists ? _e('Regenerate', 'horus') : _e('Generate', 'horus'); ?>
                                        </button>
                                        <span class="spinner" style="float:none;margin:0;"></span>
                                        <span class="horus-result" style="display:none;margin-left:10px;"></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <hr style="margin:30px 0;">

                    <h3><?php _e('System Information', 'horus'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Tailwind CLI:', 'horus'); ?></th>
                            <td>
                                <?php
                                $tailwind_cli = HORUS_PATH . 'node_modules/.bin/tailwindcss';
                                if (file_exists($tailwind_cli)) {
                                    echo '<span style="color:green;">✓ ' . __('Installed', 'horus') . '</span>';
                                } else {
                                    echo '<span style="color:red;">✗ ' . __('Not installed', 'horus') . '</span>';
                                    echo '<br><small>' . __('Run:', 'horus') . ' <code>cd ' . esc_html(HORUS_PATH) . ' && npm install</code></small>';
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('CSS Directory:', 'horus'); ?></th>
                            <td>
                                <code><?php echo esc_html(HORUS_PATH . 'assets/css/'); ?></code>
                                <?php
                                $css_dir = HORUS_PATH . 'assets/css/';
                                if (is_writable($css_dir)) {
                                    echo '<br><span style="color:green;">✓ ' . __('Writable', 'horus') . '</span>';
                                } else {
                                    echo '<br><span style="color:red;">✗ ' . __('Not writable', 'horus') . '</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Total CSS Files:', 'horus'); ?></th>
                            <td>
                                <?php
                                $css_files = glob(HORUS_PATH . 'assets/css/tailwind-page-*.css');
                                echo '<strong>' . count($css_files) . '</strong> page-specific files';
                                ?>
                            </td>
                        </tr>
                    </table>

                <?php endif; ?>
            </div>

            <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('.horus-generate-css').on('click', function(e) {
                    e.preventDefault();

                    var button = $(this);
                    var row = button.closest('tr');
                    var spinner = row.find('.spinner');
                    var result = row.find('.horus-result');
                    var pageId = button.data('page-id');
                    var pageTitle = button.data('page-title');

                    button.prop('disabled', true);
                    spinner.addClass('is-active');
                    result.hide();

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'horus_generate_page_css',
                            page_id: pageId,
                            nonce: '<?php echo wp_create_nonce('horus_generate_css'); ?>'
                        },
                        success: function(response) {
                            spinner.removeClass('is-active');
                            button.prop('disabled', false);

                            if (response.success) {
                                result.html('<span style="color:green;">✓ ' + response.data.message + '</span>').show();

                                // Refresh page after 2 seconds
                                setTimeout(function() {
                                    location.reload();
                                }, 2000);
                            } else {
                                result.html('<span style="color:red;">✗ ' + response.data.message + '</span>').show();
                            }
                        },
                        error: function() {
                            spinner.removeClass('is-active');
                            button.prop('disabled', false);
                            result.html('<span style="color:red;">✗ Error</span>').show();
                        }
                    });
                });
            });
            </script>
        </div>
        <?php
    }

    /**
     * AJAX handler for generating page CSS
     */
    public function ajax_generate_page_css() {
        check_ajax_referer('horus_generate_css', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'horus')));
        }

        $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;

        if (!$page_id) {
            wp_send_json_error(array('message' => __('Invalid page ID', 'horus')));
        }

        // Get classes first to check if there are any
        $elementor_integration = Horus_Elementor_Integration::instance();
        $classes = $elementor_integration->get_page_tailwind_classes($page_id);

        if (empty($classes)) {
            wp_send_json_error(array(
                'message' => __('No Tailwind classes found on this page. Add classes in Elementor CSS Classes field first.', 'horus')
            ));
        }

        // Generate CSS
        $result = $this->regenerate_css_for_page($page_id);

        if ($result) {
            $css_path = $this->get_page_css_path($page_id);

            if (file_exists($css_path)) {
                $size_bytes = filesize($css_path);
                $size_kb = round($size_bytes / 1024, 2);

                if ($size_bytes < 500) {
                    wp_send_json_error(array(
                        'message' => sprintf(
                            __('CSS file created but too small (%s bytes). Found %d classes but Tailwind may not have processed them correctly.', 'horus'),
                            $size_bytes,
                            count($classes)
                        )
                    ));
                } else {
                    wp_send_json_success(array(
                        'message' => sprintf(
                            __('CSS generated successfully! %s KB with %d classes', 'horus'),
                            $size_kb,
                            count($classes)
                        )
                    ));
                }
            } else {
                wp_send_json_error(array(
                    'message' => __('CSS generation returned true but file was not created. Check error logs.', 'horus')
                ));
            }
        } else {
            wp_send_json_error(array(
                'message' => __('CSS generation failed. Make sure Tailwind CLI is installed and working.', 'horus')
            ));
        }
    }

    /**
     * Handle manual regeneration
     */
    public function handle_manual_regeneration() {
        if (!isset($_POST['horus_nonce']) || !wp_verify_nonce($_POST['horus_nonce'], 'horus_regenerate_css')) {
            wp_die(__('Security check failed', 'horus'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'horus'));
        }

        $this->regenerate_css();

        wp_redirect(add_query_arg(
            array(
                'page' => 'horus-settings',
                'message' => 'regenerated'
            ),
            admin_url('admin.php')
        ));
        exit;
    }

    /**
     * Regenerate CSS
     */
    public function regenerate_css() {
        // Get all Tailwind classes used in Elementor
        $elementor_integration = Horus_Elementor_Integration::instance();
        $classes = $elementor_integration->get_all_tailwind_classes();

        if (empty($classes)) {
            // No classes found, use basic Tailwind
            $this->generate_safelist_file($classes);
            return;
        }

        // Generate safelist file for Tailwind CLI
        $this->generate_safelist_file($classes);

        // Try to generate CSS using Tailwind CLI if available
        $this->try_generate_with_cli();

        // Clean up orphaned CSS files
        $this->cleanup_orphaned_css();
    }

    /**
     * Generate safelist file
     */
    private function generate_safelist_file($classes) {
        $safelist_content = implode("\n", $classes);

        $safelist_path = HORUS_PATH . 'assets/safelist.txt';
        file_put_contents($safelist_path, $safelist_content);

        // Also create a simple CSS file with the classes for documentation
        $this->create_html_template($classes);
    }

    /**
     * Create HTML template with classes
     */
    private function create_html_template($classes) {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
<!-- This file is used for CSS generation -->
<div class="' . esc_attr(implode(' ', $classes)) . '">
    Horus - Tailwind CSS for Elementor
</div>
</body>
</html>';

        file_put_contents(HORUS_PATH . 'assets/template.html', $html);
    }

    /**
     * Try to generate CSS with Tailwind CLI
     */
    private function try_generate_with_cli() {
        // Check if shell_exec is available
        if (!function_exists('shell_exec')) {
            error_log('Horus: shell_exec is disabled. Cannot generate CSS on this server.');
            return false;
        }

        // Detect OS
        $is_windows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

        // Check if Tailwind CLI is installed
        $tailwind_js = HORUS_PATH . 'node_modules/tailwindcss/lib/cli.js';
        if (!file_exists($tailwind_js)) {
            error_log('Horus: Tailwind CLI not installed at ' . $tailwind_js);
            return false;
        }

        // Find node executable
        $node_exe = $this->find_node_executable();
        if (!$node_exe) {
            error_log('Horus: Node.js not found. Cannot generate CSS.');
            return false;
        }

        // Create tailwind config if it doesn't exist
        $this->create_tailwind_config();

        // Generate CSS
        $input_css = HORUS_PATH . 'assets/css/input.css';
        $output_css = HORUS_PATH . 'assets/css/tailwind-generated.css';

        // Use node directly with Tailwind CLI JS
        $command = sprintf(
            '"%s" "%s" -i "%s" -o "%s" --minify 2>&1',
            $node_exe,
            $tailwind_js,
            $input_css,
            $output_css
        );

        $output = shell_exec($command);

        if (!file_exists($output_css)) {
            error_log("Horus: CSS generation failed. Command: {$command}. Output: {$output}");
        }

        return file_exists($output_css);
    }

    /**
     * Find Node.js executable
     */
    private function find_node_executable() {
        $is_windows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

        // Common Node.js locations
        $common_paths = array(
            'C:\\Program Files\\nodejs\\node.exe', // Windows default
            'C:\\Program Files (x86)\\nodejs\\node.exe', // Windows 32-bit
            '/usr/bin/node', // Linux/Unix
            '/usr/local/bin/node', // macOS/Unix
            '/opt/nodejs/bin/node', // Some shared hosting
            '/home/USERNAME/.nvm/versions/node/v*/bin/node', // NVM
        );

        // Check common paths first
        foreach ($common_paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Try to find using system command
        if ($is_windows) {
            $output = @shell_exec('where node 2>&1');
        } else {
            $output = @shell_exec('which node 2>&1');
        }

        if ($output && strpos($output, 'node') !== false) {
            $node_path = trim(explode("\n", $output)[0]);
            if (file_exists($node_path)) {
                return $node_path;
            }
        }

        return false;
    }

    /**
     * Create Tailwind config file
     */
    private function create_tailwind_config() {
        $config_path = HORUS_PATH . 'tailwind.config.js';

        if (file_exists($config_path)) {
            return;
        }

        $config = <<<JS
/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './assets/**/*.html',
    './assets/safelist.txt',
  ],
  important: '.elementor',
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#f5f3ff',
          100: '#ede9fe',
          200: '#ddd6fe',
          300: '#c4b5fd',
          400: '#a78bfa',
          500: '#8b5cf6',
          600: '#7c3aed',
          700: '#6d28d9',
          800: '#5b21b6',
          900: '#4c1d95',
        },
      },
    },
  },
  plugins: [],
}
JS;

        file_put_contents($config_path, $config);
    }

    /**
     * Create input CSS file
     */
    public function create_input_css() {
        $input_css_path = HORUS_PATH . 'assets/css/input.css';

        if (file_exists($input_css_path)) {
            return;
        }

        $css = <<<CSS
@tailwind base;
@tailwind components;
@tailwind utilities;

/* Custom utilities can be added here */
CSS;

        file_put_contents($input_css_path, $css);
    }

    /**
     * Create Tailwind config for a specific page
     */
    private function create_page_tailwind_config($post_id, $classes) {
        $config_path = HORUS_PATH . "tailwind.config.page-{$post_id}.js";

        // Create safelist array from classes
        $safelist_json = json_encode($classes);

        $config = <<<JS
/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [],
  safelist: {$safelist_json},
  important: '.elementor',
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#f5f3ff',
          100: '#ede9fe',
          200: '#ddd6fe',
          300: '#c4b5fd',
          400: '#a78bfa',
          500: '#8b5cf6',
          600: '#7c3aed',
          700: '#6d28d9',
          800: '#5b21b6',
          900: '#4c1d95',
          950: '#2e1065',
        },
      },
    },
  },
  plugins: [
    require('@savvywombat/tailwindcss-grid-areas'),
  ],
}
JS;

        file_put_contents($config_path, $config);
    }

    /**
     * Build CSS for a specific page
     */
    private function build_page_css($post_id, $css_filename) {
        $input_css = HORUS_PATH . 'assets/css/input.css';
        $output_css = HORUS_PATH . 'assets/css/' . $css_filename;
        $config_file = HORUS_PATH . "tailwind.config.page-{$post_id}.js";

        // Ensure input CSS exists
        $this->create_input_css();

        // Find Node.js executable
        $node_exe = $this->find_node_executable();
        if (!$node_exe) {
            error_log("Horus: Cannot build CSS for page {$post_id} - Node.js not found");
            return false;
        }

        // Get Tailwind CLI JS path
        $tailwind_js = HORUS_PATH . 'node_modules/tailwindcss/lib/cli.js';
        if (!file_exists($tailwind_js)) {
            error_log("Horus: Tailwind CLI not found at {$tailwind_js}");
            return false;
        }

        // Build command - call node directly with Tailwind CLI
        $command = sprintf(
            '"%s" "%s" -c "%s" -i "%s" -o "%s" --minify 2>&1',
            $node_exe,
            $tailwind_js,
            $config_file,
            $input_css,
            $output_css
        );

        // Execute
        $output = shell_exec($command);

        // Log output for debugging
        if (!file_exists($output_css)) {
            error_log("Horus CSS Generation failed for page {$post_id}. Command: {$command}. Output: {$output}");
        }

        return file_exists($output_css);
    }

    /**
     * Get CSS filename for a page
     */
    public function get_page_css_filename($post_id) {
        return "tailwind-page-{$post_id}.css";
    }

    /**
     * Get CSS path for a page
     */
    public function get_page_css_path($post_id) {
        return HORUS_PATH . 'assets/css/' . $this->get_page_css_filename($post_id);
    }

    /**
     * Get CSS URL for a page
     */
    public function get_page_css_url($post_id) {
        return HORUS_URL . 'assets/css/' . $this->get_page_css_filename($post_id);
    }

    /**
     * Clean up orphaned CSS files
     */
    public function cleanup_orphaned_css() {
        global $wpdb;

        // Get all Elementor page IDs
        $page_ids = $wpdb->get_col(
            "SELECT DISTINCT post_id
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_elementor_data'"
        );

        // Get all CSS files
        $css_files = glob(HORUS_PATH . 'assets/css/tailwind-page-*.css');

        foreach ($css_files as $css_file) {
            // Extract post ID from filename
            if (preg_match('/tailwind-page-(\d+)\.css$/', $css_file, $matches)) {
                $post_id = intval($matches[1]);

                // If page doesn't exist, delete the CSS file
                if (!in_array($post_id, $page_ids)) {
                    @unlink($css_file);
                }
            }
        }
    }
}
