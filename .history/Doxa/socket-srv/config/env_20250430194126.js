import { config } from "dotenv";
import { resolve } from "path";
import path from "path";

let pkgEnv = path.resolve("../../.env");
pkgEnv = config({ path: pkgEnv });
let envDir = pkgEnv.VITE_CONSUMER_PROJECT_PATH;


config({ path: resolve(envDir) });

export default process.env;
