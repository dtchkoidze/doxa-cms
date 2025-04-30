import { config } from "dotenv";
import { resolve } from "path";
import path from "path";

const pkgEnv = path.resolve("../../env");
pkgEnv = config({ path: pkgEnv });
console.log(pkgEnv);


config({ path: resolve("../../../.env") });

export default process.env;
