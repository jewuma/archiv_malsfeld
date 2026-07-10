<template>
  <div>
    <input v-if="selectable && node.type == 'file'" type="checkbox" @click.stop="selectionChanged(node.id)" />
    <div class="tree-node" @click="clicked">
      <span v-if="hasChildren" class="toggle">
        {{ open ? "▼" : "▶" }}
      </span>
      <span v-else class="toggle-space"></span>
      📁
      <span :class="{ 'highlight': isMatch }">
        {{ node.name }}
      </span>
    </div>
    <div v-if="open" class="children">
      <TreeNode v-for="child in node.children" :key="child.id" :node="child" :search="search"
        @select="$emit('select', $event)" />
    </div>
  </div>
</template>
<script>
export default {
  name: "TreeNode",
  props: {
    node: {
      type: Object,
      required: true
    },
    search: {
      type: String,
      default: ""
    },
    selectable: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      open: true,
      checkedFiles: []
    }
  },
  computed: {
    hasChildren() {
      return this.node.children
        && this.node.children.length > 0;
    },
    isMatch() {
      if (!this.search)
        return false;
      return this.node.name
        .toLowerCase()
        .includes(
          this.search.toLowerCase()
        );
    }
  },
  watch: {
    search() {
      if (this.search)
        this.open = true;
    }
  },
  methods: {
    clicked() {
      this.$emit(
        "select",
        this.node
      );
      if (this.hasChildren)
        this.open = !this.open;
    },
    selectionChanged(nodeId) {
      this.$emit("file-selected", nodeId);
    }
  }


};
</script>
<style scoped>
.tree-node {
  cursor: pointer;
  padding: 3px 5px;
}

.tree-node:hover {
  background: #f0f0f0;
}

.toggle {
  display: inline-block;
  width: 20px;
}

.toggle-space {
  display: inline-block;
  width: 20px;
}

.children {
  margin-left: 20px;
}

.highlight {
  font-weight: bold;
}
</style>