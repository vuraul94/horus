<?php
/**
 * Debug class extraction
 */

require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

header('Content-Type: text/plain; charset=utf-8');

echo "Horus - Class Extraction Debug\n";
echo str_repeat('=', 70) . "\n\n";

global $wpdb;

// Get first Elementor page
$page = $wpdb->get_row(
    "SELECT p.ID, p.post_title
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
    WHERE pm.meta_key = '_elementor_edit_mode' AND pm.meta_value = 'builder'
    ORDER BY p.ID ASC
    LIMIT 1"
);

if (!$page) {
    die("No Elementor pages found!\n");
}

echo "Testing with: {$page->post_title} (ID: {$page->ID})\n";
echo str_repeat('-', 70) . "\n\n";

// Get raw Elementor data
$elementor_data = get_post_meta($page->ID, '_elementor_data', true);

if (!$elementor_data) {
    die("No _elementor_data found for this page!\n");
}

echo "1. Raw Elementor data length: " . strlen($elementor_data) . " characters\n\n";

// Decode
$data = json_decode($elementor_data, true);

if (!$data) {
    die("Failed to decode JSON!\n");
}

echo "2. JSON decoded successfully. Array has " . count($data) . " elements\n\n";

// Extract classes using Horus method
$elementor_integration = Horus_Elementor_Integration::instance();
$classes = $elementor_integration->get_page_tailwind_classes($page->ID);

echo "3. Classes extracted by Horus:\n";
echo str_repeat('-', 70) . "\n";

if (empty($classes)) {
    echo "   ❌ NO CLASSES FOUND!\n\n";
    echo "   Searching for CSS Classes fields in raw data...\n";
    echo str_repeat('-', 70) . "\n";

    // Manual search
    function search_for_classes($items, $depth = 0) {
        $found = [];
        $indent = str_repeat('  ', $depth);

        if (!is_array($items)) return $found;

        foreach ($items as $key => $item) {
            if (!is_array($item)) continue;

            if (isset($item['settings'])) {
                // Check both field names
                if (!empty($item['settings']['css_classes'])) {
                    echo "{$indent}✓ Found css_classes: {$item['settings']['css_classes']}\n";
                    $found[] = $item['settings']['css_classes'];
                }
                if (!empty($item['settings']['_css_classes'])) {
                    echo "{$indent}✓ Found _css_classes: {$item['settings']['_css_classes']}\n";
                    $found[] = $item['settings']['_css_classes'];
                }
            }

            // Recurse
            if (!empty($item['elements'])) {
                $found = array_merge($found, search_for_classes($item['elements'], $depth + 1));
            }
        }

        return $found;
    }

    $manual_classes = search_for_classes($data);

    if (empty($manual_classes)) {
        echo "\n   ❌ No CSS Classes fields found in Elementor data!\n";
        echo "   → You need to add classes in Elementor first\n";
        echo "   → Go to any element → Advanced tab → CSS Classes\n";
    } else {
        echo "\n   ✓ Found " . count($manual_classes) . " fields with classes\n";
    }
} else {
    echo "   ✓ Found " . count($classes) . " classes:\n\n";
    foreach ($classes as $i => $class) {
        echo "   " . ($i + 1) . ". " . $class . "\n";
        if ($i >= 19) {
            echo "   ... and " . (count($classes) - 20) . " more\n";
            break;
        }
    }

    // Check if bg-purple-200 is there
    echo "\n";
    if (in_array('bg-purple-200', $classes)) {
        echo "   ✓ bg-purple-200 IS in the list!\n";
    } else {
        echo "   ⚠ bg-purple-200 NOT in the list\n";
    }
}

echo "\n" . str_repeat('=', 70) . "\n";
echo "Debug complete\n";
