<script lang="ts">
  import { apiRequest } from "../utils/apiRequest";

  interface Props {
    filename: string;
    /**
     * Called after file is renamed.
     */
    onRenamed: (oldName: string, newName: string) => void;
    /**
     * Called after file is deleted.
     */
    onDeleted: (filename: string) => void;
  }

  let { filename, onDeleted, onRenamed }: Props = $props();

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

  async function onClickDelete() {
    const response = await apiRequest("deleteFile", {
      filename: currentName,
    });

    if (response.data.success === true) {
      onDeleted(currentName);
    }
  }

  async function onClickRenameConfirm() {
    // Don't run API call if name didn't change or we're not editing a name
    if (previousName === "" || previousName === currentName) {
      onClickRenameCancel();
      return;
    }

    const response = await apiRequest("renameFile", {
      oldName: previousName,
      newName: currentName,
    });

    const { newName } = response.data;

    if (newName === currentName) {
      console.log("Rename successful");
      console.log("TODO - Show toast on successful rename");
      onRenamed(previousName, currentName);
    } else {
      console.log("Rename failed");
      console.log("TODO - Show toast on rename failure");
      currentName = previousName;
    }

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
        <button onclick={onClickDelete}>🗑️</button>
      {/if}
    </div>
  </td>
</tr>
