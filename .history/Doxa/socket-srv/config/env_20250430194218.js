import { config } from "dotenv";
import { resolve } from "path";
import path from "path";

let pkgEnv = path.resolve("../../.env");
pkgEnv = config({ path: pkgEnv });
let envDir = process.pkgEnv.VITE_CONSUMER_PROJECT_PATH;
console.log("envDir is: ", envDir);

config({ path: resolve("../../../.env") });

export default process.env;
