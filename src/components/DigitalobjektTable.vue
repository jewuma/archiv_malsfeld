<template>
  <TableComponent :tableData="fileInfo" :display-filter="false" :fields="fields" :use-all-y-space="false" />
</template>
<script>
import TableComponent from './TableComponent.vue';
export default {
  name: "DigitalobjektTable",
  components: { TableComponent },
  emits: ["toggle-expand"],
  props: {
    item: {
      type: Object,
      required: true
    },
    fixedData: {
      type: Object,
      required: true
    },
  },
  data() {
    return {
      fields: [
        { name: 'id', label: 'ID', type: 'text', readonly: true, hidden: true },
        { name: 'titel', label: 'Titel', type: 'text', 'width': '250px' },
        { name: 'quellen_id', label: 'Quelle', type: 'select', 'width': '150px', inlineEdit: true, options: this.fixedData.quellen },
        { name: 'objekttyp', label: 'Dateiart', type: 'text', 'width': '150px' },
        { name: 'dateidatum', label: 'Dateidatum', type: 'date', 'width': '100px' },
        { name: 'archivdatum', label: 'Archiviert am', type: 'date', 'width': '100px' },
        { name: 'fileInfo', label: 'Anschauen', type: 'files', 'width': '80px' },
      ],
      fileInfo: [],

    }
  },
  async created() {
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
  }
}
</script>