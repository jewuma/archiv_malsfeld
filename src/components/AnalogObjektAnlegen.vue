<template>
  <MessageDialog title="Analogobjekt anlegen" confirm-text="Objekt anlegen" cancel-text="Abbruch" message="" xl
    @confirm="createAnalogObjekt" @cancel="$emit('cancel')">
    <div class="row mb-3">
      <label class="col-sm-3 col-form-label">Archiv-Nr.</label>
      <div class="col-sm-9">
        <input class="form-control" disabled :value="objekt.archivId">
      </div>
    </div>

    <div class="row mb-3">
      <label class="col-sm-3 col-form-label">Objekttyp</label>
      <div class="col-sm-4">
        <select class="form-select" v-model="objekt.objekttyp_id">
          <option v-for="typ in stammdaten.objektTypen" :key="typ.id" :value="typ.id">
            {{ typ.bezeichnung }}
          </option>
        </select>
      </div>
      <label class="col-sm-2 col-form-label">Seiten</label>
      <div class="col-sm-3">
        <input class="form-control" :value="objekt.seiten">
      </div>

    </div>

    <div class="row mb-3">
      <label class="col-sm-3 col-form-label">Beschreibung</label>
      <div class="col-sm-9">
        <textarea class="form-control" rows="3" v-model="objekt.beschreibung" />
      </div>
    </div>

    <div class="row mb-3">
      <label class="col-sm-3 col-form-label">Quelle</label>
      <div class="col-sm-9">
        <select class="form-select" v-model="objekt.quellen_id">
          <option v-for="quelle in stammdaten.quellen" :key="quelle.id" :value="quelle.id">
            {{ quelle.name }}, {{ quelle.vorname }}
          </option>
        </select>
      </div>
    </div>

    <div class="row mb-3">

      <div class="col-md-3">
        <label class="form-label">Lagerort</label>
        <select class="form-select" v-model="objekt.lagerort_id">
          <option v-for="lagerort in stammdaten.lagerorte" :key="lagerort.id" :value="lagerort.id">
            {{ lagerort.bezeichnung }}
          </option>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label">Regal</label>
        <select class="form-select" v-model="objekt.regal_id">
          <option v-for="regal in stammdaten.regale" :key="regal.id" :value="regal.id">
            {{ regal.langbezeichnung }}
          </option>
        </select>
      </div>

      <div class="col-md-5">
        <label class="form-label">Fach</label>
        <select class="form-select" v-model="objekt.fach_id">
          <option v-for="fach in stammdaten.faecher" :key="fach.id" :value="fach.id">
            {{ fach.langbezeichnung }}
          </option>
        </select>
      </div>

    </div>

    <div class="row mb-4">
      <label class="col-sm-3 col-form-label">Digitalisiert</label>
      <div class="col-sm-9">
        <select class="form-select" v-model="objekt.digitalisiert">
          <option :value="0">Nein</option>
          <option :value="1">Teilweise</option>
          <option :value="2">Ja</option>
        </select>
      </div>
    </div>

  </MessageDialog>
</template>
<script>
import MessageDialog from './MessageDialog.vue';
export default {
  components: {
    MessageDialog,
  },
  props: {
    archivId: {
      type: String,
      required: true
    }
  },

  emits: ["cancel"],

  data() {
    return {
      objekt: {
        archivId: "",
        objekttyp_id: 0,
        seiten: 0,
        beschreibung: "",
        quellen_id: 0,
        lagerort_id: 0,
        regal_id: 0,
        fach_id: 0
      },
      stammdaten: {
        objektTypen: [],
        quellen: [],
        lagerorte: [],
        regale: [],
        faecher: [],
      }
    };
  },
  async created() {
    this.objekt.archivId = this.archivId
    this.stammdaten = await this.getStammdaten();
  },
  methods: {
    async createAnalogObjekt() {
      this.$emit("cancel")
    },
    async getStammdaten() {
      const objektTypenResponse = await this.$axios.get("/Objekttypen/getAll");
      this.stammdaten.objektTypen = objektTypenResponse.data.data.filter(typ => typ.analog_digital === "A");
      const quellenResponse = await this.$axios.get("/Quellen/getAll");
      this.stammdaten.quellen = quellenResponse.data.data;
      const lagerorteResponse = await this.$axios.get("/Lagerorte/getAll");
      this.stammdaten.lagerorte = lagerorteResponse.data.data;
      const regaleResponse = await this.$axios.get("/Regale/getAll");
      this.stammdaten.regale = regaleResponse.data.data;
      const faecherResponse = await this.$axios.get("/Faecher/getAll");
      this.stammdaten.faecher = faecherResponse.data.data;
      return this.stammdaten;
    },
  }

};
</script>
<style scoped>
.analog {
  background-color: #bad3eb;
}
</style>