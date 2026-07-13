<template>
  <StammdatenForm title="Benutzer verwalten" :fields="fields" :api-routes="{
    get: '/Users/getAll',
    save: '/Users/save',
    delete: '/Users/delete',
    update: '/Users/update',
  }" :pre-save-check="validateFields" />
</template>
<script>
import StammdatenForm from "./StammdatenForm.vue";
export default {
  components: { StammdatenForm },
  data() {
    return {
      fields: [
        { name: "id", label: "Id", type: "number", value: "", required: false, hidden: true },
        { name: "username", label: "Benutzername", type: "text", value: "", required: true },
        { name: "firstname", label: "Vorname", type: "text", value: "", required: true },
        { name: "name", label: "Name", type: "text", value: "", required: true },
        { name: "password", label: "Passwort", type: "password", value: "", required: true, hidden: true },
        { name: "password_repeat", label: "Passwortwiederholung", type: "password", value: "", required: true, hidden: true },

        {
          name: "position",
          label: "Position",
          type: "select",
          options: [
            { display: "Administrator", value: 1 },
            { display: "Normaler Benutzer", value: 2 },
          ],
          required: true
        },
      ],
    };
  },
  methods: {
    validateFields(fields) {
      let isValid = true;

      // Passwort und Passwortwiederholung validieren
      const passwordField = fields.find((field) => field.name === "password");
      const passwordRepeatField = fields.find((field) => field.name === "password_repeat");

      if (passwordField.value.length < 8) {
        passwordField.error = true;
        isValid = false;
        this.$sendMsg(true, "Das Passwort muss mindestens 8 Zeichen lang sein.");
      } else if (!/[!@#$%^&*(),.?":{}|<>]/.test(passwordField.value)) {
        passwordField.error = true;
        isValid = false;
        this.$sendMsg(true, "Das Passwort muss mindestens ein Sonderzeichen enthalten.");
      } else if (passwordField.value !== passwordRepeatField.value) {
        passwordRepeatField.error = true;
        isValid = false;
        this.$sendMsg(true, "Das Passwort und die Passwortwiederholung stimmen nicht überein.");
      } else {
        passwordField.error = false;
        passwordRepeatField.error = false;
      }
      return isValid;
    },
  },
};
</script>
