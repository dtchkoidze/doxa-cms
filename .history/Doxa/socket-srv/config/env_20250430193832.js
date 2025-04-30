import { config } from "dotenv";
import { resolve } from "path";


const pkgEnv = path.resolve("../../env");
pkgEnv = loadEnv(mode, pkgEnvDir);
console.log(pkgEnv);


config({ path: resolve("../../../.env") });

export default process.env;
