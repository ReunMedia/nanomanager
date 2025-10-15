<svelte:options customElement="nano-filemanager" />

<script lang="ts">
  import AppFooter from "./lib/components/AppFooter.svelte";
  import FileList from "./lib/components/FileList.svelte";
  import Toasts from "./lib/components/Toasts.svelte";
</script>

<div class="container">
  <main>
    <h1>Nano File Manager</h1>
    <FileList />
    <Toasts />
  </main>
  <AppFooter />
</div>

<style>
  .container {
    display: flex;
    flex-direction: column;
    flex: 1;
    max-width: 960px;
    padding-top: 2em;
    width: 100%;
    margin: 0 auto;
  }

  main {
    flex: 1;
  }
</style>
