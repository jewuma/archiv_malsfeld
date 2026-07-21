<template>
  <div class="dashboard">

    <!-- Hauptkennzahlen -->
    <div class="row g-3 mb-4">

      <div class="col-md-4 col-lg-3">
        <StatCard title="Archivobjekte" :value="stats.archivobjekte" icon="bi-archive" />
      </div>

      <div class="col-md-4 col-lg-3">
        <StatCard title="mit Beschreibung" :value="stats.mitBeschreibung" icon="bi-card-text" variant="success" />
      </div>

      <div class="col-md-4 col-lg-3">
        <StatCard title="veröffentlicht" :value="stats.veroeffentlicht" icon="bi-globe" variant="primary" />
      </div>

      <div class="col-md-4 col-lg-3">
        <StatCard title="Analoge Objekte" :value="stats.analog" icon="bi-book" />
      </div>

      <div class="col-md-4 col-lg-3">
        <StatCard title="Digitale Objekte" :value="stats.digital" icon="bi-file-earmark" variant="info" />
      </div>

    </div>


    <!-- Orte -->
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h5 class="mb-0">
          <i class="bi bi-geo-alt"></i>
          Verteilung nach Ort
        </h5>
      </div>

      <div class="card-body">

        <div class="row g-3">

          <div v-for="ort in stats.orte" :key="ort.name" class="col-md-4 col-lg-3">
            <div class="ort-card">

              <div class="ort-name">
                {{ ort.name }}
              </div>

              <div class="ort-value">
                {{ ort.anzahl }}
              </div>

              <div class="progress mt-2">
                <div class="progress-bar" :style="{ width: ort.prozent + '%' }"></div>
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
      stats: {}
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
  padding: 1rem;
}


.ort-card {
  border: 1px solid #dee2e6;
  border-radius: .75rem;
  padding: 1rem;
  background: #fff;
}


.ort-name {
  font-size: .9rem;
  color: #6c757d;
}


.ort-value {
  font-size: 2rem;
  font-weight: 600;
}
</style>