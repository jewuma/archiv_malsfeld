<template>
  <TableComponent :tableData="analogInfo" :display-filter="false" :fields="fields" />
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
    showIcon: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  computed: {

    iconClass() {
      const type = this.item.type || 0;
      if ([1, 2, 17].includes(type)) return "bi bi-book";
      if (type === 2) return "bi bi-map";
      if (type === 7) return "bi bi-disc"
      if (type === 20) return "bi bi-camera"
      if (type === 21) return "bi bi-newspaper"
      return "bi bi-files";
    },
    tooltip() {
      return `${this.item.count} Obekte`;
    }
  },
  data() {
    return {
      fields: [
        { name: 'id', label: 'ID', type: 'text', readonly: true, width: '80px' },
        { name: 'objekttyp', label: 'Typ', type: 'text', 'width': '130px' },
        { name: 'seiten', label: 'Seiten', type: 'text', width: '80px' },
        { name: 'lagerort', label: 'Lagerort', type: 'text', 'width': '150px' },
        { name: 'quelle', label: 'Quelle', type: 'text', 'width': '150px' },
        { name: 'regal', label: 'Regal', type: 'text', 'width': '120px' },
        { name: 'fach', label: 'Fach', type: 'text', 'width': '120px' },
        { name: 'platz', label: 'Analog', type: 'analogobjekt', 'width': '70px' },
        { name: 'digitalisiert', label: 'Digitalisiert', type: 'boolean', 'width': '100px' },
        { name: 'dokumentendatum', label: 'Dok-Datum', type: 'date', 'width': '100px' },
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