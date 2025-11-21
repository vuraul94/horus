<?php
/**
 * Tailwind Integration Class
 *
 * @package Horus
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Horus_Tailwind_Integration
 */
class Horus_Tailwind_Integration {

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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_tailwind_frontend'), 5);
        add_action('elementor/editor/before_enqueue_scripts', array($this, 'enqueue_tailwind_editor'));
        add_action('elementor/preview/enqueue_styles', array($this, 'enqueue_tailwind_preview'));
    }

    /**
     * Enqueue Tailwind CSS for frontend
     * Uses page-specific CSS files ONLY - no CDN fallback
     */
    public function enqueue_tailwind_frontend() {
        // Get current post ID
        $post_id = get_the_ID();

        if (!$post_id) {
            // Not a singular page, no CSS loaded
            add_action('wp_head', function() {
                echo "<!-- Horus: Not a singular page, no Tailwind CSS loaded -->\n";
            }, 100);
            return;
        }

        // Check if this is an Elementor page
        $is_elementor = get_post_meta($post_id, '_elementor_edit_mode', true) === 'builder';

        if (!$is_elementor) {
            // Not an Elementor page, no CSS loaded
            add_action('wp_head', function() use ($post_id) {
                echo "<!-- Horus: Page $post_id is not an Elementor page, no Tailwind CSS loaded -->\n";
            }, 100);
            return;
        }

        // Get CSS generator instance
        $css_generator = Horus_CSS_Generator::instance();

        // Check if page-specific CSS exists
        $page_css_path = $css_generator->get_page_css_path($post_id);

        if (file_exists($page_css_path) && filesize($page_css_path) > 500) {
            // Use page-specific CSS
            wp_enqueue_style(
                'horus-tailwind-page-' . $post_id,
                $css_generator->get_page_css_url($post_id),
                array(),
                filemtime($page_css_path)
            );

            // Debug marker
            add_action('wp_head', function() use ($post_id, $page_css_path) {
                $size_kb = round(filesize($page_css_path) / 1024, 2);
                echo "<!-- Horus: Using page-specific CSS (Page ID: $post_id, Size: {$size_kb}KB) -->\n";
            }, 100);
        } else {
            // CSS not generated yet - NO FALLBACK, show warning
            add_action('wp_head', function() use ($post_id) {
                echo "<!-- Horus: WARNING - CSS not generated for page $post_id. Please generate it from admin. -->\n";
            }, 100);

            // Show admin notice if user is logged in
            if (current_user_can('manage_options')) {
                add_action('wp_footer', function() use ($post_id) {
                    echo '<div style="position:fixed;bottom:20px;right:20px;background:#dc2626;color:white;padding:15px 20px;border-radius:8px;box-shadow:0 4px 6px rgba(0,0,0,0.3);z-index:99999;font-family:sans-serif;max-width:300px;">';
                    echo '<strong>⚠️ Horus Warning</strong><br>';
                    echo 'CSS not generated for this page.<br>';
                    echo '<a href="' . admin_url('admin.php?page=horus-diagnostics') . '" style="color:#fbbf24;text-decoration:underline;">Generate it now →</a>';
                    echo '</div>';
                }, 999);
            }
        }

        // Load custom utilities if they exist
        $custom_css_path = HORUS_PATH . 'assets/css/custom-utilities.css';
        if (file_exists($custom_css_path)) {
            wp_enqueue_style(
                'horus-custom-utilities',
                HORUS_URL . 'assets/css/custom-utilities.css',
                array(),
                filemtime($custom_css_path)
            );
        }
    }

    /**
     * Enqueue Tailwind CSS for Elementor editor
     */
    public function enqueue_tailwind_editor() {
        // Use Tailwind Play CDN for editor (has JIT built-in)
        $this->enqueue_tailwind_cdn();

        // Add custom CSS for better Elementor integration
        wp_enqueue_style(
            'horus-elementor-integration',
            HORUS_URL . 'assets/css/elementor-integration.css',
            array(),
            HORUS_VERSION
        );
    }

    /**
     * Enqueue Tailwind CSS for Elementor preview
     */
    public function enqueue_tailwind_preview() {
        $this->enqueue_tailwind_cdn();
    }

    /**
     * Enqueue Tailwind CDN
     */
    private function enqueue_tailwind_cdn() {
        // Using Tailwind CDN with JIT
        wp_enqueue_script(
            'horus-tailwind-cdn',
            'https://cdn.tailwindcss.com',
            array(),
            HORUS_VERSION,
            false
        );

        // Add Tailwind config
        wp_add_inline_script(
            'horus-tailwind-cdn',
            $this->get_tailwind_config(),
            'after'
        );
    }

    /**
     * Get Tailwind configuration
     */
    private function get_tailwind_config() {
        $config = array(
            'darkMode' => 'class',
            'theme' => array(
                'extend' => array(
                    'colors' => array(
                        'primary' => array(
                            '50' => '#f5f3ff',
                            '100' => '#ede9fe',
                            '200' => '#ddd6fe',
                            '300' => '#c4b5fd',
                            '400' => '#a78bfa',
                            '500' => '#8b5cf6',
                            '600' => '#7c3aed',
                            '700' => '#6d28d9',
                            '800' => '#5b21b6',
                            '900' => '#4c1d95',
                        ),
                    ),
                ),
            ),
        );

        // Allow filtering the config
        $config = apply_filters('horus_tailwind_config', $config);

        return 'tailwind.config = ' . wp_json_encode($config) . ';';
    }

    /**
     * Add Tailwind directly to head (ensures it loads)
     */
    public function add_tailwind_to_head() {
        // Only on frontend, not in admin or editor
        if (is_admin() || (defined('ELEMENTOR_VERSION') && \Elementor\Plugin::$instance->editor->is_edit_mode())) {
            return;
        }

        // Check if we have generated CSS
        $generated_css_path = HORUS_PATH . 'assets/css/tailwind-generated.css';

        // Only load CDN if we don't have generated CSS
        if (!file_exists($generated_css_path) || filesize($generated_css_path) < 1000) {
            ?>
            <script src="https://cdn.tailwindcss.com"></script>
            <script>
                <?php echo $this->get_tailwind_config(); ?>
            </script>
            <?php
        }
    }

    /**
     * Get Tailwind CSS path
     */
    public function get_tailwind_css_path() {
        return HORUS_PATH . 'assets/css/tailwind-generated.css';
    }

    /**
     * Get Tailwind CSS URL
     */
    public function get_tailwind_css_url() {
        return HORUS_URL . 'assets/css/tailwind-generated.css';
    }
}
