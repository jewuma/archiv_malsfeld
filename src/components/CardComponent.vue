<template>
  <div :class="showAsModal ? 'ownModal' : 'container-fluid custom-container'"
    :style="showAsModal ? { top: posY + 'px', left: posX + 'px' } : {}">
    <div class="full-height-card card shadow-lg">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center"
        @mousedown="startDrag">
        <h4 class="mb-0">
          {{ title }}
        </h4>
        <div class="d-flex align-items-center gap-2">

          <!-- Submenu -->
          <div v-if="submenu.length" class="dropdown">
            <button class="btn btn-info btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" @mousedown.stop>
              Menü
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li v-for="(entry, index) in submenu" :key="index">
                <a class="dropdown-item d-flex align-items-center gap-2" href="#"
                  @click.prevent="!entry.disabled && menuSelected(entry)" :class="{ disabled: entry.disabled }">
                  <i v-if="entry.icon" :class="['bi', entry.icon]"></i>
                  <span>{{ entry.label }}</span>
                </a>
              </li>
            </ul>
          </div>
          <button class="btn btn-danger btn-sm" @click="cancel" @mousedown.stop>Beenden</button>
        </div>
      </div>
      <div class="card-body d-flex flex-column card-bg">
        <!-- MessageDialog basierend auf Props anzeigen -->
        <MessageDialog v-if="showModal" :title="dialogTitle" :message="dialogMessage" confirm-text="Ja"
          cancel-text="Nein" @confirm="$emit('confirm')" @cancel="$emit('cancel')" />
        <div v-if="loading !== ''" class="spinner-overlay">
          <div class="spinner-container">
            <div class="spinner" />
            <p class="loading-text">
              {{ loading }}
            </p>
          </div>
        </div>
        <slot />
        <div v-if="fields.length > 0" class="mt-3">
          <div class="row">

            <!-- Tabelle -->
            <div :class="files !== null ? 'col-md-9' : 'col-12'">
              <TableComponent ref="childTable" :fields="fields" :filterOptions="filterOptions" :table-data="tableData"
                :highlight="highlight" @row-selected="rowSelected" @edit="rowEdited" @file-dropped="fileDropped"
                @action="onAction" />
            </div>

            <!-- File Liste -->
            <div v-if="files !== null" class="col-md-3">
              <div class="d-flex justify-content-between icon-div">
                <div class="text-center flex-fill">
                  <i class="bi bi-folder2 file-icons" @click="$emit('select-folder', 'belege')"
                    @drop="onDrop($event, 'belege')" @dragover="onDragOver"></i>
                  <p class="mb-0">Monatsbelege</p>
                </div>

                <div class="text-center flex-fill">
                  <i class="bi bi-folder2 file-icons" @click="$emit('select-folder', 'dauerbelege')"
                    @drop="onDrop($event, 'dauerbelege')" @dragover="onDragOver"></i>
                  <p class="mb-0">Dauerbelege</p>
                </div>

                <div class="text-center flex-fill">
                  <i class="bi bi-recycle file-icons" @drop="onDrop($event, 'trash')" @dragover="onDragOver"></i>
                  <p class="mb-0">Müll</p>
                </div>
                <div class="text-center flex-fill" @click="$emit('refresh-files')">
                  <i class="bi bi-arrow-clockwise file-icons"></i>
                  <p class="mb-0">Neu laden</p>
                </div>
              </div>
              <div class="card h-100">
                <div class="card-body p-2">
                  <ul class="list-group list-group-flush">
                    <li v-for="file in sortedFiles" :key="file" class="filename list-group-item" draggable="true"
                      @dragover="onDragOver" @dragstart="onDragStart($event, 'fileList', file)"
                      @drop="onDrop($event, 'fileList')" @click="$emit('file-clicked', file)">
                      <span class="text-truncate">
                        {{ file }}
                      </span>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import TableComponent from "./TableComponent.vue";
import MessageDialog from "./MessageDialog.vue";
export default {
  components: {
    MessageDialog,
    TableComponent,
  },
  props: {
    dialogTitle: {
      type: String,
      default: "",
    },
    dialogMessage: {
      type: String,
      default: "",
    },
    fields: {
      type: Array,
      required: true,
    },
    files: {
      type: Array,
      default: null
    },
    filterOptions: {
      type: Array,
      required: false,
    },
    highlight: {
      type: Number,
      required: false,
      default: null,
    },
    loading: {
      type: String,
      default: "",
    },
    showAsModal: {
      type: Boolean,
      default: false
    },
    showModal: {
      type: Boolean,
      default: false,
    },
    submenu: {
      type: Array,
      default: () => []
    },
    tableData: {
      type: Array,
      required: true,
    },
    title: {
      type: String,
      required: true,
    },
    initialPosition: {
      type: String,
      required: false,
      default: "center"
    }

  },
  data() {
    return {
      posX: 100,
      posY: 80,
      dragging: false,
      offsetX: 0,
      offsetY: 0,
    }
  },
  emits: ["action", "cancel", "confirm", "edit", "file-clicked", "file-dropped", "menu-selected", "refresh-files", "row-selected", "select-folder"],
  mounted() {
    if (!this.showAsModal) {
      return;
    }

    const width = window.innerWidth;

    switch (this.initialPosition) {
      case "left":
        this.posX = 20;
        break;

      case "center":
        this.posX = Math.round(width / 2);
        break;

      case "right":
        this.posX = width - 20;
        break;
    }
  },
  computed: {
    modalStyle() {
      if (!this.showAsModal) {
        return {};
      }

      let left = this.posX + "px";

      if (this.initialPosition === "left") {
        left = "20px";
      } else if (this.initialPosition === "center") {
        left = "50%";
      } else if (this.initialPosition === "right") {
        left = "calc(100% - 20px)";
      }

      return {
        top: this.posY + "px",
        left,
        transform:
          this.initialPosition === "center"
            ? "translateX(-50%)"
            : this.initialPosition === "right"
              ? "translateX(-100%)"
              : ""
      };
    },
    sortedFiles() {
      return [...this.files].sort((a, b) => {
        const partsA = a.split('_');
        const partsB = b.split('_');
        const euroAmountA = partsA[0];
        const euroAmountB = partsB[0];
        const centA = partsA.slice(1).join('_');
        const centB = partsB.slice(1).join('_');
        const paddedEuroAmountA = euroAmountA.padStart(4, '\u00A0'); // Use Unicode character for non-breaking space
        const paddedEuroAmountB = euroAmountB.padStart(4, '\u00A0');
        const paddedCentA = centA.padStart(2, '0');
        const paddedCentB = centB.padStart(2, '0');
        const formattedAmountA = paddedEuroAmountA + '_' + paddedCentA;
        const formattedAmountB = paddedEuroAmountB + '_' + paddedCentB;
        return parseFloat(formattedAmountA) - parseFloat(formattedAmountB);
      })
    }
  },
  methods: {
    cancel() {
      if (this.showAsModal) {
        this.$emit("cancel");
      } else {
        this.$router.push("/");
      }
    },
    focusChildInput(itemId, fieldName) {
      this.$refs.childTable.focusInput(itemId, fieldName); // Methode der Kindkomponente aufrufen
    },
    fileDropped({ source, file, target }) {
      this.$emit("file-dropped", { source, file, target })
    },
    menuSelected(entry) {
      this.$emit("menu-selected", entry.value);
    },
    onAction(event) {
      this.$emit('action', event)
    },
    onDragOver(event) {
      event.preventDefault()
    },
    onDrop(event, target) {
      const data = event.dataTransfer.getData("application/json")
      if (!data) return
      let payload
      try {
        payload = JSON.parse(data)
      } catch {
        return
      }
      this.$emit("file-dropped", { source: payload.source, file: payload.file, target })
    },
    onDragStart(event, source, file) {
      event.dataTransfer.effectAllowed = "move"
      event.dataTransfer.setData("application/json", JSON.stringify({ source, file }))
    },
    rowSelected(row) {
      this.$emit("row-selected", row);
    },
    rowEdited(rowInfo) {
      this.$emit("edit", rowInfo);
    },
    startDrag(e) {
      if (!this.showAsModal) return

      this.dragging = true
      this.offsetX = e.clientX - this.posX
      this.offsetY = e.clientY - this.posY

      document.addEventListener("mousemove", this.onDrag)
      document.addEventListener("mouseup", this.stopDrag)
    },

    onDrag(e) {
      if (!this.dragging) return

      this.posX = e.clientX - this.offsetX
      this.posY = e.clientY - this.offsetY
    },

    stopDrag() {
      this.dragging = false
      document.removeEventListener("mousemove", this.onDrag)
      document.removeEventListener("mouseup", this.stopDrag)
    },
  },
};
</script>
<style>
.card-header {
  cursor: move;
}

.custom-container {
  margin: 0 auto;
  height: 100vh;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  box-sizing: border-box;
}

.filename {
  font-family: monospace;
  text-align: left;
  font-size: 0.9rem;
  cursor: move;
}

/* Karte passt sich der Höhe des Containers an */
.full-height-card {
  width: 100%;
  overflow: hidden;
  top: -10px;
}

.card-bg {
  background-color: #dddddd;
}

.file-icons {
  font-size: 1.5rem;
  cursor: pointer;
}

.icon-div {
  background-color: lightskyblue;
  border-radius: 10px;
}

.modal-body {
  max-height: 80vh;
  overflow-y: auto;
}

.ownModal {
  max-height: 50%;
  max-width: 50%;
  top: 100px;
  left: 25%;
  display: block;
  position: fixed;
  z-index: 1050;
}

.spinner-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  /* Halbtransparentes Schwarz */
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
  /* Sicherstellen, dass es über dem UI liegt */
}

/* Spinner-Container */
.spinner-container {
  background-color: #fff;
  /* Weißer Hintergrund für das Fenster */
  padding: 20px;
  border-radius: 10px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  text-align: center;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}

/* Spinner-Stil */
.spinner {
  border: 5px solid #f3f3f3;
  border-top: 5px solid #3498db;
  /* Farbe des Spinners */
  border-radius: 50%;
  width: 50px;
  height: 50px;
  animation: spin 1s linear infinite;
  margin-bottom: 10px;
}

/* Text-Stil */
.loading-text {
  font-size: 16px;
  color: #333;
  margin: 0;
}

/* Spinner-Animation */
@keyframes spin {
  0% {
    transform: rotate(0deg);
  }

  100% {
    transform: rotate(360deg);
  }
}
</style>
