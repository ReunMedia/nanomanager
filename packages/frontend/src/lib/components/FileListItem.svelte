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

  let activeOperation = $state<ConfirmableOperation | null>(null);
  /**
   * Current filename input value
   */
  let currentName = $state(filename);
  /**
   * Input element
   */
  let inputEl: HTMLInputElement | undefined;

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

    activate = () => {
      this.previousName = currentName;

      // Focus filename input and select text before file extension
      const caretPosition = currentName.lastIndexOf(".");
      inputEl?.focus();
      inputEl?.setSelectionRange(0, caretPosition);
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
</script>

<tr>
  <td style="width: 100%">
    <input
      readonly={activeOperation !== renameOperation}
      bind:value={currentName}
      bind:this={inputEl}
    />
  </td>
  <td>
    <div class="button-container">
      {#if activeOperation?.confirmationText}
        <p class="confirmation-text">{activeOperation.confirmationText}</p>
      {/if}
      <div role="group">
        {#if activeOperation}
          <button onclick={activeOperation.cancel}>❌</button>
          <button onclick={activeOperation.confirm}>✅</button>
        {:else}
          <button onclick={() => activateOperation(renameOperation)}>✏️</button>
          <button onclick={() => activateOperation(deleteOperation)}>🗑️</button>
        {/if}
      </div>
    </div>
  </td>
</tr>

<style>
  .button-container {
    display: flex;
    align-items: center;
    float: right;
    gap: 1em;
  }
  .confirmation-text {
    white-space: nowrap;
  }
</style>
