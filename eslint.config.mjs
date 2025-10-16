// eslint.config.mjs
// @ts-check
import tseslint from "typescript-eslint";
import createReunMediaConfig from "@reunmedia/eslint-config";
import { globalIgnores } from "eslint/config";

export default tseslint.config([
  ...(await createReunMediaConfig(import.meta.url)),
  globalIgnores(["dist"]),
]);
