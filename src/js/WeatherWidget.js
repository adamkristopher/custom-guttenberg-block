import React, { useState, useEffect } from "react";
import ReactDOM from "react-dom";

/**
 * Modern Weather Widget Component with Noun Project Icons
 */
const WeatherWidget = () => {
  // State to store weather data
  const [weatherData, setWeatherData] = useState(
    weatherForecastData.initialWeather || {}
  );
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [locationError, setLocationError] = useState(null);
  const [units, setUnits] = useState("celsius"); // Track temperature units
  const [timeFormat, setTimeFormat] = useState("12h"); // Track time format

  // Format temperature with degree symbol
  const formatTemp = (temp) =>
    `${Math.round(temp)}°${units === "celsius" ? "C" : "F"}`;

  // Get temperature based on current unit selection
  const getTemp = (celsius, fahrenheit) =>
    units === "celsius" ? celsius : fahrenheit;

  // Request user location on component mount
  useEffect(() => {
    // Check if we're on a secure connection (HTTPS)
    const isSecureConnection = window.location.protocol === "https:";

    if (!isSecureConnection) {
      setLocationError(
        "Geolocation requires a secure (HTTPS) connection. Using default location instead."
      );
      return; // Skip geolocation request on non-HTTPS
    }

    if (navigator.geolocation) {
      setLoading(true);
      navigator.geolocation.getCurrentPosition(
        (position) => {
          // Success - got location, now fetch weather
          fetchWeatherByCoordinates(
            position.coords.latitude,
            position.coords.longitude
          );
        },
        (error) => {
          // Handle geolocation error with more specific messages
          let errorMsg =
            "Could not get your location. Using default location instead.";

          if (error.code === 1) {
            errorMsg =
              "Location access denied. Please check your browser permissions.";
          } else if (error.code === 2) {
            errorMsg = "Location unavailable. Using default location instead.";
          } else if (error.code === 3) {
            errorMsg =
              "Location request timed out. Using default location instead.";
          }

          console.error("Geolocation error:", error);
          setLocationError(errorMsg);
          setLoading(false);
        },
        {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 0,
        }
      );
    } else {
      setLocationError("Geolocation is not supported by your browser.");
      setLoading(false);
    }
  }, []);

  // Fetch weather data using coordinates
  const fetchWeatherByCoordinates = (latitude, longitude) => {
    setLoading(true);
    setError(null);

    jQuery.ajax({
      url: weatherForecastData.ajaxurl,
      type: "POST",
      data: {
        action: "weather_forecast_get_current",
        security: weatherForecastData.nonce,
        latitude: latitude,
        longitude: longitude,
      },
      success: function (response) {
        if (response.success) {
          setWeatherData(response.data);
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

  // Toggle between Celsius and Fahrenheit
  const toggleUnits = () => {
    setUnits(units === "celsius" ? "fahrenheit" : "celsius");
  };

  // Toggle between 12h and 24h time format
  const toggleTimeFormat = () => {
    setTimeFormat(timeFormat === "12h" ? "24h" : "12h");
  };

  // Handle refresh button click (using coordinates if available)
  const handleRefresh = () => {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          fetchWeatherByCoordinates(
            position.coords.latitude,
            position.coords.longitude
          );
        },
        (error) => {
          setError("Could not get your location. Please try again.");
        }
      );
    } else {
      // Fallback to default refresh method
      setLoading(true);
      setError(null);

      jQuery.ajax({
        url: weatherForecastData.ajaxurl,
        type: "POST",
        data: {
          action: "weather_forecast_get_current",
          security: weatherForecastData.nonce,
        },
        success: function (response) {
          if (response.success) {
            setWeatherData(response.data);
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
    }
  };

  // Get Noun Project icon for a specific weather condition
  const fetchNounProjectIcon = (condition, color = null) => {
    // If Noun Project is not enabled, don't try to fetch
    if (!weatherForecastData.useNounProject) {
      return;
    }

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

  // On initial load, fetch a Noun Project icon if we have weather data but no icon
  useEffect(() => {
    if (
      weatherData &&
      weatherData.condition &&
      !weatherData.noun_project_icon &&
      weatherForecastData.useNounProject
    ) {
      fetchNounProjectIcon(weatherData.condition, "3B82F6"); // Sky Blue color
    }
  }, [weatherData?.condition]);

  // Render loading state
  if (loading) {
    return (
      <div className="weather-widget weather-loading">
        <svg
          className="tw-w-16 tw-h-16 tw-mx-auto tw-text-sky-blue loading-icon"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle
            className="tw-opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            strokeWidth="4"
          ></circle>
          <path
            className="tw-opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          ></path>
        </svg>
        <p className="tw-mt-5 tw-font-medium tw-text-night-blue">
          Fetching your weather...
        </p>
      </div>
    );
  }

  // Render error state
  if (error) {
    return (
      <div className="weather-widget weather-error">
        <svg
          className="error-icon"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
        </svg>
        <p className="tw-text-night-blue tw-font-medium">{error}</p>
        <button
          onClick={handleRefresh}
          className="tw-bg-sky-blue tw-text-white tw-px-4 tw-py-2 tw-rounded-lg tw-mt-5 hover:tw-bg-deep-blue tw-transition-colors"
        >
          Try Again
        </button>
      </div>
    );
  }

  // If no weather data yet
  if (!weatherData.location) {
    return (
      <div className="weather-widget weather-loading">
        <svg
          className="tw-w-16 tw-h-16 tw-mx-auto tw-text-sky-blue loading-icon"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle
            className="tw-opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            strokeWidth="4"
          ></circle>
          <path
            className="tw-opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          ></path>
        </svg>
        <p className="tw-mt-5 tw-font-medium tw-text-night-blue">
          Initializing weather data...
        </p>
        {locationError && (
          <p className="weather-location-error">{locationError}</p>
        )}
      </div>
    );
  }

  // Format the last updated timestamp nicely
  const formatLastUpdated = (timestamp) => {
    const date = new Date(timestamp);
    if (timeFormat === "12h") {
      return date.toLocaleTimeString([], {
        hour: "2-digit",
        minute: "2-digit",
        hour12: true,
      });
    } else {
      return date.toLocaleTimeString([], {
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
      });
    }
  };

  // Get the appropriate weather icon
  const getWeatherIcon = () => {
    // Use Noun Project icon if available
    if (weatherData.noun_project_icon) {
      return (
        <img
          src={weatherData.noun_project_icon}
          alt={weatherData.condition}
          className="tw-w-16 tw-h-16 tw-object-contain"
          title={weatherData.noun_project_attribution || weatherData.condition}
        />
      );
    }

    // Use API provided icon as fallback
    if (weatherData.icon) {
      return (
        <img
          src={weatherData.icon}
          alt={weatherData.condition}
          className="tw-w-16 tw-h-16"
        />
      );
    }

    // Default SVG icon if no others are available
    const conditionLower = weatherData.condition.toLowerCase();

    if (
      conditionLower.includes("thunderstorm") ||
      conditionLower.includes("thunder")
    ) {
      return (
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="currentColor"
          className="tw-w-8 tw-h-8 tw-text-white"
        >
          <path d="M16 2a1 1 0 0 1 1 1v6h3a1 1 0 0 1 .8 1.6l-8 11a1 1 0 0 1-1.8-.6v-6h-3a1 1 0 0 1-.8-1.6l8-11A1 1 0 0 1 16 2z" />
        </svg>
      );
    } else if (
      conditionLower.includes("rain") ||
      conditionLower.includes("drizzle")
    ) {
      return (
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="currentColor"
          className="tw-w-8 tw-h-8 tw-text-white"
        >
          <path d="M11 4a8 8 0 1 0 0 16 8 8 0 0 0 0-16zm1 13a1 1 0 1 1-2 0 6 6 0 0 1 6-6 1 1 0 0 1 0 2 4 4 0 0 0-4 4z" />
        </svg>
      );
    } else if (
      conditionLower.includes("snow") ||
      conditionLower.includes("sleet")
    ) {
      return (
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="currentColor"
          className="tw-w-8 tw-h-8 tw-text-white"
        >
          <path d="M13 16.268l1.964-1.134 1 1.732L14 18l1.964 1.134-1 1.732L13 19.732V22h-2v-2.268l-1.964 1.134-1-1.732L10 18l-1.964-1.134 1-1.732L11 16.268V14h2v2.268zM17 18v-4h-2v-2h2V8l4 4-4 4v2zM3 2h18v2H3V2z" />
        </svg>
      );
    } else if (
      conditionLower.includes("clear") ||
      conditionLower.includes("sunny")
    ) {
      return (
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="currentColor"
          className="tw-w-8 tw-h-8 tw-text-white"
        >
          <path d="M12 18a6 6 0 1 1 0-12 6 6 0 0 1 0 12zm0-2a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM11 1h2v3h-2V1zm0 19h2v3h-2v-3zM3.515 4.929l1.414-1.414L7.05 5.636 5.636 7.05 3.515 4.93zM16.95 18.364l1.414-1.414 2.121 2.121-1.414 1.414-2.121-2.121zm2.121-14.85l1.414 1.415-2.121 2.121-1.414-1.414 2.121-2.121zM5.636 16.95l1.414 1.414-2.121 2.121-1.414-1.414 2.121-2.121zM23 11v2h-3v-2h3zM4 11v2H1v-2h3z" />
        </svg>
      );
    } else if (
      conditionLower.includes("cloud") ||
      conditionLower.includes("overcast")
    ) {
      return (
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="currentColor"
          className="tw-w-8 tw-h-8 tw-text-white"
        >
          <path d="M9.5 6a6.5 6.5 0 0 0 0 13h7a4.5 4.5 0 1 0-.957-8.898A6.502 6.502 0 0 0 9.5 6z" />
        </svg>
      );
    } else if (
      conditionLower.includes("fog") ||
      conditionLower.includes("mist")
    ) {
      return (
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="currentColor"
          className="tw-w-8 tw-h-8 tw-text-white"
        >
          <path d="M4 4h4v2H4V4zm12 0h4v2h-4V4zM2 9h5v2H2V9zm7 0h5v2H9V9zm7 0h5v2h-5V9zM4 14h4v2H4v-2zm12 0h4v2h-4v-2zM2 19h5v2H2v-2zm7 0h5v2H9v-2zm7 0h5v2h-5v-2z" />
        </svg>
      );
    } else {
      // Default icon
      return (
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="currentColor"
          className="tw-w-8 tw-h-8 tw-text-white"
        >
          <path d="M12 2c5.514 0 10 4.486 10 10s-4.486 10-10 10S2 17.514 2 12 6.486 2 12 2zm0 18c4.411 0 8-3.589 8-8s-3.589-8-8-8-8 3.589-8 8 3.589 8 8 8zm3.707-11.707a1 1 0 0 0-1.414 0L12 10.586l-2.293-2.293a1 1 0 1 0-1.414 1.414L10.586 12l-2.293 2.293a1 1 0 1 0 1.414 1.414L12 13.414l2.293 2.293a1 1 0 0 0 1.414-1.414L13.414 12l2.293-2.293a1 1 0 0 0 0-1.414z" />
        </svg>
      );
    }
  };

  // Render weather data
  return (
    <div className="weather-widget weather-card">
      <div className="weather-header">
        <div className="tw-flex tw-justify-between tw-items-center">
          <h3 className="weather-location">{weatherData.location}</h3>
          <div className="tw-flex tw-gap-2">
            <button
              onClick={toggleUnits}
              className={`unit-toggle ${units === "celsius" ? "active" : ""}`}
            >
              °{units === "celsius" ? "C" : "F"}
            </button>
            <button
              onClick={toggleTimeFormat}
              className={`unit-toggle ${timeFormat === "24h" ? "active" : ""}`}
            >
              {timeFormat === "12h" ? "12h" : "24h"}
            </button>
          </div>
        </div>
        <p className="tw-text-xs tw-text-night-blue tw-opacity-75">
          {weatherData.country}
        </p>
        <p className="weather-updated tw-flex tw-items-center tw-gap-1">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            className="tw-h-4 tw-w-4 tw-text-gray-400"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
          {formatLastUpdated(weatherData.last_updated)}
        </p>
        {locationError && (
          <p className="weather-location-note">{locationError}</p>
        )}
      </div>

      <div className="weather-current">
        <div className="weather-condition">
          <div className="weather-condition-icon">{getWeatherIcon()}</div>
          <p>{weatherData.condition}</p>
        </div>

        <div className="weather-temp">
          <h2>{formatTemp(getTemp(weatherData.temp_c, weatherData.temp_f))}</h2>
          <p>
            <svg
              xmlns="http://www.w3.org/2000/svg"
              className="tw-h-4 tw-w-4 tw-text-ocean-teal"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"
              />
            </svg>
            {weatherData.humidity}%
          </p>
          <p>
            <svg
              xmlns="http://www.w3.org/2000/svg"
              className="tw-h-4 tw-w-4 tw-text-soft-purple"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
              />
            </svg>
            {weatherData.wind_kph} km/h
          </p>
        </div>
      </div>

      <div className="weather-forecast">
        <h4>
          <svg
            xmlns="http://www.w3.org/2000/svg"
            className="forecast-icon"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
            />
          </svg>
          Today's Forecast
        </h4>
        <div className="forecast-details">
          <div className="tw-flex tw-items-center tw-gap-1">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              className="tw-h-5 tw-w-5 tw-text-sunset-orange"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M5 15l7-7 7 7"
              />
            </svg>
            <p className="forecast-high">
              {formatTemp(
                getTemp(
                  weatherData.forecast.max_temp_c,
                  weatherData.forecast.max_temp_f
                )
              )}
            </p>
          </div>
          <div className="tw-flex tw-items-center tw-gap-1">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              className="tw-h-5 tw-w-5 tw-text-deep-blue"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M19 9l-7 7-7-7"
              />
            </svg>
            <p className="forecast-low">
              {formatTemp(
                getTemp(
                  weatherData.forecast.min_temp_c,
                  weatherData.forecast.min_temp_f
                )
              )}
            </p>
          </div>
          <div className="tw-mt-2 tw-w-full tw-text-center tw-text-sm tw-text-night-blue">
            {weatherData.forecast.condition}
          </div>
        </div>
      </div>

      <button className="weather-refresh-btn" onClick={handleRefresh}>
        <svg
          xmlns="http://www.w3.org/2000/svg"
          className="tw-h-5 tw-w-5"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
          />
        </svg>
        Refresh Weather
      </button>

      {weatherData.noun_project_attribution && (
        <div className="tw-text-xs tw-text-center tw-mt-4 tw-text-gray-500">
          {weatherData.noun_project_attribution}
        </div>
      )}
    </div>
  );
};

// Initialize the Weather Widget when the DOM is fully loaded
document.addEventListener("DOMContentLoaded", function () {
  // Look for either ID or class-based containers
  const idContainer = document.getElementById("weather-forecast-widget");
  const classContainers = document.querySelectorAll(
    ".weather-forecast-container"
  );

  // Log what we find for debugging
  console.log("Weather widget looking for containers");
  console.log("ID container found:", !!idContainer);
  console.log("Class containers found:", classContainers.length);

  // Render to ID container if found
  if (idContainer) {
    ReactDOM.render(<WeatherWidget />, idContainer);
    console.log("Weather widget rendered to ID container");
  }

  // Render to all class containers found
  if (classContainers.length > 0) {
    classContainers.forEach((container, index) => {
      if (container.id !== "weather-forecast-widget") {
        // Avoid double rendering
        ReactDOM.render(<WeatherWidget />, container);
        console.log("Weather widget rendered to container", index);
      }
    });
  }

  // Warning if no containers found
  if (!idContainer && classContainers.length === 0) {
    console.warn(
      "No weather widget containers found on page. Add either an element with ID 'weather-forecast-widget' or class 'weather-forecast-container'"
    );
  }
});

window.weatherForecastWidget = WeatherWidget;
export { WeatherWidget };
export default WeatherWidget;
