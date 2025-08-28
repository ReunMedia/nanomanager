<script lang="ts">
  interface ListFilesData {
    files: string[];
  }

  export const getFiles = async () => {
    const apiUrl = "http://localhost:8080";

    const response = await fetch(
      `${apiUrl}?` +
        new URLSearchParams({
          operation: "listFiles",
        }),
    );
    const data: ListFilesData = await response.json();
    return data;
  };
</script>

<ul>
  {#await getFiles() then data}
    {#each data.files as file (file)}
      <li>{file}</li>
    {/each}
  {/await}
</ul>
