<?php

namespace AuditechConsult\WeatherForecast;

/**
 * Class to handle Noun Project API interactions
 */
class NounProjectAPI {
    /**
     * API key for Noun Project
     * 
     * @var string
     */
    private $api_key;
    
    /**
     * API secret for Noun Project
     * 
     * @var string
     */
    private $api_secret;
    
    /**
     * Base API URL
     * 
     * @var string
     */
    private $api_base_url = 'https://api.thenounproject.com/v2';
    
    /**
     * Constructor
     * 
     * @param string $api_key API key for Noun Project
     * @param string $api_secret API secret for Noun Project
     */
    public function __construct($api_key = null, $api_secret = null) {
        // Get API credentials from environment variables or parameters
        $this->api_key = $api_key ?: $_ENV['NOUN_PROJECT_API_KEY'] ?? '';
        $this->api_secret = $api_secret ?: $_ENV['NOUN_PROJECT_SECRET_KEY'] ?? '';
        
        // Check if credentials are set
        if (empty($this->api_key) || empty($this->api_secret)) {
            \error_log('Noun Project API credentials are not set');
        }
    }
    
    /**
     * Search for icons by term
     * 
     * @param string $term Search term
     * @param array $options Additional options (limit, offset, etc.)
     * @return array|false Array of icons or false on failure
     */
    public function search_icons($term, $options = []) {
        // Default options
        $default_options = [
            'limit' => 10,
            'thumbnail_size' => 84,
            'blacklist' => 1
        ];
        
        $options = \array_merge($default_options, $options);
        $options['query'] = $term;
        
        // Make request
        $endpoint = $this->api_base_url . '/icon';
        $result = $this->make_request($endpoint, $options);
        
        if (!$result || isset($result['error'])) {
            \error_log('Error searching icons: ' . \json_encode($result));
            return false;
        }
        
        return $result;
    }
    
    /**
     * Get a specific icon by ID
     * 
     * @param int $icon_id Icon ID
     * @param array $options Additional options
     * @return array|false Icon data or false on failure
     */
    public function get_icon($icon_id, $options = []) {
        // Default options
        $default_options = [
            'thumbnail_size' => 84,
            'blacklist' => 1
        ];
        
        $options = \array_merge($default_options, $options);
        
        // Make request
        $endpoint = $this->api_base_url . '/icon/' . $icon_id;
        $result = $this->make_request($endpoint, $options);
        
        if (!$result || isset($result['error'])) {
            \error_log('Error getting icon: ' . \json_encode($result));
            return false;
        }
        
        return $result;
    }
    
    /**
     * Download an icon with specific color
     * 
     * @param int $icon_id Icon ID
     * @param string $color Hex color (without #)
     * @param string $filetype File type (svg or png)
     * @param int $size Size for PNG (20-1200)
     * @return array|false Icon data or false on failure
     */
    public function download_icon($icon_id, $color = null, $filetype = 'svg', $size = null) {
        $options = [];
        
        if ($color) {
            $options['color'] = $color;
        }
        
        $options['filetype'] = $filetype;
        
        if ($filetype === 'png' && $size) {
            $options['size'] = \max(20, \min(1200, (int) $size));
        }
        
        // Make request
        $endpoint = $this->api_base_url . '/icon/' . $icon_id . '/download';
        $result = $this->make_request($endpoint, $options);
        
        if (!$result || isset($result['error'])) {
            \error_log('Error downloading icon: ' . \json_encode($result));
            return false;
        }
        
        return $result;
    }
    
    /**
     * Get weather-related icons based on condition
     * 
     * @param string $condition Weather condition
     * @param string $color Hex color (without #)
     * @param string $filetype File type (svg or png)
     * @param int $size Size for PNG (20-1200)
     * @return array Icon data with URLs and caching info
     */
    public function get_weather_icon($condition, $color = null, $filetype = 'svg', $size = 84) {
        // Default icon if we can't find a match
        $default_icon_data = [
            'icon_url' => '',
            'attribution' => 'Weather icon from The Noun Project',
            'license' => 'creative-commons-attribution',
            'term' => 'weather',
            'last_updated' => \current_time('mysql')
        ];
        
        // Try to get icon from cache first
        $cache_key = 'noun_project_weather_' . \sanitize_title($condition) . '_' . ($color ?: 'default') . '_' . $filetype . '_' . $size;
        $cached_icon = \get_transient($cache_key);
        
        if (false !== $cached_icon) {
            return $cached_icon;
        }
        
        // Map weather conditions to search terms
        $search_term = $this->map_condition_to_term($condition);
        
        // Search for icons
        $search_result = $this->search_icons($search_term, ['limit' => 1]);
        
        if (!$search_result || empty($search_result['icons'])) {
            // If primary search fails, try a fallback term
            $fallback_term = $this->get_fallback_term($condition);
            $search_result = $this->search_icons($fallback_term, ['limit' => 1]);
            
            if (!$search_result || empty($search_result['icons'])) {
                return $default_icon_data;
            }
        }
        
        // Get the first icon
        $icon = $search_result['icons'][0];
        $icon_id = $icon['id'];
        
        // If color is specified, download a custom version
        if ($color) {
            $download_result = $this->download_icon($icon_id, $color, $filetype, $size);
            
            if ($download_result && isset($download_result['base64_encoded_file'])) {
                // Create data URI
                $data_uri = 'data:' . $download_result['content_type'] . ';base64,' . $download_result['base64_encoded_file'];
                
                $icon_data = [
                    'icon_url' => $data_uri,
                    'attribution' => $icon['attribution'],
                    'license' => $icon['license_description'],
                    'term' => $icon['term'],
                    'last_updated' => \current_time('mysql')
                ];
                
                // Cache for 1 week
                \set_transient($cache_key, $icon_data, 7 * DAY_IN_SECONDS);
                
                return $icon_data;
            }
        }
        
        // If we couldn't get a custom version or color wasn't specified, use the default URL
        $icon_data = [
            'icon_url' => $icon['thumbnail_url'] ?? $icon['icon_url'] ?? '',
            'attribution' => $icon['attribution'],
            'license' => $icon['license_description'],
            'term' => $icon['term'],
            'last_updated' => \current_time('mysql')
        ];
        
        // Cache for 1 week
        \set_transient($cache_key, $icon_data, 7 * DAY_IN_SECONDS);
        
        return $icon_data;
    }
    
    /**
     * Map weather condition to a search term for Noun Project
     * 
     * @param string $condition Weather condition
     * @return string Search term
     */
    private function map_condition_to_term($condition) {
        $condition = \strtolower($condition);
        
        $condition_map = [
            'clear' => 'sunny',
            'sunny' => 'sunny',
            'partly cloudy' => 'partly cloudy',
            'cloudy' => 'cloudy',
            'overcast' => 'cloudy',
            'mist' => 'fog',
            'fog' => 'fog',
            'rain' => 'rain',
            'light rain' => 'rain',
            'heavy rain' => 'heavy rain',
            'drizzle' => 'drizzle',
            'thunderstorm' => 'thunderstorm',
            'snow' => 'snow',
            'light snow' => 'snow',
            'heavy snow' => 'heavy snow',
            'sleet' => 'sleet',
            'wind' => 'wind',
            'windy' => 'wind',
            'storm' => 'storm',
            'hail' => 'hail'
        ];
        
        // Try to find an exact match
        if (isset($condition_map[$condition])) {
            return $condition_map[$condition];
        }
        
        // Try to find a partial match
        foreach ($condition_map as $key => $term) {
            if (\strpos($condition, $key) !== false) {
                return $term;
            }
        }
        
        // Default to 'weather' if no match
        return 'weather';
    }
    
    /**
     * Get fallback term if primary search fails
     * 
     * @param string $condition Weather condition
     * @return string Fallback search term
     */
    private function get_fallback_term($condition) {
        $condition = \strtolower($condition);
        
        // Check for rain-related conditions
        if (\strpos($condition, 'rain') !== false || \strpos($condition, 'shower') !== false) {
            return 'rain';
        }
        
        // Check for snow-related conditions
        if (\strpos($condition, 'snow') !== false || \strpos($condition, 'flurr') !== false) {
            return 'snow';
        }
        
        // Check for cloud-related conditions
        if (\strpos($condition, 'cloud') !== false) {
            return 'cloud';
        }
        
        // Check for sun-related conditions
        if (\strpos($condition, 'sun') !== false || \strpos($condition, 'clear') !== false) {
            return 'sun';
        }
        
        // Default fallback
        return 'weather';
    }
    
    /**
     * Make an authenticated request to the Noun Project API
     * 
     * @param string $endpoint API endpoint
     * @param array $parameters Query parameters
     * @return array|false Response data or false on failure
     */
    private function make_request($endpoint, $parameters = []) {
        if (empty($this->api_key) || empty($this->api_secret)) {
            \error_log('Noun Project API credentials are not set');
            return false;
        }
        
        // Prepare OAuth 1.0a parameters
        $oauth_nonce = \wp_generate_password(12, false);
        $oauth_timestamp = \time();
        
        // Build OAuth header
        $oauth_header = $this->build_oauth_header($endpoint, $parameters, $oauth_nonce, $oauth_timestamp);
        
        // Build URL with parameters
        $url = $endpoint;
        if (!empty($parameters)) {
            $url .= '?' . \http_build_query($parameters);
        }
        
        // Set up the request
        $args = [
            'headers' => [
                'Authorization' => $oauth_header,
                'Accept' => 'application/json'
            ],
            'timeout' => 15
        ];
        
        // Make the request
        $response = \wp_remote_get($url, $args);
        
        if (\is_wp_error($response)) {
            \error_log('Noun Project API request failed: ' . $response->get_error_message());
            return false;
        }
        
        $body = \wp_remote_retrieve_body($response);
        $data = \json_decode($body, true);
        
        if (empty($data)) {
            \error_log('Invalid response from Noun Project API: ' . $body);
            return false;
        }
        
        return $data;
    }
    
    /**
     * Build OAuth 1.0a header
     * 
     * @param string $endpoint API endpoint
     * @param array $parameters Query parameters
     * @param string $nonce OAuth nonce
     * @param int $timestamp OAuth timestamp
     * @return string OAuth header
     */
    private function build_oauth_header($endpoint, $parameters, $nonce, $timestamp) {
        // OAuth 1.0a parameters
        $oauth_params = [
            'oauth_consumer_key' => $this->api_key,
            'oauth_nonce' => $nonce,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => $timestamp,
            'oauth_version' => '1.0'
        ];
        
        // Combine all parameters
        $all_params = \array_merge($oauth_params, $parameters);
        
        // Sort parameters
        \ksort($all_params);
        
        // Build base string
        $base_string = 'GET&' . \rawurlencode($endpoint) . '&' . \rawurlencode(\http_build_query($all_params));
        
        // Generate signature
        $signature_key = \rawurlencode($this->api_secret) . '&';
        $oauth_signature = \base64_encode(\hash_hmac('sha1', $base_string, $signature_key, true));
        
        // Add signature to OAuth parameters
        $oauth_params['oauth_signature'] = $oauth_signature;
        
        // Build authorization header
        $header_parts = [];
        foreach ($oauth_params as $key => $value) {
            $header_parts[] = \rawurlencode($key) . '="' . \rawurlencode($value) . '"';
        }
        
        return 'OAuth ' . \implode(', ', $header_parts);
    }
}