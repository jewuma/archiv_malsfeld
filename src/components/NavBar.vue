<template>
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container-fluid">
      <!-- Markenname -->
      <a class="navbar-brand fw-bold text-white" href="#">
        <i class="bi bi-house-door-fill me-2"></i>
        Archiv-Verwaltung
      </a>
      <!-- Mobile Toggle Button -->
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <!-- Navigationslinks -->
      <div id="navbarNav" class="collapse navbar-collapse">
        <ul class="navbar-nav ms-auto">
          <!-- Home -->
          <li class="nav-item">
            <router-link class="nav-link text-white fw-semibold" to="/">
              <i class="bi bi-house-fill me-2"></i>
              Home
            </router-link>
          </li>
          <li class="nav-item">
            <router-link class="nav-link text-white fw-semibold" to="/archivEingang">
              <i class="bi bi-receipt me-2"></i>
              Archiveingang
            </router-link>
          </li>
          <li class="nav-item">
            <router-link class="nav-link text-white fw-semibold" to="/archivObjekt">
              <i class="bi bi-receipt me-2"></i>
              Archivbearbeitung
            </router-link>
          </li>
          <li class="nav-item">
            <router-link class="nav-link text-white fw-semibold" to="/archivSuche">
              <i class="bi bi-search me-2"></i>
              Archivsuche
            </router-link>
          </li>
          <!-- Stammdaten Dropdown -->
          <li class="nav-item dropdown">
            <a id="kundenDropdown" class="nav-item nav-link dropdown-toggle text-white fw-semibold" href="#"
              role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-person-fill me-2"></i>
              Stammdaten
            </a>
            <ul class="dropdown-menu" aria-labelledby="kundenDropdown">
              <li v-if="hasAdminRigths">
                <router-link to="/UserManager" class="dropdown-item">
                  <i class="bi bi-person-fill me-2"></i>
                  Benutzer verwalten
                </router-link>
              </li>
              <li>
                <router-link to="/schlagwortManager" class="dropdown-item">
                  <i class="bi bi-person-fill me-2"></i>
                  Schlagworte verwalten
                </router-link>
              </li>
              <li>
                <router-link to="/objekttypenManager" class="dropdown-item">
                  <i class="bi bi-person-fill me-2"></i>
                  Objekttypen verwalten
                </router-link>
              </li>
              <li>
                <router-link to="/quellenManager" class="dropdown-item">
                  <i class="bi bi-person-fill me-2"></i>
                  Quellen verwalten
                </router-link>
              </li>
            </ul>
          </li>
          <!-- Einstellungen -->
          <li class="nav-item dropdown">
            <a id="settingsDropdown" class="nav-item nav-link dropdown-toggle text-white fw-semibold" href="#"
              role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-gear-fill settings-icon me-2"></i>
              Einstellungen
            </a>
            <ul class="dropdown-menu" aria-labelledby="settingsDropdown">
              <!-- <li v-if="hasAdminRigths">
                <router-link to="/SettingsDialog" class="dropdown-item">
                  <i class="bi bi-gear-fill settings-icon me-2"></i>
                  Allgemeine Einstellungen
                </router-link>
              </li> -->
              <li class="nav-item">
                <router-link to="/changePassword" class="dropdown-item">
                  <i class="bi-key-fill me-2"></i>
                  Eigenes Passwort ändern
                </router-link>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <button class="nav-link text-white fw-semibold" @click="$emit('logout')">
              <i class="bi bi-box-arrow-right me-2"></i>
              Logout
            </button>
          </li>
        </ul>
      </div>
    </div>
  </nav>
</template>
<script>
export default {
  emits: ["logout"],
  data() {
    return {
      hasAdminRigths: false,
      staffId: 0,
    };
  },
  created() {
    const rights = JSON.parse(sessionStorage.getItem("rights"));
    if (rights) {
      this.hasAdminRigths = rights.admin;
    }
    const staffId = sessionStorage.getItem("staffId");
    if (staffId) {
      this.staffId = staffId;
    }
  },
};
</script>
<style scoped>
.navbar {
  font-family: "Roboto", sans-serif;
}

.navbar-nav .nav-link {
  transition:
    color 0.3s,
    background-color 0.3s;
}

.navbar-nav .nav-link:hover {
  color: #ffd700;
  background-color: rgba(255, 255, 255, 0.1);
  border-radius: 5px;
}

.dropdown-menu {
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.dropdown-item {
  transition:
    color 0.3s,
    background-color 0.3s;
}

.dropdown-item:hover {
  color: #fff;
  background-color: #777b77;
}
</style>
