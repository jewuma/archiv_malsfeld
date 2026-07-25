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

            <div class="row mb-3">
              <div class="col-3">
                <label class="form-label">Analogobjekt-Nr.</label>
                <input class="form-control" v-model="analogObjekt.archiv_id" @keyup="analogObjektExists">
              </div>
              <div class="col-9">
                <label class="form-label">Analogobjekt</label>
                <button v-if="!analogObjekt.id && analogObjekt.archiv_id.length > 4"
                  class="form-control btn btn-success" @click="showAnalogObjektAnlegen = true">erstellen</button>
                <input v-else type="text" class="form-control" disabled :value="archivObjekt.titel">
              </div>
            </div>

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
            <div class="row mb-3">

              <div class="col-3">
                <label class="form-label">Ab Jahr</label>
                <input class="form-control" type="text" v-model="archivObjekt.zeitraum_start">
              </div>
              <div class="col-3">
                <label class="form-label">Ergänzung</label>
                <input class="form-control" type="text" v-model="archivObjekt.start_ergaenzung">
              </div>

              <div class="col-3">
                <label class="form-label">Bis Jahr</label>
                <input class="form-control" type="text" v-model="archivObjekt.zeitraum_ende">
              </div>
              <div class="col-3">
                <label class="form-label">Ergänzung</label>
                <input class="form-control" type="text" v-model="archivObjekt.ende_ergaenzung">
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-sm-3 col-form-label">Titel</label>
              <div class="col-sm-9">
                <input class="form-control" v-model="archivObjekt.titel">
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-sm-3 col-form-label">Beschreibung</label>
              <div class="col-sm-9">
                <textarea class="form-control" rows="3" v-model="archivObjekt.beschreibung"></textarea>
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-3">
                <label class="form-label">Dokumentdatum</label>
                <input class="form-control" v-model="datei.dateidatum" @blur="normalizeDokumentDatum">
              </div>
              <div class="col-5">
                <label class="form-label">Kurztitel</label>
                <input class="form-control" v-model="kurztitel">
              </div>
              <div class="col-1">
                <label class="form-label">Sperre</label>
                <div class="form-check form-switch m-0">
                  <input class="form-check-input large-switch pt-2" type="checkbox" id="geschuetzt"
                    v-model="datei.gesperrt">
                </div>
              </div>
              <div v-if="datei.gesperrt" class="col-2">
                <label class="form-label">Sperre bis</label>
                <input class="form-control" type="text" v-model="datei.gesperrt_bis">
              </div>
            </div>
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
    <AnalogObjektAnlegen v-if="showAnalogObjektAnlegen" :analog-objekt="analogObjekt"
      @create-analog="createAnalogObjekt" @cancel="showAnalogObjektAnlegen = false" />
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
        id: null,
        zeitraum_start: null,
        start_ergaenzung: null,
        zeitraum_ende: null,
        ende_ergaenzung: null,
        titel: "",
        beschreibung: "",
        ort_id: 4,
        dateiPfad: "",
        analogObjekte: [],
        dateien: [],
      },
      analogObjekt: {
        id: null,
        archiv_id: "",
        objekttyp_id: 1,
        seiten: null,
        quellen_id: 0,
        gesperrt: false,
        gesperrt_bis: (parseInt(new Date().toISOString().substring(0, 4)) + 25).toString(),
        lagerort_id: 1,
        regal_id: null,
        fach_id: null,
        platz_id: null,
        digitalisiert: 2
      },
      datei: {
        pfad: "",
        dateiname: "",
        objekttyp_id: 1,
        quellen_id: 0,
        gesperrt: false,
        gesperrt_bis: (parseInt(new Date().toISOString().substring(0, 4)) + 25).toString(),
        dateidatum: null,
      },
      analogTitel: "",
      eingang: [],
      kurztitel: "",
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
      if (this.datei.gesperrt)
        parts.push("ZYX");
      if (this.archivObjekt.dokumentDatum) {
        parts.push(this.formatDokumentDatum(this.archivObjekt.dokumentDatum));
      } else if (this.archivObjekt.zeitraum_start || this.archivObjekt.zeitraum_ende) {
        let von = this.archivObjekt.zeitraum_start || "0000";
        let bis = this.archivObjekt.zeitraum_ende || von;
        parts.push(`${von}bis${bis}`);
      }
      if (this.kurztitel) {
        parts.push(this.makeFilename(this.kurztitel));
      }
      if (this.analogObjekt.archiv_id) {
        parts.push(this.makeFilename(this.analogObjekt.archiv_id.toString()));
      }
      if (this.archivObjekt.ort_id !== 0 && this.orte.length > 0) {
        const ortsname = this.orte.find(ort => { return ort.id === this.archivObjekt.ort_id })?.name
        parts.push(ortsname.substr(0, 2))
      }
      return this.storeBasePath + this.selectedSpeicherpfad + "/" + parts.join("_") + ".pdf";
    },
    isPfadSelected() {
      return (this.storeBasePath + this.selectedSpeicherpfad).split("/").length > 1 && this.archivObjekt.kurztitel.length > 3
    }
  },
  watch: {
    "archivObjekt.titel"(neu, alt) {
      if (this.kurztitel === "" || this.kurztitel === alt) {
        this.kurztitel = neu;
      }
    }
  },
  async created() {
    const orte = await this.$axios.get("/Orte/getAll");
    this.orte = orte.data.data
  },
  async mounted() {
    this.refreshTree()
    this.$refs.ortInput.focus()
  },
  methods: {
    async analogObjektExists() {
      this.archivObjekt.titel = ""
      if (this.analogObjekt.archiv_id.length > 4) {
        const existResponse = await this.$axios.get("/Analogobjekte/getByArchivId/" + this.analogObjekt.archiv_id)
        if (existResponse.data.data.length > 0) {
          let an = existResponse.data.data[0]
          this.analogObjekt = {
            id: an.an_id,
            archiv_id: an.archiv_id,
            objekttyp_id: an.objekttyp_id,
            seiten: an.seiten,
            titel: an.titel,
            beschreibung: an.beschreibung,
            quellen_id: an.quellen_id,
            gesperrt: an.gesperrt,
            gesperrt_bis: an.gesperrt_bis,
            lagerort_id: an.lagerort_id,
            regal_id: an.regal_id,
            fach_id: an.fach_id,
            platz_id: an.platz_id,
            digitalisiert: an.digitalisiert
          };
          this.archivObjekt.id = an.ao_id
          this.archivObjekt.ort_id = an.ort_id
          this.archivObjekt.titel = an.titel
          this.archivObjekt.beschreibung = an.beschreibung
          this.archivObjekt.zeitraum_start = an.zeitraum_start
          this.archivObjekt.start_ergaenzung = an.start_ergaenzung
          this.archivObjekt.zeitraum_ende = an.zeitraum_ende
          this.archivObjekt.ende_ergaenzung = an.ende_ergaenzung
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
    createAnalogObjekt(objekt) {
      this.analogObjekt = objekt
      this.showAnalogObjektAnlegen = false;
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

.large-switch {
  transform: scale(1.4);
  transform-origin: left center;
}

.own-color {
  background-color: blanchedalmond;
}
</style>