<?php
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

header('Content-Type: text/plain; charset=utf-8');

echo "Quick Class Check - Page 7\n";
echo str_repeat('=', 60) . "\n\n";

$elementor_integration = Horus_Elementor_Integration::instance();
$classes = $elementor_integration->get_page_tailwind_classes(7);

echo "Total classes extracted: " . count($classes) . "\n\n";

if (empty($classes)) {
    echo "❌ NO CLASSES FOUND!\n";
    echo "This means either:\n";
    echo "1. You haven't saved the page yet\n";
    echo "2. The CSS Classes field is empty\n";
    echo "3. There's an extraction error\n";
} else {
    echo "✓ Classes found:\n";
    echo str_repeat('-', 60) . "\n";
    foreach ($classes as $i => $class) {
        echo ($i + 1) . ". $class\n";
    }

    echo "\n" . str_repeat('-', 60) . "\n";

    // Check specific classes
    $check = ['grid', 'flex', 'flex-col', 'bg-green-200'];
    echo "\nChecking specific classes:\n";
    foreach ($check as $c) {
        $found = in_array($c, $classes);
        echo ($found ? '  ✓' : '  ✗') . " $c\n";
    }
}
