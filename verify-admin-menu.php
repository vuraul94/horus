<?php
/**
 * Verify admin menu registration
 * Access via: http://go-seguros.local/wp-content/plugins/horus/verify-admin-menu.php
 */

require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied. Please log in as admin first.');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Horus - Admin Menu Verification</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
        a { color: #2271b1; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>🔍 Horus - Admin Menu Verification</h1>

    <?php
    // Check if plugin is active
    echo '<div class="box">';
    echo '<h2>1. Plugin Status</h2>';

    if (class_exists('Horus_CSS_Generator')) {
        echo '<p class="success">✓ Horus_CSS_Generator class exists</p>';
    } else {
        echo '<p class="error">✗ Horus_CSS_Generator class NOT found</p>';
    }

    if (class_exists('Horus_Tailwind_Integration')) {
        echo '<p class="success">✓ Horus_Tailwind_Integration class exists</p>';
    } else {
        echo '<p class="error">✗ Horus_Tailwind_Integration class NOT found</p>';
    }

    if (class_exists('Horus_Elementor_Integration')) {
        echo '<p class="success">✓ Horus_Elementor_Integration class exists</p>';
    } else {
        echo '<p class="error">✗ Horus_Elementor_Integration class NOT found</p>';
    }
    echo '</div>';

    // Check constants
    echo '<div class="box">';
    echo '<h2>2. Plugin Constants</h2>';

    if (defined('HORUS_VERSION')) {
        echo '<p class="success">✓ HORUS_VERSION: ' . HORUS_VERSION . '</p>';
    } else {
        echo '<p class="error">✗ HORUS_VERSION not defined</p>';
    }

    if (defined('HORUS_PATH')) {
        echo '<p class="success">✓ HORUS_PATH: ' . HORUS_PATH . '</p>';
    } else {
        echo '<p class="error">✗ HORUS_PATH not defined</p>';
    }
    echo '</div>';

    // Check if menu hook exists
    echo '<div class="box">';
    echo '<h2>3. Admin Menu Hooks</h2>';

    global $wp_filter;

    if (isset($wp_filter['admin_menu'])) {
        echo '<p class="success">✓ admin_menu hook exists</p>';

        // Check if our hook is registered
        $found = false;
        foreach ($wp_filter['admin_menu']->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                if (is_array($callback['function'])) {
                    $obj = $callback['function'][0];
                    $method = $callback['function'][1];

                    if ($obj instanceof Horus_CSS_Generator && $method === 'add_admin_menu') {
                        echo '<p class="success">✓ Horus_CSS_Generator::add_admin_menu is hooked (priority: ' . $priority . ')</p>';
                        $found = true;
                    }
                }
            }
        }

        if (!$found) {
            echo '<p class="error">✗ Horus admin_menu hook NOT found</p>';
        }
    } else {
        echo '<p class="error">✗ admin_menu hook does not exist</p>';
    }
    echo '</div>';

    // Direct links
    echo '<div class="box">';
    echo '<h2>4. Direct Access Links</h2>';
    echo '<p>Try these direct links:</p>';
    echo '<ul>';
    echo '<li><a href="' . admin_url('admin.php?page=horus-settings') . '" target="_blank">Horus Settings (Original)</a></li>';
    echo '<li><a href="' . admin_url('admin.php?page=horus-diagnostics') . '" target="_blank">Horus Diagnostics (New)</a></li>';
    echo '</ul>';
    echo '</div>';

    // Check for PHP errors
    echo '<div class="box">';
    echo '<h2>5. Error Check</h2>';

    $error_log = ini_get('error_log');
    if ($error_log && file_exists($error_log)) {
        echo '<p class="info">Error log location: ' . $error_log . '</p>';

        // Read last 20 lines
        $lines = array_slice(file($error_log), -20);
        $horus_errors = array_filter($lines, function($line) {
            return stripos($line, 'horus') !== false;
        });

        if (!empty($horus_errors)) {
            echo '<p class="error">Found Horus-related errors:</p>';
            echo '<pre>' . implode('', $horus_errors) . '</pre>';
        } else {
            echo '<p class="success">No Horus-related errors in recent log</p>';
        }
    } else {
        echo '<p class="info">Error log not found or not configured</p>';
    }
    echo '</div>';

    // Instructions
    echo '<div class="box">';
    echo '<h2>6. Troubleshooting Steps</h2>';
    echo '<ol>';
    echo '<li><strong>Deactivate and reactivate the plugin:</strong> Go to <a href="' . admin_url('plugins.php') . '">Plugins</a> and toggle Horus</li>';
    echo '<li><strong>Clear WordPress cache:</strong> If using a caching plugin, clear it</li>';
    echo '<li><strong>Check browser console:</strong> Open browser DevTools (F12) and check for JavaScript errors</li>';
    echo '<li><strong>Try the direct links above</strong></li>';
    echo '</ol>';
    echo '</div>';
    ?>
</body>
</html>
