<template>
  <div class="analog">
    <div class="d-flex justify-content-between mb-2">
      <h5 class="mb-0">Analogobjekte</h5>
      <button class="btn btn-primary btn-sm" @click="hinzufuegen">
        + Hinzufügen
      </button>
    </div>
    <div class="table-responsive">
      <table class="table table-sm table-bordered align-middle">
        <thead class="table-light">
          <tr>
            <th style="width: 100px;">Archiv-Id</th>
            <th>Objekttyp</th>
            <th>Beschreibung</th>
            <th>Quelle</th>
            <th>Lagerort</th>
            <th>Regal</th>
            <th>Fach</th>
            <th>Digitalisiert</th>
            <th style="width: 60px;"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(objekt, index) in lokal" :key="index">
            <td>
              <input class="form-control form-control-sm" v-model="objekt.archiv_id">
            </td>
            <td>
              <select class="form-select form-select-sm" v-model="objekt.objekttyp_id">
                <option v-for="typ in stammdaten.objektTypen" :key="typ.id" :value="typ.id">
                  {{ typ.bezeichnung }}
                </option>
              </select>
            </td>
            <td>
              <input class="form-control form-control-sm" v-model="objekt.beschreibung">
            </td>
            <td>
              <select class="form-select form-select-sm" v-model="objekt.quellen_id">
                <option v-for="quelle in stammdaten.quellen" :key="quelle.id" :value="quelle.id">
                  {{ quelle.bezeichnung }}
                </option>
              </select>
            </td>

            <td>
              <input class="form-control form-control-sm" v-model="objekt.lagerort">
            </td>

            <td>
              <input class="form-control form-control-sm" v-model="objekt.regal">
            </td>

            <td>
              <input class="form-control form-control-sm" v-model="objekt.fach">
            </td>
            <td class="text-center">
              <select class="form-select form-select-sm" v-model="objekt.digitalisiert">
                <option value="0" selected>Nein</option>
                <option :value="1">Teilweise</option>
                <option :value="2">Ja</option>
              </select>
            </td>
            <td class="text-center">
              <button class="btn btn-danger btn-sm" @click="entfernen(index)">
                ×
              </button>
            </td>
          </tr>
          <tr v-if="lokal.length === 0">
            <td colspan="9" class="text-center text-muted py-3">
              Keine Analogobjekte vorhanden
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
<script>

export default {

  props: {
    modelValue: {
      type: Array,
      required: true
    }
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
  data() {
    return {
      stammdaten: {
        objektTypen: [],
        quellen: [],
      }
    };
  },
  async created() {
    this.stammdaten = await this.getStammdaten();
  },
  methods: {

    hinzufuegen() {

      const neu = {

        archiv_id: null,
        beschreibung: "",
        objekttyp_id: null,
        lagerort: "",
        regal: "",
        fach: "",
        quellen_id: null,
        digitalisiert: 0
      };

      this.lokal = [...this.lokal, neu];

    },

    entfernen(index) {

      const kopie = [...this.lokal];
      kopie.splice(index, 1);
      this.lokal = kopie;

    },
    async getStammdaten() {
      const objektTypenResponse = await this.$axios.get("/Objekttypen/getAll");
      this.stammdaten.objektTypen = objektTypenResponse.data.data.filter(typ => typ.analog_digital === "A");
      const quellenResponse = await this.$axios.get("/Quellen/getAll");
      this.stammdaten.quellen = quellenResponse.data.data;
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