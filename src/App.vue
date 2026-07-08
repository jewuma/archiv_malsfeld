<template>
  <div>
    <!-- Zeige Navbar nur bei eingeloggt -->
    <NavBar v-if="isLoggedIn && (hasAdminRights || hasClientRights)" @logout="handleLogout" />
    <MessageDiv v-if="message.text" :message="message" @ok="message.text = ''" />
    <!-- Route abhängig vom Login-Status -->
    <router-view v-if="isLoggedIn && $route.name === 'AppointmentForm'" :key="$route.fullPath + '-with-logout'"
      @logout="handleLogout" />
    <router-view v-else-if="isLoggedIn" :key="$route.fullPath + '-normal'" />
    <UserLogin v-else @login="handleLogin" />
  </div>
</template>

<script>
import NavBar from "./components/NavBar.vue";
import UserLogin from "./components/UserLogin.vue";
import MessageDiv from "./components/MessageDiv.vue";
export default {
  components: {
    MessageDiv,
    NavBar,
    UserLogin,
  },
  data() {
    return {
      isLoggedIn: !!sessionStorage.getItem("sessionId"), // Initialzustand
      hasClientRights: 0,
      hasAdminRights: 0,
      message: { isError: false, text: "" },
      logoutTimer: null,
      logoutAfter: 120 * 60 * 1000, // 120 Minuten Inaktivität
    };
  },
  mounted() {
    const rights = JSON.parse(sessionStorage.getItem("rights"));
    if (rights) {
      this.hasAdminRights = rights.admin === 1;
      this.hasClientRights = rights.client === 1;
    }

    window.addEventListener("globalMessage", (event) => {
      const { isError, text } = event.detail;
      this.message = { isError, text };
    });
    if (this.isLoggedIn) {
      this.startSessionTimeout();
    }
  },
  beforeUnmount() {
    this.stopSessionTimeout();
  },
  methods: {
    startSessionTimeout() {
      // Eventlistener für User-Aktivität
      ["click", "mousemove", "keydown"].forEach(evt =>
        window.addEventListener(evt, this.resetSessionTimeout)
      );
      this.resetSessionTimeout();
    },
    stopSessionTimeout() {
      clearTimeout(this.logoutTimer);
      ["click", "mousemove", "keydown"].forEach(evt =>
        window.removeEventListener(evt, this.resetSessionTimeout)
      );
    },
    resetSessionTimeout() {
      clearTimeout(this.logoutTimer);
      this.logoutTimer = setTimeout(() => {
        this.handleLogout();
      }, this.logoutAfter);
    },
    handleLogin(loginData) {
      // Session speichern und Zustand aktualisieren
      sessionStorage.setItem("sessionId", loginData.sessionId);
      sessionStorage.setItem("rights", JSON.stringify(loginData.rights));
      sessionStorage.setItem("staffId", loginData.staff_id);
      this.isLoggedIn = true;
      this.hasAdminRights = loginData.rights.admin === 1;
      this.hasClientRights = loginData.rights.clients === 1;
      this.startSessionTimeout();
      if (loginData.rights.clients !== 1 && loginData.rights.admin !== 1) {
        this.$router.push("/AppointmentForm");
      } else {
        this.$router.push("/");
      }
    },
    async handleLogout() {
      await this.$axios.post("/Users/logout").catch(() => {
        // Fehler ignorieren
      });
      // Session entfernen und Zustand zurücksetzen
      sessionStorage.removeItem("sessionId");
      sessionStorage.removeItem("rights");
      sessionStorage.removeItem("staffId");
      this.isLoggedIn = false;
      this.stopSessionTimeout();
      this.$router.push("/login");
    },
  },
};
</script>
<style>
.custom-container {
  margin-top: 0;
  height: 100vh;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 20px;
  box-sizing: border-box;
}

/* Karte passt sich der Höhe des Containers an */
.full-height-card {
  width: 100%;
  height: 95%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* Kartenkörper nimmt verfügbaren Platz ein */
.card-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  padding: 10px;
}

/* Tabelle passt sich an und kann scrollen */
.table-container {
  flex-grow: 1;
  overflow-y: auto;
  border: 1px solid #dee2e6;
  /* Optionaler Rahmen um die Tabelle */
  border-radius: 5px;
  padding: 5px;
  background-color: #f8f9fa;
  /* Hintergrund für Tabelle */
}
</style>
