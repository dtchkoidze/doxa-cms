import { config } from "dotenv";
import { resolve } from "path";
import path from "path";

// Load .env from two directories up
config({ path: path.resolve("../../.env") });

// Now access the variable
let envDir = process.env.VITE_CONSUMER_PROJECT_PATH;
console.log("envDir is: ", envDir);

// Load another .env file (optional, will overwrite existing vars unless `override: false`)
config({ path: resolve("../../../.env") });

export default process.env;
