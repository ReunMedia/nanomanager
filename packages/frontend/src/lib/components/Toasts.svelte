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
    margin: 0 auto 1em auto;
    bottom: 0;
    border: none;
    background: none;
    padding: 0;
    /* Don't block pointer on invisible dialog overlay */
    pointer-events: none;
  }

  li {
    margin-top: 1em;
    background: var(--bg-modal);
    /* Re-enable pointer events on visible toasts */
    pointer-events: auto;

    box-shadow: rgba(0, 0, 0, 0.2) 0px 2px 8px 0px;
  }
</style>
