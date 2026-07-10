<template>
  <h5>{{ title }}</h5>
  <div class="treeview">
    <input v-if="showSearch" class="form-control mb-2" v-model="search" placeholder="Suchen...">
    <TreeNode v-for="node in filteredTree" :key="node.id" :node="node" :search="search" :selectable="selectable"
      @select="selectNode" @file-selected="fileSelected" />
  </div>
</template>


<script>
import TreeNode from "./TreeNode.vue";


export default {

  name: "TreeView",
  emits: ["file-selected", "select"],
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
    selectNode(node) {
      this.$emit("select", node);
    },
    fileSelected(files) {
      this.$emit("file-selected", files);
    }
  }
};

</script>