<?php
/**
 * Quick database check - can be run from command line
 */

// Set up minimal WordPress environment
define('SHORTINIT', true);
require_once('../../../wp-load.php');

// Manually load wpdb
require_once('../../../wp-includes/wp-db.php');
require_once('../../../wp-includes/version.php');

global $wpdb;

// Define table prefix
$table_prefix = 'wp_';
$wpdb = new wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
$wpdb->set_prefix($table_prefix);

echo "Quick Database Check\n";
echo "====================\n\n";

// Get an Elementor page
$result = $wpdb->get_row(
    "SELECT p.ID, p.post_title, pm.meta_value
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
    WHERE pm.meta_key = '_elementor_data'
    ORDER BY p.ID ASC
    LIMIT 1"
);

if (!$result) {
    die("No Elementor pages found\n");
}

echo "Page: {$result->post_title} (ID: {$result->ID})\n";
echo str_repeat('=', 60) . "\n\n";

$data = json_decode($result->meta_value, true);

// Recursively search for css_classes
function find_css_classes($items, $level = 0) {
    $found = array();
    $indent = str_repeat('  ', $level);

    if (!is_array($items)) return $found;

    foreach ($items as $item) {
        if (!is_array($item)) continue;

        if (isset($item['settings'])) {
            // Check css_classes (without underscore)
            if (!empty($item['settings']['css_classes'])) {
                $classes = $item['settings']['css_classes'];
                echo "{$indent}✓ Found css_classes: $classes\n";
                $found = array_merge($found, explode(' ', $classes));
            }

            // Check _css_classes (with underscore - old format)
            if (!empty($item['settings']['_css_classes'])) {
                $classes = $item['settings']['_css_classes'];
                echo "{$indent}✓ Found _css_classes: $classes\n";
                $found = array_merge($found, explode(' ', $classes));
            }
        }

        // Recurse
        if (!empty($item['elements'])) {
            $found = array_merge($found, find_css_classes($item['elements'], $level + 1));
        }
    }

    return $found;
}

echo "Searching for CSS classes in Elementor data...\n";
echo str_repeat('-', 60) . "\n";

$all_classes = find_css_classes($data);
$all_classes = array_filter(array_map('trim', $all_classes));
$all_classes = array_unique($all_classes);

echo "\n" . str_repeat('=', 60) . "\n";
echo "Summary:\n";
echo "Total unique classes found: " . count($all_classes) . "\n\n";

if (count($all_classes) > 0) {
    echo "All classes:\n";
    foreach ($all_classes as $class) {
        echo "  - $class\n";
    }

    echo "\n";
    if (in_array('bg-purple-200', $all_classes)) {
        echo "✓ bg-purple-200 IS in the list!\n";
    } else {
        echo "✗ bg-purple-200 NOT in the list\n";
    }
} else {
    echo "No classes found!\n";
}
