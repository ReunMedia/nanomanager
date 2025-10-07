<script lang="ts">
  import { apiRequest } from "../utils/apiRequest";

  interface Props {
    /**
     * Called after files are successfully uploaded.
     *
     * @var uploadedFiles string[] List of files succesfully uploaded
     */
    onUploaded: (uploadedFiles: string[]) => void;
  }

  let { onUploaded }: Props = $props();

  /**
   * Input element
   */
  let inputEl: HTMLInputElement | undefined;

  async function onClickUpload() {
    if (!inputEl) {
      return;
    }

    const files = inputEl.files ?? null;
    if (files === null) {
      return;
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
