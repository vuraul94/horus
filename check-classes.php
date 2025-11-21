<?php
/**
 * Check what classes Horus found
 * Visit: /wp-content/plugins/horus/check-classes.php
 */

// Load WordPress
$wp_load = '../../../wp-load.php';
if (file_exists($wp_load)) {
    require_once($wp_load);
} else {
    die("WordPress not found");
}

// Check Horus is active
if (!defined('HORUS_VERSION')) {
    die("Horus not active");
}

header('Content-Type: text/plain; charset=utf-8');

echo "Horus - CSS Classes Detection Test\n";
echo "===================================\n\n";

// Get Elementor integration
$elementor = Horus_Elementor_Integration::instance();
$classes = $elementor->get_all_tailwind_classes();

echo "Total classes found: " . count($classes) . "\n\n";

if (empty($classes)) {
    echo "❌ No classes found!\n\n";
    echo "Steps to add classes:\n";
    echo "1. Open Elementor editor\n";
    echo "2. Select any widget\n";
    echo "3. Go to Advanced > CSS Classes\n";
    echo "4. Add: bg-blue-500 text-white p-4\n";
    echo "5. Click Update\n";
    echo "6. Refresh this page\n";
} else {
    echo "✅ Classes found:\n";
    echo "-------------------\n";
    foreach ($classes as $class) {
        echo "  - $class\n";
    }

    echo "\n📋 Safelist file:\n";
    $safelist = HORUS_PATH . 'assets/safelist.txt';
    if (file_exists($safelist)) {
        echo file_get_contents($safelist);
    } else {
        echo "(not generated yet)\n";
    }

    echo "\n\n✅ Now run: cd wp-content/plugins/horus && npm run build\n";
}
