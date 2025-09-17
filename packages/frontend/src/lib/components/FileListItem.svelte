<script lang="ts">
  import { showToast } from "../utils/showToast";

  interface Props {
    filename: string;
  }

  let { filename }: Props = $props();

  /**
   * Keeps track of previous filename during renaming.
   */
  let previousName = $state("");
  let currentName = $state(filename);
  let renamingActive = $derived(previousName !== "");

  /**
   * Input element
   */
  let inputEl: HTMLInputElement | undefined;

  function onClickRename() {
    previousName = currentName;

    // Focus filename input and select text before file extension
    const caretPosition = currentName.lastIndexOf(".");
    inputEl?.focus();
    inputEl?.setSelectionRange(0, caretPosition);
  }

  function onClickRenameCancel() {
    currentName = previousName;
    previousName = "";

    // Deselect input
    inputEl?.blur();
    document.getSelection()?.empty();
  }

  function onClickRenameConfirm() {
    console.log("TODO - Send rename API operation and wait for response");
    console.log("TODO - Show toast on rename success / failure");
    previousName = "";
  }
</script>

<tr>
  <td style="width: 100%">
    <input
      readonly={renamingActive.valueOf() === false}
      bind:value={currentName}
      bind:this={inputEl}
    />
  </td>
  <td>
    <div role="group">
      {#if renamingActive}
        <button onclick={onClickRenameCancel}>❌</button>
        <button onclick={onClickRenameConfirm}>✅</button>
      {:else}
        <button onclick={onClickRename}>✏️</button>
        <button>🗑️</button>
      {/if}
    </div>
  </td>
</tr>
