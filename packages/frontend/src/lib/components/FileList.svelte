<script lang="ts">
  import { apiRequest } from "../utils/apiRequest";

  export const getFiles = async () => {
    return (await apiRequest("listFiles", {})).data;
  };
</script>

<ul>
  {#await getFiles() then data}
    {#each data.files as file (file)}
      <li>{file}</li>
    {/each}
  {/await}
</ul>
