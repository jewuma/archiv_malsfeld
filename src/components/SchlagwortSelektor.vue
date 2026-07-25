<template>
  <div class="col-md-3 position-relative">
    <MessageDialog v-if="showModal" :message="dialogMessage" @confirm="neuesSchlagwortAnlegen" :title="dialogTitle"
      @cancel="showModal = false" /> <label class="form-label">Schlagworte</label>
    <input class="form-control" v-model="schlagwortSuche" @input="filterSchlagworte" @keydown.down.prevent="naechster"
      @keydown.up.prevent="vorheriger" @keydown.enter.prevent="enterSchlagwort" @keydown.esc.prevent="escGedrueckt">
    <div v-if="vorschlaege.length" class="list-group position-absolute w-100 shadow" style="z-index:1000">
      <div v-for="(wort, index) in vorschlaege" :key="wort.id" class="list-group-item list-group-item-action"
        :class="{ active: index === ausgewaehlt }" @click="addSchlagwort(wort)" style="cursor:pointer">
        {{ wort.bezeichnung }}
      </div>
    </div>
  </div>
  <div class="col-md-3 d-flex align-items-end">
    <div>
      <span v-for="wort in lokal" :key="wort.id" class="badge bg-primary me-2 mb-2">
        {{ wort.bezeichnung }}
        <span class="ms-1" style="cursor:pointer" @click="removeSchlagwort(wort.id)">×</span>
      </span>
    </div>
  </div>
</template>
<script>
import MessageDialog from "./MessageDialog.vue";

export default {
  components: {
    MessageDialog
  },
  props: {
    modelValue: {
      type: Object,
      required: true
    },
  },
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
      ausgewaehlt: 0,
      dialogMessage: "",
      dialogTitle: "Schlagwort anlegen?",
      itemRefs: [],
      schlagwortSuche: "",
      showModal: false,
      vorschlaege: [],
      schlagworte: [],
    };
  },
  async created() {
    const response = await this.$axios.get("/Schlagworte/getAll");
    this.schlagworte = response.data.data;
  },
  methods: {
    addSchlagwort(wort) {
      if (this.lokal.some(s => s.id === wort.id))
        return;
      this.lokal.push(wort);
      this.schlagwortSuche = "";
      this.vorschlaege = [];
      this.ausgewaehlt = 0;
    },
    enterSchlagwort() {

      if (this.vorschlaege.length) {
        this.addSchlagwort(this.vorschlaege[this.ausgewaehlt]);
        return;
      }

      if (this.schlagwortSuche.trim() !== "") {
        this.neuesSchlagwort();
      }

    },
    escGedrueckt() {
      this.vorschlaege = [];
      this.ausgewaehlt = 0;
    },
    filterSchlagworte() {
      if (this.schlagwortSuche.length < 2) {
        this.vorschlaege = [];
        this.ausgewaehlt = 0;
        return;
      }
      this.vorschlaege =
        this.schlagworte.filter(s => s.bezeichnung.toLowerCase().includes(this.schlagwortSuche.toLowerCase())
          &&
          !this.lokal.some(w => w.id === s.id)
        );
      this.ausgewaehlt = 0;
    },
    naechster() {
      if (this.ausgewaehlt < this.vorschlaege.length - 1)
        this.ausgewaehlt++;
      this.$nextTick(() => {
        this.itemRefs[this.ausgewaehlt]?.scrollIntoView({
          block: "nearest"
        });
      });
    },
    neuesSchlagwort() {
      this.dialogMessage = "Möchten Sie das Schlagwort '" + this.schlagwortSuche + "' anlegen?";
      this.showModal = true;
    },
    neuesSchlagwortAnlegen() {
      this.showModal = false;
      this.$axios.post("/Schlagworte/save", { bezeichnung: this.schlagwortSuche })
        .then((response) => {
          this.schlagworte.push(response.data.data);
          this.addSchlagwort(response.data.data);
          this.$sendMsg(false, "Schlagwort erfolgreich angelegt.");
        })
        .catch(error => {
          console.error("Fehler beim Anlegen des Schlagworts:", error);
        });
    },
    removeSchlagwort(id) {
      this.lokal =
        this.lokal.filter(s => s.id !== id);
    },

    uebernehmenErsten() {
      if (this.vorschlaege.length) {
        this.addSchlagwort(this.vorschlaege[0]);
      }
    },
    vorheriger() {
      if (this.ausgewaehlt > 0)
        this.ausgewaehlt--;
      this.$nextTick(() => {
        this.itemRefs[this.ausgewaehlt]?.scrollIntoView({
          block: "nearest"
        });
      });
    },
  }
};
</script>