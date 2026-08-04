<template>
  <ArchivDashboard />
  <!-- <div class="d-flex justify-content-center align-items-center" style="height: calc(100vh - 156px)">
    <img src="/logo.png" alt="Logo" class="img-fluid">
  </div> -->
</template>
<script>
import ArchivDashboard from './ArchivDashboard.vue';
export default {
  components: { ArchivDashboard }
}
</script>
<style scoped>
/* Optional: Begrenzung der Logo-Größe */
img {
  max-width: 600px;
}
</style>
