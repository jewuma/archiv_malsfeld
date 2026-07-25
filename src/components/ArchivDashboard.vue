<template>
  <div class="dashboard">

    <!-- Hauptkennzahlen -->
    <div class="row g-2 mb-3">

      <div class="col-md-4 col-lg-3">
        <StatCard title="Archivobjekte" :value="stats.archivobjekte" icon="bi-archive" variant="secondary" />
      </div>

      <div class="col-md-4 col-lg-3">
        <StatCard title="mit Beschreibung" :value="stats.mitBeschreibung" icon="bi-card-text" variant="success" />
      </div>

      <div class="col-md-4 col-lg-3">
        <StatCard title="veröffentlicht" :value="stats.veroeffentlicht" icon="bi-globe" variant="primary" />
      </div>

      <div class="col-md-4 col-lg-3">
        <StatCard title="Analoge Objekte" :value="stats.analog" icon="bi-book" variant="warning" />
      </div>

      <div class="col-md-4 col-lg-3">
        <StatCard title="Digitale Objekte" :value="stats.digital" icon="bi-file-earmark" variant="info" />
      </div>

    </div>


    <!-- Orte -->
    <div class="card shadow-sm">
      <div class="card-header bg-light border-bottom">
        <h5 class="mb-0">
          <i class="bi bi-geo-alt" style="color: #0d6efd;"></i>
          Verteilung nach Ort
        </h5>
      </div>

      <div class="card-body pb-2">

        <div class="row g-2">

          <div v-for="(ort, index) in stats.orte" :key="ort.name" class="col-md-4 col-lg-3">
            <div class="ort-card" :style="{ borderLeftColor: getColor(index) }">

              <div class="ort-name">
                {{ ort.name }}
              </div>

              <div class="ort-value" :style="{ color: getColor(index) }">
                {{ ort.anzahl.toLocaleString('de-DE') }}
              </div>

              <div class="progress mt-2" style="height: 4px;">
                <div class="progress-bar" :style="{ width: ort.prozent + '%', backgroundColor: getColor(index) }"></div>
              </div>

            </div>
          </div>

        </div>

      </div>
    </div>

  </div>
</template>


<script>
import StatCard from './StatCard.vue'

export default {

  name: 'ArchivDashboard',

  components: {
    StatCard
  },

  data() {
    return {
      stats: {},
      colors: ['#0d6efd', '#198754', '#0dcaf0', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14', '#20c997']
    }
  },

  methods: {
    getColor(index) {
      return this.colors[index % this.colors.length]
    }
  },

  async created() {
    const statResponse = await this.$axios.get("/Archiv/getStats")
    this.stats = statResponse.data.data
  }
}
</script>


<style scoped>
.dashboard {
  padding: 0.75rem;
}

.ort-card {
  border: 1px solid #e9ecef;
  border-left: 4px solid #0d6efd;
  border-radius: 0.5rem;
  padding: 0.75rem;
  background: #f8f9fa;
  transition: all 0.2s;
}

.ort-card:hover {
  background: #fff;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.ort-name {
  font-size: 0.8rem;
  color: #6c757d;
  font-weight: 500;
  margin-bottom: 0.25rem;
}

.ort-value {
  font-size: 1.4rem;
  font-weight: 700;
  color: #0d6efd;
}
</style>