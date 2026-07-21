<template>
  <div ref="tableResponsive" class="table-responsive" :style="tableStyle">
    <div v-if="filterOptions?.length > 0" class="my-2 d-flex align-items-center gap-4 flex-wrap">
      <FilterComponent :options="filterOptions" v-model="activeFilters" />
    </div>
    <table class="table table-bordered table-striped" :style="{ tableLayout: hasFixedWidth ? 'fixed' : 'auto' }">
      <thead ref="tableHead">
        <tr>
          <th v-for="(field, index) in visibleFields" :key="'header-' + field.name" :class="{
            'text-center':
              field.type === 'boolean' ||
              field.type === 'date' ||
              field.type === 'expander' ||
              field.type === 'files' ||
              field.type === 'pdf' ||
              field.type === 'time' ||
              field?.align === 'center',
            'text-right': field.type === 'currency',
          }" class="cursor-pointer" @click="sort(field.name)" :style="[
            field.width ? { width: field.width, minWidth: field.width } : {},
            index === 0 ? { position: 'relative' } : {}
          ]">
            {{ field.label }}
            <i v-if="index === 0" class="bi bi-info-circle text-muted"
              style="position: absolute; top: 4px; right: 6px; font-size: 1rem; cursor: pointer;"
              data-bs-toggle="tooltip" :title="`Anzahl Einträge: ${processedData.length}`" @click.stop></i>
            <span v-if="currentSort === field.name">
              <i v-if="currentSortDir === 'asc'" class="bi bi-arrow-up" />
              <i v-if="currentSortDir === 'desc'" class="bi bi-arrow-down" />
            </span>
          </th>
        </tr>
        <tr v-if="displayFilter">
          <th v-for="field in visibleFields" :key="'filter-' + field.name"
            :style="field.width ? { width: field.width, minWidth: field.width } : null">
            <input
              v-if="field.type !== 'boolean' && field.type !== 'pdf' && field.type !== 'select' && field.type !== 'date' && field.type !== 'expander'"
              v-model="filters[field.name]" placeholder="Suchen..." class="form-control form-control-sm">
            <select v-else-if="field.type === 'select'" v-model="filters[field.name]"
              class="form-select form-select-sm">
              <option value="">Alle</option>
              <option v-for="opt in (Array.isArray(field.options) ? field.options : [])" :key="opt.value"
                :value="opt.value">
                {{ opt.display || opt.value }}
              </option>
            </select>
            <input v-else-if="field.type === 'date'" type="date" v-model="filters[field.name]"
              class="form-control form-control-sm" />
          </th>
        </tr>
      </thead>
      <tbody class="scrollable-tbody">
        <template v-for="item in processedData" :key="item.id">
          <tr class="cursor-pointer"
            :class="{ 'highlight-row': highlight === item.id, 'table-primary': dragOverRow === item.id }"
            @click="selectRow(item)" @dragover="onDragOver" @drop="onDrop($event, item)"
            @dragenter="dragOverRow = item.id" @dragleave.self="dragOverRow = null">
            <td v-for="field in visibleFields" :key="field.name" :class="{
              'text-center':
                field.type === 'boolean' ||
                field.type === 'date' ||
                field.type === 'expander' ||
                field.type === 'files' ||
                field.type === 'analogobjekt' ||
                field.type === 'pdf' ||
                field.type === 'time' ||
                field?.align === 'center',
              'text-right': field.type === 'currency',
              'is-invalid': getCellError(item, field.name),
            }" :title="getCellError(item, field.name)" data-bs-toggle="tooltip" data-bs-placement="top"
              :style="field.width ? { width: field.width, minWidth: field.width } : null">
              <template v-if="field.type === 'boolean'">
                <template v-if="isInlineEditable(field, item)">
                  <input type="checkbox" :checked="item[field.name]"
                    @change="onEdit(item, field.name, $event.target.checked)" class="form-check-input"
                    :ref="'input-' + item.id + '-' + field.name">
                </template>
                <template v-else>
                  <div class="boolean-icon">
                    <span v-if="item[field.name]" class="text-success">
                      <i class="bi bi-check-circle-fill" />
                    </span>
                    <span v-else class="text-danger">
                      <i class="bi bi-x-circle-fill" />
                    </span>
                  </div>
                </template>
              </template>
              <template v-else-if="field.type === 'date'">
                <template v-if="isInlineEditable(field, item)">
                  <input :ref="'input-' + item.id + '-' + field.name" v-model="item[field.name]"
                    class="form-control form-control-sm" type="date"
                    @input="onEdit(item, field.name, $event.target.value)" @focus="selectRow(item)">
                </template>
                <template v-else>
                  {{ formatDate(item[field.name]) }}
                </template>
              </template>
              <template v-else-if="field.type === 'currency'">
                {{ formatCurrency(item[field.name]) }}
              </template>
              <template v-else-if="field.type === 'select'">
                <template v-if="isInlineEditable(field, item)">
                  <select :ref="'input-' + item.id + '-' + field.name" v-model="item[field.name]"
                    class="form-select form-select-sm" @change="onEdit(item, field.name, $event.target.value)"
                    @focus="selectRow(item)">
                    <option v-for="opt in field.options" :key="opt.value" :value="opt.value">
                      {{ opt.display }}
                    </option>
                  </select>
                </template>
                <template v-else>
                  {{ getDisplayValue(field, item[field.name]) }}
                </template>
              </template>
              <template v-else-if="field.type === 'time'">
                {{ formatTime(item[field.name]) }}
              </template>
              <template v-else-if="field.type === 'expander'">
                <i v-if="item.hasDetails" :class="item.expanded ? 'bi bi-chevron-down' : 'bi bi-chevron-right'"
                  @click.stop="$emit('toggle-expand', item)" style="cursor:pointer">
                </i>
              </template>
              <template v-else-if="field.type === 'password'">********</template>
              <FilesIcon v-else-if="field.type === 'files'" :item="item[field.name]" />
              <AnalogIcon v-else-if="field.type === 'analogobjekt'" :item="item[field.name]"
                @toggle-expand="$emit('toggle-expand', item)" />
              <template v-else-if="field.type === 'pdf'">
                <div v-if="item[field.name] !== ''" class="boolean-icon d-flex justify-content-center align-items-center">
                  <button class="btn btn-link" @click.stop="openPdf(item[field.name])" draggable="true"
                    @dragstart="onDragStart($event, item, item[field.name])">
                    <i class="bi bi-file-earmark-pdf-fill text-danger fs-2" />
                  </button>
                </div>
              </template>
              <template v-else>
                <template v-if="isInlineEditable(field, item)">
                  <div class="d-flex align-items-center">
                    <input :ref="'input-' + item.id + '-' + field.name" v-model="item[field.name]"
                      class="form-control form-control-sm" @input="onEdit(item, field.name, $event.target.value)"
                      @focus="selectRow(item)">
                  </div>
                </template>
                <template v-else>
                  {{ item[field.name] }}
                </template>
              </template>
              <i v-if="field?.icon" :class="field.icon" class="text-muted" style="margin-left: 4px;"
                @click="$emit('action', field.emit, item)" />
            </td>
          </tr>
          <tr v-if="item.expanded">
            <td :colspan="visibleFieldCount">
              <slot name="expanded" :item="item"></slot>
            </td>
          </tr>
        </template>
        <tr v-if="hasSumField">
          <td v-for="(field, index) in visibleFields" :key="'sum-' + field.name"
            :class="{ 'text-center': field?.align === 'center', sumtd: true, 'text-right': field.type === 'currency' }"
            :style="field.width ? { width: field.width, minWidth: field.width } : null">
            <template v-if="field.sumUp">
              <template v-if="field.type === 'currency'">
                {{ formatCurrency(sums[field.name]) }}
              </template>
              <template v-else>
                {{ sums[field.name] }}
              </template>
            </template>
            <template v-else>
              <span v-if="index === firstNonSummedIndex">Summe</span>
              <span v-else />
            </template>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import AnalogIcon from './AnalogIcon.vue';
import FilesIcon from './FilesIcon.vue';
import FilterComponent from './FilterComponent.vue';
export default {
  components: {
    AnalogIcon,
    FilesIcon,
    FilterComponent
  },
  props: {
    fields: {
      type: Array,
      required: true,
    },
    displayFilter: {
      type: Boolean,
      default: true,
      required: false
    },
    filterOptions: {
      type: Array,
      required: false,
    },
    tableData: {
      type: Array,
      required: true,
    },
    highlight: {
      type: Number,
      required: false,
      default: null,
    },
    useAllYSpace: {
      type: Boolean,
      required: false,
      default: true
    }
  },
  emits: ["edit", "row-selected", "file-dropped", "action", "toggle-expand"],
  data() {
    return {
      activeFilters: [],
      checkboxFilters: {},
      currentSort: "",
      currentSortDir: "asc",
      debouncedEmit: null,
      dragOverRow: null,
      errors: {},
      filters: {},
      localData: [],
      startY: 0,
    };
  },
  computed: {
    hasFixedWidth() {
      return this.fields.some(f => f.width)
    },
    hasSumField() {
      return this.fields.some((field) => field.sumUp);
    },
    firstNonSummedIndex() {
      // Finde den Index des ersten Feldes, das nicht summiert werden soll
      return this.fields.findIndex((field) => !field.sumUp);
    },
    processedData() {
      let data = [...this.localData]
      const fieldMap = Object.fromEntries(this.fields.map(f => [f.name, f]))

      data = data.filter(row => {
        return Object.keys(this.filters).every(key => {
          const field = fieldMap[key]
          const filterValue = this.filters[key]

          if (!field || !filterValue) return true

          const value = row[key]

          if (field.type === "select") {
            return value === filterValue
          }

          if (field.type === "date") {
            return (value || "").split(" ")[0] === filterValue
          }

          return value?.toString().toLowerCase()
            .startsWith(filterValue.toLowerCase())
        })
      })

      // 🔘 Checkbox Filter
      data = data.filter(row => {
        return this.fields.every(field => {
          if (!field.filterable || !this.checkboxFilters[field.name]) return true
          return typeof field.filterable === "function"
            ? field.filterable(row)
            : true
        })
      })

      // 🧠 externe Filter
      data = data.filter(row =>
        this.activeFilters.every(fn => fn(row))
      )

      // 🔃 Sort
      if (this.currentSort) {
        const dir = this.currentSortDir === "desc" ? -1 : 1

        const normalize = (val) => {
          if (!isNaN(val)) return Number(val)
          return val?.toString().toLowerCase()
        }

        data.sort((a, b) => {
          const aVal = normalize(a[this.currentSort])
          const bVal = normalize(b[this.currentSort])

          if (aVal < bVal) return -1 * dir
          if (aVal > bVal) return 1 * dir
          return 0
        })
      }

      return data
    },
    sums() {
      const result = {}

      this.fields.forEach(field => {
        if (!field.sumUp) return

        result[field.name] = this.processedData.reduce((sum, row) => {
          const val = parseFloat(row[field.name])
          return !isNaN(val) ? sum + val : sum
        }, 0)
      })

      return result
    },
    tableStyle() {
      if (!this.useAllYSpace) return {}
      return {
        height: `calc(100vh - ${this.startY}px)`,
      };
    },
    visibleFieldCount() {
      return this.fields.filter(f => !f.hidden).length;
    },
    visibleFields() {
      return this.fields.filter(f => !f.hidden);
    },
  },
  watch: {
    tableData: {
      immediate: true,
      handler(newData) {
        this.localData = JSON.parse(JSON.stringify(newData))
      },
    },
    fields: {
      handler: "loadSelectOptions",
      immediate: true, // Stellt sicher, dass die Funktion auch direkt beim Mounten aufgerufen wird
      deep: true, // Erfasst Änderungen an verschachtelten Eigenschaften
    },
  },
  created() {
    this.filters = this.fields.reduce((acc, field) => {
      acc[field.name] = ""; // Leerer Standardwert für jeden Filter
      return acc;
    }, {});
    this.fields.forEach((field) => {
      if (field.filterable) this.checkboxFilters[field.name] = true;
    });
    this.debouncedEmit = this.createDebounce((id, fieldName, value) => {
      this.$emit("edit", { id, fieldName, value });
    }, 300);
  },
  async mounted() {
    this.calculateStartY();
  },
  methods: {
    calculateSum(fieldName) {
      if (!Array.isArray(this.processedData) || this.processedData.length === 0) {
        return 0;
      }
      const total = this.processedData.reduce((sum, row) => {
        const value = parseFloat(row[fieldName]);
        return !isNaN(value) ? sum + value : sum;
      }, 0);
      return total.toFixed(2); // Formatierung auf zwei Dezimalstellen
    },
    calculateStartY() {
      const element = this.$refs.tableResponsive;
      //const header = this.$refs.tableHead;
      this.startY = element.getBoundingClientRect().top; // + header.getBoundingClientRect().height;
    },

    createDebounce(callback, delay) {
      let timer;
      return function (...args) {
        clearTimeout(timer);
        timer = setTimeout(() => {
          callback(...args);
        }, delay);
      };
    },
    emitEdit(item, fieldName, event) {
      let value;

      // Check the input type dynamically
      if (event.target.type === "checkbox") {
        value = event.target.checked; // For checkboxes, use the `checked` property
      } else {
        value = event.target.value; // For text inputs, use the `value` property
      }

      if (this.debouncedEmit) {
        this.debouncedEmit(item.id, fieldName, value);
      }
    },
    focusInput(itemId, fieldName) {
      const inputRef = this.$refs[`input-${itemId}-${fieldName}`];
      if (inputRef && inputRef[0]) {
        inputRef[0].focus(); // Fokus auf das erste Element setzen
      }
    },
    formatCurrency(value) {
      if (!value) return "";
      return new Intl.NumberFormat("de-DE", {
        style: "currency",
        currency: "EUR",
      }).format(value);
    },
    formatDate(value) {
      if (!value) return "";
      const [year, month, day] = value.split("-");
      return `${day}.${month}.${year}`;
    },
    formatTime(value) {
      if (!value) return "";
      const [hours, minutes] = value.split(":");
      return `${hours}:${minutes}`;
    },
    getCellError(row, fieldName) {
      const field = this.fields.find((f) => f.name === fieldName);
      if (!field) return null; // Kein entsprechendes Feld gefunden
      return this.validateCell(row, field); // Fehler für die Zelle zurückgeben
    },
    getDisplayValue(field, value) {
      if (!field.options || !value || typeof (field.options) === "string") return value;
      const option = field.options.find((option) => option.value === value);
      return option ? option.display : value;
    },
    isInlineEditable(field, row) {
      if (typeof field.inlineEdit === "function") {
        return field.inlineEdit(row)
      }
      return Boolean(field.inlineEdit)
    },
    async loadSelectOptions() {
      for (const field of this.fields) {
        if (typeof field.options !== "string") continue
        const urlToLoad = field.options; // String sichern
        if (field._loadedUrl !== urlToLoad) {
          try {
            // URL merken (nicht reaktiv)
            Object.defineProperty(field, "_loadedUrl", {
              value: urlToLoad,
              enumerable: false,
              writable: true
            });
            const response = await this.$axios.get(urlToLoad);
            field.options = response.data.data || [];

          } catch (e) {
            console.error(e);
          }
        }
      };
    },
    onDragOver(event) {
      event.preventDefault()
    },
    onDragStart(event, sourceElement, file) {
      event.dataTransfer.effectAllowed = "move"
      event.dataTransfer.setData("application/json", JSON.stringify({ source: sourceElement, file }))
    },
    onDrop(event, target) {
      let payload
      try {
        payload = JSON.parse(event.dataTransfer.getData("application/json"))
      } catch {
        return
      }
      this.$emit("file-dropped", { source: payload.source, file: payload.file, target })
    },
    onEdit(item, fieldName, value) {
      item[fieldName] = value // nur localData!

      this.debouncedEmit?.(item.id, fieldName, value)
    },
    async openPdf(pdfValue) {
      if (!pdfValue) {
        console.warn("Kein PDF-Wert vorhanden.");
        return;
      }

      try {
        let blob;

        if (pdfValue.fileContent) { //fileContent direkt vorhanden (Base64)
          const byteCharacters = atob(pdfValue.fileContent);
          const byteNumbers = new Array(byteCharacters.length);

          for (let i = 0; i < byteCharacters.length; i++) {
            byteNumbers[i] = byteCharacters.charCodeAt(i);
          }

          const byteArray = new Uint8Array(byteNumbers);
          blob = new Blob([byteArray], { type: "application/pdf" });
        }
        else {
          const path = typeof pdfValue === "string"
            ? pdfValue
            : pdfValue?.apiPath;

          if (!path) {
            console.warn("Keine PDF-Quelle gefunden.");
            return;
          }

          const response = await this.$axios.get(path, { responseType: "blob" });
          blob = new Blob([response.data], { type: "application/pdf" });
        }

        // 🔗 Anzeigen
        const url = window.URL.createObjectURL(blob);
        window.open(url, "_blank");

      } catch (error) {
        this.$sendMsg(true, "Fehler beim Laden der PDF");
        console.error("Fehler beim Laden der PDF:", error);
      }
    },
    selectRow(row) {
      this.$emit("row-selected", row);
    },
    sort(fieldName) {
      if (this.currentSort === fieldName) {
        this.currentSortDir = this.currentSortDir === "asc" ? "desc" : "asc";
      } else {
        this.currentSort = fieldName;
        this.currentSortDir = "asc";
      }
    },
    toggleFilter(fieldName) {
      this.checkboxFilters[fieldName] = !this.checkboxFilters[fieldName];
    },
    validateCell(row, field) {
      const value = row[field.name];

      // Prüfung auf `required`
      if (field.required && (value === null || value === undefined || value === "")) {
        return `${field.label} ist erforderlich.`;
      }

      // Prüfung mit `validate`-Funktion
      if (field.validate && typeof field.validate === "function") {
        const error = field.validate(row, value);
        if (error) {
          return error; // Fehlertext von der `validate`-Funktion
        }
      }

      return null; // Kein Fehler
    },
  },
};
</script>

<style scoped>
table td,
table th {
  padding: 0.5rem;
  line-height: 1;
  font-size: 0.9rem;
}

table tr {
  cursor: pointer;
}

table tbody tr:hover {
  background-color: #f0f0f0;
}

.scrollable-tbody {
  display: block;
  overflow-y: auto;
  table-layout: fixed;
}

.scrollable-tbody tr {
  display: table;
  width: 100%;
  table-layout: fixed;
}

thead tr {
  display: table;
  width: 100%;
  table-layout: fixed;
}

thead input {
  width: 100%;
  padding: 0.25rem;
  font-size: 0.9rem;
}

.text-center {
  text-align: center;
}

.text-right {
  text-align: right;
}

/* Boolean- und PDF-Icons zentrieren */
.boolean-icon {
  font-size: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  min-height: 40px;
}

.boolean-icon button {
  padding: 0;
  line-height: 1;
}

.boolean-icon i {
  margin: 0;
}

/* Zelleninhalt bei boolean und pdf zentrieren */
table td {
  vertical-align: middle;
}

.sumtd {
  font-weight: bold;
  background-color: #f8f5f5;
}

.is-invalid {
  border: 2px solid red;
}

.invalid-feedback {
  font-size: 0.8rem;
  color: red;
}

.cursor-pointer {
  cursor: pointer;
}

.inline-filter {
  display: inline-flex;
  /* Elemente horizontal ausrichten */
  align-items: center;
  /* Vertikal zentrieren */
  gap: 0.5rem;
  /* Abstand zwischen Text und Checkbox */
  font-size: 0.85rem;
  /* Optional: Schriftgröße anpassen */
}

.inline-filter input[type="checkbox"] {
  margin: 0;
  /* Zusätzlichen Standardabstand der Checkbox entfernen */
}

.table-responsive {
  position: relative;
  overflow-y: auto;
}

table {
  border-collapse: separate;
  border-spacing: 0;
}

thead {
  position: sticky;
  top: 0;
  z-index: 10;
  background-color: white;
  /* Hintergrundfarbe der Kopfzeile */
}

thead th {
  box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
  /* Schatteneffekt, um Kopfzeile hervorzuheben */
}

.highlight-row td {
  background-color: #d1ecf1 !important;
}

.highlight-row:hover td {
  background-color: #bee5eb !important;
}
</style>
