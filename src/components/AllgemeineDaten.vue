<template>
  <div class="allgemein">
    <div class="row g-3">
      <div class="col-6">
        <label class="form-label">Titel</label>
        <input class="form-control" v-model="lokal.titel" placeholder="Titel des Archivobjekts">
      </div>
      <div class="col-3">
        <label class="form-label">Ort</label>
        <select class="form-select" v-model="lokal.ort">
          <option :value="null">Bitte wählen</option>
          <option v-for="ort in stammdaten.orte" :key="ort.id" :value="ort.id">
            {{ ort.name }}
          </option>
        </select>
      </div>
      <div class="col-3">
        <label class="form-label">Thema</label>
        <select class="form-select" v-model="lokal.ort">
          <option :value="null">Bitte wählen</option>
          <option v-for="thema in stammdaten.themen" :key="thema.id" :value="thema.id">
            {{ thema.name }}
          </option>
        </select>
      </div>
      <SchlagwortSelektor v-model="lokal.schlagworte"></SchlagwortSelektor>
      <!-- Beschreibung -->
      <div class="col-12">
        <label class="form-label">Beschreibung</label>
        <textarea class="form-control" rows="5" v-model="lokal.beschreibung" placeholder="Beschreibung">
            </textarea>
      </div>

      <!-- Datum -->
      <div class="col-md-4">
        <label class="form-label">Datum</label>
        <input type="date" class="form-control" v-model="lokal.datum">
      </div>

      <!-- Startjahr -->
      <div class="col-md-2">
        <label class="form-label">Startjahr</label>
        <input type="number" class="form-control" v-model="lokal.startJahr">
      </div>
      <div class="col-md-2">
        <label class="form-label">Startjahr Zusatz</label>
        <input type="text" class="form-control" v-model="lokal.start_ergaenzung">
      </div>

      <!-- Endjahr -->
      <div class="col-md-2">
        <label class="form-label">Endjahr</label>
        <input type="number" class="form-control" v-model="lokal.endJahr">
      </div>
      <div class="col-md-2">
        <label class="form-label">Endjahr Zusatz</label>
        <input type="text" class="form-control" v-model="lokal.ende_ergaenzung">
      </div>

    </div>

  </div>
</template>
<script>
import SchlagwortSelektor from "./SchlagwortSelektor.vue";

export default {
  components: {
    SchlagwortSelektor
  },
  props: {
    modelValue: {
      type: Object,
      required: true
    },
  },
  data() {
    return {
      vorschlaege: [],
      ausgewaehlt: 0,
      stammdaten: {
        orte: [],
        objektTypen: [],
        quellen: [],
        themen: [],
      }
    };

  },
  emits: ["update:modelValue"],

  computed: {
    lokal: {
      get() {
        return this.modelValue;
      },
      set(value) {
        this.$emit("update:modelValue", value);
      }
    }
  },
  async created() {
    this.stammdaten = await this.getStammdaten();
  },
  methods: {
    async getStammdaten() {
      const orteResponse = await this.$axios.get("/Orte/getAll");
      this.stammdaten.orte = orteResponse.data.data;
      const objektTypenResponse = await this.$axios.get("/Objekttypen/getAll");
      this.stammdaten.objektTypen = objektTypenResponse.data.data;
      const quellenResponse = await this.$axios.get("/Quellen/getAll");
      this.stammdaten.quellen = quellenResponse.data.data;
      const themenResponse = await this.$axios.get("/Themen/getAll");
      this.stammdaten.themen = themenResponse.data.data;
      return this.stammdaten;
    },
  }
};
</script>
<style scoped>
.allgemein {
  background-color: #e8e9ea;
  border: 1px solid #dee2e6;
  border-radius: 0.375rem;
}
</style>