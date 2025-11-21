<?php
/**
 * Test page-specific CSS generation
 */

require_once('../../../wp-load.php');

header('Content-Type: text/plain; charset=utf-8');

echo "Testing Page-Specific CSS Generation\n";
echo "=====================================\n\n";

// Get home page ID
$home_page_id = get_option('page_on_front');

if (!$home_page_id) {
    // Try to find an Elementor page
    global $wpdb;
    $home_page_id = $wpdb->get_var(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_data' ORDER BY post_id ASC LIMIT 1"
    );
}

if (!$home_page_id) {
    die("No Elementor pages found\n");
}

$post_title = get_the_title($home_page_id);
echo "Testing with: $post_title (ID: $home_page_id)\n";
echo str_repeat('=', 60) . "\n\n";

// Get Elementor integration instance
$elementor_integration = Horus_Elementor_Integration::instance();

// Extract classes from this page
echo "Step 1: Extracting Tailwind classes from page...\n";
$classes = $elementor_integration->get_page_tailwind_classes($home_page_id);

if (empty($classes)) {
    echo "  ❌ No Tailwind classes found!\n\n";
} else {
    echo "  ✓ Found " . count($classes) . " classes\n";
    echo "  First 10 classes: " . implode(', ', array_slice($classes, 0, 10)) . "\n\n";
}

// Get CSS generator instance
$css_generator = Horus_CSS_Generator::instance();

// Trigger CSS generation
echo "Step 2: Generating page-specific CSS...\n";
$result = $css_generator->regenerate_css_for_page($home_page_id);

if ($result) {
    echo "  ✓ CSS generation completed\n\n";
} else {
    echo "  ⚠ CSS generation returned false\n\n";
}

// Check if CSS file exists
echo "Step 3: Checking generated CSS file...\n";
$css_path = $css_generator->get_page_css_path($home_page_id);
$css_url = $css_generator->get_page_css_url($home_page_id);

echo "  Path: $css_path\n";
echo "  URL: $css_url\n";

if (file_exists($css_path)) {
    $size_bytes = filesize($css_path);
    $size_kb = round($size_bytes / 1024, 2);
    echo "  ✓ CSS file exists\n";
    echo "  Size: {$size_kb} KB ({$size_bytes} bytes)\n\n";

    // Show first few lines of the CSS
    echo "  First 500 characters of CSS:\n";
    echo "  " . str_repeat('-', 58) . "\n";
    $css_content = file_get_contents($css_path);
    echo "  " . substr($css_content, 0, 500) . "...\n";
    echo "  " . str_repeat('-', 58) . "\n\n";

    // Check if bg-purple-200 is in there
    if (strpos($css_content, 'bg-purple-200') !== false) {
        echo "  ✓ bg-purple-200 class found in CSS!\n";
    } else {
        echo "  ⚠ bg-purple-200 NOT found in CSS\n";
    }
} else {
    echo "  ❌ CSS file NOT created\n\n";
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "Test Complete\n";
echo "Visit http://go-seguros.local/ and check page source for:\n";
echo "<!-- Horus: Using page-specific CSS (Page ID: {$home_page_id}) -->\n";
