<template>
  <form @submit.prevent="changePassword">
    <div class="container mt-3">
      <div class="card mx-auto bg-light shadow-lg">
        <!-- Kopfzeile -->
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
          <h3>Passwort ändern</h3>
          <ReducedMenu />
        </div>

        <!-- Formularfelder -->
        <div class="card-body">
          <div class="form-fields">
            <div class="form-group row mt-2">
              <label for="currentPassword" class="col-4 col-form-label form-control-sm">
                Aktuelles Passwort
              </label>
              <div class="col-8">
                <input
                  id="currentPassword"
                  v-model="currentPassword"
                  type="password"
                  class="form-control form-control-sm"
                  required
                >
              </div>
            </div>

            <div class="form-group row mt-2">
              <label for="newPassword" class="col-4 col-form-label form-control-sm">
                Neues Passwort
              </label>
              <div class="col-8">
                <input
                  id="newPassword"
                  v-model="newPassword"
                  type="password"
                  class="form-control form-control-sm"
                  required
                >
              </div>
            </div>

            <div class="form-group row mt-2">
              <label for="confirmPassword" class="col-4 col-form-label form-control-sm">
                Passwort bestätigen
              </label>
              <div class="col-8">
                <input
                  id="confirmPassword"
                  v-model="confirmPassword"
                  type="password"
                  class="form-control form-control-sm"
                  required
                >
              </div>
            </div>
          </div>

          <!-- Buttons -->
          <div class="row mt-3">
            <div class="col-4" />
            <div class="col-8 btn-group">
              <button class="btn btn-primary m-1" type="submit">Speichern</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>
</template>

<script>
import ReducedMenu from "./ReducedMenu.vue"
export default {
  data() {
    return {
      currentPassword: '',
      newPassword: '',
      confirmPassword: '',
    };
  },
  components: {
    ReducedMenu,
  },
  methods: {
    async changePassword() {
      if (this.newPassword !== this.confirmPassword) {
        this.$sendMsg(true, "Die neuen Passwörter stimmen nicht überein");
        return;
      }
      if (this.newPassword.length < 8) {
        this.$sendMsg(true, "Das Passwort muss mindestens 8 Zeichen lang sein.");
        return
      } else if (!/[!@#$%^&*(),.?":{}|<>]/.test(this.newPassword)) {
        this.$sendMsg(true, "Das Passwort muss mindestens ein Sonderzeichen enthalten.");
        return
      }
      try {
        await this.$axios.post("/Users/changePassword", {
          currentPassword: this.currentPassword,
          newPassword: this.newPassword,
        });
        this.$sendMsg(false, "Passwort erfolgreich geändert");
        this.$router.push("/");
      } catch (e) {
        console.error(e);
        this.$sendMsg(true, "Fehler beim Ändern des Passworts");
      }
    },
    cancel() {
      this.$router.push("/");
    },
  },
};
</script>

<style scoped>
.container {
  max-width: 600px;
}

.card {
  margin-top: 20px;
}

.card-header {
  font-size: 1.3rem;
}

.btn-group .btn {
  min-width: 100px;
}

.form-group {
  margin-bottom: 0.5rem;
}

input {
  font-size: 0.9rem;
}
</style>
