<template>
  <TableComponent :tableData="analogInfo" :display-filter="false" :fields="fields" :use-all-y-space="false" />
</template>
<script>
import TableComponent from './TableComponent.vue';
export default {
  name: "AnalogTable",
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
        { name: 'id', label: 'ID', type: 'text', readonly: true, width: '80px', hidden: true },
        { name: 'archiv_id', label: 'ArchivID', type: 'text', readonly: true, width: '80px' },
        { name: 'objekttyp', label: 'Typ', type: 'text', 'width': '130px' },
        { name: 'seiten', label: 'Seiten', type: 'text', width: '80px' },
        { name: 'lagerort', label: 'Lagerort', type: 'text', 'width': '150px' },
        { name: 'quelle', label: 'Quelle', type: 'text', 'width': '150px' },
        { name: 'regal', label: 'Regal', type: 'text', 'width': '80px' },
        { name: 'fach', label: 'Fach', type: 'text', 'width': '80px' },
        { name: 'platz', label: 'Platz', type: 'text', 'width': '70px' },
        { name: 'digitalisiert', label: 'Digitalisiert', type: 'boolean', 'width': '100px' },
        { name: 'dokumentendatum', label: 'Dok-Datum', type: 'text', 'width': '100px' },
        { name: 'archivdatum', label: 'Archiviert am', type: 'date', 'width': '100px' }
      ],
      analogInfo: [],

    }
  },
  async created() {
    if (!this.showIcon) {
      const analogInfo = await this.$axios.get("/Archiv/getAnalogobjects/" + this.item.id)
      this.analogInfo = analogInfo.data.data
    }
  }
}
</script>