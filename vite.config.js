import { defineConfig } from "vite";
import adminViteConfig from "./packages/Doxa/Admin/vite.config.js";
import userViteConfig from "./packages/Doxa/User/vite.config.js";

export default defineConfig({
	// Common Vite configuration options go here (like server, plugins)
	build: {
		outDir: "public/build",
	},
	plugins: [
		// Shared plugins across all subpackages
	],
	resolve: {
		alias: {
			"@admin": "/packages/Doxa/Admin/resources",
			"@user": "/packages/Doxa/User/resources",
		},
	},
	// Import and apply subpackage-specific Vite configurations
	...adminViteConfig, // Admin-specific Vite config
	...userViteConfig, // User-specific Vite config
});
