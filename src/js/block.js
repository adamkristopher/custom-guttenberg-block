import React, { useState, useEffect } from "react";
import { registerBlockType } from "@wordpress/blocks";
import { InspectorControls } from "@wordpress/block-editor";
import {
  PanelBody,
  TextControl,
  Button,
  ToggleControl,
  ColorPalette,
} from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import WeatherWidget from "./WeatherWidget";

// Import your Tailwind CSS
import "../css/style.css";

registerBlockType("auditech/weather-forecast", {
  apiVersion: 2,
  title: __("Weather Forecast", "weather-forecast"),
  icon: "cloud",
  category: "widgets",

  edit: ({ attributes, setAttributes }) => {
    const [location, setLocation] = useState(attributes.location || "");
    const [weatherData, setWeatherData] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [useCustomColor, setUseCustomColor] = useState(false);
    const [iconColor, setIconColor] = useState("#3B82F6"); // Default sky blue

    // Save attributes when they change
    useEffect(() => {
      setAttributes({ location });
    }, [location, setAttributes]);

    useEffect(() => {
      if (weatherForecastData.initialWeather) {
        setWeatherData(weatherForecastData.initialWeather);
      }
    }, []);

    const updateWeather = () => {
      if (!location) return;

      setLoading(true);
      setError(null);

      // Use WordPress AJAX
      jQuery.ajax({
        url: weatherForecastData.ajaxurl,
        type: "POST",
        data: {
          action: "weather_forecast_get_current",
          security: weatherForecastData.nonce,
          location: location,
        },
        success: function (response) {
          if (response.success) {
            setWeatherData(response.data);

            // If we have Noun Project enabled and a custom color, fetch icon with that color
            if (
              weatherForecastData.useNounProject &&
              useCustomColor &&
              response.data.condition
            ) {
              fetchNounProjectIcon(response.data.condition, iconColor);
            }
          } else {
            setError("Failed to fetch weather data");
          }
          setLoading(false);
        },
        error: function (xhr, status, error) {
          setError("Error: " + error);
          setLoading(false);
        },
      });
    };

    // Get Noun Project icon with custom color
    const fetchNounProjectIcon = (condition, color) => {
      // Clean up the color (remove # if present)
      if (color && color.startsWith("#")) {
        color = color.substring(1);
      }

      jQuery.ajax({
        url: weatherForecastData.ajaxurl,
        type: "POST",
        data: {
          action: "weather_forecast_get_icon",
          security: weatherForecastData.nonce,
          condition: condition,
          color: color,
          filetype: "svg",
          size: 84,
        },
        success: function (response) {
          if (response.success && response.data.icon_url) {
            // Update the weather data with the new icon
            setWeatherData((prevData) => ({
              ...prevData,
              noun_project_icon: response.data.icon_url,
              noun_project_attribution: response.data.attribution,
            }));
          }
        },
        error: function (xhr, status, error) {
          console.error("Error fetching Noun Project icon:", error);
        },
      });
    };

    // Update icon color when it changes
    const handleColorChange = (color) => {
      setIconColor(color);
      if (weatherData && weatherData.condition) {
        fetchNounProjectIcon(weatherData.condition, color);
      }
    };

    return (
      <>
        <InspectorControls>
          <PanelBody title={__("Weather Settings", "weather-forecast")}>
            <TextControl
              label={__("Location", "weather-forecast")}
              value={location}
              onChange={setLocation}
              className="mb-3"
            />
            <Button isPrimary onClick={updateWeather} disabled={!location}>
              {__("Update Weather", "weather-forecast")}
            </Button>

            {weatherForecastData.useNounProject && (
              <div className="tw-mt-4">
                <ToggleControl
                  label={__("Use Custom Icon Color", "weather-forecast")}
                  checked={useCustomColor}
                  onChange={setUseCustomColor}
                />

                {useCustomColor && (
                  <div className="tw-mt-2">
                    <p className="components-base-control__label">
                      {__("Icon Color", "weather-forecast")}
                    </p>
                    <ColorPalette
                      colors={[
                        { name: "Sky Blue", color: "#3B82F6" },
                        { name: "Deep Blue", color: "#1E40AF" },
                        { name: "Ocean Teal", color: "#0EA5E9" },
                        { name: "Sunset Orange", color: "#F97316" },
                        { name: "Soft Purple", color: "#8B5CF6" },
                        { name: "Night Blue", color: "#1E293B" },
                      ]}
                      value={iconColor}
                      onChange={handleColorChange}
                    />
                  </div>
                )}
              </div>
            )}
          </PanelBody>
        </InspectorControls>

        <div className="tw-p-4">
          <WeatherWidget
            weatherData={weatherData}
            loading={loading}
            error={error}
            onRefresh={updateWeather}
          />
        </div>
      </>
    );
  },

  save: () => {
    // Return null to use the block's PHP render_callback
    return null;
  },
});
