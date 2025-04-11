<?php
/**
 * Plugin Name: Auditech - Weather Forcast Block
 * Plugin URI: 
 * Description: Displays current weather forecast as a Gutenberg block
 * Version: 1.0.0
 * Author: Adam Carter
 * Author URI: https://auditechconsult.com
 * Text Domain: weather-forecast
 */

if (!defined('WPINC')) {
    die;
}

// Load Composer autoloader
$autoloader = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
} else {
    // Log error or display admin notice
    add_action('admin_notices', function() {
        echo '<div class="error"><p>Weather Forecast Block: Composer dependencies missing. Please run <code>composer install</code> or contact the plugin author.</p></div>';
    });
    return; // Prevent plugin from initializing
}

// Load environment variables from .env file if it exists
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Initialize the plugin
function weather_forecast_init() {
    return \AuditechConsult\WeatherForecast\Plugin::get_instance();
}

// Start the plugin
weather_forecast_init();
