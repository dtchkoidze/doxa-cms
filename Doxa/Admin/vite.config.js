import { defineConfig, loadEnv } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import vueDevTools from "vite-plugin-vue-devtools";
import path from "path";

export default defineConfig(({ mode }) => {
	const pkgEnvDir = path.resolve("../../");
	const pkgEnv = loadEnv(mode, pkgEnvDir);

	const consumerRoot = pkgEnv?.VITE_CONSUMER_PROJECT_PATH;

	const rootEnvDir =
		pkgEnv?.VITE_PKG_MODE == "local" ? consumerRoot : "../../../";

	const rootEnv = loadEnv(mode, rootEnvDir);
	Object.assign(process.env, rootEnv, pkgEnv);

	return {
		base: "/doxa/admin/",
		build: {
			buildDirectory: "",
			outDir: "./src/Resources/assets/dist",
			emptyOutDir: true,
			commonjsOptions: {
				transformMixedEsModules: true,
			},
		},
		rootEnv,
		pkgEnv,
		plugins: [
			vue(),
			vueDevTools({
				appendTo: "admin.js",
			}),
			laravel({
				hotFile: consumerRoot
					? consumerRoot + "/public/doxa-admin-vite.hot"
					: false,
				publicDirectory: "./src/Resources/assets/dist",
				input: [
					"./src/Resources/assets/js/admin.js",
					"./src/Resources/assets/css/admin.css",
				],
				refresh: true,
			}),
		],
		resolve: {
			alias: {
				vue: "vue/dist/vue.esm-bundler.js",
			},
		},
	};
});
