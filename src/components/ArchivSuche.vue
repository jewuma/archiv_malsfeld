<template>
  <CardComponent title="Archivsuche" :table-data="tableData" :fields="fields" :loading="loading"
    @toggle-expand="toggleExpand" @row-selected="rowSelected" @edit="entryEdited">
    <div class="search-tabs-wrap">
      <ul class="nav nav-tabs search-tabs mb-0">
        <li class="nav-item">
          <button class="nav-link" :class="{ active: activeTab === 'basis' }" type="button"
            @click="activeTab = 'basis'">
            Hauptsuche
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link" :class="{ active: activeTab === 'details' }" type="button"
            @click="activeTab = 'details'">
            Weitere Filter
          </button>
        </li>
      </ul>
    </div>

    <div class="search-panel">
      <div v-if="activeTab === 'basis'" class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Suchbegriff</label>
          <input class="form-control form-control-sm" v-model="suche.suchbegriff">
        </div>

        <div class="col-md-4">
          <label class="form-label">Ort</label>
          <select class="form-select form-select-sm" v-model="ort">
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
          <input class="form-control form-control-sm" type="number" v-model="suche.startJahr">
        </div>

        <div class="col-md-2">
          <label class="form-label">Bis Jahr</label>
          <input class="form-control form-control-sm" type="number" v-model="suche.endJahr">
        </div>

        <SchlagwortSelektor v-model="schlagworte"></SchlagwortSelektor>
      </div>

      <div v-else class="row g-2 align-items-end">
        <div class="col-md-2">
          <label class="form-label">Beschreibung</label>
          <select class="form-select form-select-sm" v-model="suche.beschreibung">
            <option value="">Alle</option>
            <option value="ja">Ja</option>
            <option value="nein">Nein</option>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label">Archivstatus</label>
          <select class="form-select form-select-sm" v-model="suche.archivstatus">
            <option value="">Alle</option>
            <option :value="1">Nur erfasst</option>
            <option :value="2">Vollständig</option>
            <option :value="3">Veröffentlicht</option>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label">Dokumententyp</label>
          <select class="form-select form-select-sm" v-model="suche.objekttyp_id">
            <option value="">Alle</option>
            <option v-for="objekttyp in objekttypen" :key="objekttyp.id" :value="objekttyp.id">
              {{ objekttyp.bezeichnung }}
            </option>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label">Analogobjekt-ID</label>
          <input class="form-control form-control-sm" type="number" v-model="suche.analog_archiv_id">
        </div>

        <div class="col-md-4">
          <div class="digitalisiert-box">
            <div class="form-check compact-check mb-0">
              <input id="digitalisiert-ohne-datei" class="form-check-input" type="checkbox"
                v-model="suche.digitalisiert_ohne_datei">
              <label class="form-check-label" for="digitalisiert-ohne-datei">
                Digitalisiert ohne Datei
              </label>
            </div>
          </div>
        </div>
      </div>
      <div class="search-actions mt-3">
        <button class="btn btn-primary" @click="sucheStarten">Suchen</button>
      </div>
    </div>
    <template #expanded="{ item }">
      <AnalogTable v-if="item.analogobjekt_anzahl" :key="`analog-${item.id}-${item.analogReloadKey || 0}`" :item="item"
        :fixedData="analogFixedData" @add-analogobjekt="(row) => addAnalogobjekt(item, row)"
        @delete-analogobjekt="(row) => deleteAnalogobjekt(item, row)" />
      <FilesTable v-if="item.datei_anzahl" :item="item" />
    </template>
  </CardComponent>
  <MessageDialog v-if="showAddAnalogDialog" title="Analogobjekt anlegen" confirm-text="Speichern"
    cancel-text="Abbrechen" message="" full-width @confirm="saveAddAnalogobjekt" @cancel="closeAddAnalogDialog">
    <AnalogTable v-if="addAnalogDialogItem" :item="addAnalogDialogItem" :fixedData="analogFixedData"
      :persist-edits="false" :show-action-buttons="false" @draft-updated="updateAddAnalogDraft" />
  </MessageDialog>
  <MessageDialog v-if="showDeleteAnalogDialog" title="Analogobjekt löschen" confirm-text="Löschen"
    cancel-text="Abbrechen" :message="deleteAnalogDialogMessage" @confirm="confirmDeleteAnalogobjekt"
    @cancel="closeDeleteAnalogDialog" />
</template>
<script>
import CardComponent from './CardComponent.vue';
import SchlagwortSelektor from './SchlagwortSelektor.vue';
import FilesTable from './FilesTable.vue';
import AnalogTable from './AnalogTable.vue';
import MessageDialog from './MessageDialog.vue';

export default {
  components: {
    AnalogTable,
    CardComponent,
    FilesTable,
    MessageDialog,
    SchlagwortSelektor
  },
  data() {
    return {
      fields:
        [
          { name: 'id', label: 'ID', type: 'text', readonly: true, width: '80px' },
          { name: 'ort_id', label: 'Ort', type: 'select', 'width': '140px', inlineEdit: true, options: [] },
          { name: 'themen_id', label: 'Thema', type: 'select', width: '150px', inlineEdit: true, options: [] },
          { name: 'titel', label: 'Titel', type: 'text', 'width': '350px', inlineEdit: true },
          { name: 'beschreibung', label: 'Beschreibung', type: 'text', inlineEdit: true },
          { name: 'zeitraum_start', label: 'Zeitraum Start', type: 'text', 'width': '120px', inlineEdit: true },
          { name: 'zeitraum_ende', label: 'Zeitraum Ende', type: 'text', 'width': '120px', inlineEdit: true },
          { name: 'analogobjekt', label: 'Analog', type: 'analogobjekt', 'width': '70px' },
          { name: 'archivdatei', label: 'Digital', type: 'files', 'width': '70px' },
          { name: 'expander', label: '', type: "expander", width: "50px" },
        ],
      analogFixedData: {
        objekttypen: [],
        quellen: [],
        lagerorte: [],
        regale: [],
        faecher: [],
        plaetze: []
      },
      loading: "",
      suche: {
        ort_id: null,
        schlagworte_ids: [],
        startJahr: 0,
        endJahr: 0,
        objektart: "",
        beschreibung: "",
        archivstatus: "",
        objekttyp_id: "",
        analog_archiv_id: "",
        digitalisiert_ohne_datei: false
      },

      orte: [],
      objekttypen: [],
      schlagworte: [],
      quellen: [],
      lagerorte: [],
      regale: [],
      faecher: [],
      plaetze: [],

      objektarten: [
        { value: "", text: "Alle" },
        { value: "analog", text: "Analoge Objekte" },
        { value: "digital", text: "Digitale Objekte" }
      ],
      activeTab: 'basis',
      ort: null,
      tableData: [],
      themen: [],
      treffer: [],
      showAddAnalogDialog: false,
      addAnalogDialogItem: null,
      addAnalogDialogParent: null,
      addAnalogDialogDraft: null,
      showDeleteAnalogDialog: false,
      deleteAnalogDialogParent: null,
      deleteAnalogDialogRow: null,
    };
  },
  computed: {
    deleteAnalogDialogMessage() {
      const archivId = this.deleteAnalogDialogRow?.archiv_id ?? '';
      if (!archivId) {
        return 'Dieses Analogobjekt wirklich löschen?';
      }
      return `Analogobjekt ${archivId} wirklich löschen?`;
    },
  },
  async mounted() {
    const [orteResponse, objekttypenResponse, themenResponse,
      quellenResponse, lagerorteResponse, regaleResponse, faecherResponse, plaetzeResponse
    ] = await Promise.all([
      this.$axios.get("/Orte/getAll"),
      this.$axios.get("/Objekttypen/getAll"),
      this.$axios.get("/Themen/getAll"),
      this.$axios.get("/Quellen/getAll"),
      this.$axios.get("/Lagerorte/getAll"),
      this.$axios.get("/Regale/getAll"),
      this.$axios.get("/Faecher/getAll"),
      this.$axios.get("/Plaetze/getAll")
    ]);
    this.themen = themenResponse.data.data;
    this.orte = orteResponse.data.data;
    this.objekttypen = objekttypenResponse.data.data;
    this.analogFixedData.objekttypen = this.objekttypen.map(objekttyp => ({ value: objekttyp.id, display: objekttyp.bezeichnung }));
    this.quellen = quellenResponse.data.data;
    this.analogFixedData.quellen = this.quellen.map(quelle => ({ value: quelle.id, display: quelle.name + ', ' + quelle.vorname }));
    this.lagerorte = lagerorteResponse.data.data;
    this.analogFixedData.lagerorte = this.lagerorte.map(lagerort => ({ value: lagerort.id, display: lagerort.bezeichnung }));
    this.regale = regaleResponse.data.data;
    this.analogFixedData.regale = this.regale.map(regal => ({ value: regal.id, display: regal.kurzbezeichnung }));
    this.faecher = faecherResponse.data.data;
    this.analogFixedData.faecher = this.faecher.map(fach => ({ value: fach.id, display: fach.kurzbezeichnung }));
    this.plaetze = plaetzeResponse.data.data;
    this.analogFixedData.plaetze = this.plaetze.map(platz => ({ value: platz.id, display: platz.kurzbezeichnung }));
    this.fields.find(f => f.name === 'ort_id').options = this.orte.map(ort => ({ value: ort.id, display: ort.name }));
    this.fields.find(f => f.name === 'themen_id').options = this.themen.map(thema => ({ value: thema.id, display: thema.name }));
  },
  methods: {
    addAnalogobjekt(parentItem, sourceRow) {
      const tempId = -Date.now();
      const draft = {
        id: tempId,
        archiv_id: '',
        archivobjekt_id: sourceRow.archivobjekt_id ?? parentItem.id,
        objekttyp_id: 1,
        seiten: 1,
        lagerort_id: 1,
        quellen_id: 110,
        regal_id: 21,
        fach_id: 21,
        platz_id: 71,
        gesperrt: false,
        gesperrt_bis: null,
        dokumentendatum: null,
        archivdatum: new Date().toISOString().split('T')[0],
      };
      this.addAnalogDialogParent = parentItem;
      this.addAnalogDialogDraft = draft;
      this.addAnalogDialogItem = {
        id: parentItem.id,
        analogobjekte: [draft],
      };
      this.showAddAnalogDialog = true;
    },
    closeAddAnalogDialog() {
      this.showAddAnalogDialog = false;
      this.addAnalogDialogItem = null;
      this.addAnalogDialogParent = null;
      this.addAnalogDialogDraft = null;
    },
    updateAddAnalogDraft(row) {
      this.addAnalogDialogDraft = { ...row };
    },
    async saveAddAnalogobjekt() {
      if (!this.addAnalogDialogDraft || !this.addAnalogDialogParent) {
        return;
      }
      const archivId = (this.addAnalogDialogDraft.archiv_id || '').toString().trim();
      if (archivId.length < 5) {
        this.$sendMsg(true, 'ArchivID muss mindestens 5 Zeichen lang sein.');
        return;
      }
      const existsResponse = await this.$axios.get('/Analogobjekte/exists/' + archivId);
      if (existsResponse.data?.data?.exists) {
        this.$sendMsg(true, 'ArchivID existiert bereits. Bitte eine andere ID wählen.');
        return;
      }

      const rest = { ...this.addAnalogDialogDraft };
      delete rest.id;
      delete rest.delete;
      delete rest.add;

      await this.$axios.post('/Analogobjekte/save', {
        ...rest,
        archiv_id: archivId,
        archivobjekt_id: this.addAnalogDialogParent.id,
      });

      const parentIndex = this.tableData.findIndex(row => row.id === this.addAnalogDialogParent.id);
      if (parentIndex >= 0) {
        const parentRow = { ...this.tableData[parentIndex] };
        const nextCount = Number(parentRow.analogobjekt_anzahl || 0) + 1;
        parentRow.analogobjekt_anzahl = nextCount;
        parentRow.analogobjekt = {
          ...(parentRow.analogobjekt || {}),
          id: parentRow.id,
          count: nextCount,
        };
        parentRow.hasDetails = true;
        parentRow.analogReloadKey = (parentRow.analogReloadKey || 0) + 1;
        this.tableData.splice(parentIndex, 1, parentRow);
        this.tableData = [...this.tableData];
      }

      this.$sendMsg(false, 'Analogobjekt wurde angelegt.');
      this.closeAddAnalogDialog();
    },
    deleteAnalogobjekt(parentItem, row) {
      this.deleteAnalogDialogParent = parentItem;
      this.deleteAnalogDialogRow = row;
      this.showDeleteAnalogDialog = true;
    },
    closeDeleteAnalogDialog() {
      this.showDeleteAnalogDialog = false;
      this.deleteAnalogDialogParent = null;
      this.deleteAnalogDialogRow = null;
    },
    async confirmDeleteAnalogobjekt() {
      if (!this.deleteAnalogDialogRow?.id || !this.deleteAnalogDialogParent?.id) {
        this.closeDeleteAnalogDialog();
        return;
      }

      await this.$axios.get(`/Analogobjekte/delete/${this.deleteAnalogDialogRow.id}`);

      const parentIndex = this.tableData.findIndex(row => row.id === this.deleteAnalogDialogParent.id);
      if (parentIndex >= 0) {
        const parentRow = { ...this.tableData[parentIndex] };
        const nextCount = Math.max(0, Number(parentRow.analogobjekt_anzahl || 0) - 1);
        parentRow.analogobjekt_anzahl = nextCount;
        parentRow.analogobjekt = {
          ...(parentRow.analogobjekt || {}),
          id: parentRow.id,
          count: nextCount,
        };
        parentRow.hasDetails = nextCount > 0 || Number(parentRow.datei_anzahl || 0) > 0;
        parentRow.analogReloadKey = (parentRow.analogReloadKey || 0) + 1;
        if (nextCount === 0) {
          parentRow.expanded = false;
        }
        this.tableData.splice(parentIndex, 1, parentRow);
        this.tableData = [...this.tableData];
      }

      this.$sendMsg(false, 'Analogobjekt wurde gelöscht.');
      this.closeDeleteAnalogDialog();
    },
    async entryEdited(editedRow) {
      const payload = {
        id: editedRow.id,
        [editedRow.fieldName]: editedRow.value
      };
      await this.$axios.post("/Archivobjekte/update", payload);
      this.tableData = this.tableData.map(row => row.id === editedRow.id
        ? { ...row, [editedRow.fieldName]: editedRow.value }
        : row);
    },
    async sucheStarten() {
      const payload = {
        schlagworte_ids: this.schlagworte.map(s => s.id)
      };

      if (this.ort) {
        payload.ort_id = this.ort;
      }
      if (Number(this.suche.startJahr) > 0) {
        payload.startJahr = Number(this.suche.startJahr);
      }
      if (Number(this.suche.endJahr) > 0) {
        payload.endJahr = Number(this.suche.endJahr);
      }
      if (this.suche.suchbegriff) {
        payload.suchbegriff = this.suche.suchbegriff;
      }
      if (this.suche.beschreibung) {
        payload.beschreibung = this.suche.beschreibung;
      }
      if (this.suche.archivstatus !== "") {
        payload.archivstatus = Number(this.suche.archivstatus);
      }
      if (this.suche.objekttyp_id !== "") {
        payload.objekttyp_id = Number(this.suche.objekttyp_id);
      }
      if (this.suche.analog_archiv_id !== "") {
        payload.analog_archiv_id = Number(this.suche.analog_archiv_id);
      }
      if (this.suche.digitalisiert_ohne_datei) {
        payload.digitalisiert_ohne_datei = true;
      }

      this.loading = "Suche läuft..."
      const response = await this.$axios.post("/Archiv/search", payload);
      this.loading = "";
      this.tableData = response.data.data.map(item => {
        item.expanded = false
        if (item.datei_anzahl === 1) {
          item.archivdatei = { id: item.id, count: 1, firstId: item.erste_datei_id, type: "pdf" }
        } else {
          item.archivdatei = { id: item.id, count: item.datei_anzahl }
        }
        if (item.analogobjekt_anzahl === 1) {
          item.analogobjekt = { id: item.id, count: 1, firstId: item.erste_analogbojekt_id, type: item.erste_analogobjekt_typ_id }
        } else {
          item.analogobjekt = { id: item.id, count: item.analogobjekt_anzahl }
        }
        if (item.datei_anzahl > 0 || item.analogobjekt_anzahl > 0) {
          item.hasDetails = true
        }
        return item;
      });
    },
    rowSelected(selectedRow) {
      console.log('Row selected:', selectedRow);
    },
    toggleExpand(toggleRow) {
      const index = this.tableData.findIndex(row => row.id === toggleRow.id)
      this.tableData[index].expanded = !this.tableData[index].expanded
      this.tableData = [...this.tableData]
    }
  }
}
</script>
<style scoped>
.search-tabs-wrap {
  border-bottom: 1px solid #d9dee3;
  margin-bottom: 0.75rem;
}

.search-tabs {
  gap: 0.35rem;
  border-bottom: 0;
}

.search-tabs .nav-link {
  border: 0;
  border-radius: 0.5rem 0.5rem 0 0;
  color: #495057;
  font-size: 0.9rem;
  font-weight: 600;
  padding: 0.45rem 0.8rem;
}

.search-tabs .nav-link.active {
  background: #eef3f7;
  color: #0d6efd;
}

.search-panel {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  padding: 0.9rem;
}

.search-panel :deep(.form-label) {
  font-size: 0.82rem;
  font-weight: 600;
  margin-bottom: 0.25rem;
}

.search-panel :deep(.form-control),
.search-panel :deep(.form-select) {
  min-height: 2rem;
}

.compact-check {
  min-height: 2rem;
  display: flex;
  align-items: center;
}

.digitalisiert-box {
  display: inline-flex;
  flex-direction: column;
  gap: 0.2rem;
  padding: 0.55rem 0.75rem;
  background: #fff8df;
  border: 1px solid #f2d98a;
  border-radius: 0.65rem;
}

.digitalisiert-box :deep(.form-check-input) {
  margin-top: 0;
}

.digitalisiert-box :deep(.form-check-label) {
  font-weight: 700;
  color: #7a5a00;
}

.digitalisiert-hint {
  color: #7a5a00;
  font-size: 0.78rem;
  line-height: 1.2;
}

.search-actions {
  display: flex;
  justify-content: flex-end;
}

@media (max-width: 767.98px) {
  .search-panel {
    padding: 0.75rem;
  }

  .search-actions {
    justify-content: stretch;
  }

  .search-actions .btn {
    width: 100%;
  }
}
</style>
