<?php
/**
 * Check CSS content
 */

require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

header('Content-Type: text/plain; charset=utf-8');

echo "Checking Generated CSS Content\n";
echo str_repeat('=', 70) . "\n\n";

// Get home page
$page_id = get_option('page_on_front');

if (!$page_id) {
    global $wpdb;
    $page_id = $wpdb->get_var(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_data' ORDER BY post_id ASC LIMIT 1"
    );
}

if (!$page_id) {
    die("No page found\n");
}

echo "Page ID: $page_id\n";
echo "Page URL: " . get_permalink($page_id) . "\n";
echo str_repeat('-', 70) . "\n\n";

// Check if CSS file exists
$css_generator = Horus_CSS_Generator::instance();
$css_path = $css_generator->get_page_css_path($page_id);
$css_url = $css_generator->get_page_css_url($page_id);

echo "CSS Path: $css_path\n";
echo "CSS URL: $css_url\n";

if (!file_exists($css_path)) {
    die("\n❌ CSS file does not exist!\n");
}

$size = filesize($css_path);
echo "File Size: " . round($size/1024, 2) . " KB ($size bytes)\n\n";

if ($size < 500) {
    die("❌ File is too small (< 500 bytes)\n");
}

// Read CSS content
$css_content = file_get_contents($css_path);

echo "CSS Content Check:\n";
echo str_repeat('-', 70) . "\n\n";

// Classes the user added
$test_classes = array(
    'flex',
    'flex-col',
    'lg:grid',
    'bg-green-200',
    'grid-cols-',
    'grid-rows-',
    'grid-template-areas'
);

echo "Checking for classes:\n";
foreach ($test_classes as $class) {
    $found = strpos($css_content, $class) !== false;
    $status = $found ? '✓' : '✗';
    echo "  $status $class\n";
}

echo "\n" . str_repeat('-', 70) . "\n";
echo "First 1000 characters of CSS:\n";
echo str_repeat('-', 70) . "\n";
echo substr($css_content, 0, 1000) . "\n...\n";

echo "\n" . str_repeat('-', 70) . "\n";
echo "Search for 'bg-green':\n";
echo str_repeat('-', 70) . "\n";

if (preg_match_all('/\.bg-green[^\s\{]*[^\}]*\}/s', $css_content, $matches)) {
    foreach ($matches[0] as $match) {
        echo $match . "\n";
    }
} else {
    echo "NOT FOUND\n";
}

echo "\n" . str_repeat('-', 70) . "\n";
echo "Check safelist file:\n";
echo str_repeat('-', 70) . "\n";

// Check what classes were in the safelist
$elementor_integration = Horus_Elementor_Integration::instance();
$extracted_classes = $elementor_integration->get_page_tailwind_classes($page_id);

echo "Classes extracted: " . count($extracted_classes) . "\n\n";
echo "First 30 classes:\n";
foreach (array_slice($extracted_classes, 0, 30) as $i => $class) {
    echo "  " . ($i + 1) . ". $class\n";
}

if (in_array('bg-green-200', $extracted_classes)) {
    echo "\n✓ bg-green-200 IS in extracted classes\n";
} else {
    echo "\n✗ bg-green-200 NOT in extracted classes\n";
}

echo "\n" . str_repeat('=', 70) . "\n";
echo "Check complete\n";
