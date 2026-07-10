<template>
  <CardComponent title="Archivsuche" :table-data="tableData" :fields="fields" :loading="loading">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Ort</label>
        <select class="form-select" v-model="ort">
          <option :value="null">
            Alle
          </option>
          <option v-for="ort in orte" :key="ort.id" :value="ort.id">
            {{ ort.name }}
          </option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Von Jahr</label>
        <input class="form-control" type="number" v-model="suche.startJahr">
      </div>
      <div class="col-md-2">
        <label class="form-label">Bis Jahr</label>
        <input class="form-control" type="number" v-model="suche.endJahr">
      </div>
      <SchlagwortSelektor v-model="schlagworte"></SchlagwortSelektor>
      <div class="col-md-4">
        <label class="form-label">Suchbegriff</label>
        <input class="form-control" v-model="suche.suchbegriff">
      </div>
    </div>
    <div class="mt-3">
      <button class="btn btn-primary" @click="sucheStarten">Suchen</button>
    </div>
  </CardComponent>
</template>
<script>
import CardComponent from './CardComponent.vue';
import SchlagwortSelektor from './SchlagwortSelektor.vue';
export default {
  components: {
    CardComponent,
    SchlagwortSelektor
  },
  data() {
    return {
      fields:
        [
          { name: 'id', label: 'ID', type: 'text', readonly: true },
          { name: 'ort', label: 'Ort', type: 'text', 'width': '100px' },
          { name: 'thema', label: 'Thema', type: 'text' },
          { name: 'titel', label: 'Titel', type: 'text', 'width': '350px' },
          { name: 'beschreibung', label: 'Beschreibung', type: 'text', 'width': '350px' },
          { name: 'datum', label: 'Datum', type: 'date', 'width': '150px' },
          { name: 'zeitraum_start', label: 'Zeitraum Start', type: 'text', 'width': '150px' },
          { name: 'zeitraum_ende', label: 'Zeitraum Ende', type: 'text', 'width': '150px' },
          { name: 'archivdatei', label: 'Vorschau', type: 'files' }
        ],
      loading: "",
      suche: {
        ort_id: null,
        schlagworte_ids: [],
        startJahr: 0,
        endJahr: 0,
        objektart: ""
      },

      orte: [],
      schlagworte: [],

      objektarten: [
        { value: "", text: "Alle" },
        { value: "analog", text: "Analoge Objekte" },
        { value: "digital", text: "Digitale Objekte" }
      ],
      ort: null,
      tableData: [],
      treffer: []
    };
  },
  async created() {
    const orteResponse = await this.$axios.get("/Orte/getAll");
    this.orte = orteResponse.data.data;
  },
  methods: {
    async sucheStarten() {
      this.suche.schlagworte_ids = this.schlagworte.map(s => s.id);
      this.ort
        ? this.suche.ort_id = this.ort
        : delete this.suche.ort_id;
      this.suche.startJahr === 0
        ? delete this.suche.startJahr
        : this.suche.startJahr = this.suche.startJahr;
      this.suche.endJahr === 0
        ? delete this.suche.endJahr
        : this.suche.endJahr = this.suche.endJahr;
      this.loading = "Suche läuft..."
      const response = await this.$axios.post("/Archiv/search", this.suche);
      this.loading = "";
      this.tableData = response.data.data;
      this.tableData.map(item => {
        if (item.datei_anzahl === 1) {
          item.archivdatei = { count: 1, firstId: item.erste_datei_id, type: "pdf" }
        } else {
          item.archivdatei = { count: item.datei_anzahl }
        }
        return item;
      });
    }
  }
}
</script>
