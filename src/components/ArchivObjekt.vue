<template>
  <CardComponent :title="archivObjekt.id ? 'Archivobjekt bearbeiten' : 'Neues Archivobjekt'" :fields="[]"
    :table-data="[]">
    <ul class="nav nav-tabs mb-4">

      <li class="nav-item">
        <button class="nav-link allgemein" :class="{ active: aktiveRegisterkarte === 'allgemein' }"
          @click="aktiveRegisterkarte = 'allgemein'">
          Allgemeine Daten
        </button>
      </li>

      <li class="nav-item">
        <button class="nav-link analog" :class="{ active: aktiveRegisterkarte === 'analog' }"
          @click="aktiveRegisterkarte = 'analog'">
          Analogobjekte
        </button>
      </li>

      <li class="nav-item">
        <button class="nav-link digital" :class="{ active: aktiveRegisterkarte === 'digital' }"
          @click="aktiveRegisterkarte = 'digital'">
          Digitalobjekte
        </button>
      </li>

    </ul>

    <AllgemeineDaten v-show="aktiveRegisterkarte === 'allgemein'" v-model="archivObjekt.allgemein" />

    <AnalogObjekte v-show="aktiveRegisterkarte === 'analog'" v-model="archivObjekt.analogObjekte" />

    <DigitalObjekte v-show="aktiveRegisterkarte === 'digital'" v-model="archivObjekt.digitalObjekte" />

    <div class="card-footer text-end">

      <button class="btn btn-primary" @click="speichern">
        Speichern
      </button>

    </div>
  </CardComponent>
</template>
<script>
import AllgemeineDaten from "./AllgemeineDaten.vue";
import AnalogObjekte from "./AnalogObjekte.vue";
import CardComponent from "./CardComponent.vue";
import DigitalObjekte from "./DigitalObjekte.vue";
export default {
  components: {
    AllgemeineDaten,
    AnalogObjekte,
    DigitalObjekte,
    CardComponent
  },
  data() {

    return {

      aktiveRegisterkarte: "allgemein",
      archivObjekt: {
        id: null,
        allgemein: {
          titel: "",
          beschreibung: "",
          datum: "",
          startJahr: "",
          endJahr: "",
          ort: null,
          schlagworte: []
        },
        analogObjekte: [],
        digitalObjekte: [],
      },
    };

  },

  methods: {
    async save() {
      await this.$emit("/ArchivObjekt/speichern", this.archivObjekt);
      this.$sendMsg(false, "Archivobjekt erfolgreich gespeichert.");
    },
  },
}
</script>
<style scoped>
.allgemein.active {
  background-color: #dee2e6;
}

.analog.active {
  background-color: #bad3eb;
}

.digital.active {
  background-color: #baebd3;
}
</style>