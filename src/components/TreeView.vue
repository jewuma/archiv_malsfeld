<template>
  <h5>{{ title }}</h5>
  <div class="treeview">
    <input v-if="showSearch" class="form-control mb-2" v-model="search" placeholder="Suchen...">
    <TreeNode v-for="node in filteredTree" :key="node.path" :node="node" :siblings="filteredTree" :search="search"
      :selectable="selectable" @select="selectNode" @file-selected="fileSelected"
      @load-children="$emit('load-children', $event)" @preview="$emit('preview', $event)" @move-up="moveUp"
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
      search: ""
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
    moveUp(node, siblings) {
      this.$emit("move-up", node, siblings)
    },
    moveDown(node, siblings) {
      this.$emit("move-down", node, siblings)
    },
    selectNode(node) {
      this.$emit("select", node);
    },
    fileSelected(path, isSelected) {
      this.$emit("file-selected", path, isSelected);
    }
  }
};

</script>