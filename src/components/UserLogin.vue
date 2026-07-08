<template>
  <div class="container mt-5">
    <div class="card mx-auto bg-light shadow-lg" style="max-width: 400px">
      <div class="card-header bg-primary text-white text-center">
        <h3>Anmeldung</h3>
      </div>
      <div class="card-body">
        <form @submit.prevent="handleLogin">
          <!-- Benutzername -->
          <div class="form-group row mt-3">
            <label for="username" class="col-12 col-form-label form-control-sm">Benutzername</label>
            <div class="col-12">
              <input id="username" v-model="username" type="text" class="form-control form-control-sm"
                placeholder="Benutzername eingeben" required>
            </div>
          </div>

          <!-- Passwort -->
          <div class="form-group row mt-3">
            <label for="password" class="col-12 col-form-label form-control-sm">Passwort</label>
            <div class="col-12">
              <input id="password" v-model="password" type="password" class="form-control form-control-sm"
                placeholder="Passwort eingeben" required>
            </div>
          </div>

          <!-- Fehlermeldung -->
          <div v-if="errorMessage" class="alert alert-danger mt-3" role="alert">
            {{ errorMessage }}
          </div>

          <!-- Aktionen -->
          <div class="row mt-4">
            <div class="col-12 d-flex justify-content-center">
              <button class="btn btn-primary btn-sm px-4" type="submit">Anmelden</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  emits: ["login"],
  data() {
    return {
      username: "",
      password: "",
      errorMessage: "",
    };
  },
  methods: {
    async handleLogin() {
      try {
        const response = await this.$axios.post("/Users/login", { username: this.username, password: this.password });
        if (response.status === 200) {
          this.$emit("login", response.data.data);
        } else {
          this.$sendMsg(true, response.data.message);
        }
      } catch (error) {
        if (response === undefined) {
          this.$sendMsg(true, "Verbindungsfehler")
          return
        }
        this.$sendMsg(true, response.data.message);
      }
    },
  },
};
</script>

<style scoped>
.card {
  border-radius: 0.5rem;
}

button {
  border-radius: 0.25rem;
}

input {
  border-radius: 0.25rem;
}
</style>
