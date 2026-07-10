<template>
  <div class="digital">
    <div class="d-flex justify-content-between mb-2">
      <h5 class="mb-0">Digitalobjekte</h5>
      <label class="btn btn-primary btn-sm mb-0">
        + Datei hinzufügen
        <input type="file" multiple hidden @change="dateienAuswaehlen">
      </label>
    </div>
    <!-- Dropzone -->
    <div class="border rounded p-4 text-center mb-3 bg-light" @dragover.prevent @drop.prevent="dropHandler">
      Dateien hierher ziehen oder oben auswählen
    </div>
    <!-- Liste -->
    <div v-if="lokal.length > 0" class="table-responsive">
      <table class="table table-sm table-bordered align-middle">
        <thead class="table-light">
          <tr>
            <th>Name</th>
            <th>Typ</th>
            <th>Größe</th>
            <th style="width: 60px;"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(datei, index) in lokal" :key="index">
            <td>{{ datei.name }}</td>
            <td>{{ datei.typ }}</td>
            <td>{{ formatSize(datei.groesse) }}</td>
            <td class="text-center">
              <button class="btn btn-danger btn-sm" @click="entfernen(index)">×</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div v-else class="text-muted text-center py-3">
      Keine Dateien vorhanden
    </div>
    <div class="row">
      <div class="col-6">
        <TreeView :tree="eingang" @select="pfadGewählt" title="Posteingang" selectable
          @file-selected="gewaehlteDateien" />
      </div>
      <div class="col-6">
        <TreeView :tree="tree" @select="pfadGewählt" title="Zielverzeichnis" />
      </div>
    </div>
  </div>
</template>
<script>
import TreeView from './TreeView.vue';
export default {
  components: {
    TreeView
  },
  props: {
    modelValue: {
      type: Array,
      required: true
    }
  },
  data() {
    return {
      tree: [],
      selectedFiles: [],

    }
  },
  emits: ["update:modelValue"],
  computed: {
    lokal: {
      get() {
        return this.modelValue;
      },
      set(value) {
        this.$emit("update:modelValue", value);
      }
    }
  },
  async created() {
    const treeResposne = await this.$axios.get("/ArchivFiles/getTree")
    this.tree = treeResposne.data.data
  },
  methods: {
    addFiles(fileList) {
      const neueDateien = Array.from(fileList).map(f => ({
        id: null,
        datei: f,
        name: f.name,
        typ: f.type,
        groesse: f.size
      }));
      this.lokal = [...this.lokal, ...neueDateien];
    },
    dateiGewaehlt(dateien) {
      this.selectedFiles = dateien
    },
    dateienAuswaehlen(event) {
      this.addFiles(event.target.files);
      event.target.value = null;
    },
    dropHandler(event) {
      this.addFiles(event.dataTransfer.files);
    },
    entfernen(index) {
      const kopie = [...this.lokal];
      kopie.splice(index, 1);
      this.lokal = kopie;
    },

    formatSize(bytes) {
      if (bytes < 1024) return bytes + " B";
      if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + " KB";
      return (bytes / (1024 * 1024)).toFixed(1) + " MB";
    },
    pfadGewählt(node) {
      console.log(node);
      this.zielPfad = node;
    }
  }
};
</script>
<style scoped>
.digital {
  background-color: #baebd3;
}
</style>