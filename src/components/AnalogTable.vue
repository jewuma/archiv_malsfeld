<template>
  <TableComponent :tableData="analogInfo" :display-filter="false" :fields="fields" :use-all-y-space="false"
    @edit="entryEdited" @action="checkAction" />
</template>
<script>
import TableComponent from './TableComponent.vue';
export default {
  name: "AnalogTable",
  components: { TableComponent },
  emits: ["toggle-expand", "deleteAnalogobjekt", "addAnalogobjekt", "draft-updated"],
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
    }
  },
  data() {
    return {
      fields: [
        { name: 'id', label: 'ID', type: 'text', readonly: true, width: '80px', hidden: true },
        {
          name: 'archiv_id', label: 'ArchivID', type: 'text', width: '60px', inlineEdit: true,
          validate: async (row, value) => {
            if (!/^\d{5,}$/.test(String(value))) {
              return "ArchivID muss mindestens 5-stellig und numerisch sein.";
            }
            const original = this.analogInfo.find(
              item => item.id === row.id
            );

            if (String(value) === String(original.archiv_id)) {
              return false;
            }

            try {
              const response = await this.$axios.get(
                "/Analogobjekte/exists/" + value
              );

              if (response.data.data.exists) {
                return "ArchivID existiert bereits. Bitte eine andere ID wählen.";
              }
            } catch (error) {
              console.error(error);
              return "Die ArchivID konnte nicht geprüft werden.";
            }
            return false;
          }
        },
        { name: 'objekttyp_id', label: 'Typ', type: 'select', 'width': '140px', inlineEdit: true, options: this.fixedData.objekttypen },
        { name: 'seiten', label: 'Seiten', type: 'number', width: '60px', inlineEdit: true },
        { name: 'lagerort_id', label: 'Lagerort', type: 'select', 'width': '150px', inlineEdit: true, options: this.fixedData.lagerorte },
        { name: 'quellen_id', label: 'Quelle', type: 'select', 'width': '150px', inlineEdit: true, options: this.fixedData.quellen },
        { name: 'regal_id', label: 'Regal', type: 'select', 'width': '80px', inlineEdit: true, options: this.fixedData.regale },
        { name: 'fach_id', label: 'Fach', type: 'select', 'width': '80px', inlineEdit: true, options: this.fixedData.faecher },
        { name: 'platz_id', label: 'Platz', type: 'select', 'width': '70px', inlineEdit: true, options: this.fixedData.plaetze },
        { name: 'gesperrt', label: 'Sperre', type: 'boolean', 'width': '50px', inlineEdit: true },
        { name: 'gesperrt_bis', label: 'Sperre bis', type: 'text', 'width': '70px', inlineEdit: true },
        { name: 'dokumentendatum', label: 'Dok-Datum', type: 'date', 'width': '100px', inlineEdit: true },
        { name: 'archivdatum', label: 'Archiviert am', type: 'date', 'width': '100px', inlineEdit: true },
      ],
      analogInfo: [],

    }
  },
  created() {
    if (this.showActionButtons) {
      this.fields.push(
        { name: 'delete', label: '', type: 'icon', 'width': '30px', icon: "bi bi-x-circle-fill text-danger", emit: "deleteAnalogobjekt" },
        { name: 'add', label: '', type: 'icon', 'width': '30px', icon: "bi bi-plus-circle-fill text-success", emit: "addAnalogobjekt" },
      )
    }
  },
  async mounted() {
    const initialData = this.item.analogobjekte ?? this.item.analogObjekte
    if (Array.isArray(initialData)) {
      this.analogInfo = initialData
      return
    }

    const analogInfo = await this.$axios.get("/Archiv/getAnalogobjects/" + this.item.id)
    this.analogInfo = analogInfo.data.data
  },
  methods: {
    checkAction(action, row) {
      if (action === 'addAnalogobjekt') {
        this.$emit('addAnalogobjekt', row);
      } else if (action === 'deleteAnalogobjekt') {
        this.$emit('deleteAnalogobjekt', row);
      }
    },
    async entryEdited(editedRow) {
      if (editedRow.fieldName === 'archiv_id') {
        if (editedRow.value.length > 4) {
          const originalArchivId = parseInt(this.analogInfo.find(item => item.id === editedRow.id).archiv_id);
          if (parseInt(editedRow.value) !== originalArchivId) {
            try {
              const existsResponse = await this.$axios.get("/Analogobjekte/exists/" + editedRow.value);
              if (existsResponse.data.data.exists) {
                this.$sendMsg(true, "ArchivID existiert bereits. Bitte eine andere ID wählen.");
                return;
              }
            } catch (error) {
              console.error(error);
            }
          }
        } else {
          return;
        }
      }
      if (!this.persistEdits) {
        const rowIndex = this.analogInfo.findIndex(item => item.id === editedRow.id)
        if (rowIndex >= 0) {
          this.analogInfo[rowIndex] = {
            ...this.analogInfo[rowIndex],
            [editedRow.fieldName]: editedRow.value,
          }
          this.$emit('draft-updated', { ...this.analogInfo[rowIndex] })
        }
        return
      }
      const payload = {
        id: editedRow.id,
        [editedRow.fieldName]: editedRow.value
      };
      await this.$axios.post("/Analogobjekte/update", payload);
    },

  }

}
</script>