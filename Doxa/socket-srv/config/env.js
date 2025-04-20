import { config } from "dotenv";
import { resolve } from "path";

config({ path: resolve("../../../.env") });

export default process.env;
