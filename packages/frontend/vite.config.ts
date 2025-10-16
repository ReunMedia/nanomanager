import { defineConfig } from "vite";
import { svelte } from "@sveltejs/vite-plugin-svelte";
import liveReload from "vite-plugin-live-reload";
import { ViteMinifyPlugin } from "vite-plugin-minify";
import cssnano from "cssnano";
import { version, license, author } from "./package.json";

// https://vite.dev/config/
export default defineConfig({
  plugins: [svelte(), liveReload(["../php/src/**/*.php"]), ViteMinifyPlugin()],
  css: {
    postcss: {
      plugins: [cssnano],
    },
  },
  define: {
    "import.meta.env.PACKAGE_VERSION": JSON.stringify(version),
  },
  build: {
    rollupOptions: {
      output: {
        entryFileNames: `nanomanager.js`,
        banner: `
/*! Nano File Manager version ${version}
 * Copyright © 2025 ${author}
 * Licensed under ${license}
 */`,
      },
    },
  },
});
