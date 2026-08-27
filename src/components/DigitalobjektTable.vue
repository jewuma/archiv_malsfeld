<template>
  <TableComponent :tableData="fileInfo" :display-filter="false" :fields="fields" :use-all-y-space="false"
    @edit="entryEdited" @action="checkAction" />
</template>
<script>
import TableComponent from './TableComponent.vue';
export default {
  name: "DigitalobjektTable",
  components: { TableComponent },
  emits: ["toggle-expand", "addDigitalobjekt", "deleteDigitalobjekt"],
  props: {
    item: {
      type: Object,
      required: true
    },
    fixedData: {
      type: Object,
      required: true
    },
    persistEdits: {
      type: Boolean,
      default: true
    },
    showActionButtons: {
      type: Boolean,
      default: true
    },
  },
  data() {
    return {
      fields: [
        { name: 'id', label: 'ID', type: 'text', readonly: true, hidden: true },
        { name: 'titel', label: 'Titel', type: 'text', 'width': '250px', inlineEdit: true },
        { name: 'quellen_id', label: 'Quelle', type: 'select', 'width': '150px', inlineEdit: true, options: this.fixedData.quellen },
        { name: 'objekttyp', label: 'Dateiart', type: 'text', 'width': '150px' },
        { name: 'dateidatum', label: 'Dateidatum', type: 'date', 'width': '100px', inlineEdit: true },
        { name: 'archivdatum', label: 'Archiviert am', type: 'date', 'width': '100px' },
        { name: 'fileInfo', label: 'Anschauen', type: 'files', 'width': '80px' },
      ],
      fileInfo: [],

    }
  },
  async created() {
    if (this.showActionButtons) {
      this.fields.push(
        { name: 'delete', label: '', type: 'icon', 'width': '30px', icon: "bi bi-x-circle-fill text-danger", emit: "deleteDigitalobjekt" },
        { name: 'add', label: '', type: 'icon', 'width': '30px', icon: "bi bi-plus-circle-fill text-success", emit: "addDigitalobjekt" },
      )
    }
    const dateiInfo = await this.$axios.get("/Archiv/getFileobjects/" + this.item.id)
    this.fileInfo = dateiInfo.data.data.map(file => {
      return {
        ...file,
        fileInfo: {
          count: 1,
          id: file.id,
          type: file.dateiendung,
          firstId: file.id,
        }
      }
    })
  },
  methods: {
    checkAction(action, row) {
      if (action === 'addDigitalobjekt') {
        this.$emit('addDigitalobjekt', row);
      } else if (action === 'deleteDigitalobjekt') {
        this.$emit('deleteDigitalobjekt', row);
      }
    },
    async entryEdited(editedRow) {
      if (!this.persistEdits) {
        const rowIndex = this.fileInfo.findIndex(item => item.id === editedRow.id)
        if (rowIndex >= 0) {
          this.fileInfo[rowIndex] = {
            ...this.fileInfo[rowIndex],
            [editedRow.fieldName]: editedRow.value,
          }
          this.$emit('draft-updated', { ...this.fileInfo[rowIndex] })
        }
        return
      }
      const payload = {
        id: editedRow.id,
        [editedRow.fieldName]: editedRow.value
      };
      await this.$axios.post("/Digitalobjekte/update", payload);
    },

  }
}
</script>