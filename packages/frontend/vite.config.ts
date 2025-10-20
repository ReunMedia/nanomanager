import { defineConfig } from "vite";
import { svelte } from "@sveltejs/vite-plugin-svelte";
import liveReload from "vite-plugin-live-reload";
import { ViteMinifyPlugin } from "vite-plugin-minify";
import cssnano from "cssnano";
import { version, license, author } from "./package.json";
import { dirname, resolve } from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = dirname(fileURLToPath(import.meta.url));

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
    lib: {
      entry: resolve(__dirname, "src/main.ts"),
      name: "Nanomanager",
      fileName: "nanomanager",
      // We're manually running esbuild again in `package.json` build script to
      // minify output afterwards. At the time of writing, Vite doesn't support
      // minifying ES modules.
      //
      // - https://github.com/vitejs/vite/issues/5167
      // - https://github.com/vitejs/vite/issues/6079
      // - https://github.com/vitejs/vite/issues/6555
      // - https://github.com/vitejs/vite/pull/6585
      // - https://github.com/vitejs/vite/pull/18737
      formats: ["es"],
    },
    rollupOptions: {
      output: {
        banner: `
/*! Nano File Manager version ${version}
 * Copyright © 2025 ${author}
 * Licensed under ${license}
 */`,
      },
    },
  },
});
