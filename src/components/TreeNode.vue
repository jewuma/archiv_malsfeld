<template>
  <div>
    <div class="tree-node" @click="clicked">
      <input v-if="selectable && node.type == 'file'" type="checkbox" v-model="selected" :value="node.path"
        @change="fileSelected(node.path, selected)" />
      <span v-if="hasChildren" class="toggle">
        {{ open ? "▼" : "▶" }}
      </span>
      <span v-else-if="node.type === 'directory'" class="toggle-space"></span>
      {{ icon }}
      {{ node.name }}
      <div v-if="selectable && node.type == 'file'" class="tree-actions">
        <i v-if="node.canMoveUp" class="bi bi-arrow-up-circle me-2" @click.stop="$emit('move-up', node, siblings)"></i>
        <i v-if="node.canMoveDown" class="bi bi-arrow-down-circle me-2"
          @click.stop="$emit('move-down', node, siblings)"></i>
        <i class="bi bi-eye-fill me-2" @click.stop="$emit('preview', node)"></i>
      </div>
    </div>
    <div v-if="open" class="children">
      <TreeNode v-for="child in node.children" :key="child.path" :node="child" :siblings="node.children"
        :search="search" :selectable="selectable" @select="$emit('select', $event)" @preview="preview" @move-up="moveUp"
        @move-down="moveDown" @load-children="loadChildren" @file-selected="fileSelected" />
    </div>
  </div>
</template>
<script>
export default {
  name: "TreeNode",
  emits: ["move-up", "move-down", "select", "preview", "file-selected"],
  props: {
    node: {
      type: Object,
      required: true
    },
    siblings: {
      Array,
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
      selected: false,
    }
  },
  computed: {
    hasChildren() {
      return this.node.children
        && this.node.children.length > 0;
    },
    icon() {

      if (this.node.type === "directory")
        return "📁";

      switch ((this.node.mimeType ?? "").toLowerCase()) {

        case "application/pdf":
          return "📕";

        case "image/jpeg":
          return "🖼️";

        case "image/png":
          return "🖼️";

        case "image/tiff":
          return "🖼️";

        case "video/mp4":
          return "🎬";

        case "audio/mpeg":
          return "🎵";

        default:
          return "📄";
      }
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
      this.$emit("select", this.node);
      if (this.hasChildren)
        this.open = !this.open;
    },
    loadChildren(node) {
      this.$emit('load-children', node)
    },
    moveUp(node, siblings) {
      this.$emit('move-up', node, siblings)
    },
    moveDown(node, siblings) {
      this.$emit('move-down', node, siblings)
    },
    preview(node) {
      this.$emit("preview", node)
    },
    fileSelected(nodePath, value) {
      this.$emit("file-selected", nodePath, value);
    }
  }


};
</script>
<style scoped>
.tree-node {
  cursor: pointer;
  padding: 3px 5px;
  display: flex;
  align-items: center;
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


.tree-actions {
  display: flex;
  margin-left: auto;
  align-items: center;
}
</style>