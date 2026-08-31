<template>
  <MessageDialog title="Digitalobjekt hinzufügen" confirm-text="Speichern" cancel-text="Abbrechen" message="" full-width
    @confirm="onConfirm" @cancel="onCancel">
    <div class="row dialog-row">
      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-header">
            <h5 class="mb-0">Posteingang</h5>
          </div>
          <div class="card-body file-select-color">
            <TreeView :tree="tree" title="" selectable @file-selected="selectFile" @refresh-tree="refreshTree" />
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-header">
            <h5 class="mb-0">Digitalobjekt</h5>
          </div>
          <div class="card-body own-color">
            <div class="row mb-3">
              <label class="col-sm-3 col-form-label">Titel</label>
              <div class="col-sm-9">
                <input class="form-control" v-model="titel">
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-sm-3 col-form-label">Dateidatum</label>
              <div class="col-sm-9">
                <input class="form-control" type="date" v-model="dateidatum">
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-sm-3 col-form-label">Quelle</label>
              <div class="col-sm-9">
                <select class="form-select" v-model="quellenId">
                  <option :value="null">Keine</option>
                  <option v-for="quelle in quellen" :key="quelle.value" :value="quelle.value">
                    {{ quelle.display }}
                  </option>
                </select>
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-sm-3 col-form-label">Sperre</label>
              <div class="col-sm-9">
                <div class="form-check form-switch m-0">
                  <input id="add-digital-gesperrt" class="form-check-input large-switch mt-2" type="checkbox"
                    v-model="gesperrt">
                </div>
              </div>
            </div>

            <div v-show="gesperrt" class="row mb-3">
              <label class="col-sm-3 col-form-label">Sperre bis</label>
              <div class="col-sm-9">
                <input class="form-control" type="number" v-model.number="gesperrtBisJahr" min="1900" max="2999"
                  step="1" placeholder="JJJJ">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </MessageDialog>
</template>

<script>
import MessageDialog from './MessageDialog.vue';
import TreeView from './TreeView.vue';

export default {
  name: "AddDigitalDialog",
  components: { MessageDialog, TreeView },
  props: {
    tree: {
      type: Array,
      required: true,
    },
    quellen: {
      type: Array,
      default: () => [],
    },
    row: {
      type: Object,
      default: null,
    },
  },
  emits: ["confirm", "cancel"],
  data() {
    return {
      titel: this.row?.titel || '',
      quellenId: this.row?.quellen_id ?? null,
      dateidatum: this.row?.dateidatum || '',
      gesperrt: this.row?.gesperrt || false,
      gesperrtBisJahr: this.row?.gesperrt_bis ? Number(this.row.gesperrt_bis) : null,
      selectedFile: null,
    };
  },
  methods: {
    selectFile(path, isSelected) {
      this.selectedFile = isSelected ? path : null;
    },
    refreshTree() {
      this.$emit('refresh-tree');
    },
    onConfirm() {
      this.$emit('confirm', {
        titel: this.titel,
        quellen_id: this.quellenId,
        dateidatum: this.dateidatum,
        gesperrt: this.gesperrt,
        gesperrt_bis: this.gesperrt ? this.gesperrtBisJahr : null,
        file: this.selectedFile,
      });
    },
    onCancel() {
      this.$emit('cancel');
    },
  },
};
</script>

<style scoped>
:deep(.modal-dialog) {
  height: 90vh;
}

:deep(.modal-content) {
  height: 100%;
  display: flex;
  flex-direction: column;
}

:deep(.modal-body) {
  flex: 1;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}

.dialog-row {
  flex: 1;
  min-height: 0;
}

.file-select-color {
  background-color: azure;
}

.own-color {
  background-color: blanchedalmond;
}

.large-switch {
  transform: scale(1.4);
  transform-origin: left center;
}
</style>
