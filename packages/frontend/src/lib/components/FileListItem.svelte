<script lang="ts">
  interface Props {
    filename: string;
  }

  let { filename }: Props = $props();

  /**
   * Keeps track of previous filename during renaming.
   */
  let previousName = $state("");
  let currentName = $state(filename);
  let renamingActive = $derived(previousName !== "");

  function onClickRename() {
    previousName = currentName;
  }

  function onClickRenameCancel() {
    currentName = previousName;
    previousName = "";
  }

  function onClickRenameConfirm() {
    console.log("TODO - Send rename API operation and wait for response");
    console.log("TODO - Show toast on rename success / failure");
    previousName = "";
  }
</script>

<tr>
  <td style="width: 100%">
    <input
      readonly={renamingActive.valueOf() === false}
      bind:value={currentName}
    />
  </td>
  <td>
    <div role="group">
      {#if renamingActive}
        <button onclick={onClickRenameCancel}>❌</button>
        <button onclick={onClickRenameConfirm}>✅</button>
      {:else}
        <button onclick={onClickRename}>✏️</button>
        <button>🗑️</button>
      {/if}
    </div>
  </td>
</tr>
