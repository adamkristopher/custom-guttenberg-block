# Weather Forecast Block for WordPress

A modern WordPress Gutenberg block that displays real-time weather forecasts with beautiful icons from The Noun Project.

![Weather Forecast Block Preview](screenshot.png)

## Features

- 🌦️ Real-time weather data with automatic updates
- 📱 Fully responsive design that looks great on all devices
- 🔄 Geolocation support for visitor-specific weather
- 🎨 Professional icons from The Noun Project API
- 🌈 Customizable colors to match your site's theme
- 🔄 Toggle between Celsius and Fahrenheit
- 🕒 12/24 hour time format support
- ✨ Modern glass-morphism UI with animations

## Requirements

- WordPress 6.2 or higher
- PHP 7.4 or higher
- [Composer](https://getcomposer.org/)
- [Node.js](https://nodejs.org/) (v14 or higher) and npm
- An active [Noun Project API](https://api.thenounproject.com/) key (optional but recommended)
- A [WeatherAPI.com](https://www.weatherapi.com/) API key

## Development Setup

### 1. Clone the Repository

```bash
git clone https://github.com/your-username/weather-forecast-block.git
cd weather-forecast-block
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install
```

### 3. Configure API Keys

Create a `.env` file in the plugin root directory:

```
WEATHER_API_KEY=your_weather_api_key_here
NOUN_PROJECT_API_KEY=your_noun_project_key_here
NOUN_PROJECT_SECRET_KEY=your_noun_project_secret_here
```

### 4. Build Assets

```bash
# For development (with source maps and watching)
npm run dev

# For production (minified)
npm run build
```

### 5. Activate the Plugin

1. Create a symlink in your WordPress plugins directory:

   ```bash
   ln -s /path/to/weather-forecast-block /path/to/wordpress/wp-content/plugins/
   ```

2. Alternatively, zip the plugin and install via the WordPress admin:

   ```bash
   zip -r weather-forecast.zip . -x "node_modules/*" "vendor/*" ".git/*"
   ```

3. Activate the plugin through the WordPress admin interface

## Usage

After activation, you can add the Weather Forecast block to any page or post using the WordPress block editor:

1. Click the "+" button to add a new block
2. Search for "Weather Forecast"
3. Configure the block settings in the sidebar:
   - Location (optional - will use visitor's location if empty)
   - Icon color customization (if using Noun Project)

## Block Configuration Options

| Option                | Description                                        |
| --------------------- | -------------------------------------------------- |
| Location              | Specify a city or location name (e.g., "New York") |
| Use Custom Icon Color | Toggle to enable icon color customization          |
| Icon Color            | Color picker for Noun Project icons                |

## Building for Production

To prepare the plugin for production use:

```bash
# Clean previous builds
rm -rf build/

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci

# Build assets
npm run build

# Create a production-ready zip excluding development files
zip -r weather-forecast.zip . -x "node_modules/*" "vendor/*" ".git/*" ".github/*" "src/js/*" "src/css/*" "*.json" "*.lock" "*.md" ".gitignore" ".env" "webpack.config.js" "tailwind.config.js" "postcss.config.js"
```

## Project Structure

```
weather-forecast-block/
├── build/                  # Compiled assets
│   ├── css/                # Compiled CSS
│   └── js/                 # Compiled JavaScript
├── src/                    # Source files
│   ├── css/                # CSS/SCSS files
│   └── js/                 # JavaScript/React files
├── vendor/                 # Composer dependencies
├── .env                    # Environment variables (not tracked)
├── .gitignore              # Git ignore file
├── block.json              # Block metadata
├── composer.json           # Composer configuration
├── index.php               # Plugin initialization
├── package.json            # npm configuration
├── Plugin.php              # Main plugin class
├── README.md               # This file
├── tailwind.config.js      # Tailwind CSS configuration
├── WeatherAPI.php          # WeatherAPI integration
├── webpack.config.js       # Webpack configuration
└── NounProjectAPI.php      # Noun Project API integration
```

## Extending the Plugin

### Available Filters

```php
// Modify weather data before it's returned
add_filter('weather_forecast_data', function($data, $location) {
    // Modify $data
    return $data;
}, 10, 2);

// Change the default location
add_filter('weather_forecast_default_location', function($location) {
    return 'London';
});

// Customize the cache duration (in seconds)
add_filter('weather_forecast_cache_duration', function($duration) {
    return 60 * 30; // 30 minutes
});
```

### Action Hooks

```php
// Run after fresh weather data is fetched
add_action('weather_forecast_data_updated', function($weather_data) {
    // Do something with the new data
});
```

## Troubleshooting

### Common Issues

- **Block not appearing**: Make sure your build files are generated correctly with `npm run build`
- **API errors**: Check your API keys in the `.env` file
- **CORS issues**: Ensure your WordPress installation can make external API requests

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch: `git checkout -b feature/my-new-feature`
3. Commit your changes: `git commit -am 'Add some feature'`
4. Push to the branch: `git push origin feature/my-new-feature`
5. Submit a pull request

## License

This project is licensed under the GPL v2 or later - see the LICENSE file for details.

---

Built by [Auditech Consulting](https://auditechconsult.com)
