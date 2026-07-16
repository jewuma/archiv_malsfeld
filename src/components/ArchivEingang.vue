<template>
  <CardComponent title="Archiveingang bearbeiten" :fields="[]" :table-data="[]">
    <div class="row">
      <!-- Eingangsdateien -->
      <div class="col-lg-6 mb-3">
        <div class="card h-100">
          <div class="card-header">
            <h5 class="mb-0">Posteingang</h5>
          </div>
          <div class="card-body file-select-color">
            <TreeView :tree="eingang" title="" :showSearch="true" :selectable="true" @file-selected="fileSelected"
              @refresh-tree="refreshTree" />
          </div>
        </div>
      </div>
      <!-- Archivinformationen -->
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0">Archivinformationen</h5>
          </div>
          <div class="card-body own-color">
            <!-- Ort -->
            <div class="mb-3">
              <label class="form-label">Ort</label>
              <select class="form-select" v-model="archivObjekt.ort_id" ref="ortInput"
                @change="selectedSpeicherpfad = ''; storeBasePath = ''">
                <option value="">Bitte wählen...</option>
                <option v-for="o in orte" :key="o.id" :value="Number(o.id)">
                  {{ o.name }}
                </option>
              </select>
            </div>

            <!-- Zeitraum -->
            <div class="row">

              <div class="col-6 mb-3">
                <label class="form-label">Ab Jahr</label>
                <input class="form-control" type="number" v-model="archivObjekt.abJahr">
              </div>

              <div class="col-6 mb-3">
                <label class="form-label">Bis Jahr</label>
                <input class="form-control" type="number" v-model="archivObjekt.bisJahr">
              </div>

            </div>

            <div class="row">
              <div class="col-3 mb-3">
                <label class="form-label">Dokumentdatum</label>
                <input class="form-control" v-model="archivObjekt.dokumentDatum" @blur="normalizeDokumentDatum">
              </div>
              <div class="col-9 mb-3">
                <label class="form-label">Kurztitel</label>
                <input class="form-control" v-model="archivObjekt.kurztitel">
              </div>
            </div>
            <div class="row">
              <div class="col-3 mb-3">
                <label class="form-label">Analogobjekt-Nr.</label>
                <input class="form-control" v-model="archivObjekt.analogNummer" @keyup="analogObjektExists">
              </div>
              <div class="col-9 mb-3">
                <label class="form-label">Analogobjekt</label>
                <button v-if="analogTitel === '' && archivObjekt.analogNummer.length > 4"
                  class="form-control btn btn-success" @click="showAnalogObjektAnlegen = true">erstellen</button>
                <input v-else type="text" class="form-control" disabled :value="analogTitel">
              </div>
            </div>
            <div class="row">
              <div class="col-3 mb-3">
                <input class="form-check-input" type="checkbox" id="gesperrt" v-model="archivObjekt.gesperrt" />
                <label class="form-check-label" for="gesperrt">
                  Gesperrt
                </label>
              </div>
              <div class="col-9 mb-3">
                <input v-if="archivObjekt.gesperrt" class="form-control" type="number"
                  v-model="archivObjekt.gesperrtBis">
              </div>
            </div>

            <!-- Speicherpfad -->
            <div class="mb-3">
              <button class="btn btn-outline-secondary" @click="chooseStoragePath">
                Speicherpfad wählen...
              </button>
            </div>
            <div class="mb-3">
              <label class="form-label">Speicherpfad</label>
              <div class="row">
                <span class="badge bg-secondary">{{ speicherpfad }}</span>
              </div>
            </div>
            <hr>
            <div class="d-grid gap-2">
              <button v-if="saveMode === 2" class="btn btn-primary" :disabled="!isPfadSelected"
                @click="save">Zusammenfassen
                und speichern</button>
              <button v-else class="btn btn-primary" :disabled="saveMode === 0 || !isPfadSelected"
                @click="save">Speichern</button>
              <button class="btn btn-outline-danger" :disabled="Object.keys(selectedFiles).length === 0"
                @click="deleteSelected">
                Ausgewählte löschen
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal zur Auswahl des Speicherpfades -->
    <StoragePathDialog v-if="showStorageDialog" @path-selected="storagePathSelected" :base-path="storeBasePath"
      @cancel="showStorageDialog = false" @refresh-tree="refreshTree" />
    <AnalogObjektAnlegen v-if="showAnalogObjektAnlegen" :archiv-id="analogNummer"
      @cancel="showAnalogObjektAnlegen = false" />
    <MessageDialog v-if="showDeleteDialog" title="Ausgewählte Dateien wirklich löschen?"
      message="Sollen die gewählten Dateien wirklich gelöscht werden?" @confirm="deleteConfirmed"
      @cancel="showDeleteDialog = false" confirm-text="Löschen" />
  </CardComponent>
</template>

<script>
import CardComponent from "./CardComponent.vue";
import TreeView from "./TreeView.vue";
import StoragePathDialog from "@/components/StoragePathDialog.vue";
import AnalogObjektAnlegen from "./AnalogObjektAnlegen.vue";
import MessageDialog from "./MessageDialog.vue";
export default {

  components: {
    AnalogObjektAnlegen,
    CardComponent,
    MessageDialog,
    StoragePathDialog,
    TreeView,
  },

  data() {
    return {
      archivObjekt: {
        abJahr: 0,
        analogNummer: "",
        bisJahr: 0,
        dokumentDatum: "",
        gesperrt: false,
        gesperrtBis: 0,
        kurztitel: "",
        ort_id: 0,
        dateiPfad: "",
      },
      analogTitel: "",
      eingang: [],
      orte: [],
      saveMode: 0,
      selectedFiles: {},
      selectedSpeicherpfad: "",
      showAnalogObjektAnlegen: false,
      showDeleteDialog: false,
      showStorageDialog: false,
      storeBasePath: "",
    };
  },
  computed: {

    speicherpfad() {
      let parts = [];
      if (this.archivObjekt.gesperrt)
        parts.push("ZYX");
      if (this.archivObjekt.dokumentDatum) {
        parts.push(this.formatDokumentDatum(this.archivObjekt.dokumentDatum));
      } else if (this.archivObjekt.abJahr || this.archivObjekt.bisJahr) {
        let von = this.archivObjekt.abJahr || "0000";
        let bis = this.archivObjekt.bisJahr || von;
        parts.push(`${von}bis${bis}`);
      }
      if (this.archivObjekt.kurztitel) {
        parts.push(this.makeFilename(this.archivObjekt.kurztitel));
      }
      if (this.archivObjekt.analogNummer) {
        parts.push(this.makeFilename(this.archivObjekt.analogNummer));
      }
      if (this.archivObjekt.ort_id !== 0) {
        const ortsname = this.orte.find(ort => { return ort.id === this.archivObjekt.ort_id })?.name
        parts.push(ortsname.substr(0, 2))
      }
      return this.storeBasePath + this.selectedSpeicherpfad + "/" + parts.join("_") + ".pdf";
    },
    isPfadSelected() {
      return (this.storeBasePath + this.selectedSpeicherpfad).split("/").length > 1 && this.archivObjekt.kurztitel.length > 3
    }
  },
  async mounted() {
    const orte = await this.$axios.get("/Orte/getAll");
    this.orte = orte.data.data
    this.refreshTree()
    this.$refs.ortInput.focus()
  },
  methods: {
    async analogObjektExists() {
      this.analogTitel = ""
      if (this.archivObjekt.analogNummer.length > 4) {
        const existResponse = await this.$axios.get("/Analogobjekte/getByArchivId/" + this.archivObjekt.analogNummer)
        if (existResponse.data.data.length > 0) {
          this.analogTitel = existResponse.data.data[0].titel
        }
      }
    },
    chooseStoragePath() {
      const selectedOrt = this.orte.find(ort => { return ort.id === this.archivObjekt.ort_id })
      if (selectedOrt === undefined) this.storeBasePath = ""
      else this.storeBasePath = selectedOrt.name + "/"
      this.showStorageDialog = true
    },
    collectSelected(nodes, result = []) {
      for (const node of nodes) {
        if (
          node.type === "file" &&
          Object.prototype.hasOwnProperty.call(this.selectedFiles, node.path)
        ) {
          result.push(node.path);
        }

        if (node.children) {
          this.collectSelected(node.children, result);
        }
      }

      return result;
    },
    async deleteConfirmed() {
      this.showDeleteDialog = false
      const filesToDelete = this.collectSelected(this.eingang)
      await this.$axios.post("/ArchivFiles/deleteMulti", { "files": filesToDelete })
      this.refreshTree()
    },
    deleteSelected() {
      this.showDeleteDialog = true
    },
    fileSelected(path, isSelected) {
      if (isSelected) {
        this.selectedFiles[path] = true
      } else {
        delete this.selectedFiles[path];
      }
      this.setSaveMode()
    },
    formatDokumentDatum(value) {
      let m;
      if ((m = value.match(/^(\d{2})\.(\d{2})\.(\d{4})$/))) {
        return `${m[3]}_${m[2]}${m[1]}`;
      }
      if ((m = value.match(/^(\d{2})\.(\d{4})$/))) {
        return `${m[2]}_${m[1]}01`;
      }
      if ((m = value.match(/^(\d{4})$/))) {
        return `${m[1]}_0101`;
      }
      return "";
    },
    normalizeDokumentDatum() {
      let value = this.archivObjekt.dokumentDatum.replace(/\D/g, "");

      if (value.length === 8) {
        // DDMMYYYY -> DD.MM.YYYY
        this.archivObjekt.dokumentDatum =
          `${value.slice(0, 2)}.${value.slice(2, 4)}.${value.slice(4)}`;
      }
      else if (value.length === 6) {
        // MMYYYY -> MM.YYYY
        this.archivObjekt.dokumentDatum =
          `${value.slice(0, 2)}.${value.slice(2)}`;
      }
      else if (value.length === 4) {
        // YYYY
        this.archivObjekt.dokumentDatum = value;
      }
    },
    makeFilename(text) {

      return text
        .replace(/ä/g, "ae")
        .replace(/ö/g, "oe")
        .replace(/ü/g, "ue")
        .replace(/Ä/g, "Ae")
        .replace(/Ö/g, "Oe")
        .replace(/Ü/g, "Ue")
        .replace(/ß/g, "ss")
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/[^A-Za-z0-9]/g, "_")
        .replace(/_+/g, "_")
        .replace(/^_+|_+$/g, "");
    },
    async refreshTree() {
      const eingangResponse = await this.$axios.post("/ArchivFiles/getTree", { "directory": "archiveingang", "withFiles": true })
      this.eingang = eingangResponse.data.data
      this.setSaveMode()
    },
    saveAnalogObject() {

    },
    async save() {
      const selectedFiles = this.collectSelected(this.eingang)
      this.archivObjekt.dateiPfad = this.speicherpfad
      await this.$axios.post("/ArchivFiles/saveFiles", {
        "files": selectedFiles,
        "targetPath": this.storeBasePath + this.selectedSpeicherpfad,
        "targetFilename": this.speicherpfad.split('/').pop()
      })
      const saveObjekt = JSON.parse(JSON.stringify(this.archivObjekt))
      saveObjekt.dokumentDatum = this.formatDokumentDatum(saveObjekt.dokumentDatum).replace(/^(\d{4})_(\d{2})(\d{2})$/, "$1-$2-$3");
      await this.$axios.post("/Archiv/create", saveObjekt)
      if (selectedFiles.length === 1) {
        this.$sendMsg(false, "Datei gespeichert")
      } else {
        this.$sendMsg(false, "Dateien gespeichert")
      }
      this.refreshTree()
    },
    setSaveMode() {
      const files = this.collectSelected(this.eingang)
      if (files.length === 0) this.saveMode = 0
      else if (files.length === 1) this.saveMode = 1
      else this.saveMode = 2
    },
    storagePathSelected(path) {
      this.selectedSpeicherpfad = path
      this.showStorageDialog = false
    },
  }

};
</script>
<style scoped>
.file-select-color {
  background-color: azure;
}

.own-color {
  background-color: blanchedalmond;
}
</style>