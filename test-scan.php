<?php
/**
 * Test scan script - Run this to test if scanning works
 */

// Load WordPress
require_once('../../../wp-load.php');

if (!defined('HORUS_VERSION')) {
    die("Horus plugin not active\n");
}

echo "Testing Horus CSS Scanning\n";
echo "==========================\n\n";

// Get Elementor integration instance
$elementor_integration = Horus_Elementor_Integration::instance();

// Scan for classes
echo "Scanning Elementor pages...\n";
$classes = $elementor_integration->get_all_tailwind_classes();

echo "Found " . count($classes) . " classes:\n\n";

if (empty($classes)) {
    echo "❌ No classes found!\n\n";
    echo "Possible reasons:\n";
    echo "1. No Elementor pages have Horus Tailwind classes\n";
    echo "2. You haven't added classes in the 'Tailwind CSS (Horus)' field\n";
    echo "3. You haven't saved the page after adding classes\n\n";
    echo "Solution:\n";
    echo "1. Open Elementor editor\n";
    echo "2. Select a widget\n";
    echo "3. Go to Advanced > Tailwind CSS (Horus)\n";
    echo "4. Add classes like: bg-blue-500 text-white p-4\n";
    echo "5. Click 'Update' to save\n";
    echo "6. Run this script again\n";
} else {
    foreach ($classes as $class) {
        echo "  - $class\n";
    }

    echo "\n✅ Classes found! Now regenerating CSS...\n";

    // Regenerate CSS
    $css_generator = Horus_CSS_Generator::instance();
    $css_generator->regenerate_css();

    echo "\n✅ Done! Check the safelist:\n";
    echo "cat wp-content/plugins/horus/assets/safelist.txt\n";
}
