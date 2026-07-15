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
              <select class="form-select" v-model="ort_id" @change="selectedSpeicherpfad = ''; storeBasePath = ''">
                <option value="">Bitte wählen...</option>
                <option v-for="o in orte" :key="o.id" :value="o.id">
                  {{ o.name }}
                </option>
              </select>
            </div>

            <!-- Zeitraum -->
            <div class="row">

              <div class="col-6 mb-3">
                <label class="form-label">Ab Jahr</label>
                <input class="form-control" type="number" v-model="abJahr">
              </div>

              <div class="col-6 mb-3">
                <label class="form-label">Bis Jahr</label>
                <input class="form-control" type="number" v-model="bisJahr">
              </div>

            </div>

            <div class="row">
              <div class="col-3 mb-3">
                <label class="form-label">Dokumentdatum</label>
                <input class="form-control" v-model="dokumentDatum">
              </div>
              <div class="col-9 mb-3">
                <label class="form-label">Kurztitel</label>
                <input class="form-control" v-model="kurztitel">
              </div>
            </div>
            <div class="row">
              <div class="col-3 mb-3">
                <label class="form-label">Analogobjekt-Nr.</label>
                <input class="form-control" v-model="analogNummer" @keyup="analogObjektExists">
              </div>
              <div class="col-9 mb-3">
                <label class="form-label">Analogobjekt</label>
                <button v-if="analogTitel === '' && analogNummer.length > 4" class="form-control btn btn-success"
                  @click="showAnalogObjektAnlegen = true">erstellen</button>
                <input v-else type="text" class="form-control" disabled :value="analogTitel">
              </div>
            </div>

            <!-- Gesperrt -->
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" id="gesperrt" v-model="gesperrt">
              <label class="form-check-label" for="gesperrt">
                Gesperrt
              </label>
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
              <button v-if="Object.keys(selectedFiles).length > 1" class="btn btn-primary" :disabled="!pfadSelected"
                @click="save">Zusammenfassen
                und speichern</button>
              <button v-else class="btn btn-primary"
                :disabled="Object.keys(selectedFiles).length === 0 || !pfadSelected" @click="save">Speichern</button>
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
    <StoragePathDialog v-if="showStoragDialog" ref="storageDialog" @path-selected="storagePathSelected"
      :base-path="storeBasePath" @cancel="showStoragDialog = false" @refresh-tree="refreshTree" />
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
      abJahr: "",
      analogNummer: "",
      analogTitel: "",
      bisJahr: "",
      dokumentDatum: "",
      eingang: [],
      gesperrt: false,
      kurztitel: "",
      ort_id: 0,
      orte: [],
      selectedFiles: {},
      selectedSpeicherpfad: "",
      showAnalogObjektAnlegen: false,
      showDeleteDialog: false,
      showStoragDialog: false,
      storeBasePath: "",
    };
  },
  computed: {

    speicherpfad() {
      let parts = [];
      if (this.gesperrt)
        parts.push("ZYX");
      if (this.dokumentDatum) {
        parts.push(this.formatDokumentDatum(this.dokumentDatum));
      } else if (this.abJahr || this.bisJahr) {
        let von = this.abJahr || "0000";
        let bis = this.bisJahr || von;
        parts.push(`${von}bis${bis}`);
      }
      if (this.kurztitel) {
        parts.push(this.makeFilename(this.kurztitel));
      }
      if (this.analogNummer) {
        parts.push(this.makeFilename(this.analogNummer));
      }
      if (this.ort_id !== 0) {
        const ortsname = this.orte.find(ort => { return ort.id === this.ort_id })?.name
        parts.push(ortsname.substr(0, 2))
      }
      return this.storeBasePath + this.selectedSpeicherpfad + "/" + parts.join("_") + ".pdf";
    },
    pfadSelected() {
      return (this.storeBasePath + this.selectedSpeicherpfad).split("/").length > 1 && this.kurztitel.length > 3
    }
  },
  async created() {
    const orte = await this.$axios.get("/Orte/getAll");
    this.orte = orte.data.data
    this.refreshTree()
  },
  methods: {
    async analogObjektExists() {
      this.analogTitel = ""
      if (this.analogNummer.length > 4) {
        const existResponse = await this.$axios.get("/Analogobjekte/getByArchivId/" + this.analogNummer)
        if (existResponse.data.data.length > 0) {
          this.analogTitel = existResponse.data.data[0].titel
        }
      }
    },
    chooseStoragePath() {
      const selectedOrt = this.orte.find(ort => { return ort.id === this.ort_id })
      if (selectedOrt === undefined) this.storeBasePath = ""
      else this.storeBasePath = selectedOrt.name + "/"
      this.showStoragDialog = true
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
        this.selectedFiles[path] = ""
      } else {
        delete this.selectedFiles[path];
      }
    },
    formatDokumentDatum(value) {
      value = value.trim();
      let m;
      if ((m = value.match(/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/))) {
        let tag = m[1].padStart(2, "0");
        let monat = m[2].padStart(2, "0");
        let jahr = m[3];
        return `${jahr}_${monat}${tag}`;
      }
      if ((m = value.match(/^(\d{1,2})\.(\d{4})$/))) {
        let monat = m[1].padStart(2, "0");
        let jahr = m[2];
        return `${jahr}_${monat}00`;
      }
      if ((m = value.match(/^(\d{4})$/))) {
        return `${m[1]}_0000`;
      }
      return value;
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
    },
    saveAnalogObject() {

    },
    async save() {
      const selectedFiles = this.collectSelected(this.eingang)
      await this.$axios.post("/ArchivFiles/saveFiles", {
        "files": selectedFiles,
        "targetPath": this.storeBasePath + this.selectedSpeicherpfad,
        "targetFilename": this.speicherpfad.split('/').pop()
      })
      if (selectedFiles.length === 1) {
        this.$sendMsg(false, "Datei gespeichert")
      } else {
        this.$sendMsg(false, "Dateien gespeichert")
      }
      this.refreshTree()
    },
    storagePathSelected(path) {
      this.selectedSpeicherpfad = path
      this.showStoragDialog = false
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