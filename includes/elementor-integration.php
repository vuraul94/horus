<?php
/**
 * Elementor Integration Class
 *
 * @package Horus
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Horus_Elementor_Integration
 */
class Horus_Elementor_Integration {

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
        // Only hook to save event to regenerate CSS
        add_action('elementor/editor/after_save', array($this, 'after_save_document'), 10, 2);
    }

    /**
     * Add Tailwind CSS controls to Elementor widgets
     */
    public function add_tailwind_controls($element, $section_id, $args) {
        // Add controls only to the Advanced tab's Custom CSS section
        if ('section_custom_css_pro' !== $section_id && 'section_custom_css' !== $section_id) {
            return;
        }

        $element->start_controls_section(
            'horus_tailwind_section',
            array(
                'label' => __('Tailwind CSS', 'horus') . ' (Horus)',
                'tab' => \Elementor\Controls_Manager::TAB_ADVANCED,
            )
        );

        $element->add_control(
            'horus_tailwind_classes',
            array(
                'label' => __('Tailwind Classes', 'horus'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'placeholder' => __('e.g., bg-blue-500 text-white p-4 rounded-lg shadow-xl', 'horus'),
                'description' => __('Enter Tailwind CSS classes. They will be applied in real-time.', 'horus'),
                'dynamic' => array(
                    'active' => true,
                ),
            )
        );

        $element->add_control(
            'horus_tailwind_responsive',
            array(
                'label' => __('Responsive Classes', 'horus'),
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            )
        );

        $element->add_control(
            'horus_tailwind_mobile',
            array(
                'label' => __('Mobile Classes', 'horus'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'placeholder' => __('e.g., sm:text-sm sm:p-2', 'horus'),
                'description' => __('Tailwind classes for mobile (sm: prefix)', 'horus'),
            )
        );

        $element->add_control(
            'horus_tailwind_tablet',
            array(
                'label' => __('Tablet Classes', 'horus'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'placeholder' => __('e.g., md:text-base md:p-3', 'horus'),
                'description' => __('Tailwind classes for tablet (md: prefix)', 'horus'),
            )
        );

        $element->add_control(
            'horus_tailwind_desktop',
            array(
                'label' => __('Desktop Classes', 'horus'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'placeholder' => __('e.g., lg:text-lg lg:p-4', 'horus'),
                'description' => __('Tailwind classes for desktop (lg: prefix)', 'horus'),
            )
        );

        $element->add_control(
            'horus_tailwind_help',
            array(
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => sprintf(
                    __('Need help? Check the <a href="%s" target="_blank">Tailwind CSS Documentation</a>', 'horus'),
                    'https://tailwindcss.com/docs'
                ),
                'content_classes' => 'elementor-descriptor',
            )
        );

        $element->end_controls_section();
    }

    /**
     * Render Tailwind classes on element
     */
    public function before_render_element($element) {
        $settings = $element->get_settings();

        $classes = array();

        // Add base Tailwind classes
        if (!empty($settings['horus_tailwind_classes'])) {
            $classes[] = $settings['horus_tailwind_classes'];
        }

        // Add mobile classes
        if (!empty($settings['horus_tailwind_mobile'])) {
            $classes[] = $settings['horus_tailwind_mobile'];
        }

        // Add tablet classes
        if (!empty($settings['horus_tailwind_tablet'])) {
            $classes[] = $settings['horus_tailwind_tablet'];
        }

        // Add desktop classes
        if (!empty($settings['horus_tailwind_desktop'])) {
            $classes[] = $settings['horus_tailwind_desktop'];
        }

        if (!empty($classes)) {
            $element->add_render_attribute('_wrapper', 'class', implode(' ', $classes));
        }
    }

    /**
     * After Elementor document save
     * Triggers CSS regeneration for this specific page
     */
    public function after_save_document($post_id, $document) {
        // Log the save event
        error_log("Horus: Elementor saved page $post_id, triggering CSS regeneration");

        // Trigger immediate CSS regeneration for this page only
        $result = $this->regenerate_css_for_page($post_id);

        if ($result) {
            error_log("Horus: CSS regeneration completed for page $post_id");
        } else {
            error_log("Horus: CSS regeneration FAILED for page $post_id");
        }
    }

    /**
     * Regenerate CSS for a specific page
     */
    private function regenerate_css_for_page($post_id) {
        // Get CSS generator instance
        $css_generator = Horus_CSS_Generator::instance();

        // Regenerate CSS only for this page
        return $css_generator->regenerate_css_for_page($post_id);
    }

    /**
     * Regenerate CSS immediately (legacy - regenerates all)
     */
    private function regenerate_css_immediately() {
        // Get CSS generator instance
        $css_generator = Horus_CSS_Generator::instance();

        // Regenerate CSS
        $css_generator->regenerate_css();
    }

    /**
     * Schedule CSS regeneration (legacy method, keeping for compatibility)
     */
    private function schedule_css_regeneration($post_id) {
        // Use WordPress transients to avoid immediate regeneration
        // This allows batching multiple saves
        set_transient('horus_needs_css_regeneration', time(), 60);

        // Schedule regeneration if not already scheduled
        if (!wp_next_scheduled('horus_regenerate_css')) {
            wp_schedule_single_event(time() + 30, 'horus_regenerate_css');
        }
    }

    /**
     * Get Tailwind classes from a specific page
     */
    public function get_page_tailwind_classes($post_id) {
        global $wpdb;

        $classes = array();

        // Query specific page Elementor data
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT meta_value
                FROM {$wpdb->postmeta}
                WHERE post_id = %d AND meta_key = '_elementor_data'",
                $post_id
            )
        );

        if ($result && $result->meta_value) {
            $data = json_decode($result->meta_value, true);
            if ($data) {
                $classes = $this->extract_tailwind_classes($data);
            }
        }

        // array_unique preserves keys, so we need array_values to reset them
        return array_values(array_unique($classes));
    }

    /**
     * Get all Tailwind classes from Elementor pages
     */
    public function get_all_tailwind_classes() {
        global $wpdb;

        $classes = array();

        // Query all Elementor data
        $results = $wpdb->get_results(
            "SELECT post_id, meta_value
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_elementor_data'",
            ARRAY_A
        );

        foreach ($results as $row) {
            $data = json_decode($row['meta_value'], true);
            if ($data) {
                $classes = array_merge($classes, $this->extract_tailwind_classes($data));
            }
        }

        // array_unique preserves keys, so we need array_values to reset them
        return array_values(array_unique($classes));
    }

    /**
     * Extract Tailwind classes from Elementor data
     * Reads from the native "CSS Classes" field (css_classes)
     */
    private function extract_tailwind_classes($data) {
        $classes = array();

        if (is_array($data)) {
            foreach ($data as $item) {
                if (isset($item['settings'])) {
                    // Extract from native CSS Classes field (without underscore)
                    if (!empty($item['settings']['css_classes'])) {
                        $extracted = explode(' ', $item['settings']['css_classes']);
                        $classes = array_merge($classes, $extracted);
                    }

                    // Also check with underscore (backwards compatibility)
                    if (!empty($item['settings']['_css_classes'])) {
                        $extracted = explode(' ', $item['settings']['_css_classes']);
                        $classes = array_merge($classes, $extracted);
                    }

                    // Also check custom_css for inline classes
                    if (!empty($item['settings']['custom_css'])) {
                        // Extract classes from custom CSS (look for class names)
                        preg_match_all('/\.([\w\-:\/\[\]]+)/', $item['settings']['custom_css'], $matches);
                        if (!empty($matches[1])) {
                            $classes = array_merge($classes, $matches[1]);
                        }
                    }
                }

                // Recursively check nested elements
                if (!empty($item['elements'])) {
                    $classes = array_merge($classes, $this->extract_tailwind_classes($item['elements']));
                }
            }
        }

        // Clean up classes (remove empty strings and trim)
        $classes = array_filter(array_map('trim', $classes));

        // Filter out invalid classes (keep anything that looks like a CSS class)
        // Accept ANY Tailwind pattern including arbitrary values with special characters
        $classes = array_filter($classes, function($class) {
            // Remove if empty or contains spaces (spaces separate classes)
            if (empty($class) || strpos($class, ' ') !== false) {
                return false;
            }

            // Accept any string that could be a valid CSS class name
            // This includes:
            // - Arbitrary values: [grid-area:title], [100px], etc.
            // - Important prefix: !font-bold, !bg-red-500
            // - Responsive/states: lg:flex, hover:bg-blue-500
            // - Negatives: -mt-4
            // Just ensure it doesn't start with a number (unless prefixed with ! or -)
            return preg_match('/^[!a-zA-Z_\-\[\]:\.\/\'\"\(\)#,%0-9]+$/', $class) &&
                   !preg_match('/^[0-9]/', $class); // Don't start with number
        });

        // Reset array keys to 0, 1, 2, 3... so JSON encodes as array not object
        return array_values($classes);
    }
}
