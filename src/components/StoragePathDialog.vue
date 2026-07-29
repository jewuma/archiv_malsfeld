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
        <TreeView :tree="tree" @select="selectNode" add-folder-allowed @add-folder="askForFoldername" />
      </div>
    </div>
    <MessageDialog v-if="showAddFolderDialog" title="Unterordner anlegen" message="Neuen Unterordner anlegen?"
      confirm-text="Anlegen" cancel-text="Abbruch" @confirm="addFolder" @cancel="showAddFolderDialog = false">
      <input v-model="newFolderName" type="text" class="form-control" placeholder="Name des Unterordners">
    </MessageDialog>
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
      showAddFolderDialog: false,
      newFolderName: "",
      currentNode: null
    }
  },
  async created() {
    this.refreshTree()
  },
  methods: {
    askForFoldername(node) {
      this.showAddFolderDialog = true
      this.newFolderName = ""
      this.currentNode = node
    },
    async addFolder() {
      const folderName = this.newFolderName
      const path = this.basePath + this.currentNode.path
      if (folderName) {
        try {
          await this.$axios.post("/ArchivFiles/createFolder", { "directory": path, "folderName": folderName })
          this.refreshTree()
          this.selectedPath = path + "/" + folderName
          this.$sendMsg(false, "Unterordner erfolgreich angelegt")
        } catch (error) {
          this.$sendMsg(true, "Fehler beim Anlegen des Unterordners: " + error.response.data.message)
        }
        //await this.$axios.post("/ArchivFiles/createFolder", { "directory": path, "folderName": folderName })
        //this.refreshTree()
      }
      this.showAddFolderDialog = false
    },
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