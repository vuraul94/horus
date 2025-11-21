<?php
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

header('Content-Type: text/plain; charset=utf-8');

echo "Testing Safelist Generation\n";
echo str_repeat('=', 70) . "\n\n";

// Get classes
$elementor_integration = Horus_Elementor_Integration::instance();
$classes = $elementor_integration->get_page_tailwind_classes(7);

echo "Classes extracted: " . count($classes) . "\n";
echo str_repeat('-', 70) . "\n";
foreach ($classes as $i => $class) {
    echo ($i + 1) . ". $class\n";
}

echo "\n" . str_repeat('=', 70) . "\n";
echo "JSON encoded safelist:\n";
echo str_repeat('-', 70) . "\n";

$safelist_json = json_encode($classes, JSON_PRETTY_PRINT);
echo $safelist_json . "\n";

echo "\n" . str_repeat('=', 70) . "\n";
echo "Tailwind config preview:\n";
echo str_repeat('-', 70) . "\n";

$config = <<<JS
module.exports = {
  content: [],
  safelist: {$safelist_json},
  important: '.elementor',
  theme: { extend: {} },
  plugins: [],
}
JS;

echo $config . "\n";

echo "\n" . str_repeat('=', 70) . "\n";
echo "Potential issues:\n";
echo str_repeat('-', 70) . "\n";

// Check for problematic characters
$problematic = [];
foreach ($classes as $class) {
    if (strpos($class, "'") !== false) {
        $problematic[] = "$class - contains single quotes";
    }
    if (strpos($class, ":") !== false && strpos($class, "grid-area") !== false) {
        $problematic[] = "$class - complex arbitrary value with colon";
    }
}

if (empty($problematic)) {
    echo "✓ No obvious issues detected\n";
} else {
    echo "Found potential issues:\n";
    foreach ($problematic as $issue) {
        echo "  ⚠ $issue\n";
    }
}
