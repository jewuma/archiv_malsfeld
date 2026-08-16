<template>
  <div>
    <MessageDialog title="Datei hinzufügen" message="Welche Datei(en) möchten Sie hinzufügen?" cancel-text="Abbruch"
      @confirm="confirmAddDatei" @cancel="closeAddDateiDialog" />
    <TreeView :tree="eingang" title="" :showSearch="true" :selectable="true" @file-selected="fileSelected"
      @refresh-tree="refreshTree" />
  </div>
</template>

<script>
import MessageDialog from './MessageDialog.vue';
import TreeView from './TreeView.vue';

export default {
  name: 'DateiHinzuDialog',
  components: {
    MessageDialog,
    TreeView,
  },
  props: {
    eingang: {
      type: Array,
      required: true
    }
  },
  methods: {
    confirmAddDatei() {
      this.$emit('confirm-add-datei');
    },
    closeAddDateiDialog() {
      this.$emit('close-add-datei-dialog');
    },
    fileSelected(file) {
      this.$emit('file-selected', file);
    },
    refreshTree() {
      this.$emit('refresh-tree');
    }
  }
};
</script>