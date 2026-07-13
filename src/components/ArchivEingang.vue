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
            <TreeView :tree="eingang" title="" :showSearch="true" :selectable="true" @file-selected="fileSelected" />
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
              <select class="form-select" v-model="ort">
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
              <div class="col-4 mb-3">
                <label class="form-label">Dokumentdatum</label>
                <input class="form-control" v-model="dokumentDatum" placeholder="z.B. 13.04.1956 oder 1956">
              </div>
              <div class="col-8 mb-3">
                <label class="form-label">Kurztitel</label>
                <input class="form-control" v-model="kurztitel">
              </div>
            </div>

            <!-- Analogobjekt -->
            <div class="mb-3">
              <label class="form-label">Analogobjekt-Nr.</label>
              <input class="form-control" v-model="analogNummer">
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
              <span class="badge bg-secondary">{{ speicherpfad }}</span>
            </div>

            <hr>

            <div class="d-grid gap-2">

              <button class="btn btn-primary" :disabled="selectedFiles.length < 2" @click="mergeAndSave">
                Zusammenfassen und speichern
              </button>

              <button class="btn btn-success" @click="saveAnalogObject">
                Analogobjekt speichern
              </button>

              <button class="btn btn-outline-danger" :disabled="selectedFiles.length == 0" @click="deleteSelected">
                Ausgewählte löschen
              </button>

            </div>

          </div>

        </div>

      </div>

    </div>

    <!-- Modal zur Auswahl des Speicherpfades -->
    <StoragePathDialog v-if="showStoragDialog" ref="storageDialog" @path-selected="storagePathSelected"
      :base-path="storeBasePath" @cancel="showStoragDialog = false" />

  </CardComponent>
</template>

<script>
import CardComponent from "./CardComponent.vue";
import TreeView from "./TreeView.vue";
import StoragePathDialog from "@/components/StoragePathDialog.vue";

export default {

  components: {
    CardComponent,
    StoragePathDialog,
    TreeView,
  },

  data() {
    return {

      eingang: [],

      selectedFiles: [],

      orte: [],

      ort: "",
      abJahr: "",
      bisJahr: "",
      dokumentDatum: "",
      kurztitel: "",
      analogNummer: "",
      gesperrt: false,
      storeBasePath: "",
      selectedSpeicherpfad: "",
      showStoragDialog: false,
    };
  },
  computed: {

    speicherpfad() {

      let parts = [];

      // Gesperrt
      if (this.gesperrt)
        parts.push("ZYX");

      // Dokumentdatum oder Zeitraum
      if (this.dokumentDatum) {

        parts.push(this.formatDokumentDatum(this.dokumentDatum));

      } else if (this.abJahr || this.bisJahr) {

        let von = this.abJahr || "0000";
        let bis = this.bisJahr || von;

        parts.push(`${von}bis${bis}`);
      }

      // Kurztitel
      if (this.kurztitel) {
        parts.push(this.makeFilename(this.kurztitel));
      }

      // Analognummer
      if (this.analogNummer) {
        parts.push(this.makeFilename(this.analogNummer));
      }

      return this.selectedSpeicherpfad + "/" + parts.join("_") + ".pdf";
    }

  },
  async created() {
    const orte = await this.$axios.get("/Orte/getAll");
    this.orte = orte.data.data
    const eingangResponse = await this.$axios.post("/ArchivFiles/getTree", { "directory": "archiveingang", "withFiles": true })
    this.eingang = eingangResponse.data.data
  },
  methods: {
    chooseStoragePath() {
      const selectedOrt = this.orte.find(ort => { return ort.id === this.ort })
      if (selectedOrt === undefined) this.storeBasePath = ""
      else this.storeBasePath = selectedOrt.name
      this.showStoragDialog = true
    },
    deleteSelected() {

    },
    fileSelected(path, selected) {

      if (selected) {
        if (!this.selectedFiles.includes(path))
          this.selectedFiles.push(path);
      } else {
        this.selectedFiles =
          this.selectedFiles.filter(f => f !== path);
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
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/ä/g, "ae")
        .replace(/ö/g, "oe")
        .replace(/ü/g, "ue")
        .replace(/Ä/g, "Ae")
        .replace(/Ö/g, "Oe")
        .replace(/Ü/g, "Ue")
        .replace(/ß/g, "ss")
        .replace(/[^A-Za-z0-9]/g, "_")
        .replace(/_+/g, "_")
        .replace(/^_+|_+$/g, "");
    },
    mergeAndSave() {
    },
    saveAnalogObject() {

    },
    storagePathSelected(path) {
      console.log("hoho", path)
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