<template>
  <div class="save-options-overlay">
    <div class="save-options-panel card shadow-lg" :style="dialogStyle" ref="dialog">

      <!-- Kopfzeile -->
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center"
        @mousedown="startDrag">
        <h5 class="mb-0">Archivierungseinstellungen</h5>

        <button class="btn btn-danger btn-sm" @click="cancel" @mousedown.stop>
          Beenden
        </button>
      </div>

      <!-- Inhalt -->
      <div class="card-body overflow-hidden">
        <div class="d-flex gap-3 h-100 position-relative">

          <!-- Vorschau -->
          <div class="preview-column d-flex flex-column gap-3">

            <div class="preview-frame flex-grow-1">
              <img v-if="imageUrl" :src="imageUrl" class="preview-image" alt="Dokumentenvorschau">

              <div v-else class="text-secondary text-center">
                Keine Vorschau verfügbar
              </div>
            </div>
          </div>

          <!-- Eingabefelder -->
          <div class="options-column flex-grow-1">

            <div class="row mb-3">
              <label class="col-sm-3 col-form-label">
                Titel
              </label>

              <div class="col-sm-9">
                <input v-model="titles[currentIndex]" class="form-control" type="text">
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-sm-3 col-form-label">
                Dateidatum
              </label>

              <div class="col-sm-9">
                <input class="form-control" v-model="dateidaten[currentIndex]" @blur="normalizeDokumentDatum">
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-sm-3 col-form-label">
                Quelle
              </label>

              <div class="col-sm-9">
                <select class="form-select" v-model="quellen_ids[currentIndex]">
                  <option value="">Bitte wählen...</option>
                  <option v-for="quelle in quellen" :key="quelle.id" :value="Number(quelle.id)">
                    {{ quelle.display }}
                  </option>
                </select>
              </div>
            </div>
            <div class="row mb-3">



              <label class="col-sm-3 col-form-label" for="save-options-gesperrt">
                Gesperrt
              </label>

              <div class="col-sm-9">
                <div class="form-check form-switch m-0">

                  <input id="save-options-gesperrt" v-model="gesperrt[currentIndex]" type="checkbox"
                    class="form-check-input large-switch mt-2">
                </div>
              </div>
            </div>


            <div class="row mb-3" :class="{ invisible: !gesperrt[currentIndex] }">
              <label class="col-sm-3 col-form-label" for="save-options-gesperrt-bis">
                Gesperrt bis
              </label>

              <div class="col-sm-9">
                <input id="save-options-gesperrt-bis" v-model="gesperrt_bis[currentIndex]" class="form-control"
                  type="text" :disabled="!gesperrt[currentIndex]">
              </div>
            </div>
            <div class="row mb-3">
              <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm" :disabled="currentIndex === 0" @click="previousFile">
                  Zurück
                </button>

                <button class="btn btn-outline-secondary btn-sm" :disabled="currentIndex >= files.length - 1"
                  @click="nextFile">
                  Vor
                </button>
              </div>
            </div>
          </div>

          <!-- Aktionsbuttons -->
          <div class="position-absolute bottom-0 end-0 d-flex gap-2">
            <button class="btn btn-outline-secondary" @click="cancel">
              Abbrechen
            </button>

            <button class="btn btn-primary" @click="apply">
              Übernehmen und Speichern
            </button>
          </div>

        </div>
      </div>

    </div>
  </div>
</template>

<script>
export default {
  name: "ArchivSaveOptionen",

  props: {
    files: {
      type: Array,
      required: true
    },
    quellen: {
      type: Array,
      required: true
    },
    defaults: {
      type: Object,
      default: () => ({})
    }
  },

  data() {
    return {
      currentIndex: 0,
      dateidaten: [],
      dragging: false,
      filePrefix: "",
      gesperrt: [],
      gesperrt_bis: [],
      imageUrl: "",
      newFilePrefix: false,
      offsetX: 0,
      offsetY: 0,
      quellen_ids: [],
      posX: 0,
      posY: 0,
      titles: [],
    };
  },
  computed: {
    dialogStyle() {
      return {
        left: this.posX + "px",
        top: this.posY + "px",
      };
    },
  },

  methods: {
    normalizeDokumentDatum() {
      if (this.dateidaten[this.currentIndex].trim() === "") {
        this.dateidaten[this.currentIndex] = "";
        return;
      }
      let value = String(this.dateidaten[this.currentIndex]).replace(/\D/g, "");

      if (value.length === 8) {
        // DDMMYYYY -> DD.MM.YYYY
        this.dateidaten[this.currentIndex] =
          `${value.slice(0, 2)}.${value.slice(2, 4)}.${value.slice(4)}`;
      }
      else if (value.length === 6) {
        // MMYYYY -> MM.YYYY
        this.dateidaten[this.currentIndex] =
          `${value.slice(0, 2)}.${value.slice(2)}`;
      }
      else if (value.length === 4) {
        // YYYY
        this.dateidaten[this.currentIndex] = value;
      }
    },
    startDrag(event) {
      if (event.button !== 0) return;

      this.dragging = true;
      this.offsetX = event.clientX - this.posX;
      this.offsetY = event.clientY - this.posY;
      document.addEventListener("mousemove", this.onDrag);
      document.addEventListener("mouseup", this.stopDrag);
    },

    onDrag(event) {
      if (!this.dragging) return;

      this.posX = event.clientX - this.offsetX;
      this.posY = event.clientY - this.offsetY;
    },

    stopDrag() {
      this.dragging = false;
      document.removeEventListener("mousemove", this.onDrag);
      document.removeEventListener("mouseup", this.stopDrag);
    },

    async loadPreview() {
      if (this.imageUrl) {
        URL.revokeObjectURL(this.imageUrl);
        this.imageUrl = "";
      }

      const file = this.files?.[this.currentIndex];

      if (!file) {
        return;
      }

      try {
        const formData = new FormData();

        formData.append("path", file);
        formData.append("fromInbox", true);

        const response = await this.$axios.post(
          "/ArchivFiles/getPreviewByPath",
          formData,
          {
            responseType: "blob"
          }
        );

        const blob = new Blob(
          [response.data],
          { type: "image/webp" }
        );

        this.imageUrl = URL.createObjectURL(blob);
      } catch (error) {
        console.error(
          "Fehler beim Laden der Vorschau:",
          error
        );
      }
    },

    previousFile() {
      if (this.currentIndex > 0) {
        this.currentIndex--;
        this.loadPreview();
      }
    },

    nextFile() {
      if (this.currentIndex < this.files.length - 1) {
        this.currentIndex++;
        this.loadPreview();
      }
    },

    cancel() {
      this.$emit("cancel");
    },

    apply() {
      this.$emit("apply", {
        titles: this.titles,
        files: this.files,
        dateidaten: this.dateidaten,
        quellen_ids: this.quellen_ids,
        gesperrt: this.gesperrt,
        gesperrt_bis: this.gesperrt_bis
      });
    }
  },

  mounted() {
    this.$nextTick(() => {
      const dialog = this.$refs.dialog;
      const rect = dialog.getBoundingClientRect();

      this.posX = (window.innerWidth - rect.width) / 2;
      this.posY = (window.innerHeight - rect.height) / 2;
    });
    for (let i = 0; i < this.files.length; i++) {
      this.titles[i] = this.defaults.titel || "";
      this.dateidaten[i] = this.defaults.dateidatum || "";
      this.gesperrt[i] = Boolean(this.defaults.gesperrt);
      this.gesperrt_bis[i] = this.defaults.gesperrt_bis || "";
      this.quellen_ids[i] = this.defaults.quellen_id || 0;
    }
    this.loadPreview();
  },

  beforeUnmount() {
    this.stopDrag();

    if (this.imageUrl) {
      URL.revokeObjectURL(this.imageUrl);
    }
  },

  watch: {
    files: {
      handler() {
        this.currentIndex = 0;

        this.titles = this.files.map(
          () => this.defaults.titel || ""
        );

        this.loadPreview();
      }
    }
  }
};
</script>

<style scoped>
.save-options-overlay {
  position: fixed;
  inset: 0;
  z-index: 1050;
  background: linear-gradient(135deg,
      rgba(13, 110, 253, 0.25),
      rgba(111, 66, 193, 0.3));

  backdrop-filter: blur(3px);
}

.save-options-panel {
  position: fixed;
  width: min(96vw, 1400px);
  height: min(90vh, 900px);
  max-height: calc(100vh - 2rem);

  display: flex;
  flex-direction: column;
}

.save-options-panel .card-header {
  cursor: move;
  user-select: none;
}

.preview-column {
  flex: 0 0 700px;
  min-width: 0;
}

.options-column {
  min-width: 0;
  padding-bottom: 3.5rem;
  overflow-x: hidden;
}

/*
 * Die Vorschau füllt den verfügbaren Platz
 * innerhalb der linken Spalte.
 */
.preview-frame {
  min-height: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  border: 1px solid #ddd;
  border-radius: 0.5rem;

  background: #f8f9fa;

  overflow: hidden;
  padding: 1rem;
}

.preview-image {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}


/*
 * Bei schmalen Fenstern wird die Vorschau
 * oberhalb der Eingabefelder dargestellt.
 */
@media (max-width: 900px) {
  .save-options-overlay {
    inset: 50px 0 0;
    padding: 0.5rem;
  }

  .save-options-content {
    flex-direction: column;
    overflow-y: auto;
  }

  .preview-column {
    flex: 0 0 300px;
    width: 100%;
  }

  .preview-frame {
    min-height: 250px;
  }

  .options-column {
    overflow: visible !important;
    padding-bottom: 4rem;
  }
}
</style>