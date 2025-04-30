import { config } from "dotenv";
import { resolve } from "path";
import path from "path";

config({ path: path.resolve("../../.env") });

let envDir = process.env.VITE_CONSUMER_PROJECT_PATH;
console.log("envDir is: ", envDir);

config({ path: resolve("../../../.env") });

export default process.env;
