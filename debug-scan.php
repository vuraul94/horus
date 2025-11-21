<?php
/**
 * Debug: Scan database for CSS classes
 */

require_once('../../../wp-load.php');

header('Content-Type: text/plain; charset=utf-8');

echo "Horus - Debug CSS Classes Scan\n";
echo "================================\n\n";

global $wpdb;

// Get all Elementor data
$results = $wpdb->get_results(
    "SELECT post_id, meta_value
    FROM {$wpdb->postmeta}
    WHERE meta_key = '_elementor_data'
    ORDER BY post_id DESC
    LIMIT 10"
);

echo "Found " . count($results) . " Elementor pages\n\n";

foreach ($results as $row) {
    $data = json_decode($row->meta_value, true);

    if (!is_array($data)) {
        continue;
    }

    $post_title = get_the_title($row->post_id);
    echo "Page: $post_title (ID: {$row->post_id})\n";
    echo str_repeat('-', 60) . "\n";

    $found_classes = false;

    // Recursive function to find classes
    $find_classes = function($items, $level = 0) use (&$find_classes, &$found_classes) {
        $indent = str_repeat('  ', $level);

        foreach ($items as $item) {
            if (isset($item['settings']['_css_classes']) && !empty($item['settings']['_css_classes'])) {
                $found_classes = true;
                $widget_type = $item['widgetType'] ?? $item['elType'] ?? 'unknown';
                echo "{$indent}Widget: $widget_type\n";
                echo "{$indent}Classes: {$item['settings']['_css_classes']}\n";
            }

            if (isset($item['elements']) && is_array($item['elements'])) {
                $find_classes($item['elements'], $level + 1);
            }
        }
    };

    $find_classes($data);

    if (!$found_classes) {
        echo "  (No CSS classes found)\n";
    }

    echo "\n";
}

echo "\n";
echo "Now testing the Elementor Integration scanner...\n";
echo str_repeat('=', 60) . "\n\n";

if (class_exists('Horus_Elementor_Integration')) {
    $elementor = Horus_Elementor_Integration::instance();
    $classes = $elementor->get_all_tailwind_classes();

    echo "Scanner found " . count($classes) . " Tailwind classes:\n";
    foreach ($classes as $class) {
        echo "  - $class\n";
    }
} else {
    echo "Horus_Elementor_Integration class not found!\n";
}
