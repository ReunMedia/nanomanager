// @ts-check

/**
 * This ESLint configuration extends the root configuration by adding
 * project-specific plugins.
 */

import tseslint from "typescript-eslint";
import rootConfig from "../../eslint.config.mjs";
import svelte from "eslint-plugin-svelte";
import globals from "globals";
import svelteConfig from "./svelte.config.js";

export default tseslint.config(
  ...rootConfig,
  ...svelte.configs.recommended,
  {
    languageOptions: {
      globals: {
        ...globals.browser,
      },
    },
  },
  {
    files: ["**/*.svelte", "**/*.svelte.ts", "**/*.svelte.js"],
    languageOptions: {
      parserOptions: {
        projectService: true,
        extraFileExtensions: [".svelte"], // Add support for additional file extensions, such as .svelte
        parser: tseslint.parser,
        svelteConfig,
      },
    },
  },
);
