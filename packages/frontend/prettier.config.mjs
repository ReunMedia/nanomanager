/**
 * This Prettier configuration extends the root configuration by adding
 * project-specific plugins.
 */

import rootConfig from "../../prettier.config.mjs";

/** @type {import("prettier").Config} */
export default {
  ...rootConfig,
  plugins: [
    ...(rootConfig.plugins ?? []),
    "prettier-plugin-svelte",
  ],
  overrides: [
    ...(rootConfig.overrides ?? []),
    {
      files: "*.svelte",
      options: { parser: "svelte" },
    }
  ],
};
