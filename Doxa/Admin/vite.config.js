import { defineConfig, loadEnv } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import vueDevTools from "vite-plugin-vue-devtools";
import path from "path";

export default defineConfig(({ mode }) => {
	const envDir = "../../";
	let p = path.resolve(envDir);
	Object.assign(process.env, loadEnv(mode, p));
	let consumerRoot = process.env.VITE_CONSUMER_PROJECT_PATH;
	if (!consumerRoot) {
		throw new Error(
			"VITE_CONSUMER_PROJECT_PATH environment variable is not set."
		);
	}
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
		envDir,
		plugins: [
			vue(),
			vueDevTools({
				appendTo: "admin.js",
			}),
			laravel({
				hotFile: consumerRoot ? consumerRoot + "/public/doxa-admin-vite.hot" : false,
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
