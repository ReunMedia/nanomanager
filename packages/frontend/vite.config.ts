import { defineConfig } from "vite";
import { svelte } from "@sveltejs/vite-plugin-svelte";
import liveReload from "vite-plugin-live-reload";

// https://vite.dev/config/
export default defineConfig({
  plugins: [svelte(), liveReload(["../php/src/**/*.php"])],
});
