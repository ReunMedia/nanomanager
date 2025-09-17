<script lang="ts">
  import { apiRequest } from "../utils/apiRequest";
  import FileListItem from "./FileListItem.svelte";

  export const getFiles = async () => {
    return (await apiRequest("listFiles", {})).data;
  };
</script>

<table>
  {#await getFiles() then data}
    {#each data.files as file (file)}
      <FileListItem filename={file} />
    {/each}
  {/await}
</table>
