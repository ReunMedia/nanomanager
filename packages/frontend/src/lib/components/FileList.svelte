<script lang="ts">
  import { apiRequest } from "../utils/apiRequest";
  import FileListItem from "./FileListItem.svelte";

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
  }

  function onDeleted(filename: string) {
    const index = files.indexOf(filename);
    if (index !== -1) {
      files.splice(index, 1);
    }
  }
</script>

<table>
  {#each files as file (file)}
    <FileListItem filename={file} {onDeleted} {onRenamed} />
  {/each}
</table>
