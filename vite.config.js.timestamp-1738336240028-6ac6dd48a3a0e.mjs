// vite.config.js
import { defineConfig } from "file:///C:/xampp/htdocs/presidential-camp/node_modules/vite/dist/node/index.js";
import laravel from "file:///C:/xampp/htdocs/presidential-camp/node_modules/laravel-vite-plugin/dist/index.js";
import vue from "file:///C:/xampp/htdocs/presidential-camp/node_modules/@vitejs/plugin-vue/dist/index.mjs";
var vite_config_default = defineConfig({
  plugins: [
    laravel({
      input: [
        "resources/css/app.css",
        "resources/js/app.js"
      ],
      refresh: true
    }),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false
        }
      }
    })
  ]
  // server: {
  //     host: '0.0.0.0', // Bind to all IPs
  //     port: 5173,      // Default Vite port
  //     hmr: {
  //         host: '192.168.3.47', // Your computer's IP address
  //     },
  // },
});
export {
  vite_config_default as default
};
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsidml0ZS5jb25maWcuanMiXSwKICAic291cmNlc0NvbnRlbnQiOiBbImNvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9kaXJuYW1lID0gXCJDOlxcXFx4YW1wcFxcXFxodGRvY3NcXFxccHJlc2lkZW50aWFsLWNhbXBcIjtjb25zdCBfX3ZpdGVfaW5qZWN0ZWRfb3JpZ2luYWxfZmlsZW5hbWUgPSBcIkM6XFxcXHhhbXBwXFxcXGh0ZG9jc1xcXFxwcmVzaWRlbnRpYWwtY2FtcFxcXFx2aXRlLmNvbmZpZy5qc1wiO2NvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9pbXBvcnRfbWV0YV91cmwgPSBcImZpbGU6Ly8vQzoveGFtcHAvaHRkb2NzL3ByZXNpZGVudGlhbC1jYW1wL3ZpdGUuY29uZmlnLmpzXCI7aW1wb3J0IHsgZGVmaW5lQ29uZmlnIH0gZnJvbSAndml0ZSc7XG5pbXBvcnQgbGFyYXZlbCBmcm9tICdsYXJhdmVsLXZpdGUtcGx1Z2luJztcbmltcG9ydCB2dWUgZnJvbSAnQHZpdGVqcy9wbHVnaW4tdnVlJztcblxuZXhwb3J0IGRlZmF1bHQgZGVmaW5lQ29uZmlnKHtcbiAgICBwbHVnaW5zOiBbXG4gICAgICAgIGxhcmF2ZWwoe1xuICAgICAgICAgICAgaW5wdXQ6IFtcbiAgICAgICAgICAgICAgICAncmVzb3VyY2VzL2Nzcy9hcHAuY3NzJyxcbiAgICAgICAgICAgICAgICAncmVzb3VyY2VzL2pzL2FwcC5qcycsXG4gICAgICAgICAgICBdLFxuICAgICAgICAgICAgcmVmcmVzaDogdHJ1ZSxcbiAgICAgICAgfSksXG5cbiAgICAgICAgdnVlKHtcbiAgICAgICAgICAgIHRlbXBsYXRlOntcbiAgICAgICAgICAgICAgICB0cmFuc2Zvcm1Bc3NldFVybHM6e1xuICAgICAgICAgICAgICAgICAgICBiYXNlOm51bGwsXG4gICAgICAgICAgICAgICAgICAgIGluY2x1ZGVBYnNvbHV0ZTogZmFsc2UsXG4gICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgIH0sXG4gICAgICAgIH0pXG4gICAgXSxcblxuICAgIC8vIHNlcnZlcjoge1xuICAgIC8vICAgICBob3N0OiAnMC4wLjAuMCcsIC8vIEJpbmQgdG8gYWxsIElQc1xuICAgIC8vICAgICBwb3J0OiA1MTczLCAgICAgIC8vIERlZmF1bHQgVml0ZSBwb3J0XG4gICAgLy8gICAgIGhtcjoge1xuICAgIC8vICAgICAgICAgaG9zdDogJzE5Mi4xNjguMy40NycsIC8vIFlvdXIgY29tcHV0ZXIncyBJUCBhZGRyZXNzXG4gICAgLy8gICAgIH0sXG4gICAgLy8gfSxcbn0pO1xuIl0sCiAgIm1hcHBpbmdzIjogIjtBQUE2UixTQUFTLG9CQUFvQjtBQUMxVCxPQUFPLGFBQWE7QUFDcEIsT0FBTyxTQUFTO0FBRWhCLElBQU8sc0JBQVEsYUFBYTtBQUFBLEVBQ3hCLFNBQVM7QUFBQSxJQUNMLFFBQVE7QUFBQSxNQUNKLE9BQU87QUFBQSxRQUNIO0FBQUEsUUFDQTtBQUFBLE1BQ0o7QUFBQSxNQUNBLFNBQVM7QUFBQSxJQUNiLENBQUM7QUFBQSxJQUVELElBQUk7QUFBQSxNQUNBLFVBQVM7QUFBQSxRQUNMLG9CQUFtQjtBQUFBLFVBQ2YsTUFBSztBQUFBLFVBQ0wsaUJBQWlCO0FBQUEsUUFDckI7QUFBQSxNQUNKO0FBQUEsSUFDSixDQUFDO0FBQUEsRUFDTDtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBU0osQ0FBQzsiLAogICJuYW1lcyI6IFtdCn0K
