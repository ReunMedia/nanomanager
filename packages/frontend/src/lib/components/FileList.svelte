<script lang="ts">
  import { apiRequest } from "../utils/apiRequest";
  import FileListItem from "./FileListItem.svelte";
  import FileUpload from "./FileUpload.svelte";

  let files = $state<string[]>([]);

  async function fetchFiles() {
    files = (await apiRequest("listFiles", {})).data.files;
  }

  fetchFiles();

  function onRenamed(oldName: string, newName: string) {
    const index = files.indexOf(oldName);
    if (index !== -1) {
      files[index] = newName;
    }
    sortFiles();
  }

  function onDeleted(filename: string) {
    const index = files.indexOf(filename);
    if (index !== -1) {
      files.splice(index, 1);
    }
  }

  function onUploaded(uploadedFiles: string[]) {
    // Using `Set()` makes sure there are no duplicates in case a file is
    // uploaded with a same name that already exsits.
    files = [...new Set([...files, ...uploadedFiles])];
    sortFiles();
  }

  function sortFiles() {
    files.sort((a, b) => a.localeCompare(b, "en", { ignorePunctuation: true }));
  }
</script>

<table>
  <FileUpload {onUploaded} />
  {#each files as file (file)}
    <FileListItem filename={file} {onDeleted} {onRenamed} />
  {/each}
</table>
