<script lang="ts">
  import { apiRequest } from "../utils/apiRequest";
  import { showToast } from "../utils/toasts";

  interface Props {
    /**
     * Initial filename passed via props. Use `currentName` instead of this.
     */
    filename: string;
    /**
     * Base URL used for file link
     */
    baseUrl: string;
    /**
     * Called after file is renamed.
     */
    onRenamed: (oldName: string, newName: string) => void;
    /**
     * Called after file is deleted.
     */
    onDeleted: (filename: string) => void;
  }

  let { filename, baseUrl, onDeleted, onRenamed }: Props = $props();

  let activeOperation = $state<ConfirmableOperation | null>(null);
  /**
   * Current filename input value
   */
  let currentName = $state(filename);
  /**
   * Input element
   */
  let inputEl: HTMLInputElement | undefined;
  /**
   * URL to file
   */
  let fileUrl = $derived(baseUrl.replace(/\/+$/, "") + "/" + currentName);

  interface ConfirmableOperation {
    /**
     * Text displayed before cancel and confirm buttons
     */
    confirmationText?: string;

    /**
     * Called when confirm button is pressed
     */
    confirm(): void;

    /**
     * Called when cancel button is pressed
     */
    cancel(): void;

    /**
     * Called when button to activate operation is pressed
     */
    activate?(): void;
  }

  class RenameOperation implements ConfirmableOperation {
    /**
     * Keeps track of previous filename during renaming.
     */
    previousName = $state("");

    confirm = async () => {
      // Don't run API call if name didn't change or we're not editing a name
      if (this.previousName === "" || this.previousName === currentName) {
        this.cancel();
        return;
      }

      const response = await apiRequest("renameFile", {
        oldName: this.previousName,
        newName: currentName,
      });

      const { newName } = response.data;

      if (newName === currentName) {
        // TODO - Show toast on successful rename
        onRenamed(this.previousName, currentName);
      } else {
        // TODO - Show toast on rename failure
        currentName = this.previousName;
      }

      this.previousName = "";

      activeOperation = null;
    };

    cancel = () => {
      currentName = this.previousName;
      this.previousName = "";

      // Deselect input
      inputEl?.blur();
      document.getSelection()?.empty();

      activeOperation = null;
    };

    activate = async () => {
      // Wait for filename input to render after setting it to `display: block`
      await new Promise(requestAnimationFrame);

      this.previousName = currentName;

      // Focus filename input and select text before file extension
      const caretPosition = currentName.lastIndexOf(".");
      inputEl?.focus();
      inputEl?.setSelectionRange(0, caretPosition);
    };

    onInputKeydown = (e: KeyboardEvent) => {
      if (e.key === "Enter") {
        this.confirm();
      } else if (e.key === "Escape") {
        this.cancel();
      }
    };
  }

  class DeleteOperation implements ConfirmableOperation {
    confirmationText = "Delete file?";

    confirm = async () => {
      const response = await apiRequest("deleteFile", {
        filename: currentName,
      });

      if (response.data.success === true) {
        onDeleted(currentName);
      }

      activeOperation = null;
    };

    cancel = () => {
      activeOperation = null;
    };
  }

  const renameOperation = new RenameOperation();
  const deleteOperation = new DeleteOperation();

  function activateOperation(operation: ConfirmableOperation) {
    activeOperation = operation;
    activeOperation.activate?.();
  }

  function onClickCopyLink() {
    navigator.clipboard.writeText(fileUrl);
    showToast("Link copied to clipboard");
  }
</script>

<div class="filename-container">
  <input
    style:display={activeOperation === renameOperation ? "" : "none"}
    bind:value={currentName}
    bind:this={inputEl}
    onkeydown={renameOperation.onInputKeydown}
  />

  <a
    class="filename-link bordered"
    style:display={activeOperation === renameOperation ? "none" : ""}
    href={fileUrl}>{currentName}</a
  >
</div>

<div class="button-container">
  {#if activeOperation?.confirmationText}
    <p class="confirmation-text">{activeOperation.confirmationText}</p>
  {/if}
  <div role="group">
    {#if activeOperation}
      <button onclick={activeOperation.cancel}>❌</button>
      <button onclick={activeOperation.confirm}>✅</button>
    {:else}
      <button onclick={onClickCopyLink}>🔗</button>
      <button onclick={() => activateOperation(renameOperation)}>✏️</button>
      <button onclick={() => activateOperation(deleteOperation)}>🗑️</button>
    {/if}
  </div>
</div>

<style>
  .filename-link {
    text-decoration: none;
    /* We're using `.bordered` to make `<a>` visually identical to input but
       hide the actual border */
    border-color: transparent;
    width: 100%;
  }

  .filename-container {
    flex: 1;
    display: flex;
  }

  .button-container {
    display: flex;
    align-items: center;
    gap: 1em;
  }
  .confirmation-text {
    white-space: nowrap;
  }
</style>
