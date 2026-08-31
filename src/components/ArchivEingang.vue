<template>
  <CardComponent title="Archiveingang bearbeiten" :fields="[]" :table-data="[]">
    <div class="row g-0 content-row">
      <!-- Eingangsdateien -->
      <div class="col-lg-6 inner-col">
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
      <div class="col-lg-6 inner-col">
        <div class="card h-100">
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
                <SchlagwortSelektor v-model="archivObjekt.schlagworte"></SchlagwortSelektor>
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
                <label class="form-label">Quelle</label>
                <select class="form-select" v-model="digitalObjekt.quellen_id">
                  <option value="">Bitte wählen...</option>
                  <option v-for="quelle in quellen" :key="quelle.id" :value="Number(quelle.id)">
                    {{ quelle.display }}
                  </option>
                </select>
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
            <hr>
            <div class="row g-2">
              <div class="col">
                <button class="btn btn-outline-danger w-100" :disabled="filesSelected === FilesSelected.None"
                  @click="deleteSelected"
                  :title="filesSelected === FilesSelected.None ? 'Es muss mindestens eine Datei ausgewählt sein' : ''">
                  Ausgewählte löschen
                </button>
              </div>

              <div class="col">
                <span :title="optionsDisabledReason">
                  <button class="btn btn-outline-primary w-100" @click="showOptions"
                    :disabled="optionsDisabledReason.length > 0">
                    Einzeln speichern
                  </button>
                </span>
              </div>

              <div class="col">
                <span :title="saveDisabledReason">
                  <button class="btn btn-primary w-100" @click="save" :disabled="saveDisabledReason !== ''">
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
    <AnalogObjektAnlegen v-if="showAnalogObjektAnlegen" :analog-objekt="analogObjekt"
      @create-analog="createAnalogObjekt" @cancel="showAnalogObjektAnlegen = false" />
    <MessageDialog v-if="showDeleteDialog" title="Ausgewählte Dateien wirklich löschen?"
      message="Sollen die gewählten Dateien wirklich gelöscht werden?" @confirm="deleteConfirmed"
      @cancel="showDeleteDialog = false" confirm-text="Löschen" />
    <ArchivSaveOptionen v-if="showOptionDialog" :files="filesToSave" :defaults="saveOptionDefaults" :quellen="quellen"
      @cancel="showOptionDialog = false" @apply="saveOptionsApplied" />
  </CardComponent>
</template>

<script>
import CardComponent from "./CardComponent.vue";
import TreeView from "./TreeView.vue";
import AnalogObjektAnlegen from "./AnalogObjektAnlegen.vue";
import MessageDialog from "./MessageDialog.vue";
import ArchivSaveOptionen from "./ArchivSaveOptionen.vue";
import SchlagwortSelektor from "./SchlagwortSelektor.vue";
export const FilesSelected = Object.freeze({
  None: 0,
  Single: 1,
  Multiple: 2,
})
export default {
  components: {
    AnalogObjektAnlegen,
    ArchivSaveOptionen,
    CardComponent,
    MessageDialog,
    SchlagwortSelektor,
    TreeView,
  },

  data() {
    return {
      archivObjekt: {
        analogObjekte: [],
        archivOption: "CombineFiles",
        beschreibung: "",
        digitalObjekte: [],
        ende_ergaenzung: "",
        ort_id: 4,
        schlagworte: [],
        start_ergaenzung: "",
        status: 1,
        titel: "",
        zeitraum_ende: "",
        zeitraum_start: "",
        sourceFiles: [],
      },
      analogObjekt: {
        archiv_id: "",
        objekttyp_id: 1,
        seiten: null,
        quellen_id: null,
        gesperrt: false,
        gesperrt_bis: (parseInt(new Date().toISOString().substring(0, 4)) + 25).toString(),
        lagerort_id: 1,
        regal_id: null,
        fach_id: null,
        platz_id: null,
        digitalisiert: 2
      },
      digitalObjekt: {
        sourcefilepath: "",
        quellen_id: null,
        gesperrt: false,
        gesperrt_bis: (parseInt(new Date().toISOString().substring(0, 4)) + 25).toString(),
        dateidatum: "",
      },
      analogObjektAngelegt: false,
      eingang: [],
      filesToSave: [],
      nonPdfSelected: false,
      FilesSelected,
      filesSelected: FilesSelected.None,
      selectedFiles: {},
      showAnalogObjektAnlegen: false,
      showDeleteDialog: false,
      showStorageDialog: false,
      showOptionDialog: false,
      orte: [],
      quellen: [],
    };
  },
  computed: {
    saveOptionDefaults() {
      return {
        titel: this.archivObjekt.titel,
        dateidatum: this.digitalObjekt.dateidatum,
        gesperrt: this.digitalObjekt.gesperrt,
        gesperrt_bis: this.digitalObjekt.gesperrt_bis,
        quellen_id: this.digitalObjekt.quellen_id,
      }
    },
    optionsDisabledReason() {
      if (this.filesSelected !== FilesSelected.Multiple)
        return "Es müssen mindestens zwei Dateien ausgewählt werden, um die Speicheroptionen zu ändern.";
      if (this.archivObjekt.titel.trim() === "")
        return "Es muss ein Titel angegeben werden, um die Dateien einzeln zu speichern.";
      return "";
    },
    saveDisabledReason() {
      if (this.nonPdfSelected)
        return "Es können nur PDF-Dateien zusammengefasst werden.";
      if (this.filesSelected === FilesSelected.None)
        return "Es muss mindestens eine Datei ausgewählt werden";
      if (this.filesSelected === FilesSelected.Single && this.archivObjekt.titel.trim() === "")
        return "Es muss ein Titel angegeben werden, um die Datei zu speichern.";
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
    const orte = await this.$axios.get("/Orte/getAll");
    this.orte = orte.data.data
    const quellenResponse = await this.$axios.get("/Quellen/getSelector");
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
          this.archivObjekt.beschreibung = an.beschreibung
          this.archivObjekt.zeitraum_start = an.zeitraum_start
          this.archivObjekt.start_ergaenzung = an.start_ergaenzung
          this.archivObjekt.zeitraum_ende = an.zeitraum_ende
          this.archivObjekt.ende_ergaenzung = an.ende_ergaenzung
          this.analogObjektAngelegt = true
        } else {
          this.analogObjekt.id = null
          this.analogObjektAngelegt = false
          this.analogObjekt.objekttyp_id = 1
          this.analogObjekt.seiten = null
          this.analogObjekt.quellen_id = null
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
    async createAnalogObjekt(objekt) {
      this.analogObjekt = objekt
      this.archivObjekt.quellen_id = objekt.quellen_id
      this.archivObjekt.titel = objekt.titel
      this.archivObjekt.archivOption = ""
      this.archivObjekt.analogObjekte = [this.analogObjekt]
      delete this.archivObjekt.digitalObjekte
      const response = await this.$axios.post("/Archivobjekte/saveOrUpdate", { archivObjekt: this.archivObjekt })
      const savedArchivObjekt = response.data.data.archivObjekt
      this.archivObjekt.id = savedArchivObjekt.id
      this.analogObjekt.id = savedArchivObjekt.analogObjekte[0].id
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
          `01.${value.slice(0, 2)}.${value.slice(2)}`;
      }
      else if (value.length === 4) {
        // YYYY
        this.digitalObjekt.dateidatum = `01.01.${value}`;
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
      const orte = this.orte
      const quellen = this.quellen
      const initialState = this.$options.data.call(this);
      Object.assign(this.$data, initialState);
      this.orte = orte
      this.quellen = quellen
    },
    async save() {
      //Possible archivOptions: 
      // "CombineFiles"
      // "SingleFile"
      // "MultipleFiles"
      const selectedFiles = this.collectSelected(this.eingang)

      if (selectedFiles.length === 0) {
        this.$sendMsg(true, "Keine Dateien ausgewählt.")
        return
      }

      if (
        this.filesSelected === FilesSelected.Multiple &&
        this.archivObjekt.archivOption !== "MultipleFiles"
      ) {
        this.archivObjekt.archivOption = "CombineFiles"
      }

      if (
        this.nonPdfSelected &&
        this.archivObjekt.archivOption === "CombineFiles"
      ) {
        this.$sendMsg(true, "Es können nur PDF-Dateien zusammengefasst werden.")
        return
      }

      if (this.filesSelected === FilesSelected.Single) {
        this.archivObjekt.archivOption = "SingleFile"
      }

      const formattedDate = this.formatDokumentDatum(this.digitalObjekt.dateidatum)

      const dateidatum = formattedDate
        ? formattedDate.replace(/^(\d{4})_(\d{2})(\d{2})$/, "$1-$2-$3")
        : null
      const status =
        this.archivObjekt.status > 2 ? this.archivObjekt.status :
          (this.archivObjekt.beschreibung.trim() === "" ? 1 : 2)

      this.archivObjekt = {
        ...this.archivObjekt,
        zeitraum_start: this.archivObjekt.zeitraum_start ?? "",
        zeitraum_ende: this.archivObjekt.zeitraum_ende ?? "",
        start_ergaenzung: this.archivObjekt.start_ergaenzung ?? "",
        ende_ergaenzung: this.archivObjekt.ende_ergaenzung ?? "",
        status: Number(status),
      }

      const archivOption = this.archivObjekt.archivOption

      if (archivOption === "CombineFiles") {
        this.archivObjekt.sourceFiles = selectedFiles

        this.archivObjekt.digitalObjekte = [{
          dateidatum,
          titel: this.archivObjekt.titel,
          quellen_id: this.digitalObjekt.quellen_id,
          sourcefilepath: "",
          gesperrt: this.digitalObjekt.gesperrt,
          gesperrt_bis: this.digitalObjekt.gesperrt
            ? this.digitalObjekt.gesperrt_bis
            : null,
        }]
      }

      if (archivOption === "SingleFile") {
        this.archivObjekt.digitalObjekte = [{
          dateidatum,
          titel: this.archivObjekt.titel,
          quellen_id: this.digitalObjekt.quellen_id,
          sourcefilepath: selectedFiles[0],
          gesperrt: this.digitalObjekt.gesperrt,
          gesperrt_bis: this.digitalObjekt.gesperrt
            ? this.digitalObjekt.gesperrt_bis
            : null,
        }]
      }

      if (this.analogObjektAngelegt) {
        this.archivObjekt.analogObjekte = [{
          ...this.analogObjekt,
          gesperrt_bis: this.analogObjekt.gesperrt
            ? this.analogObjekt.gesperrt_bis
            : null,
        }]
      } else {
        delete this.archivObjekt.analogObjekte
      }
      if (this.archivObjekt.digitalObjekte.length === 0) {
        delete this.archivObjekt.digitalObjekte
      }
      try {
        await this.$axios.post(
          "/Archivobjekte/saveOrUpdate",
          { archivObjekt: this.archivObjekt }
        )
        this.$sendMsg(false, "Archivobjekt erfolgreich gespeichert.")
        this.resetData()
        this.refreshTree()
      } catch (error) {
        this.$sendMsg(
          true,
          "Fehler beim Speichern des Archivobjekts. " +
          (
            error?.response?.data?.message ||
            error?.message ||
            "Unbekannter Fehler"
          )
        )
      }
    },
    async saveOptionsApplied(options) {
      this.showOptionDialog = false
      this.archivObjekt.archivOption = "MultipleFiles"
      this.archivObjekt.digitalObjekte = options.titles.map((title, index) => ({
        titel: title,
        sourcefilepath: this.filesToSave[index],
        quellen_id: options.quellen_ids[index] ?? null,
        gesperrt: options.gesperrt[index] ?? false,
        gesperrt_bis: options.gesperrt[index]
          ? options.gesperrt_bis[index]
          : null,
        dateidatum: options.dateidaten[index] ?? null,
      }))

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
  // try {
  //   await this.$axios.post("/ArchivFiles/saveFiles", {
  //     "files": selectedFiles,
  //     "keepSource": true,
  //     "combine": archivOption === "CombineFiles",
  //   })

  //   switch (archivOption) {
  //     case "newArchivObjectPerFile":
  //     case "selectTitlePerFile":
  //       {
  //         let index = 1
  //         for (let i = 0; i < selectedFiles.length; i++) {
  //           const extension = selectedFiles[i].split('.').pop().toLowerCase();
  //           const filename = selectedFiles[i].split('/').pop()
  //           const digitalObjekt = {
  //             ...baseDigitalObjekt,
  //             dateiname: useFilePrefix
  //               ? this.saveOptions.filePrefix + "_" + ("00000" + index).slice(-5) + "." + extension
  //               : filename,
  //           }
  //           const archivobjekt = {
  //             ...baseArchivobjekt,
  //             titel: archivOption === "selectTitlePerFile"
  //               ? this.saveOptions.titles[i]
  //               : baseArchivobjekt.titel,
  //           }
  //           await this.$axios.post("/Archiveingang/createOrUpdate", {
  //             ...basePayload,
  //             archivobjekt,
  //             digitalobjekte: [digitalObjekt],
  //           })
  //           index++
  //         }
  //         break
  //       }
  //     case "saveAllFilesToOneArchivObject":
  //       {
  //         let digitalobjekte = []
  //         let index = 1
  //         for (let i = 0; i < selectedFiles.length; i++) {
  //           const extension = selectedFiles[i].split('.').pop().toLowerCase();
  //           const filename = selectedFiles[i].split('/').pop()
  //           digitalobjekte.push({
  //             ...baseDigitalObjekt,
  //             dateiname: useFilePrefix
  //               ? this.saveOptions.filePrefix + "_" + ("00000" + index).slice(-5) + "." + extension
  //               : filename,
  //           })
  //           index++
  //         }
  //         await this.$axios.post("/Archiveingang/createOrUpdate", {
  //           ...basePayload,
  //           digitalobjekte,
  //         })
  //         break
  //       }
  //     case "CombineFilesToOneArchivObjekt":
  //       await this.$axios.post("/Archiveingang/createOrUpdate", {
  //         ...basePayload,
  //         digitalobjekte: [baseDigitalObjekt],
  //       })
  //       break
  //     default:
  //       throw new Error("Unbekannte Speicheroption")
  //   }

  //   await this.$axios.post("/ArchivFiles/finalizeSavedFiles", {
  //     "files": selectedFiles
  //   })
  //   if (selectedFiles.length === 1) {
  //     this.$sendMsg(false, "Datei gespeichert")
  //   } else {
  //     this.$sendMsg(false, "Dateien gespeichert")
  //   }
  //   this.refreshTree()
  //   this.resetData()
  // } catch (error) {
  //   await this.$axios.post("/ArchivFiles/cleanupSavedFile", {
  //     "files": selectedFiles,
  //     "combine": archivOption === "CombineFilesToOneArchivObjekt",
  //     useFilePrefix
  //   })
  //   const errorMessage = error?.response?.data?.message || error?.message || "Unbekannter Fehler"
  //   this.$sendMsg(true, "Fehler beim Speichern: " + errorMessage)
  // }
};
</script>

<style scoped>
.content-row {
  flex: 1;
  min-height: 0;
}

.inner-col {
  padding: 5px 10px 10px 10px;
  box-sizing: border-box;
}

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