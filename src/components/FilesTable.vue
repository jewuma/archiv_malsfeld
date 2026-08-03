<template>
  <TableComponent :tableData="fileInfo" :display-filter="false" :fields="fields" :use-all-y-space="false" />
</template>
<script>
import TableComponent from './TableComponent.vue';
export default {
  name: "FilesTable",
  components: { TableComponent },
  emits: ["toggle-expand"],
  props: {
    item: {
      type: Object,
      required: true
    },
  },
  data() {
    return {
      fields: [
        { name: 'id', label: 'ID', type: 'text', readonly: true, hidden: true },
        { name: 'pfad', label: 'Pfad', type: 'text', 'width': '250px' },
        { name: 'dateiname', label: 'Dateiname', type: 'text', width: '250px' },
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
          type: file.dateiname.split('.').pop().toLowerCase(),
          firstId: file.id,
        }
      }
    })
  }
}
</script>