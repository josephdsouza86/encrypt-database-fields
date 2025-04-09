<?php
/**
 * Plugin Name: Encrypt Database Fields
 * Plugin URI: https://github.com/josephdsouza86/encrypt-database-fields
 * Description: Adds encryption functionality to ACF and user meta fields.
 * Version: 1.0.0
 * Author: Joseph D'Souza
 * Author URI: https://github.com/josephdsouza86
 * License: GPL-2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define a shared global value for encryption prefix
define('ENCRYPT_DB_FIELDS_PREFIX', '_edbf_');

if (!defined('ENCRYPT_DB_FIELDS_SECRET_KEY')) {
    // Indicate a warning if the secret key is not defined
    trigger_error('ENCRYPT_DB_FIELDS_SECRET_KEY is not defined. Please define a secret key in your wp-config.php file.', E_USER_WARNING);
}

// Autoload classes
spl_autoload_register(function ($class) {
    $prefix = 'EncryptDatabaseFields\\';
    $base_dir = __DIR__ . '/includes/';
    $len = strlen($prefix);

    // Check if the class belongs to our namespace
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Convert PascalCase to kebab-case for the file name
    $file_name = str_replace("--", "-", 'class-' . strtolower(preg_replace('/([A-Z])([a-z])/', '-$1$2', $relative_class))) . '.php';

    // Construct the full file path
    $file = $base_dir . $file_name;

    // Require the file if it exists
    if (file_exists($file)) {
        require_once $file;
    }
});

// Initialize the plugin components
new EncryptDatabaseFields\ACFEncryption();
$userMetaEncryption = new EncryptDatabaseFields\UserMetaEncryption();
new EncryptDatabaseFields\WooCommerce($userMetaEncryption);