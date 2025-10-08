<script lang="ts">
  import type { Snippet } from "svelte";

  interface Props {
    content: Snippet;
    cancelLabel: string;
    confirmLabel: string;
    dialog?: HTMLDialogElement;
  }

  let {
    content,
    cancelLabel,
    confirmLabel,
    dialog = $bindable(),
  }: Props = $props();
</script>

<dialog class="bordered" bind:this={dialog}>
  {@render content()}
  <form method="dialog">
    <button value="cancel">{cancelLabel}</button>
    <button value="confirm">{confirmLabel}</button>
  </form>
</dialog>

<style>
  dialog {
    padding: 1em;
    margin: 0;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: var(--color-bg);
    box-shadow: rgba(0, 0, 0, 0.2) 0px 2px 8px 0px;
    opacity: 0;

    /* easeOutSine */
    transition: all 0.2s cubic-bezier(0.61, 1, 0.88, 1) allow-discrete;

    &::backdrop {
      background-color: transparent;
      transition: all 0.2s cubic-bezier(0.61, 1, 0.88, 1) allow-discrete;
    }

    &:open {
      opacity: 1;

      &::backdrop {
        background-color: rgba(0, 0, 0, 0.25);
      }
    }

    form {
      margin-top: 2em;
      display: flex;
      justify-content: space-between;
      gap: 1em;
    }
  }

  @starting-style {
    dialog:open {
      opacity: 0;
      &::backdrop {
        background-color: transparent;
      }
    }
  }
</style>
