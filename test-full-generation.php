<?php
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

header('Content-Type: text/plain; charset=utf-8');

echo "Full CSS Generation Test\n";
echo str_repeat('=', 70) . "\n\n";

// Step 1: Get classes
$elementor_integration = Horus_Elementor_Integration::instance();
$classes = $elementor_integration->get_page_tailwind_classes(7);

echo "Step 1: Classes extracted: " . count($classes) . "\n";
echo str_repeat('-', 70) . "\n";
$safelist_json = json_encode($classes, JSON_PRETTY_PRINT);
echo $safelist_json . "\n\n";

// Check if it's an array
$decoded = json_decode($safelist_json, true);
$is_array = isset($decoded[0]) && !isset($decoded["0"]);
echo "Is proper array (not object)? " . ($is_array ? "✓ YES" : "✗ NO") . "\n\n";

// Step 2: Create test config
echo str_repeat('=', 70) . "\n";
echo "Step 2: Creating test Tailwind config\n";
echo str_repeat('-', 70) . "\n";

$test_config_path = HORUS_PATH . 'test-tailwind.config.js';
$test_css_path = HORUS_PATH . 'assets/css/test-output.css';

$config_content = <<<JS
/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [],
  safelist: $safelist_json,
  important: '.elementor',
  theme: {
    extend: {},
  },
  plugins: [
    require('@savvywombat/tailwindcss-grid-areas'),
  ],
}
JS;

file_put_contents($test_config_path, $config_content);
echo "✓ Config created at: $test_config_path\n\n";

// Step 3: Try to generate CSS
echo str_repeat('=', 70) . "\n";
echo "Step 3: Running Tailwind CLI\n";
echo str_repeat('-', 70) . "\n";

$node_exe = 'C:\\Program Files\\nodejs\\node.exe';
$tailwind_js = HORUS_PATH . 'node_modules\\tailwindcss\\lib\\cli.js';
$input_css = HORUS_PATH . 'assets\\css\\input.css';

if (!file_exists($node_exe)) {
    echo "✗ Node.js not found at: $node_exe\n";
} else {
    echo "✓ Node.js found\n";
}

if (!file_exists($tailwind_js)) {
    echo "✗ Tailwind CLI not found at: $tailwind_js\n";
} else {
    echo "✓ Tailwind CLI found\n";
}

$command = sprintf(
    '"%s" "%s" -c "%s" -i "%s" -o "%s" 2>&1',
    $node_exe,
    $tailwind_js,
    $test_config_path,
    $input_css,
    $test_css_path
);

echo "\nCommand:\n$command\n\n";
echo "Executing...\n";
echo str_repeat('-', 70) . "\n";

$output = shell_exec($command);
echo $output . "\n";
echo str_repeat('-', 70) . "\n\n";

// Step 4: Check result
echo str_repeat('=', 70) . "\n";
echo "Step 4: Checking output\n";
echo str_repeat('-', 70) . "\n";

if (file_exists($test_css_path)) {
    $size = filesize($test_css_path);
    echo "✓ CSS file created: " . round($size/1024, 2) . " KB\n\n";

    if ($size < 500) {
        echo "⚠ File is too small - probably only base styles\n\n";
    }

    // Check for specific classes
    $css_content = file_get_contents($test_css_path);

    echo "Checking for classes in generated CSS:\n";
    $check_classes = ['flex', 'flex-col', 'relative', 'grid-cols', 'grid-area'];
    foreach ($check_classes as $check) {
        $found = strpos($css_content, $check) !== false;
        echo "  " . ($found ? '✓' : '✗') . " $check\n";
    }

    echo "\nLast 500 characters of CSS:\n";
    echo str_repeat('-', 70) . "\n";
    echo substr($css_content, -500) . "\n";
} else {
    echo "✗ CSS file was NOT created\n";
    echo "Expected at: $test_css_path\n";
}

// Cleanup
@unlink($test_config_path);
echo "\n" . str_repeat('=', 70) . "\n";
echo "Test complete\n";
