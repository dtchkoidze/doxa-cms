import { defineConfig, loadEnv } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import vueDevTools from "vite-plugin-vue-devtools";

export default defineConfig(({ mode }) => {
	const envDir = "../../../";
	Object.assign(process.env, loadEnv(mode, envDir));

	return {
		base: "/doxa/user/",
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
				appendTo: "user.js",
			}),
			laravel({
				hotFile: "../../../public/doxa-user-vite.hot",
				publicDirectory: "./src/Resources/assets/dist",
				input: [
					"./src/Resources/assets/js/user.js",
					"./src/Resources/assets/css/user.css",
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
