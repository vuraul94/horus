<?php
/**
 * Investigate how Elementor stores CSS Classes
 */

require_once('../../../wp-load.php');

header('Content-Type: text/plain; charset=utf-8');

echo "Investigating Elementor CSS Classes Storage\n";
echo "============================================\n\n";

global $wpdb;

// Get a recent Elementor page
$result = $wpdb->get_row(
    "SELECT post_id, meta_value
    FROM {$wpdb->postmeta}
    WHERE meta_key = '_elementor_data'
    ORDER BY post_id DESC
    LIMIT 1"
);

if (!$result) {
    die("No Elementor data found\n");
}

$post_id = $result->post_id;
$post_title = get_the_title($post_id);
echo "Page: $post_title (ID: $post_id)\n";
echo str_repeat('=', 60) . "\n\n";

// Decode the data
$data = json_decode($result->meta_value, true);

if (!is_array($data)) {
    die("Invalid Elementor data\n");
}

// Function to recursively search for CSS classes
function search_settings($items, $path = '', $level = 0) {
    $indent = str_repeat('  ', $level);

    foreach ($items as $key => $item) {
        if (!is_array($item)) continue;

        $current_path = $path ? "$path[$key]" : "[$key]";

        if (isset($item['settings']) && is_array($item['settings'])) {
            echo "{$indent}Element at $current_path:\n";
            echo "{$indent}Type: " . ($item['elType'] ?? 'unknown') . "\n";

            // Show ALL settings keys
            echo "{$indent}Settings keys: " . implode(', ', array_keys($item['settings'])) . "\n";

            // Check for CSS-related fields
            $css_fields = array_filter(array_keys($item['settings']), function($key) {
                return stripos($key, 'css') !== false || stripos($key, 'class') !== false;
            });

            if (!empty($css_fields)) {
                echo "{$indent}CSS-related fields found:\n";
                foreach ($css_fields as $field) {
                    $value = $item['settings'][$field];
                    if (!is_array($value)) {
                        echo "{$indent}  - $field: $value\n";
                    } else {
                        echo "{$indent}  - $field: [array]\n";
                    }
                }
            }

            echo "\n";
        }

        // Recurse into elements
        if (isset($item['elements']) && is_array($item['elements'])) {
            search_settings($item['elements'], $current_path . '[elements]', $level + 1);
        }
    }
}

echo "Searching for CSS Classes fields...\n";
echo str_repeat('-', 60) . "\n\n";

search_settings($data);

echo "\n" . str_repeat('=', 60) . "\n";
echo "Complete JSON structure (first element):\n";
echo str_repeat('=', 60) . "\n";
echo json_encode($data[0] ?? [], JSON_PRETTY_PRINT);
