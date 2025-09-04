// vite.config.js
import { defineConfig, loadEnv } from "file:///C:/xampp/htdocs/doxa-cms.loc/Doxa/User/node_modules/vite/dist/node/index.js";
import laravel from "file:///C:/xampp/htdocs/doxa-cms.loc/Doxa/User/node_modules/laravel-vite-plugin/dist/index.js";
import vue from "file:///C:/xampp/htdocs/doxa-cms.loc/Doxa/User/node_modules/@vitejs/plugin-vue/dist/index.mjs";
import vueDevTools from "file:///C:/xampp/htdocs/doxa-cms.loc/Doxa/User/node_modules/vite-plugin-vue-devtools/dist/vite.mjs";
import path from "path";
var vite_config_default = defineConfig(({ mode }) => {
  const pkgEnvDir = path.resolve("../../");
  const pkgEnv = loadEnv(mode, pkgEnvDir);
  const consumerRoot = pkgEnv?.VITE_CONSUMER_PROJECT_PATH;
  const rootEnvDir = pkgEnv?.VITE_PKG_MODE == "local" ? consumerRoot : "../../../";
  const rootEnv = loadEnv(mode, rootEnvDir);
  Object.assign(process.env, rootEnv, pkgEnv);
  return {
    base: "/doxa/user/",
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
        appendTo: "user.js"
      }),
      laravel({
        hotFile: consumerRoot ? consumerRoot + "/public/doxa-user-vite.hot" : false,
        publicDirectory: "./src/Resources/assets/dist",
        input: [
          "./src/Resources/assets/js/user.js",
          "./src/Resources/assets/css/user.css"
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
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsidml0ZS5jb25maWcuanMiXSwKICAic291cmNlc0NvbnRlbnQiOiBbImNvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9kaXJuYW1lID0gXCJDOlxcXFx4YW1wcFxcXFxodGRvY3NcXFxcZG94YS1jbXMubG9jXFxcXERveGFcXFxcVXNlclwiO2NvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9maWxlbmFtZSA9IFwiQzpcXFxceGFtcHBcXFxcaHRkb2NzXFxcXGRveGEtY21zLmxvY1xcXFxEb3hhXFxcXFVzZXJcXFxcdml0ZS5jb25maWcuanNcIjtjb25zdCBfX3ZpdGVfaW5qZWN0ZWRfb3JpZ2luYWxfaW1wb3J0X21ldGFfdXJsID0gXCJmaWxlOi8vL0M6L3hhbXBwL2h0ZG9jcy9kb3hhLWNtcy5sb2MvRG94YS9Vc2VyL3ZpdGUuY29uZmlnLmpzXCI7aW1wb3J0IHsgZGVmaW5lQ29uZmlnLCBsb2FkRW52IH0gZnJvbSBcInZpdGVcIjtcclxuaW1wb3J0IGxhcmF2ZWwgZnJvbSBcImxhcmF2ZWwtdml0ZS1wbHVnaW5cIjtcclxuaW1wb3J0IHZ1ZSBmcm9tIFwiQHZpdGVqcy9wbHVnaW4tdnVlXCI7XHJcbmltcG9ydCB2dWVEZXZUb29scyBmcm9tIFwidml0ZS1wbHVnaW4tdnVlLWRldnRvb2xzXCI7XHJcbmltcG9ydCBwYXRoIGZyb20gXCJwYXRoXCI7XHJcblxyXG5leHBvcnQgZGVmYXVsdCBkZWZpbmVDb25maWcoKHsgbW9kZSB9KSA9PiB7XHJcblx0Y29uc3QgcGtnRW52RGlyID0gcGF0aC5yZXNvbHZlKFwiLi4vLi4vXCIpO1xyXG5cdGNvbnN0IHBrZ0VudiA9IGxvYWRFbnYobW9kZSwgcGtnRW52RGlyKTtcclxuXHJcblx0Y29uc3QgY29uc3VtZXJSb290ID0gcGtnRW52Py5WSVRFX0NPTlNVTUVSX1BST0pFQ1RfUEFUSDtcclxuXHJcblx0Y29uc3Qgcm9vdEVudkRpciA9XHJcblx0XHRwa2dFbnY/LlZJVEVfUEtHX01PREUgPT0gXCJsb2NhbFwiID8gY29uc3VtZXJSb290IDogXCIuLi8uLi8uLi9cIjtcclxuXHJcblx0Y29uc3Qgcm9vdEVudiA9IGxvYWRFbnYobW9kZSwgcm9vdEVudkRpcik7XHJcblx0T2JqZWN0LmFzc2lnbihwcm9jZXNzLmVudiwgcm9vdEVudiwgcGtnRW52KTtcclxuXHJcblx0cmV0dXJuIHtcclxuXHRcdGJhc2U6IFwiL2RveGEvdXNlci9cIixcclxuXHRcdGJ1aWxkOiB7XHJcblx0XHRcdGJ1aWxkRGlyZWN0b3J5OiBcIlwiLFxyXG5cdFx0XHRvdXREaXI6IFwiLi9zcmMvUmVzb3VyY2VzL2Fzc2V0cy9kaXN0XCIsXHJcblx0XHRcdGVtcHR5T3V0RGlyOiB0cnVlLFxyXG5cdFx0XHRjb21tb25qc09wdGlvbnM6IHtcclxuXHRcdFx0XHR0cmFuc2Zvcm1NaXhlZEVzTW9kdWxlczogdHJ1ZSxcclxuXHRcdFx0fSxcclxuXHRcdH0sXHJcblx0XHRyb290RW52LFxyXG5cdFx0cGtnRW52LFxyXG5cdFx0cGx1Z2luczogW1xyXG5cdFx0XHR2dWUoKSxcclxuXHRcdFx0dnVlRGV2VG9vbHMoe1xyXG5cdFx0XHRcdGFwcGVuZFRvOiBcInVzZXIuanNcIixcclxuXHRcdFx0fSksXHJcblx0XHRcdGxhcmF2ZWwoe1xyXG5cdFx0XHRcdGhvdEZpbGU6IGNvbnN1bWVyUm9vdFxyXG5cdFx0XHRcdFx0PyBjb25zdW1lclJvb3QgKyBcIi9wdWJsaWMvZG94YS11c2VyLXZpdGUuaG90XCJcclxuXHRcdFx0XHRcdDogZmFsc2UsXHJcblx0XHRcdFx0cHVibGljRGlyZWN0b3J5OiBcIi4vc3JjL1Jlc291cmNlcy9hc3NldHMvZGlzdFwiLFxyXG5cdFx0XHRcdGlucHV0OiBbXHJcblx0XHRcdFx0XHRcIi4vc3JjL1Jlc291cmNlcy9hc3NldHMvanMvdXNlci5qc1wiLFxyXG5cdFx0XHRcdFx0XCIuL3NyYy9SZXNvdXJjZXMvYXNzZXRzL2Nzcy91c2VyLmNzc1wiLFxyXG5cdFx0XHRcdF0sXHJcblx0XHRcdFx0cmVmcmVzaDogdHJ1ZSxcclxuXHRcdFx0fSksXHJcblx0XHRdLFxyXG5cdFx0cmVzb2x2ZToge1xyXG5cdFx0XHRhbGlhczoge1xyXG5cdFx0XHRcdHZ1ZTogXCJ2dWUvZGlzdC92dWUuZXNtLWJ1bmRsZXIuanNcIixcclxuXHRcdFx0fSxcclxuXHRcdH0sXHJcblx0fTtcclxufSk7XHJcbiJdLAogICJtYXBwaW5ncyI6ICI7QUFBZ1QsU0FBUyxjQUFjLGVBQWU7QUFDdFYsT0FBTyxhQUFhO0FBQ3BCLE9BQU8sU0FBUztBQUNoQixPQUFPLGlCQUFpQjtBQUN4QixPQUFPLFVBQVU7QUFFakIsSUFBTyxzQkFBUSxhQUFhLENBQUMsRUFBRSxLQUFLLE1BQU07QUFDekMsUUFBTSxZQUFZLEtBQUssUUFBUSxRQUFRO0FBQ3ZDLFFBQU0sU0FBUyxRQUFRLE1BQU0sU0FBUztBQUV0QyxRQUFNLGVBQWUsUUFBUTtBQUU3QixRQUFNLGFBQ0wsUUFBUSxpQkFBaUIsVUFBVSxlQUFlO0FBRW5ELFFBQU0sVUFBVSxRQUFRLE1BQU0sVUFBVTtBQUN4QyxTQUFPLE9BQU8sUUFBUSxLQUFLLFNBQVMsTUFBTTtBQUUxQyxTQUFPO0FBQUEsSUFDTixNQUFNO0FBQUEsSUFDTixPQUFPO0FBQUEsTUFDTixnQkFBZ0I7QUFBQSxNQUNoQixRQUFRO0FBQUEsTUFDUixhQUFhO0FBQUEsTUFDYixpQkFBaUI7QUFBQSxRQUNoQix5QkFBeUI7QUFBQSxNQUMxQjtBQUFBLElBQ0Q7QUFBQSxJQUNBO0FBQUEsSUFDQTtBQUFBLElBQ0EsU0FBUztBQUFBLE1BQ1IsSUFBSTtBQUFBLE1BQ0osWUFBWTtBQUFBLFFBQ1gsVUFBVTtBQUFBLE1BQ1gsQ0FBQztBQUFBLE1BQ0QsUUFBUTtBQUFBLFFBQ1AsU0FBUyxlQUNOLGVBQWUsK0JBQ2Y7QUFBQSxRQUNILGlCQUFpQjtBQUFBLFFBQ2pCLE9BQU87QUFBQSxVQUNOO0FBQUEsVUFDQTtBQUFBLFFBQ0Q7QUFBQSxRQUNBLFNBQVM7QUFBQSxNQUNWLENBQUM7QUFBQSxJQUNGO0FBQUEsSUFDQSxTQUFTO0FBQUEsTUFDUixPQUFPO0FBQUEsUUFDTixLQUFLO0FBQUEsTUFDTjtBQUFBLElBQ0Q7QUFBQSxFQUNEO0FBQ0QsQ0FBQzsiLAogICJuYW1lcyI6IFtdCn0K
