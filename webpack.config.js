const path = require("path");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");

module.exports = {
  entry: {
    block: "./src/js/block.js",
    "weather-widget": "./src/js/WeatherWidget.js", // Updated to match your actual filename
    style: "./src/css/style.css",
  },
  output: {
    filename: "js/[name].js",
    path: path.resolve(__dirname, "build"),
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: "babel-loader",
          options: {
            presets: ["@babel/preset-env", "@babel/preset-react"],
          },
        },
      },
      {
        test: /\.css$/,
        use: [
          MiniCssExtractPlugin.loader,
          "css-loader",
          {
            loader: "postcss-loader",
            options: {
              postcssOptions: {
                plugins: [require("tailwindcss"), require("autoprefixer")],
              },
            },
          },
        ],
      },
    ],
  },
  plugins: [
    new MiniCssExtractPlugin({
      filename: "css/[name].css",
    }),
  ],
  externals: {
    react: "React",
    "react-dom": "ReactDOM",
    "@wordpress/blocks": "wp.blocks",
    "@wordpress/block-editor": "wp.blockEditor",
    "@wordpress/components": "wp.components",
    "@wordpress/i18n": "wp.i18n",
    "@wordpress/element": "wp.element",
    "@wordpress/data": "wp.data",
  },
  // Add resolve configuration to help with file resolution
  resolve: {
    extensions: [".js", ".json"],
    modules: [path.resolve(__dirname, "src"), "node_modules"],
  },
};
