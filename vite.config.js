import { defineConfig } from "vite";
import adminViteConfig from "./packages/Doxa/Admin/vite.config.js";
import userViteConfig from "./packages/Doxa/User/vite.config.js";

export default defineConfig({
	build: {
		outDir: "public/build",
	},
	plugins: [
	],
	resolve: {
		alias: {
			"@admin": "/packages/Doxa/Admin/resources",
			"@user": "/packages/Doxa/User/resources",
		},
	},
	...adminViteConfig, 
	...userViteConfig,  
});
