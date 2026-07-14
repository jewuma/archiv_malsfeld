<template>
  <h5>{{ title }}</h5>
  <div class="treeview">
    <div class="d-flex align-items-center mb-2">
      <input v-if="showSearch" class="form-control me-2" v-model="search" placeholder="Suchen...">

      <button type="button" class="btn btn-info" @click="$emit('refresh-tree')">
        <i class="bi bi-arrow-clockwise me-1"></i>
        Aktualisieren
      </button>
    </div>
    <TreeNode v-for="node in filteredTree" :key="node.path" :node="node" :siblings="filteredTree" :search="search"
      :selectable="selectable" @select="selectNode" @file-selected="fileSelected"
      @load-children="$emit('load-children', $event)" @preview="showPreview($event)" @move-up="moveUp"
      @move-down="moveDown" />
  </div>
</template>


<script>
import TreeNode from "./TreeNode.vue";


export default {

  name: "TreeView",
  emits: [
    "file-selected",
    "select",
    "load-children",
    "preview",
    "move-up",
    "move-down"
  ],
  components: {
    TreeNode
  },
  props: {

    tree: {
      type: Array,
      required: true
    },

    title: {
      type: String,
      default: ""
    },

    selectable: {
      type: Boolean,
      default: false
    },

    showSearch: {
      type: Boolean,
      default: true
    }

  },
  data() {
    return {
      search: "",
    }
  },
  computed: {
    filteredTree() {
      if (this.search.trim() === "")
        return this.tree;
      return this.filterNodes(this.tree);
    }
  },
  methods: {
    canMoveUp(node) {
      if (node.type !== "file" || node.sort === 0) return false;
      return true;
    },
    canMoveDown(node) {
      if (node.type !== "file") return false;
      const fileNodes = this.tree.filter(singleNode => {
        return singleNode.path === node.path && singleNode.type === "file";
      });
      return node.sort < fileNodes.length;
    },
    fileSelected(path, isSelected) {
      this.$emit("file-selected", path, isSelected);
    },
    filterNodes(nodes) {
      const result = [];
      nodes.forEach(node => {
        const children = this.filterNodes(
          node.children ?? []
        );
        const match = node.name
          .toLowerCase()
          .includes(
            this.search.toLowerCase()
          );
        if (match || children.length) {
          result.push({
            ...node,
            children
          });
        }
      });
      return result;
    },
    getMovableSiblings(siblings) {
      return siblings.filter(n => n.type === 'file');
    },
    moveUp(node, siblings) {
      const files = siblings.filter(n => n.type === 'file');

      const fileIndex = files.indexOf(node);

      if (fileIndex <= 0) {
        return;
      }

      const previousFile = files[fileIndex - 1];

      const nodeIndex = siblings.indexOf(node);
      const previousIndex = siblings.indexOf(previousFile);

      [siblings[nodeIndex], siblings[previousIndex]] =
        [siblings[previousIndex], siblings[nodeIndex]];

      this.updateMoveFlags(siblings);
    },
    moveDown(node, siblings) {
      const files = siblings.filter(n => n.type === 'file');

      const fileIndex = files.indexOf(node);

      if (fileIndex < 0 || fileIndex === files.length) {
        return;
      }

      const nextFile = files[fileIndex + 1];

      const nodeIndex = siblings.indexOf(node);
      const nextIndex = siblings.indexOf(nextFile);

      [siblings[nodeIndex], siblings[nextIndex]] =
        [siblings[nextIndex], siblings[nodeIndex]];

      this.updateMoveFlags(siblings);
    },
    selectNode(node) {
      this.$emit("select", node);
    },
    async showPreview(node) {
      const formData = new FormData()
      formData.append("path", node.path);
      formData.append("fromInbox", true);
      this.$axios.post(
        "/ArchivFiles/getByPath",
        formData,
        {
          responseType: "blob"
        }
      ).then(response => {
        const blob = new Blob([response.data], { type: "application/pdf" });
        const url = window.URL.createObjectURL(blob);
        window.open(url, "_blank");
      })
    },
    updateMoveFlags(siblings) {
      const files = this.getMovableSiblings(siblings);
      files.forEach((file, index) => {
        file.canMoveUp = index > 0;
        file.canMoveDown = index < files.length - 1;
      });
    }
  }
};

</script>