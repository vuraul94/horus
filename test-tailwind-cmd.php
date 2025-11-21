<?php
/**
 * Test Tailwind command execution
 */

$horus_path = __DIR__ . DIRECTORY_SEPARATOR;
$is_windows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
$tailwind_cli = $horus_path . 'node_modules/.bin/tailwindcss' . ($is_windows ? '.cmd' : '');

header('Content-Type: text/plain');

echo "Tailwind CLI Test\n";
echo str_repeat('=', 60) . "\n\n";

echo "OS: " . PHP_OS . "\n";
echo "Is Windows: " . ($is_windows ? 'YES' : 'NO') . "\n";
echo "Horus Path: " . $horus_path . "\n";
echo "Tailwind CLI: " . $tailwind_cli . "\n";
echo "CLI Exists: " . (file_exists($tailwind_cli) ? 'YES' : 'NO') . "\n\n";

if (!file_exists($tailwind_cli)) {
    die("ERROR: Tailwind CLI not found!\n");
}

echo str_repeat('-', 60) . "\n";
echo "Testing Tailwind CLI execution...\n";
echo str_repeat('-', 60) . "\n\n";

// Test 1: Version check
echo "Test 1: Version check\n";
$command = '"' . $tailwind_cli . '" --help 2>&1';
echo "Command: " . $command . "\n";
$output = shell_exec($command);
echo "Output:\n" . substr($output, 0, 300) . "\n\n";

// Test 2: Simple build
echo str_repeat('-', 60) . "\n";
echo "Test 2: Simple CSS build\n";
echo str_repeat('-', 60) . "\n\n";

$input_css = $horus_path . 'assets/css/input.css';
$output_css = $horus_path . 'assets/css/test-output.css';

echo "Input: " . $input_css . "\n";
echo "Output: " . $output_css . "\n";
echo "Input exists: " . (file_exists($input_css) ? 'YES' : 'NO') . "\n\n";

if (!file_exists($input_css)) {
    echo "Creating input.css...\n";
    $css_content = "@tailwind base;\n@tailwind components;\n@tailwind utilities;";
    file_put_contents($input_css, $css_content);
    echo "Created!\n\n";
}

$command = sprintf(
    '"%s" -i "%s" -o "%s" --minify 2>&1',
    $tailwind_cli,
    $input_css,
    $output_css
);

echo "Command: " . $command . "\n\n";
echo "Executing...\n";

$output = shell_exec($command);

echo "Shell output:\n";
echo $output . "\n\n";

if (file_exists($output_css)) {
    $size = filesize($output_css);
    echo "✓ SUCCESS! CSS file created\n";
    echo "Size: " . round($size/1024, 2) . " KB (" . $size . " bytes)\n";

    echo "\nFirst 500 characters:\n";
    echo str_repeat('-', 60) . "\n";
    echo substr(file_get_contents($output_css), 0, 500) . "\n";
    echo str_repeat('-', 60) . "\n";
} else {
    echo "✗ FAILED! CSS file was NOT created\n";
    echo "Expected at: " . $output_css . "\n";
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "Test complete\n";
