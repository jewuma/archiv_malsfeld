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
    fixedData: {
      type: Object,
      required: true
    },
  },
  data() {
    return {
      fields: [
        { name: 'id', label: 'ID', type: 'text', readonly: true, width: '80px', hidden: true },
        { name: 'archiv_id', label: 'ArchivID', type: 'text', readonly: true, width: '80px', inlineEdit: true },
        { name: 'objekttyp_id', label: 'Typ', type: 'select', 'width': '130px', inlineEdit: true, options: this.fixedData.objekttypen },
        { name: 'seiten', label: 'Seiten', type: 'number', width: '80px', inlineEdit: true },
        { name: 'lagerort_id', label: 'Lagerort', type: 'select', 'width': '150px', inlineEdit: true, options: this.fixedData.lagerorte },
        { name: 'quellen_id', label: 'Quelle', type: 'select', 'width': '150px', inlineEdit: true, options: this.fixedData.quellen },
        { name: 'regal_id', label: 'Regal', type: 'select', 'width': '80px', inlineEdit: true, options: this.fixedData.regale },
        { name: 'fach_id', label: 'Fach', type: 'select', 'width': '80px', inlineEdit: true, options: this.fixedData.faecher },
        { name: 'platz_id', label: 'Platz', type: 'select', 'width': '70px', inlineEdit: true, options: this.fixedData.plaetze },
        { name: 'digitalisiert', label: 'Digitalisiert', type: 'boolean', 'width': '100px', inlineEdit: true },
        { name: 'dokumentendatum', label: 'Dok-Datum', type: 'date', 'width': '100px', inlineEdit: true },
        { name: 'archivdatum', label: 'Archiviert am', type: 'date', 'width': '100px', inlineEdit: true }
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