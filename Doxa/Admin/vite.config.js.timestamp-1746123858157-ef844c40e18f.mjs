// vite.config.js
import { defineConfig, loadEnv } from "file:///C:/xampp/htdocs/doxa-cms.loc/Doxa/Admin/node_modules/vite/dist/node/index.js";
import laravel from "file:///C:/xampp/htdocs/doxa-cms.loc/Doxa/Admin/node_modules/laravel-vite-plugin/dist/index.js";
import vue from "file:///C:/xampp/htdocs/doxa-cms.loc/Doxa/Admin/node_modules/@vitejs/plugin-vue/dist/index.mjs";
import vueDevTools from "file:///C:/xampp/htdocs/doxa-cms.loc/Doxa/Admin/node_modules/vite-plugin-vue-devtools/dist/vite.mjs";
import path from "path";
var vite_config_default = defineConfig(({ mode }) => {
  const pkgEnvDir = path.resolve("../../");
  const pkgEnv = loadEnv(mode, pkgEnvDir);
  const consumerRoot = pkgEnv?.VITE_CONSUMER_PROJECT_PATH;
  const rootEnvDir = pkgEnv?.VITE_PKG_MODE == "local" ? consumerRoot : "../../../";
  const rootEnv = loadEnv(mode, rootEnvDir);
  Object.assign(process.env, rootEnv, pkgEnv);
  return {
    base: "/doxa/admin/",
    build: {
      buildDirectory: "",
      outDir: "./src/Resources/assets/dist",
      emptyOutDir: true,
      commonjsOptions: {
        transformMixedEsModules: true
      }
    },
    rootEnv,
    pkgEnv,
    plugins: [
      vue(),
      vueDevTools({
        appendTo: "admin.js"
      }),
      laravel({
        hotFile: consumerRoot ? consumerRoot + "/public/doxa-admin-vite.hot" : false,
        publicDirectory: "./src/Resources/assets/dist",
        input: [
          "./src/Resources/assets/js/admin.js",
          "./src/Resources/assets/css/admin.css"
        ],
        refresh: true
      })
    ],
    resolve: {
      alias: {
        vue: "vue/dist/vue.esm-bundler.js"
      }
    }
  };
});
export {
  vite_config_default as default
};
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsidml0ZS5jb25maWcuanMiXSwKICAic291cmNlc0NvbnRlbnQiOiBbImNvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9kaXJuYW1lID0gXCJDOlxcXFx4YW1wcFxcXFxodGRvY3NcXFxcZG94YS1jbXMubG9jXFxcXERveGFcXFxcQWRtaW5cIjtjb25zdCBfX3ZpdGVfaW5qZWN0ZWRfb3JpZ2luYWxfZmlsZW5hbWUgPSBcIkM6XFxcXHhhbXBwXFxcXGh0ZG9jc1xcXFxkb3hhLWNtcy5sb2NcXFxcRG94YVxcXFxBZG1pblxcXFx2aXRlLmNvbmZpZy5qc1wiO2NvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9pbXBvcnRfbWV0YV91cmwgPSBcImZpbGU6Ly8vQzoveGFtcHAvaHRkb2NzL2RveGEtY21zLmxvYy9Eb3hhL0FkbWluL3ZpdGUuY29uZmlnLmpzXCI7aW1wb3J0IHsgZGVmaW5lQ29uZmlnLCBsb2FkRW52IH0gZnJvbSBcInZpdGVcIjtcclxuaW1wb3J0IGxhcmF2ZWwgZnJvbSBcImxhcmF2ZWwtdml0ZS1wbHVnaW5cIjtcclxuaW1wb3J0IHZ1ZSBmcm9tIFwiQHZpdGVqcy9wbHVnaW4tdnVlXCI7XHJcbmltcG9ydCB2dWVEZXZUb29scyBmcm9tIFwidml0ZS1wbHVnaW4tdnVlLWRldnRvb2xzXCI7XHJcbmltcG9ydCBwYXRoIGZyb20gXCJwYXRoXCI7XHJcblxyXG5leHBvcnQgZGVmYXVsdCBkZWZpbmVDb25maWcoKHsgbW9kZSB9KSA9PiB7XHJcblx0Y29uc3QgcGtnRW52RGlyID0gcGF0aC5yZXNvbHZlKFwiLi4vLi4vXCIpO1xyXG5cdGNvbnN0IHBrZ0VudiA9IGxvYWRFbnYobW9kZSwgcGtnRW52RGlyKTtcclxuXHJcblx0Y29uc3QgY29uc3VtZXJSb290ID0gcGtnRW52Py5WSVRFX0NPTlNVTUVSX1BST0pFQ1RfUEFUSDtcclxuXHJcblx0Y29uc3Qgcm9vdEVudkRpciA9XHJcblx0XHRwa2dFbnY/LlZJVEVfUEtHX01PREUgPT0gXCJsb2NhbFwiID8gY29uc3VtZXJSb290IDogXCIuLi8uLi8uLi9cIjtcclxuXHJcblx0Y29uc3Qgcm9vdEVudiA9IGxvYWRFbnYobW9kZSwgcm9vdEVudkRpcik7XHJcblx0T2JqZWN0LmFzc2lnbihwcm9jZXNzLmVudiwgcm9vdEVudiwgcGtnRW52KTtcclxuXHJcblx0cmV0dXJuIHtcclxuXHRcdGJhc2U6IFwiL2RveGEvYWRtaW4vXCIsXHJcblx0XHRidWlsZDoge1xyXG5cdFx0XHRidWlsZERpcmVjdG9yeTogXCJcIixcclxuXHRcdFx0b3V0RGlyOiBcIi4vc3JjL1Jlc291cmNlcy9hc3NldHMvZGlzdFwiLFxyXG5cdFx0XHRlbXB0eU91dERpcjogdHJ1ZSxcclxuXHRcdFx0Y29tbW9uanNPcHRpb25zOiB7XHJcblx0XHRcdFx0dHJhbnNmb3JtTWl4ZWRFc01vZHVsZXM6IHRydWUsXHJcblx0XHRcdH0sXHJcblx0XHR9LFxyXG5cdFx0cm9vdEVudixcclxuXHRcdHBrZ0VudixcclxuXHRcdHBsdWdpbnM6IFtcclxuXHRcdFx0dnVlKCksXHJcblx0XHRcdHZ1ZURldlRvb2xzKHtcclxuXHRcdFx0XHRhcHBlbmRUbzogXCJhZG1pbi5qc1wiLFxyXG5cdFx0XHR9KSxcclxuXHRcdFx0bGFyYXZlbCh7XHJcblx0XHRcdFx0aG90RmlsZTogY29uc3VtZXJSb290XHJcblx0XHRcdFx0XHQ/IGNvbnN1bWVyUm9vdCArIFwiL3B1YmxpYy9kb3hhLWFkbWluLXZpdGUuaG90XCJcclxuXHRcdFx0XHRcdDogZmFsc2UsXHJcblx0XHRcdFx0cHVibGljRGlyZWN0b3J5OiBcIi4vc3JjL1Jlc291cmNlcy9hc3NldHMvZGlzdFwiLFxyXG5cdFx0XHRcdGlucHV0OiBbXHJcblx0XHRcdFx0XHRcIi4vc3JjL1Jlc291cmNlcy9hc3NldHMvanMvYWRtaW4uanNcIixcclxuXHRcdFx0XHRcdFwiLi9zcmMvUmVzb3VyY2VzL2Fzc2V0cy9jc3MvYWRtaW4uY3NzXCIsXHJcblx0XHRcdFx0XSxcclxuXHRcdFx0XHRyZWZyZXNoOiB0cnVlLFxyXG5cdFx0XHR9KSxcclxuXHRcdF0sXHJcblx0XHRyZXNvbHZlOiB7XHJcblx0XHRcdGFsaWFzOiB7XHJcblx0XHRcdFx0dnVlOiBcInZ1ZS9kaXN0L3Z1ZS5lc20tYnVuZGxlci5qc1wiLFxyXG5cdFx0XHR9LFxyXG5cdFx0fSxcclxuXHR9O1xyXG59KTtcclxuIl0sCiAgIm1hcHBpbmdzIjogIjtBQUFtVCxTQUFTLGNBQWMsZUFBZTtBQUN6VixPQUFPLGFBQWE7QUFDcEIsT0FBTyxTQUFTO0FBQ2hCLE9BQU8saUJBQWlCO0FBQ3hCLE9BQU8sVUFBVTtBQUVqQixJQUFPLHNCQUFRLGFBQWEsQ0FBQyxFQUFFLEtBQUssTUFBTTtBQUN6QyxRQUFNLFlBQVksS0FBSyxRQUFRLFFBQVE7QUFDdkMsUUFBTSxTQUFTLFFBQVEsTUFBTSxTQUFTO0FBRXRDLFFBQU0sZUFBZSxRQUFRO0FBRTdCLFFBQU0sYUFDTCxRQUFRLGlCQUFpQixVQUFVLGVBQWU7QUFFbkQsUUFBTSxVQUFVLFFBQVEsTUFBTSxVQUFVO0FBQ3hDLFNBQU8sT0FBTyxRQUFRLEtBQUssU0FBUyxNQUFNO0FBRTFDLFNBQU87QUFBQSxJQUNOLE1BQU07QUFBQSxJQUNOLE9BQU87QUFBQSxNQUNOLGdCQUFnQjtBQUFBLE1BQ2hCLFFBQVE7QUFBQSxNQUNSLGFBQWE7QUFBQSxNQUNiLGlCQUFpQjtBQUFBLFFBQ2hCLHlCQUF5QjtBQUFBLE1BQzFCO0FBQUEsSUFDRDtBQUFBLElBQ0E7QUFBQSxJQUNBO0FBQUEsSUFDQSxTQUFTO0FBQUEsTUFDUixJQUFJO0FBQUEsTUFDSixZQUFZO0FBQUEsUUFDWCxVQUFVO0FBQUEsTUFDWCxDQUFDO0FBQUEsTUFDRCxRQUFRO0FBQUEsUUFDUCxTQUFTLGVBQ04sZUFBZSxnQ0FDZjtBQUFBLFFBQ0gsaUJBQWlCO0FBQUEsUUFDakIsT0FBTztBQUFBLFVBQ047QUFBQSxVQUNBO0FBQUEsUUFDRDtBQUFBLFFBQ0EsU0FBUztBQUFBLE1BQ1YsQ0FBQztBQUFBLElBQ0Y7QUFBQSxJQUNBLFNBQVM7QUFBQSxNQUNSLE9BQU87QUFBQSxRQUNOLEtBQUs7QUFBQSxNQUNOO0FBQUEsSUFDRDtBQUFBLEVBQ0Q7QUFDRCxDQUFDOyIsCiAgIm5hbWVzIjogW10KfQo=
