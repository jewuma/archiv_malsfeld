<template>
  <StammdatenForm title="Quellen verwalten" :fields="fields" :api-routes="{
    get: '/Quellen/getAll',
    save: '/Quellen/save',
    delete: '/Quellen/delete',
    update: '/Quellen/update',
  }" :pre-save-check="validateFields" />
</template>
<script>
import StammdatenForm from "./StammdatenForm.vue";
export default {
  name: "QuellenManager",
  components: { StammdatenForm },
  data() {
    return {
      fields: [
        { name: "id", label: "Id", type: "number", value: "", required: false, hidden: true },
        { name: "vorname", label: "Vorname", type: "text", value: "", required: true },
        { name: "name", label: "Name", type: "text", value: "", required: true },
        { name: "strasse", label: "Straße", type: "text", value: "", required: true },
        { name: "plz", label: "PLZ", type: "text", value: "", required: true },
        { name: "ort", label: "Ort", type: "text", value: "", required: true },
        { name: "uebergabe_datum", label: "Übergabedatum", type: "date", value: "", required: false },
        { name: "anmerkung", label: "Anmerkung", type: "text", value: "", required: false },
      ],
    };
  },
  methods: {
    validateFields(fields) {
      let isValid = true;
      console.log(fields)
      return isValid;
    },
  },
};
</script>