<script lang="ts">
  import { apiRequest } from "../utils/apiRequest";
  import FileListItem from "./FileListItem.svelte";
  import FileUpload from "./FileUpload.svelte";

  let files = $state<string[]>([]);
  let baseUrl = $state("");

  async function fetchFiles() {
    const data = (await apiRequest("listFiles", {})).data;
    files = data.files;
    baseUrl = data.baseUrl;
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

<ul class="file-list">
  <li>
    <FileUpload {onUploaded} />
  </li>
  {#each files as file (file)}
    <li>
      <FileListItem {baseUrl} filename={file} {onDeleted} {onRenamed} />
    </li>
  {/each}
</ul>

<style>
  .file-list {
    li {
      display: flex;
      gap: 2em;
      flex-direction: row;
      min-height: 1px;
    }

    & li + li {
      margin-top: 1em;
      padding-top: 1em;
      border-top: var(--color-border) solid 1px;
    }
  }
</style>
