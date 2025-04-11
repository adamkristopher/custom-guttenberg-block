module.exports = {
  content: ["./src/**/*.{js,jsx}", "./src/**/*.css"],
  theme: {
    extend: {
      colors: {
        "sky-blue": "#3B82F6",
        "deep-blue": "#1E40AF",
        "ocean-teal": "#0EA5E9",
        "sunset-orange": "#F97316",
        "cloud-white": "#F9FAFB",
        "night-blue": "#1E293B",
        "soft-purple": "#8B5CF6",
        "light-cloud": "#E5E7EB",
      },
      boxShadow: {
        weather:
          "0 10px 15px -3px rgba(59, 130, 246, 0.1), 0 4px 6px -2px rgba(59, 130, 246, 0.05)",
      },
      borderRadius: {
        xl: "1rem",
        "2xl": "1.5rem",
      },
    },
  },
  plugins: [],
  prefix: "tw-",
};
