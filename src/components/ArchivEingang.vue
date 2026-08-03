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
            <div class="row mb-3">
              <div class="col-3">
                <label class="form-label">Ort</label>
                <select class="form-select" v-model="archivObjekt.ort_id" ref="ortInput"
                  @change="selectedSpeicherpfad = ''; storeBasePath = ''">
                  <option value="">Bitte wählen...</option>
                  <option v-for="o in orte" :key="o.id" :value="Number(o.id)">
                    {{ o.name }}
                  </option>
                </select>
              </div>
              <div class="col-9">
                <label class="form-label">Thema</label>
                <select class="form-select" v-model="archivObjekt.themen_id">
                  <option value="">Bitte wählen...</option>
                  <option v-for="t in themen" :key="t.id" :value="Number(t.id)">
                    {{ t.name }}
                  </option>
                </select>
              </div>
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
                <input class="form-control" v-model="digitalObjekt.dateidatum" @blur="normalizeDokumentDatum">
              </div>
              <div class="col-5">
                <label class="form-label">Kurztitel</label>
                <input class="form-control" v-model="kurztitel">
              </div>
              <div class="col-1">
                <label class="form-label">Sperre</label>
                <div class="form-check form-switch m-0">
                  <input class="form-check-input large-switch mt-2" type="checkbox" id="geschuetzt"
                    v-model="digitalObjekt.gesperrt">
                </div>
              </div>
              <div v-if="digitalObjekt.gesperrt" class="col-2">
                <label class="form-label">Sperre bis</label>
                <input class="form-control" type="text" v-model="digitalObjekt.gesperrt_bis">
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-3 col-form-label">Quelle</label>
              <div class="col-6">
                <select class="form-select" v-model="digitalObjekt.quellen_id">
                  <option value="">Bitte wählen...</option>
                  <option v-for="quelle in quellen" :key="quelle.id" :value="Number(quelle.id)">
                    {{ quelle.name + ", " + quelle.vorname }}
                  </option>
                </select>
              </div>
              <div class="col-3">
                <button class="btn btn-outline-secondary text-nowrap" @click="chooseStoragePath">
                  Speicherpfad wählen...
                </button>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Speicherpfad</label>
              <div class="row">
                <span class="badge bg-secondary">{{ displaypfad }}</span>
              </div>
            </div>
            <hr>
            <div class="row g-2">
              <div class="col">
                <button class="btn btn-outline-danger w-100">
                  Ausgewählte löschen
                </button>
              </div>

              <div class="col">
                <span :title="saveDisabledReason">
                  <button class="btn btn-outline-primary w-100" @click="showOptions"
                    :disabled="filesSelected !== FilesSelected.Multiple">
                    Speicheroptionen
                  </button>
                </span>
              </div>

              <div class="col">
                <span :title="saveDisabledReason">
                  <button class="btn btn-primary w-100" @click="save" :disabled="!isPfadSelected || nonPdfSelected">
                    {{ filesSelected === FilesSelected.Multiple ? "Zusammenfassen und speichern" : "Speichern" }}
                  </button>
                </span>
              </div>
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
    <ArchivSaveOptionen v-if="showOptionDialog" :files="filesToSave" @cancel="showOptionDialog = false"
      @apply="saveOptionsApplied" />
  </CardComponent>
</template>

<script>
import CardComponent from "./CardComponent.vue";
import TreeView from "./TreeView.vue";
import StoragePathDialog from "@/components/StoragePathDialog.vue";
import AnalogObjektAnlegen from "./AnalogObjektAnlegen.vue";
import MessageDialog from "./MessageDialog.vue";
import ArchivSaveOptionen from "./ArchivSaveOptionen.vue";
export const FilesSelected = Object.freeze({
  None: 0,
  Single: 1,
  Multiple: 2,
})
export default {
  components: {
    AnalogObjektAnlegen,
    CardComponent,
    MessageDialog,
    StoragePathDialog,
    TreeView,
    ArchivSaveOptionen,
  },

  data() {
    return {
      archivObjekt: {
        zeitraum_start: "",
        start_ergaenzung: "",
        zeitraum_ende: "",
        ende_ergaenzung: "",
        themen_id: 0,
        status: 1,
        titel: "",
        beschreibung: "",
        ort_id: 4,
        dateiPfad: "",
        analogObjekte: [],
        digitalObjekte: [],
      },
      analogObjekt: {
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
      digitalObjekt: {
        pfad: "",
        dateiname: "",
        objekttyp_id: 1,
        quellen_id: 0,
        gesperrt: false,
        gesperrt_bis: (parseInt(new Date().toISOString().substring(0, 4)) + 25).toString(),
        dateidatum: "",
      },
      analogObjektAngelegt: false,
      eingang: [],
      filesToSave: [],
      kurztitel: "",
      nonPdfSelected: false,
      FilesSelected,
      filesSelected: FilesSelected.None,
      saveOptions: {
        archivOption: "CombineFilesToOneArchivObjekt",
        filePrefix: "",
        newFilePrefix: false,
        titles: [],
      },
      selectedFiles: {},
      selectedSpeicherpfad: "",
      showAnalogObjektAnlegen: false,
      showDeleteDialog: false,
      showStorageDialog: false,
      showOptionDialog: false,
      storeBasePath: "",
      orte: [],
      quellen: [],
      themen: [],
    };
  },
  computed: {
    displaypfad() {
      if (this.speicherpfad.length > 120) {
        return "..." + this.speicherpfad.substring(this.speicherpfad.length - 120);
      }
      return this.speicherpfad;
    },
    speicherpfad() {
      let parts = [];
      if (this.digitalObjekt.gesperrt)
        parts.push("ZYX");
      if (this.digitalObjekt.dateidatum) {
        parts.push(this.formatDokumentDatum(this.digitalObjekt.dateidatum));
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
        if (ortsname) {
          parts.push(ortsname.substr(0, 2))
        }
      }
      return this.storeBasePath + this.selectedSpeicherpfad + "/" + parts.join("_") + ".pdf";
    },
    isPfadSelected() {
      return (this.storeBasePath + this.selectedSpeicherpfad).split("/").length > 1 && this.kurztitel.length > 3
    },
    saveDisabledReason() {
      if (this.isPfadSelected && !this.nonPdfSelected)
        return "";

      if (!this.isPfadSelected)
        if (this.kurztitel.length < 4)
          return "Kurztitel muss mindestens 4 Zeichen lang sein.";
        else
          return "Bitte zuerst ein Zielverzeichnis auswählen.";

      if (this.nonPdfSelected)
        return "Es können nur PDF-Dateien zusammengefasst werden.";

      return "";
    },
  },
  watch: {
    "archivObjekt.titel"(neu, alt) {
      if (this.kurztitel === "" || this.kurztitel === alt) {
        this.kurztitel = neu;
      }
    }
  },
  async created() {
    const themenResponse = await this.$axios.get("/Themen/getAll");
    this.themen = themenResponse.data.data
    const orte = await this.$axios.get("/Orte/getAll");
    this.orte = orte.data.data
    const quellenResponse = await this.$axios.get("/Quellen/getAll");
    this.quellen = quellenResponse.data.data;

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
          this.archivObjekt.id = an.id
          this.archivObjekt.ort_id = an.ort_id
          this.archivObjekt.titel = an.titel
          this.archivObjekt.themen_id = an.themen_id
          this.archivObjekt.beschreibung = an.beschreibung
          this.archivObjekt.zeitraum_start = an.zeitraum_start
          this.archivObjekt.start_ergaenzung = an.start_ergaenzung
          this.archivObjekt.zeitraum_ende = an.zeitraum_ende
          this.archivObjekt.ende_ergaenzung = an.ende_ergaenzung
          this.digitalObjekt.quellen_id = an.quellen_id
          this.analogObjektAngelegt = true
        } else {
          this.analogObjekt.id = null
          this.analogObjektAngelegt = false
          this.analogObjekt.objekttyp_id = 1
          this.analogObjekt.seiten = null
          this.analogObjekt.quellen_id = 0
          this.analogObjekt.gesperrt = false
          this.analogObjekt.gesperrt_bis = (parseInt(new Date().toISOString().substring(0, 4)) + 25).toString(),
            this.analogObjekt.lagerort_id = 1
          this.analogObjekt.regal_id = null
          this.analogObjekt.fach_id = null
          this.analogObjekt.platz_id = null
          this.analogObjekt.digitalisiert = 2

        }
      }
    },
    chooseStoragePath() {
      const selectedOrt = this.orte.find(ort => { return ort.id === this.archivObjekt.ort_id })
      if (selectedOrt === undefined) this.storeBasePath = ""
      else {
        this.storeBasePath = selectedOrt.name + "/"
        const selectedThema = this.themen.find(thema => { return thema.id === this.archivObjekt.themen_id });
        if (selectedThema && selectedThema.pfadteil) {
          const prefix = selectedOrt.name.substr(0, 2) + "_"
          this.storeBasePath += prefix + "Dokumente/" + prefix + selectedThema.pfadteil + "/";
        }
      }
      this.showStorageDialog = true
    },
    collectSelected(nodes, result = []) {
      for (const node of nodes) {
        if (
          node.type === "file" &&
          Object.prototype.hasOwnProperty.call(this.selectedFiles, node.path)
        ) {
          if (node.mimeType !== "application/pdf") {
            this.nonPdfSelected = true
          }
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
      this.archivObjekt.quellen_id = objekt.quellen_id
      this.analogObjektAngelegt = true
      this.showAnalogObjektAnlegen = false
    },
    async deleteConfirmed() {
      this.showDeleteDialog = false
      this.nonPdfSelected = false
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
      this.setFilesSelected()
    },
    formatDokumentDatum(value) {
      if (value === null || value === undefined || value === "") {
        return "";
      }
      value = String(value);
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
      if (!this.digitalObjekt.dateidatum) {
        this.digitalObjekt.dateidatum = "";
        return;
      }
      let value = String(this.digitalObjekt.dateidatum).replace(/\D/g, "");

      if (value.length === 8) {
        // DDMMYYYY -> DD.MM.YYYY
        this.digitalObjekt.dateidatum =
          `${value.slice(0, 2)}.${value.slice(2, 4)}.${value.slice(4)}`;
      }
      else if (value.length === 6) {
        // MMYYYY -> MM.YYYY
        this.digitalObjekt.dateidatum =
          `${value.slice(0, 2)}.${value.slice(2)}`;
      }
      else if (value.length === 4) {
        // YYYY
        this.digitalObjekt.dateidatum = value;
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
      this.setFilesSelected()
    },
    resetData() {
      const themen = this.themen
      const orte = this.orte
      const initialState = this.$options.data.call(this);
      Object.assign(this.$data, initialState);
      this.themen = themen
      this.orte = orte
      this.saveOptions = {
        archivOption: "CombineFilesToOneArchivObjekt",
        filePrefix: "",
        newFilePrefix: false,
        titles: [],
      }
    },
    async save() {
      //Possible archivOptions: 
      // "CombineFilesToOneArchivObjekt"
      // "newArchivObjectPerFile",
      // "selectTitlePerFile"
      // "saveAllFilesToOneArchivObject"

      this.nonPdfSelected = false
      const selectedFiles = this.collectSelected(this.eingang)
      if (selectedFiles.length === 0) {
        this.$sendMsg(true, "Keine Dateien ausgewählt.")
        return
      }
      if (this.nonPdfSelected && this.saveOptions.archivOption === "CombineFilesToOneArchivObjekt") {
        this.$sendMsg(true, "Es können nur PDF-Dateien zusammengefasst werden.")
        return
      }
      this.archivObjekt.dateiPfad = this.speicherpfad
      const targetPath = this.storeBasePath + this.selectedSpeicherpfad
      const targetFilename = this.speicherpfad.split('/').pop()
      const archivOption = this.saveOptions.archivOption
      if (archivOption === "selectTitlePerFile") {
        if (this.saveOptions.titles.length !== selectedFiles.length) {
          this.$sendMsg(true, "Anzahl der Titel stimmt nicht mit Anzahl der Dateien überein.")
          return
        }
      }

      const useFilePrefix = this.saveOptions.newFilePrefix && this.saveOptions.filePrefix.length > 0 ? this.saveOptions.filePrefix : null
      const formattedDate = this.formatDokumentDatum(this.digitalObjekt.dateidatum)
      const dateidatum = formattedDate
        ? formattedDate.replace(/^(\d{4})_(\d{2})(\d{2})$/, "$1-$2-$3")
        : ""

      const baseArchivobjekt = {
        ...this.archivObjekt,
        zeitraum_start: this.archivObjekt.zeitraum_start ?? "",
        zeitraum_ende: this.archivObjekt.zeitraum_ende ?? "",
        start_ergaenzung: this.archivObjekt.start_ergaenzung ?? "",
        ende_ergaenzung: this.archivObjekt.ende_ergaenzung ?? "",
        themen_id: Number(this.archivObjekt.themen_id ?? 0),
        status: Number(this.archivObjekt.status ?? 1),
      }

      const baseDigitalObjekt = {
        ...this.digitalObjekt,
        pfad: targetPath,
        dateiname: targetFilename,
        dateidatum,
        gesperrt_bis: this.digitalObjekt.gesperrt ? this.digitalObjekt.gesperrt_bis : null,
      }

      const basePayload = {
        archivobjekt: baseArchivobjekt,
      }

      if (this.analogObjektAngelegt) {
        basePayload.analogobjekte = [{
          ...this.analogObjekt,
          gesperrt_bis: this.analogObjekt.gesperrt ? this.analogObjekt.gesperrt_bis : null,
        }]
      }

      try {
        await this.$axios.post("/ArchivFiles/saveFiles", {
          "files": selectedFiles,
          "targetPath": targetPath,
          "targetFilename": targetFilename,
          "keepSource": true,
          "combine": archivOption === "CombineFilesToOneArchivObjekt",
          useFilePrefix
        })

        switch (archivOption) {
          case "newArchivObjectPerFile":
          case "selectTitlePerFile":
            {
              let index = 1
              for (let i = 0; i < selectedFiles.length; i++) {
                const extension = selectedFiles[i].split('.').pop().toLowerCase();
                const filename = selectedFiles[i].split('/').pop()
                const digitalObjekt = {
                  ...baseDigitalObjekt,
                  dateiname: useFilePrefix
                    ? this.saveOptions.filePrefix + "_" + ("00000" + index).slice(-5) + "." + extension
                    : filename,
                }
                const archivobjekt = {
                  ...baseArchivobjekt,
                  titel: archivOption === "selectTitlePerFile"
                    ? this.saveOptions.titles[i]
                    : baseArchivobjekt.titel,
                }
                await this.$axios.post("/Archiveingang/createOrUpdate", {
                  ...basePayload,
                  archivobjekt,
                  digitalobjekte: [digitalObjekt],
                })
                index++
              }
              break
            }
          case "saveAllFilesToOneArchivObject":
            {
              let digitalobjekte = []
              let index = 1
              for (let i = 0; i < selectedFiles.length; i++) {
                const extension = selectedFiles[i].split('.').pop().toLowerCase();
                const filename = selectedFiles[i].split('/').pop()
                digitalobjekte.push({
                  ...baseDigitalObjekt,
                  dateiname: useFilePrefix
                    ? this.saveOptions.filePrefix + "_" + ("00000" + index).slice(-5) + "." + extension
                    : filename,
                })
                index++
              }
              await this.$axios.post("/Archiveingang/createOrUpdate", {
                ...basePayload,
                digitalobjekte,
              })
              break
            }
          case "CombineFilesToOneArchivObjekt":
            await this.$axios.post("/Archiveingang/createOrUpdate", {
              ...basePayload,
              digitalobjekte: [baseDigitalObjekt],
            })
            break
          default:
            throw new Error("Unbekannte Speicheroption")
        }

        await this.$axios.post("/ArchivFiles/finalizeSavedFiles", {
          "files": selectedFiles
        })
        if (selectedFiles.length === 1) {
          this.$sendMsg(false, "Datei gespeichert")
        } else {
          this.$sendMsg(false, "Dateien gespeichert")
        }
        this.refreshTree()
        this.resetData()
      } catch (error) {
        await this.$axios.post("/ArchivFiles/cleanupSavedFile", {
          "targetPath": targetPath,
          "targetFilename": targetFilename,
          "files": selectedFiles,
          "combine": archivOption === "CombineFilesToOneArchivObjekt",
          useFilePrefix
        })
        const errorMessage = error?.response?.data?.message || error?.message || "Unbekannter Fehler"
        this.$sendMsg(true, "Fehler beim Speichern: " + errorMessage)
      }
    },
    async saveOptionsApplied(options) {
      this.showOptionDialog = false
      this.saveOptions = {
        ...options
      }
      await this.save()
    },
    setFilesSelected() {
      this.nonPdfSelected = false
      const files = this.collectSelected(this.eingang)
      if (files.length === 0) this.filesSelected = FilesSelected.None
      else if (files.length === 1) this.filesSelected = FilesSelected.Single
      else this.filesSelected = FilesSelected.Multiple
    },
    showOptions() {
      this.nonPdfSelected = false
      this.filesToSave = this.collectSelected(this.eingang)
      this.showOptionDialog = true
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