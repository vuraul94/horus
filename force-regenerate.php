<?php
/**
 * Force CSS Regeneration
 * Visit: http://go-seguros.local/wp-content/plugins/horus/force-regenerate.php
 */

// Load WordPress
require_once('../../../wp-load.php');

if (!defined('HORUS_VERSION')) {
    die('Horus not active');
}

header('Content-Type: text/plain; charset=utf-8');

echo "Horus - Force CSS Regeneration\n";
echo "================================\n\n";

// Get instances
$elementor = Horus_Elementor_Integration::instance();
$generator = Horus_CSS_Generator::instance();

// Scan for classes
echo "1. Scanning Elementor pages...\n";
$classes = $elementor->get_all_tailwind_classes();

echo "   Found " . count($classes) . " classes\n\n";

if (!empty($classes)) {
    echo "2. Classes found:\n";
    foreach ($classes as $class) {
        echo "   - $class\n";
    }
    echo "\n";
}

// Regenerate
echo "3. Regenerating CSS...\n";
$generator->regenerate_css();

// Check safelist
$safelist_path = HORUS_PATH . 'assets/safelist.txt';
if (file_exists($safelist_path)) {
    $safelist_content = file_get_contents($safelist_path);
    echo "   Safelist updated (" . strlen($safelist_content) . " bytes)\n\n";
}

// Build with Tailwind CLI
echo "4. Building with Tailwind CLI...\n";
$cwd = HORUS_PATH;
$output = shell_exec("cd " . escapeshellarg($cwd) . " && npm run build 2>&1");
echo $output . "\n";

// Check result
$css_path = HORUS_PATH . 'assets/css/tailwind-generated.css';
if (file_exists($css_path)) {
    $size = filesize($css_path);
    $size_kb = round($size / 1024, 2);
    echo "✅ CSS generated: $size_kb KB\n";
    echo "\n";
    echo "Now refresh http://go-seguros.local/ and the styles should appear!\n";
} else {
    echo "❌ CSS generation failed\n";
}
