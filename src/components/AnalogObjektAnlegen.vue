<template>
  <MessageDialog title="Analogobjekt anlegen" confirm-text="Objekt anlegen" cancel-text="Abbruch" message="" xl
    @confirm="createAnalogObjekt" @cancel="$emit('cancel')">
    <div class="formbackground">

      <div class="row mb-3">
        <label class="col-sm-3 col-form-label">Analogobjekt-Nr.</label>
        <div class="col-sm-9">
          <input class="form-control" disabled :value="objekt.archiv_id">
        </div>
      </div>

      <div class="row mb-3">
        <label class="col-sm-3 col-form-label">Objekttyp</label>
        <div class="col-sm-5">
          <select class="form-select" v-model="objekt.objekttyp_id">
            <option v-for="typ in stammdaten.objektTypen" :key="typ.id" :value="typ.id">
              {{ typ.bezeichnung }}
            </option>
          </select>
        </div>
        <label class="col-sm-2 col-form-label">Seiten</label>
        <div class="col-sm-2">
          <input class="form-control" v-model="objekt.seiten">
        </div>
      </div>
      <div class="row mb-3 align-items-center">
        <label class="col-sm-3 col-form-label">Titel</label>

        <div class="col-sm-9">
          <input class="form-control" v-model="objekt.titel">
        </div>
      </div>

      <div class="row mb-3 align-items-center">
        <label class="col-sm-3 col-form-label">Quelle</label>

        <div class="col-sm-9">
          <select class="form-select" v-model="objekt.quellen_id">
            <option v-for="quelle in stammdaten.quellen" :key="quelle.id" :value="quelle.id">
              {{ quelle.display }}
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

        <div class="col-md-3">
          <label class="form-label">Regal</label>
          <select class="form-select" v-model="objekt.regal_id">
            <option :value="null">Ohne</option>
            <option v-for="regal in stammdaten.regale" :key="regal.id" :value="regal.id">
              {{ regal.langbezeichnung }}
            </option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Fach</label>
          <select class="form-select" v-model="objekt.fach_id">
            <option :value="null">Ohne</option>
            <option v-for="fach in stammdaten.faecher" :key="fach.id" :value="fach.id">
              {{ fach.langbezeichnung }}
            </option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Platz</label>
          <select class="form-select" v-model="objekt.platz_id">
            <option :value="null">Ohne</option>
            <option v-for="platz in stammdaten.plaetze" :key="platz.id" :value="platz.id">
              {{ platz.langbezeichnung }}
            </option>
          </select>
        </div>

      </div>
      <div class="row mb-3">

        <div class="col-md-3">
          <label class="form-label">Digitalisiert</label>
          <select class="form-select" v-model="objekt.digitalisiert">
            <option :value="0">Nein</option>
            <option :value="1">Teilweise</option>
            <option :value="2">Ja</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Gesperrt</label>
          <div class="form-check form-switch m-0">
            <input class="form-check-input large-switch" type="checkbox" id="gesperrt" v-model="objekt.gesperrt">
          </div>
        </div>
        <div v-if="objekt.gesperrt" class="col-md-3">
          <label class="form-label">Gesperrt bis</label>
          <input class="form-control" type="text" v-model="objekt.gesperrt_bis">
        </div>
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
    analogObjekt: {
      type: Object,
      required: true
    }
  },

  emits: ["cancel", "createAnalog"],

  data() {
    return {
      objekt: {
        id: null,
        archiv_id: "",
        objekttyp_id: 1,
        seiten: null,
        quellen_id: 0,
        gesperrt: false,
        gesperrt_bis: null,
        lagerort_id: 1,
        regal_id: null,
        fach_id: null,
        platz_id: null,
        digitalisiert: 2
      },
      stammdaten: {
        objektTypen: [],
        quellen: [],
        lagerorte: [],
        regale: [],
        faecher: [],
        plaetze: [],
      }
    };
  },
  async created() {
    this.objekt = { ...this.analogObjekt }
    this.stammdaten = await this.getStammdaten();
  },
  methods: {
    async createAnalogObjekt() {
      this.$emit("createAnalog", this.objekt)
    },
    async getStammdaten() {
      const objektTypenResponse = await this.$axios.get("/Objekttypen/getAll");
      this.stammdaten.objektTypen = objektTypenResponse.data.data.filter(typ => typ.analog_digital === "A");
      const quellenResponse = await this.$axios.get("/Quellen/getSelector");
      this.stammdaten.quellen = quellenResponse.data.data;
      const lagerorteResponse = await this.$axios.get("/Lagerorte/getAll");
      this.stammdaten.lagerorte = lagerorteResponse.data.data;
      const regaleResponse = await this.$axios.get("/Regale/getAll");
      this.stammdaten.regale = regaleResponse.data.data;
      const faecherResponse = await this.$axios.get("/Faecher/getAll");
      this.stammdaten.faecher = faecherResponse.data.data;
      const plaetzeResponse = await this.$axios.get("/Plaetze/getAll");
      this.stammdaten.plaetze = plaetzeResponse.data.data;
      return this.stammdaten;
    },
  }

};
</script>
<style scoped>
.formbackground {
  background-color: #e9ffea;
  padding: 20px;
  border-radius: 5px;
}

.large-switch {
  transform: scale(1.4);
  transform-origin: left center;
}
</style>