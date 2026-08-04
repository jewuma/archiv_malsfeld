<template>
  <div class="save-options-overlay">
    <div class="save-options-panel card shadow-lg">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center"
        @mousedown="startDrag">
        <h5 class="mb-0">Archivierungseinstellungen</h5>
        <button class="btn btn-danger btn-sm" @click="cancel" @mousedown.stop>Beenden</button>
      </div>

      <div class="card-body">
        <div class="save-options-content">
          <div class="preview-column">
            <div class="preview-frame">
              <img v-if="imageUrl" :src="imageUrl" class="preview-image">
              <div v-else class="preview-placeholder">Keine Vorschau verfügbar</div>
            </div>

            <div class="preview-controls">
              <button class="btn btn-outline-secondary btn-sm" @click="previousFile">Zurück</button>
              <button class="btn btn-outline-secondary btn-sm" @click="nextFile">Vor</button>
              <input v-if="archivOption === 'selectTitlePerFile'" v-model="titles[currentIndex]"
                class="form-control form-control-sm" placeholder="Titel">
            </div>
          </div>

          <div class="options-column">
            <div class="card mb-3">
              <div class="card-header">Speicheroption</div>

              <div class="card-body">
                <div class="form-check mb-2">
                  <input class="form-check-input" type="radio" id="newArchivObjectPerFile"
                    value="newArchivObjectPerFile" v-model="archivOption" name="archivOption">
                  <label class="form-check-label" for="newArchivObjectPerFile">
                    Neues Archivobjekt für jede Datei anlegen
                  </label>
                </div>

                <div class="form-check mb-2">
                  <input class="form-check-input" type="radio" id="saveAllFilesToOneArchivObject"
                    value="saveAllFilesToOneArchivObject" v-model="archivOption" name="archivOption">
                  <label class="form-check-label" for="saveAllFilesToOneArchivObject">
                    Alle Dateien unter einem Archivobjekt speichern
                  </label>
                </div>

                <div class="form-check">
                  <input class="form-check-input" type="radio" id="selectTitlePerFile" value="selectTitlePerFile"
                    v-model="archivOption" name="archivOption">
                  <label class="form-check-label" for="selectTitlePerFile">
                    Für jede Datei einen eigenen Titel vergeben
                  </label>
                </div>
              </div>
            </div>

            <div class="card mb-3">
              <div class="card-header">Dateiname</div>

              <div class="card-body">
                <div class="form-check mb-2">
                  <input class="form-check-input" type="checkbox" id="newFilePrefix" v-model="newFilePrefix">
                  <label class="form-check-label" for="newFilePrefix">
                    Neue Dateinamen vergeben (Prefix)
                  </label>
                </div>

                <input v-if="newFilePrefix" class="form-control" v-model="filePrefix" placeholder="Dateiname">
              </div>
            </div>
          </div>

          <div class="action-buttons">
            <button class="btn btn-outline-secondary" @click="cancel">Abbrechen</button>
            <button class="btn btn-primary" @click="apply">Übernehmen und Speichern</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "ArchivSaveOptionen",
  components: {
  },
  props: {
    files: {
      type: Array,
      required: true
    },
  },

  data() {
    return {
      currentIndex: 0,
      currentTitle: "",
      imageUrl: "",
      newFilePrefix: false,
      archivOption: "newArchivObjectPerFile",
      filePrefix: "",
      titles: []
    };
  },

  methods: {
    async loadPreview() {
      if (this.imageUrl) {
        URL.revokeObjectURL(this.imageUrl);
      }

      this.imageUrl = "";

      const file = this.files?.[this.currentIndex];
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

        const blob = new Blob([response.data], { type: "image/webp" });
        this.imageUrl = window.URL.createObjectURL(blob);
      } catch (error) {
        console.error("Fehler beim Laden der Vorschau:", error);
      }
    },

    previousFile() {
      if (this.currentIndex > 0) {
        this.currentIndex -= 1;
        this.loadPreview();
      }
    },

    nextFile() {
      if (this.currentIndex < this.files.length - 1) {
        this.currentIndex += 1;
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
        archivOption: this.archivOption,
        newFilePrefix: this.newFilePrefix,
        filePrefix: this.filePrefix
      });
    },

    beforeUnmount() {
      if (this.imageUrl) {
        URL.revokeObjectURL(this.imageUrl);
      }
    }
  },

  mounted() {
    this.loadPreview();
  },

  watch: {
    files: {
      handler() {
        this.loadPreview();
      },
      immediate: true
    }
  }
};
</script>
<style scoped>
.save-options-overlay {
  position: fixed;
  inset: 50px 0 10px 0;
  z-index: 1050;
  display: flex;
  justify-content: center;
  align-items: stretch;
  padding: 1rem;
  background: linear-gradient(135deg, rgba(13, 110, 253, 0.25), rgba(111, 66, 193, 0.3));
  backdrop-filter: blur(3px);
}

.save-options-panel {
  width: min(96vw, 1400px);
  height: 100%;
  max-height: calc(100vh - 60px);
  display: flex;
  flex-direction: column;
  border: 1px solid rgba(255, 255, 255, 0.35);
  background: linear-gradient(145deg, #f8f9ff 0%, #eef4ff 100%);
}

.save-options-panel .card-body {
  flex: 1;
  min-height: 0;
  overflow: hidden;
}

.save-options-content {
  display: flex;
  gap: 1.5rem;
  height: 100%;
  min-height: 0;
  position: relative;
}

.preview-column {
  flex: 0 0 50vw;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.preview-frame {
  flex: 1;
  min-height: 0;
  display: flex;
  justify-content: center;
  align-items: center;
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

.preview-placeholder {
  color: #6c757d;
  text-align: center;
}

.preview-controls {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.options-column {
  flex: 1 1 0;
  min-width: 320px;
  overflow-y: auto;
}

.action-buttons {
  position: absolute;
  right: 0;
  bottom: 0;
  display: flex;
  gap: 0.5rem;
  padding: 0.5rem 0 0 0;
}
</style>