<script lang="ts">
  import { apiRequest } from "../utils/apiRequest";
  import { waitForEvent } from "../utils/waitForEvent";
  import ModalDialog from "./ModalDialog.svelte";

  interface Props {
    /**
     * Called after files are successfully uploaded.
     *
     * @var uploadedFiles string[] List of files succesfully uploaded
     */
    onUploaded: (uploadedFiles: string[]) => void;

    /**
     * Set of all current files. Used to check for duplicates before uploading
     * and replacing them.
     */
    currentFiles: Set<string>;
  }

  let { onUploaded, currentFiles }: Props = $props();

  /**
   * Input element
   */
  let inputEl: HTMLInputElement | undefined;
  let confirmReplaceDialog: HTMLDialogElement | undefined = $state();
  /**
   * Populated when trying to upload duplicate files
   */
  let duplicateFiles = $state<string[]>([]);

  async function onClickUpload() {
    if (!inputEl || !confirmReplaceDialog) {
      return;
    }

    const files = inputEl.files ?? null;
    if (files === null) {
      return;
    }

    // Check if there are duplicate files that will be replaced and if so, show
    // a replace confirmation dialog.

    // TODO - Replace with `Set.intersection()` after updating TS compiler
    // target
    //
    // See:
    // https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Set/intersection#browser_compatibility
    duplicateFiles = [...files]
      .map((x) => x.name)
      .filter((x) => currentFiles.has(x));

    if (duplicateFiles.length > 0) {
      confirmReplaceDialog.showModal();
      await waitForEvent(confirmReplaceDialog, "close");
      if (confirmReplaceDialog.returnValue !== "confirm") {
        return;
      }
    }

    const response = await apiRequest("uploadFile", {
      files,
    });

    inputEl.value = "";
    onFileInputChange();

    onUploaded(response.data.uploadedFiles);
  }

  let uploadButtonEnabled = $state(false);

  function onFileInputChange() {
    uploadButtonEnabled =
      typeof inputEl?.files?.length === "number" && inputEl.files.length !== 0;
  }
</script>

<input type="file" onchange={onFileInputChange} bind:this={inputEl} multiple />
<div>
  <button
    disabled={!uploadButtonEnabled}
    style="width: 7em;"
    onclick={onClickUpload}>📤</button
  >
</div>

{#snippet dialogContent()}
  <p>The following files already exist and will be replaced</p>
  <ul style="margin-top: 1em;">
    {#each duplicateFiles as file (file)}
      <li>{file}</li>
    {/each}
  </ul>
{/snippet}

<ModalDialog
  content={dialogContent}
  cancelLabel="Cancel upload"
  confirmLabel="Replace files"
  bind:dialog={confirmReplaceDialog}
/>
