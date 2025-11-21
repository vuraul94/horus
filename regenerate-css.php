#!/usr/bin/env php
<?php
/**
 * Horus - CSS Regeneration Script
 *
 * Usage: php regenerate-css.php
 * or from project root: php wp-content/plugins/horus/regenerate-css.php
 */

// Load WordPress
$wp_load_paths = array(
    __DIR__ . '/../../../wp-load.php',
    __DIR__ . '/../../../../wp-load.php',
);

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die("Error: Could not find wp-load.php\n");
}

// Check if Horus is active
if (!defined('HORUS_VERSION')) {
    die("Error: Horus plugin is not active\n");
}

echo "Horus CSS Regeneration Tool\n";
echo "============================\n\n";

// Get instances
$elementor_integration = Horus_Elementor_Integration::instance();
$css_generator = Horus_CSS_Generator::instance();

// Scan for Tailwind classes
echo "Scanning Elementor pages for Tailwind classes...\n";
$classes = $elementor_integration->get_all_tailwind_classes();

if (empty($classes)) {
    echo "No Tailwind classes found in Elementor pages.\n";
    echo "Make sure you've added Tailwind classes to your Elementor widgets.\n";
    exit(1);
}

echo "Found " . count($classes) . " unique Tailwind classes:\n";
echo "----------------------------------------\n";

// Group and display classes by category
$grouped = array();
foreach ($classes as $class) {
    $prefix = explode('-', $class)[0];
    if (!isset($grouped[$prefix])) {
        $grouped[$prefix] = array();
    }
    $grouped[$prefix][] = $class;
}

foreach ($grouped as $prefix => $prefix_classes) {
    echo "$prefix: " . implode(', ', array_slice($prefix_classes, 0, 5));
    if (count($prefix_classes) > 5) {
        echo " ... (+" . (count($prefix_classes) - 5) . " more)";
    }
    echo "\n";
}

echo "\n";

// Regenerate CSS
echo "Generating optimized CSS...\n";
$css_generator->regenerate_css();

// Check if CSS was generated
$css_path = HORUS_PATH . 'assets/css/tailwind-generated.css';
if (file_exists($css_path)) {
    $size = filesize($css_path);
    $size_kb = round($size / 1024, 2);
    echo "✓ CSS generated successfully!\n";
    echo "  File: $css_path\n";
    echo "  Size: $size_kb KB\n";

    // Try to build with Tailwind CLI if available
    echo "\nBuilding with Tailwind CLI...\n";
    $output = shell_exec("cd " . escapeshellarg(HORUS_PATH) . " && npm run build 2>&1");

    if ($output) {
        echo $output . "\n";

        // Check new size
        if (file_exists($css_path)) {
            $new_size = filesize($css_path);
            $new_size_kb = round($new_size / 1024, 2);
            echo "✓ Optimized CSS built!\n";
            echo "  Final size: $new_size_kb KB\n";
        }
    } else {
        echo "Note: Tailwind CLI not available. Using safelist generation only.\n";
        echo "To use Tailwind CLI, run: cd " . HORUS_PATH . " && npm install\n";
    }
} else {
    echo "✗ CSS generation failed\n";
    exit(1);
}

echo "\n✓ Done! Your Tailwind CSS is optimized and ready.\n";
echo "The frontend will now load only the classes you're using (" . count($classes) . " classes).\n";
