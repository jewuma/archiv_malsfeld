<template>
  <MessageDialog title="Speicherpfad wählen" confirm-text="Pfad übernehmen" cancel-text="Abbruch"
    :message="selectedPath" xl @confirm="pathSelected(selectedPath)" @cancel="$emit('cancel')"
    @refresh-tree="refreshTree">
    <div class="storage-dialog">

      <div class="selected-path">
        <span class="badge bg-primary">
          {{ selectedPath }}
        </span>
      </div>

      <div class="tree-container">
        <TreeView :tree="tree" @select="selectNode" />
      </div>

    </div>
  </MessageDialog>
</template>
<script>
import MessageDialog from './MessageDialog.vue';
import TreeView from './TreeView.vue';
export default {
  components: {
    MessageDialog,
    TreeView
  },
  props: {
    basePath: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      tree: [],
      selectedPath: "",
    }
  },
  async created() {
    this.refreshTree()
  },
  methods: {
    async refreshTree() {
      const treeResponse = await this.$axios.post("/ArchivFiles/getTree", { "directory": this.basePath })
      this.tree = treeResponse.data.data
    },
    selectNode(node) {
      this.selectedPath = node.path
    },
    pathSelected(path) {
      this.$emit("path-selected", path)
      console.log(path)
    }
  }
}
</script>
<style scoped>
.storage-dialog {
  width: 100%;
  min-width: 600px;
}

.selected-path {
  position: sticky;
  top: 0;
  background: white;
  padding-bottom: 10px;
  z-index: 10;
}

.tree-container {
  max-height: 60vh;
  overflow-y: auto;
}
</style>