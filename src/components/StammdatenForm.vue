<template>
  <CardComponent :title="title" :fields="fields" :filterOptions="filterOptions" :table-data="tableData"
    :show-modal="showModal" :dialog-title="dialogTitle" :dialog-message="dialogMessage" :highlight="selectedId"
    :show-as-modal="showAsModal" @row-selected="selectEntry" @confirm="onConfirm" @cancel="onCancel">
    <div class="scroll-container">
      <div class="form-fields" :class="{ 'two-column': localFields.length > 6 }">
        <div v-for="(field, index) in localFields" :key="index" ref="formGroups" class="form-group row mt-1">
          <label :for="field.name" class="col-4 col-form-label form-control-sm">
            {{ field.label }}
            <span v-if="field.required" class="text-danger">*</span>
          </label>
          <div class="col-8 d-flex align-items-center">
            <input v-if="
              field.type === 'text' ||
              field.type === 'email' ||
              field.type === 'number' ||
              field.type === 'date' ||
              field.type === 'password'
            " :id="field.name" v-model="field.value" :name="field.name" :type="field.type"
              class="form-control form-control-sm" :class="{ 'is-invalid': field.error }" :required="field.required"
              :disabled="field.name === 'id'" :autocomplete="field.type === 'password' ? 'new-password' : null"
              @input="resetFieldError(field)" @focus="scrollToField(field)" :title="field.error || ''">
            <input v-if="field.type === 'boolean'" :id="field.name" v-model="field.value" :name="field.name"
              type="checkbox" class="form-check-input" :true-value="1" :false-value="0">
            <select v-if="field.type === 'select'" :id="field.name" v-model="field.value" :name="field.name"
              class="form-control form-control-sm" :class="{ 'is-invalid': field.error }" :required="field.required"
              @change="resetFieldError(field)" :title="field.error || ''">
              <option value="" disabled>Bitte auswählen</option>
              <option v-for="(option, idx) in field.options" :key="idx" :value="option[valueKey] || option">
                {{ option[displayKey] || option }}
              </option>
            </select>
            <div v-if="field.type === 'pdf'">
              <!-- Upload -->
              <input v-if="!field.value?.valid" :id="field.name" :key="fileInputKeys[field.name]" type="file"
                accept=".pdf" class="form-control form-control-sm" :class="{ 'is-invalid': field.error }"
                :required="field.required" @change="handleFileUpload(field, $event)">

              <!-- Anzeige -->
              <div v-else class="d-flex align-items-center">
                <button class="btn btn-link" @click.stop="openPdf(field)">
                  <i class="bi bi-file-earmark-pdf-fill text-danger" />
                </button>

                <button type="button" class="btn btn-sm btn-outline-secondary ms-2" @click="removePdf(field)">
                  Ändern
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-4" />
      <div class="col-8 btn-group">
        <button :disabled="!isSaveable" :class="['btn', 'btn-primary', 'col-4', 'm-2', { 'btn-sm': noNewDatasets }]"
          type="button" @click="saveData">
          Speichern
        </button>
        <button v-if="!noNewDatasets" class="btn btn-info col-4 m-2" type="button" @click="newEntry">Neu</button>
        <button v-if="!noNewDatasets" :disabled="selectedId === null" class="btn btn-danger col-4 m-2" type="button"
          @click="deleteEntry">
          Löschen
        </button>
      </div>
    </div>
  </CardComponent>
</template>
<script>
import CardComponent from "./CardComponent.vue";
import { createPdf, fileToBase64, openPdf } from "../utils/pdf"

export default {
  components: { CardComponent },
  props: {
    apiRoutes: {
      type: Object,
      required: false,
      default: () => ({
        get: "/entries",
        save: "/entry/save",
        delete: "/entry/delete",
        update: "/entry/update",
      }),
    },
    fields: {
      type: Array,
      required: true,
    },
    filterOptions: {
      type: Array,
      required: false,
    },
    noNewDatasets: {
      type: Boolean,
      default: false,
      required: false,
    },
    preSaveCheck: {
      type: Function, // Die Pre-Save-Check-Methode wird als Prop übergeben
      default: null, // Optional, falls kein Check erforderlich ist
    },
    showAsModal: {
      type: Boolean,
      default: false
    },
    title: {
      type: String,
      default: "Stammdaten verwalten",
    },
  },
  emits: ["saved"],
  data() {
    return {
      dialogTitle: "Datensatz wirklich löschen?",
      dialogMessage: "",
      displayKey: "display", // Default für Anzeige von Optionen
      fileInputKeys: {},
      localFields: [],
      selectedId: null,
      showModal: false,
      tableData: [],
      valueKey: "value", // Default für den gespeicherten Wert von Optionen
    };
  },
  created() {
    this.fields.forEach((field) => {
      this.localFields.push({ ...field });
      if (field.type === "pdf") {
        this.fileInputKeys[field.name] = Date.now();
      }
    });
    this.fetchData();
    this.loadSelectOptions();
  },
  computed: {
    isSaveable() {
      return this.localFields.every(field => {
        // Pflichtfelder müssen gefüllt sein
        if (field.required && (field.value === null || field.value === undefined || field.value === "")) {
          return false;
        }

        // Falls bereits ein Fehler gesetzt ist
        if (field.error) {
          return false;
        }

        return true;
      });
    }
  },
  methods: {
    cancel() {
      this.$router.push("/");
    },
    deleteEntry() {
      if (this.selectedId !== null) {
        this.dialogMessage = `Datensatz mit der Nummer ${this.selectedId} wirklich löschen?`;
        this.showModal = true;
      }
    },
    async fetchData() {
      const response = await this.$axios.post(this.apiRoutes.get, this.localFields);
      this.tableData = response.data.data;
      this.tableData.forEach(row => {
        this.localFields.forEach(field => {
          if (field.type === "pdf" && row[field.name]) {
            row[field.name] = createPdf({
              action: "keep",
              apiPath: this.apiRoutes.openPdf + "/" + field.name + "-" + row['id'],
              valueType: "id"
            });
          }
        });
      });
    },
    async handleFileUpload(field, event) {
      const file = event.target.files[0];
      if (!file) return
      if (file.size > 5000000) {
        this.$sendMsg(true, "Datei ist zu groß");
        return;
      }
      const base64 = await fileToBase64(file)
      field.value = createPdf({
        action: 'replace',
        fileContent: base64,
      });
    },
    async loadSelectOptions() {
      for (const field of this.localFields) {
        if (Array.isArray(field.options)) continue; // Wenn feste Optionen vorhanden sind, überspringen
        if (typeof field.options === "string") {
          try {
            const response = await this.$axios.get(field.options);
            field.options = response.data.data || [];
          } catch (error) {
            field.options = []; // Leere Optionen setzen, falls der Request fehlschlägt
          }
        }
      }
    },
    newEntry() {
      this.localFields.forEach((field) => {
        field.value = null
        if (field.type === "pdf") {
          field.valueType = null
          field.action = "keep"
          this.fileInputKeys[field.name] = Date.now();
        }
        this.resetFieldError(field)
      });
      this.selectedId = null;
    },
    onCancel() {
      this.showModal = false;
    },
    onConfirm() {
      this.showModal = false;
      this.$axios.get(`${this.apiRoutes.delete}/${this.selectedId}`).then(() => {
        this.$sendMsg(false, "Eintrag gelöscht.");
        this.fetchData();
        this.newEntry();
      });
    },
    async openPdf(field, ev) {
      openPdf(field.value, this.$axios);
    },
    removePdf(field, ev) {
      field.value = "";
      field.action = "delete";
      field.valueType = null;
    },
    resetFieldError(field) {
      field.error = "";
      const value = field.value;

      if (field.required && (value === null || value === undefined || value === "")) {
        field.error = `${field.label} ist ein Pflichtfeld.`;
        return;
      }

      if (field.validate?.pattern && value) {
        const regex = new RegExp(field.validate.pattern);
        if (!regex.test(value)) {
          field.error = `${field.label} hat ein ungültiges Format.`;
          return;
        }
      }

      if (field.type === "number" && value) {
        const number = Number(value);
        if (field.validate?.min !== undefined && number < field.validate.min) {
          field.error = `${field.label} zu klein.`;
          return;
        }
        if (field.validate?.max !== undefined && number > field.validate.max) {
          field.error = `${field.label} zu groß.`;
          return;
        }
      }
    },
    async saveData() {
      const errors = this.validateFields();
      if (errors.length > 0) {
        this.$sendMsg(true, errors.join("\n"));
        const firstErrorField = this.localFields.find(f => f.error);
        if (firstErrorField) {
          this.scrollToField(firstErrorField);
        }
        return;
      }
      if (this.preSaveCheck) {
        const checkResult = this.preSaveCheck(this.localFields);
        const isValid = await Promise.resolve(checkResult);
        if (!isValid) return; // Speichern abbrechen
      }
      const entry = {};
      this.localFields.forEach((field) => {
        entry[field.name] = field.value;
      });
      const route = this.selectedId !== null ? this.apiRoutes.update : this.apiRoutes.save;
      this.$axios.post(route, entry).then(async () => {
        this.$sendMsg(false, "Daten erfolgreich gespeichert");
        await this.fetchData();
        this.newEntry();
        this.$emit("saved", entry);
      });
    },
    scrollToField(field) {
      const formGroup = this.$refs.formGroups[this.localFields.indexOf(field)];
      if (formGroup) {
        const container = formGroup.closest(".scroll-container");
        const containerRect = container.getBoundingClientRect();
        const fieldRect = formGroup.getBoundingClientRect();
        const offset = fieldRect.top - containerRect.top - container.clientHeight / 2 + formGroup.clientHeight / 2;

        container.scrollBy({
          top: offset,
          behavior: "smooth",
        });
      }
    },
    selectEntry(row) {
      this.localFields.forEach((field) => {
        if (field.type === "pdf") {
          if (row[field.name]) {
            field.value = createPdf({
              action: "keep",
              apiPath: this.apiRoutes.openPdf + "/" + field.name + "-" + row['id'],
            });
          } else {
            field.value = createPdf({
              action: "keep",
            });
          }
        } else {
          field.value = row[field.name] || "";
        }
      });
      this.selectedId = row.id;
      this.validateFields();
    },
    validateFields() {
      const errors = [];

      this.localFields.forEach((field) => {
        const value = field.value;
        field.error = "";
        // Pflichtfeldprüfung
        if (field.required && (!value || value === "")) {
          field.error = `${field.label} ist ein Pflichtfeld.`;
          errors.push(`${field.label} ist ein Pflichtfeld.`);
        }

        // Musterprüfung (z.B. für E-Mail)
        if (field.validate?.pattern && value) {
          const regex = new RegExp(field.validate.pattern);
          if (!regex.test(value)) {
            field.error = `${field.label} hat ein ungültiges Format.`;
            errors.push(`${field.label} hat ein ungültiges Format.`);
          }
        }

        // Zahlenbereich prüfen
        if (field.type === "number" && value) {
          const number = Number(value);
          if (field.validate?.min !== undefined && number < field.validate.min) {
            field.error = `${field.label} muss mindestens ${field.validate.min} sein.`;
            errors.push(`${field.label} muss mindestens ${field.validate.min} sein.`);
          }
          if (field.validate?.max !== undefined && number > field.validate.max) {
            field.error = `${field.label} darf maximal ${field.validate.max} sein.`;
            errors.push(`${field.label} darf maximal ${field.validate.max} sein.`);
          }
        }
      });
      return errors;
    },
  },
};
</script>
<style scoped>
.is-invalid {
  border-color: #dc3545 !important;
  box-shadow: 0 0 0 0.1rem rgba(220, 53, 69, 0.25);
}

.btn {
  font-size: 0.9rem;
  /* Kleinere Buttons, wenn gewünscht */
}

.form-fields {
  display: grid;
  gap: 0.3rem;
  /* Abstand zwischen den Feldern */
}

.form-fields.two-column {
  grid-template-columns: repeat(2, 1fr);
  /* Zwei Spalten für breite Formulare */
}

.form-group {
  display: flex;
  flex-direction: row;
  align-items: center;
  margin-left: 0;
  /* Standard-Abstand optimieren */
}

input,
select {
  font-size: 0.9rem;
  /* Einheitliche Feldgröße */
}

.scroll-container {
  min-height: 150px;
  max-height: 300px;
  overflow-y: auto;
  overflow-x: hidden;
  border: 1px solid #ddd;
  padding: 10px;
  box-sizing: border-box;
  position: relative;
}
</style>
