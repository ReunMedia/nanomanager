<svelte:options customElement="nano-file-manager" />

<script lang="ts">
  import AppFooter from "./lib/components/AppFooter.svelte";
  import FileList from "./lib/components/FileList.svelte";
  import Toasts from "./lib/components/Toasts.svelte";
  import { store } from "./lib/store/store.svelte";

  interface Props {
    /**
     * Full URL to Nanomanager PHP API
     *
     * E.g. `https:example.com/admin/nanomanager`
     */
    "api-url": string;

    /**
     * Override automatic theme that is based on `prefers-color-scheme`
     *
     * Can be used to match theme when embedding to existing website.
     */
    theme?: "dark" | "light";
  }

  let { theme, "api-url": apiUrl }: Props = $props();

  store.apiUrl =
    apiUrl === undefined && import.meta.env.DEV
      ? "http://localhost:8080"
      : apiUrl;
</script>

<div part="body" class={["body", theme]}>
  <div class="container">
    <main>
      <h1>Nano File Manager</h1>
      <FileList />
      <Toasts />
    </main>
    <AppFooter />
  </div>
</div>

<style>
  /*****************
   * Global styles *
   *****************/

  /*
   * Set reusable theme variables
   */
  .body {
    /* DRY theme toggle insipred by
       https://css-tricks.com/a-dry-approach-to-color-themes-in-css/ */
    --ON: initial;
    --OFF: ;

    --rounded: 0.5em;
    /* Colors are roughly based on this palette: https://coolors.co/palette/0d1b2a-1b263b-415a77-778da9-e0e1dd */
    --color-text: var(--dark, #dfe5ec) var(--light, #0d1b2a);
    --color-bg: var(--dark, #0d1b2a) var(--light, #e5edf7);
    --color-border: #415a77;
    --color-highlight: #778da9;
  }

  /* Dark mode by default */
  .body,
  .body.dark {
    --dark: var(--ON);
    --light: var(--OFF);
  }

  /* Selected light mode */
  .body.light {
    --dark: var(--OFF);
    --light: var(--ON);
  }

  /* Automatic light mode */
  @media (prefers-color-scheme: light) {
    .body:not(.dark):not(.light) {
      --dark: var(--OFF);
      --light: var(--ON);
    }
  }

  /* Minimal CSS reset */
  :global(*) {
    margin: 0;
    color: var(--color-text);
    &,
    &:before,
    &:after {
      box-sizing: border-box;
    }
  }

  :global(ul) {
    padding: 0;
    list-style: none;
  }

  :global(input),
  :global(button),
  :global(textarea),
  :global(select) {
    font: inherit;
  }

  :global(input) {
    width: 100%;
  }

  :global(input),
  :global(input::file-selector-button),
  :global(button),
  :global(.bordered) {
    color: inherit;
    background: transparent;
    border: var(--color-border) solid 1px;
    border-radius: var(--rounded);
    padding: 0.5em 1em;
  }

  :global(input[type="file"]) {
    /* Negate padding for file input in favor of `::file-selector-button`
       padding */
    padding: 0 0 0 1em;
    border-color: transparent;
    outline: none;

    &::file-selector-button {
      /* More space between file input button and text */
      margin-right: 1em;
    }
  }

  /* Button group */
  /* TODO - This could be refactored into a Svelte component */
  :global(div[role="group"]) {
    display: flex;

    & button {
      border-radius: 0;
    }

    & button:first-child {
      border-radius: var(--rounded) 0 0 var(--rounded);
    }

    & button + button {
      border-left: 0;
    }

    & button:last-child {
      border-radius: 0 var(--rounded) var(--rounded) 0;
    }
  }

  /* Match file input and upload button height */
  :global(button),
  :global(input::file-selector-button) {
    height: 100%;
  }

  :global(button:hover:not(:disabled)),
  :global(input::file-selector-button:hover) {
    background: var(--color-highlight);
    cursor: pointer;
  }

  :global(button:disabled) {
    opacity: 0.75;
  }

  /****************
   * Local styles *
   ****************/

  .body {
    background: var(--color-bg);
    /* https://systemfontstack.com/ */
    font-family:
      -apple-system,
      BlinkMacSystemFont,
      avenir next,
      avenir,
      segoe ui,
      helvetica neue,
      Adwaita Sans,
      Cantarell,
      Ubuntu,
      roboto,
      noto,
      helvetica,
      arial,
      sans-serif;
    /* More readable line height */
    line-height: 1.5;
    /* Use fixed font size on body so that we can use `em` units for all sizes */
    font-size: 16px;
    width: 100%;
    height: 100%;
    display: flex;
    overflow-y: auto;
  }

  .container {
    display: flex;
    flex-direction: column;
    flex: 1;
    max-width: 960px;
    width: 100%;
    margin: 0 auto;
  }

  main {
    flex: 1;
  }

  h1 {
    margin: 2rem 1rem;
  }
</style>
