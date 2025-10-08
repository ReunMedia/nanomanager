<script lang="ts">
  import { fade } from "svelte/transition";
  import { toasts } from "../utils/toasts";
  import { sineOut } from "svelte/easing";
</script>

<dialog open={toasts.size > 0 || true}>
  <ul>
    {#each toasts.entries() as [id, text] (id)}
      <li transition:fade={{ easing: sineOut }} class="bordered">{text}</li>
    {/each}
  </ul>
</dialog>

<style>
  dialog {
    position: fixed;
    margin: auto;
    bottom: 0;
    border: none;
    background: none;
  }

  li {
    margin-top: 1em;
  }
</style>
