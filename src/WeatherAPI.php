<?php

namespace AuditechConsult\WeatherForecast;

/**
 * Handles all weather API interactions
 */
class WeatherAPI {
    /**
     * API key for weather service
     * 
     * @var string
     */
    private $api_key;
    
    /**
     * Default location
     * 
     * @var string
     */
    private $default_location;
    
    /**
     * Constructor
     * 
     * @param string $api_key API key for weather service
     * @param string $default_location Default location for weather
     */
    public function __construct($api_key = null, $default_location = 'New York') {
        // Get API key from environment variable or parameter
        $this->api_key = $api_key ?: $_ENV['WEATHER_API_KEY'] ?? 'aa12afc61ef445e3a70204739251004';
        $this->default_location = $default_location;
    }
    
    /**
     * Get forecast from API using coordinates
     * 
     * @param float|null $latitude Latitude
     * @param float|null $longitude Longitude
     * @return array Weather data or error
     */
    public function get_forecast_by_coordinates($latitude = null, $longitude = null) {
        // If coordinates not provided, use default location
        if ($latitude === null || $longitude === null) {
            return $this->get_forecast();
        }
        
        // Build API request with coordinates
        $api_url = 'https://api.weatherapi.com/v1/forecast.json';
        $request_url = \add_query_arg(
            array(
                'key' => $this->api_key,
                'q' => $latitude . ',' . $longitude,
                'days' => 1,
                'aqi' => 'no',
                'alerts' => 'no'
            ),
            $api_url
        );
        
        return $this->process_api_request($request_url);
    }
    
    /**
     * Get forecast from API
     * 
     * @param string|null $location Location to get weather for
     * @return array Weather data or error
     */
    public function get_forecast($location = null) {
        // Use provided location or fallback to default
        $location = $location ?: $this->default_location;
        
        // Build API request
        $api_url = 'https://api.weatherapi.com/v1/forecast.json';
        $request_url = \add_query_arg(
            array(
                'key' => $this->api_key,
                'q' => $location,
                'days' => 1,
                'aqi' => 'no',
                'alerts' => 'no'
            ),
            $api_url
        );
        
        return $this->process_api_request($request_url);
    }
    
    /**
     * Process API request and format response
     * 
     * @param string $request_url Full API request URL
     * @return array Formatted weather data or error
     */
    private function process_api_request($request_url) {
        // Make request
        $response = \wp_remote_get($request_url);
        
        if (\is_wp_error($response)) {
            return array(
                'error' => true,
                'message' => $response->get_error_message()
            );
        }
        
        $body = \wp_remote_retrieve_body($response);
        $data = \json_decode($body, true);
        
        if (empty($data)) {
            return array(
                'error' => true,
                'message' => 'Unable to retrieve weather data'
            );
        }
        
        // Format and return data
        return $this->format_weather_data($data);
    }
    
    /**
     * Format raw API data into useful structure
     * 
     * @param array $data Raw API data
     * @return array Formatted weather data
     */
    private function format_weather_data($data) {
        return array(
            'location' => $data['location']['name'],
            'country' => $data['location']['country'],
            'temp_c' => $data['current']['temp_c'],
            'temp_f' => $data['current']['temp_f'],
            'condition' => $data['current']['condition']['text'],
            'icon' => $data['current']['condition']['icon'],
            'humidity' => $data['current']['humidity'],
            'wind_kph' => $data['current']['wind_kph'],
            'forecast' => array(
                'max_temp_c' => $data['forecast']['forecastday'][0]['day']['maxtemp_c'],
                'min_temp_c' => $data['forecast']['forecastday'][0]['day']['mintemp_c'],
                'max_temp_f' => $data['forecast']['forecastday'][0]['day']['maxtemp_f'],
                'min_temp_f' => $data['forecast']['forecastday'][0]['day']['mintemp_f'],
                'condition' => $data['forecast']['forecastday'][0]['day']['condition']['text'],
                'icon' => $data['forecast']['forecastday'][0]['day']['condition']['icon']
            ),
            'last_updated' => \current_time('mysql')
        );
    }
    
    /**
     * Get cached forecast or fetch new data
     * 
     * @param string|null $location Location to get weather for
     * @param float|null $latitude Latitude
     * @param float|null $longitude Longitude
     * @return array Weather data
     */
    public function get_cached_forecast($location = null, $latitude = null, $longitude = null) {
        $transient_key = 'weather_forecast_data';
        
        // If coordinates provided, create a coordinates-specific cache key
        if ($latitude !== null && $longitude !== null) {
            $transient_key = 'weather_forecast_coord_' . md5($latitude . '_' . $longitude);
            $cached_forecast = \get_transient($transient_key);
            
            if (false === $cached_forecast) {
                $forecast = $this->get_forecast_by_coordinates($latitude, $longitude);
                \set_transient($transient_key, $forecast, 3 * HOUR_IN_SECONDS);
                return $forecast;
            }
            
            return $cached_forecast;
        }
        
        // If location provided, create a location-specific cache key
        if ($location) {
            $transient_key = 'weather_forecast_' . \sanitize_title($location);
        }
        
        $cached_forecast = \get_transient($transient_key);
        
        if (false === $cached_forecast) {
            $forecast = $this->get_forecast($location);
            \set_transient($transient_key, $forecast, 3 * HOUR_IN_SECONDS);
            return $forecast;
        }
        
        return $cached_forecast;
    }
}