<?php

namespace AuditechConsult\WeatherForecast;

/**
 * Main plugin class
 */
class Plugin {
    /**
     * Plugin version
     */
    const VERSION = '1.0.0';

    /**
     * The single instance of the class
     */
    private static $instance = null;

    /**
     * Main plugin path
     */
    private $plugin_path;

    /**
     * Main plugin URL
     */
    private $plugin_url;
    
    /**
     * WeatherAPI instance
     */
    private $weather_api;
    
    /**
     * NounProjectAPI instance
     */
    private $noun_project_api;

    /**
     * Get singleton instance
     *
     * @return Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->plugin_path = \plugin_dir_path(\dirname(__FILE__));
        $this->plugin_url = \plugin_dir_url(\dirname(__FILE__));
        
        // Initialize the APIs
        $this->weather_api = new WeatherAPI();
        $this->noun_project_api = new NounProjectAPI();

        $this->define_constants();
        $this->register_hooks();
    }

    /**
     * Define plugin constants
     */
    private function define_constants() {
        \define('WEATHER_FORECAST_VERSION', self::VERSION);
        \define('WEATHER_FORECAST_PLUGIN_DIR', $this->plugin_path);
        \define('WEATHER_FORECAST_PLUGIN_URL', $this->plugin_url);
    }

    /**
     * Register all hooks and actions
     */
    private function register_hooks() {
        // Register scripts and styles
        \add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        \add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_scripts'));
        
        // Register Gutenberg block
        \add_action('init', array($this, 'register_block'));
        
        // Register shortcode (for backward compatibility)
        \add_shortcode('weather_forecast', array($this, 'shortcode_callback'));
        
        // AJAX handlers
        \add_action('wp_ajax_weather_forecast_get_current', array($this, 'get_current_temp'));
        \add_action('wp_ajax_nopriv_weather_forecast_get_current', array($this, 'get_current_temp'));
        
        // New AJAX handler for Noun Project icons
        \add_action('wp_ajax_weather_forecast_get_icon', array($this, 'get_weather_icon'));
        \add_action('wp_ajax_nopriv_weather_forecast_get_icon', array($this, 'get_weather_icon'));
        
        // Register activation and deactivation hooks
        \register_activation_hook(\dirname(__DIR__) . '/index.php', array($this, 'activate'));
        \register_deactivation_hook(\dirname(__DIR__) . '/index.php', array($this, 'deactivate'));
    }

    /**
     * Enqueue scripts and styles for the frontend
     */
    public function enqueue_frontend_scripts() {
        // Only enqueue on frontend if the shortcode is used or the block is present
        if (!\has_shortcode(\get_post_field('post_content', \get_the_ID()), 'weather_forecast') && 
            !\has_block('auditech/weather-forecast')) {
            return;
        }
        
        // Check if build files exist
        $script_path = WEATHER_FORECAST_PLUGIN_DIR . 'build/js/weather-widget.js';
        $style_path = WEATHER_FORECAST_PLUGIN_DIR . 'build/css/style.css';
        
        if (!\file_exists($script_path) || !\file_exists($style_path)) {
            return;
        }
        
        // Enqueue our plugin script
        \wp_enqueue_script(
            'weather-forecast-widget',
            WEATHER_FORECAST_PLUGIN_URL . 'build/js/weather-widget.js',
            array('react', 'react-dom', 'jquery'),
            \filemtime($script_path), // Use file modification time for cache busting
            true
        );
        
        // Enqueue styles
        \wp_enqueue_style(
            'weather-forecast-style',
            WEATHER_FORECAST_PLUGIN_URL . 'build/css/style.css',
            array(),
            \filemtime($style_path) // Use file modification time for cache busting
        );
        
        // Localize script with necessary data
        \wp_localize_script(
            'weather-forecast-widget',
            'weatherForecastData',
            array(
                'ajaxurl' => \admin_url('admin-ajax.php'),
                'nonce' => \wp_create_nonce('weather_forecast_nonce'),
                'initialWeather' => $this->get_cached_forecast(),
                'useNounProject' => !empty($_ENV['NOUN_PROJECT_API_KEY']) && !empty($_ENV['NOUN_PROJECT_SECRET_KEY'])
            )
        );
    }

    /**
     * Enqueue scripts and styles for the block editor
     */
    public function enqueue_editor_scripts() {
        // Check if build files exist
        $script_path = WEATHER_FORECAST_PLUGIN_DIR . 'build/js/block.js';
        $style_path = WEATHER_FORECAST_PLUGIN_DIR . 'build/css/style.css';
        
        if (!\file_exists($script_path) || !\file_exists($style_path)) {
            return;
        }
        
        // Enqueue block editor assets
        \wp_enqueue_script(
            'weather-forecast-block',
            WEATHER_FORECAST_PLUGIN_URL . 'build/js/block.js',
            array('wp-blocks', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-element'),
            \filemtime($script_path), // Use file modification time for cache busting
            true
        );
        
        // Enqueue styles for editor
        \wp_enqueue_style(
            'weather-forecast-editor-style',
            WEATHER_FORECAST_PLUGIN_URL . 'build/css/style.css',
            array(),
            \filemtime($style_path) // Use file modification time for cache busting
        );
        
        // Localize script with data for editor
        \wp_localize_script(
            'weather-forecast-block',
            'weatherForecastData',
            array(
                'ajaxurl' => \admin_url('admin-ajax.php'),
                'nonce' => \wp_create_nonce('weather_forecast_nonce'),
                'initialWeather' => $this->get_cached_forecast(),
                'useNounProject' => !empty($_ENV['NOUN_PROJECT_API_KEY']) && !empty($_ENV['NOUN_PROJECT_SECRET_KEY'])
            )
        );
    }

    /**
     * Register the Gutenberg block
     */
    public function register_block() {
        // Check if block.json exists
        $block_json_path = WEATHER_FORECAST_PLUGIN_DIR . 'block.json';
        
        if (!\file_exists($block_json_path)) {
            return;
        }
        
        // Register block type using metadata from block.json
        \register_block_type(
            $block_json_path,
            array(
                'render_callback' => array($this, 'render_block')
            )
        );
    }

    /**
     * Render callback for the block
     * 
     * @param array $attributes Block attributes
     * @return string Block HTML
     */
    public function render_block($attributes) {
        // Get location attribute if provided
        $location = isset($attributes['location']) ? \sanitize_text_field($attributes['location']) : null;
        
        // Class name from attributes
        $class_name = isset($attributes['className']) ? ' ' . \esc_attr($attributes['className']) : '';
        
        // Ensure scripts are enqueued
        \wp_enqueue_script('weather-forecast-widget');
        \wp_enqueue_style('weather-forecast-style');
        
        // Return container with both ID and class
        return '<div id="weather-forecast-widget" class="weather-forecast-container' . $class_name . '"></div>';
    }

    /**
     * Shortcode callback (for backward compatibility)
     * 
     * @param array $atts Shortcode attributes
     * @return string Shortcode HTML
     */
    public function shortcode_callback($atts) {
        $atts = \shortcode_atts(
            array(
                'location' => null,
                'class' => ''
            ),
            $atts,
            'weather_forecast'
        );
        
        // Ensure scripts are enqueued
        $this->enqueue_frontend_scripts();
        
        // Get location attribute if provided
        $location = $atts['location'] ? \sanitize_text_field($atts['location']) : null;
        
        // Update localized data with location-specific info if needed
        if ($location) {
            \wp_localize_script(
                'weather-forecast-widget',
                'weatherForecastData',
                array(
                    'ajaxurl' => \admin_url('admin-ajax.php'),
                    'nonce' => \wp_create_nonce('weather_forecast_nonce'),
                    'initialWeather' => $this->get_cached_forecast($location),
                    'location' => $location,
                    'useNounProject' => !empty($_ENV['NOUN_PROJECT_API_KEY']) && !empty($_ENV['NOUN_PROJECT_SECRET_KEY'])
                )
            );
        }
        
        $class = $atts['class'] ? ' ' . \esc_attr($atts['class']) : '';
        
        return '<div id="weather-forecast-widget" class="weather-forecast-container' . $class . '"></div>';
    }
    
    /**
     * Get cached forecast or fetch new data
     */
    public function get_cached_forecast($location = null) {
        return $this->weather_api->get_cached_forecast($location);
    }

    /**
     * AJAX handler for getting current weather
     */
    public function get_current_temp() {
        // Check nonce for security
        \check_ajax_referer('weather_forecast_nonce', 'security');
        
        // Get location or coordinates from request
        $location = isset($_POST['location']) ? \sanitize_text_field($_POST['location']) : null;
        $latitude = isset($_POST['latitude']) ? \floatval($_POST['latitude']) : null;
        $longitude = isset($_POST['longitude']) ? \floatval($_POST['longitude']) : null;
        
        // Determine which method to use based on provided data
        if ($latitude !== null && $longitude !== null) {
            // Get fresh data using coordinates (not cached)
            $weather_data = $this->weather_api->get_forecast_by_coordinates($latitude, $longitude);
            
            // Update cache with fresh data
            $transient_key = 'weather_forecast_coord_' . md5($latitude . '_' . $longitude);
            \set_transient($transient_key, $weather_data, 3 * HOUR_IN_SECONDS);
        } else {
            // Get fresh data using location (not cached)
            $weather_data = $this->weather_api->get_forecast($location);
            
            // Update cache with fresh data
            if ($location) {
                \set_transient('weather_forecast_' . \sanitize_title($location), $weather_data, 3 * HOUR_IN_SECONDS);
            } else {
                \set_transient('weather_forecast_data', $weather_data, 3 * HOUR_IN_SECONDS);
            }
        }
        
        // If Noun Project API is available, get an icon for the condition
        if (!empty($_ENV['NOUN_PROJECT_API_KEY']) && !empty($_ENV['NOUN_PROJECT_SECRET_KEY']) && !empty($weather_data['condition'])) {
            $icon_data = $this->noun_project_api->get_weather_icon($weather_data['condition'], null, 'svg');
            
            if ($icon_data && !empty($icon_data['icon_url'])) {
                $weather_data['noun_project_icon'] = $icon_data['icon_url'];
                $weather_data['noun_project_attribution'] = $icon_data['attribution'];
            }
        }
        
        \wp_send_json_success($weather_data);
    }
    
    /**
     * AJAX handler for getting weather icons from Noun Project
     */
    public function get_weather_icon() {
        // Check nonce for security
        \check_ajax_referer('weather_forecast_nonce', 'security');
        
        // Check if API credentials are available
        if (empty($_ENV['NOUN_PROJECT_API_KEY']) || empty($_ENV['NOUN_PROJECT_SECRET_KEY'])) {
            \wp_send_json_error(['message' => 'Noun Project API credentials not set']);
            return;
        }
        
        // Get parameters
        $condition = isset($_POST['condition']) ? \sanitize_text_field($_POST['condition']) : null;
        $color = isset($_POST['color']) ? \sanitize_text_field($_POST['color']) : null;
        $filetype = isset($_POST['filetype']) ? \sanitize_text_field($_POST['filetype']) : 'svg';
        $size = isset($_POST['size']) ? \intval($_POST['size']) : 84;
        
        if (!$condition) {
            \wp_send_json_error(['message' => 'Weather condition not provided']);
            return;
        }
        
        // Get icon for the weather condition
        $icon_data = $this->noun_project_api->get_weather_icon($condition, $color, $filetype, $size);
        
        if (!$icon_data || empty($icon_data['icon_url'])) {
            \wp_send_json_error(['message' => 'Failed to get icon for ' . $condition]);
            return;
        }
        
        \wp_send_json_success($icon_data);
    }

    /**
     * Plugin activation hook
     */
    public function activate() {
        // Trigger an initial API call to cache weather data
        $this->weather_api->get_cached_forecast();
    }
    
    /**
     * Plugin deactivation hook
     */
    public function deactivate() {
        // Clear all weather forecast transients
        global $wpdb;
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
                '_transient_weather_forecast_%'
            )
        );
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
                '_transient_timeout_weather_forecast_%'
            )
        );
        
        // Also clear Noun Project icon transients
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
                '_transient_noun_project_weather_%'
            )
        );
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
                '_transient_timeout_noun_project_weather_%'
            )
        );
    }
}