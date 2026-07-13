<template>
  <StammdatenForm title="Schlagworte verwalten" :fields="fields" :api-routes="{
    get: '/Schlagworte/getAll',
    save: '/Schlagworte/save',
    delete: '/Schlagworte/delete',
    update: '/Schlagworte/update',
  }" :pre-save-check="validateFields" />
</template>
<script>
import StammdatenForm from "./StammdatenForm.vue";
export default {
  name: "SchlagwortManager",
  components: { StammdatenForm },
  data() {
    return {
      fields: [
        { name: "id", label: "Id", type: "number", value: "", required: false, hidden: true },
        { name: "bezeichnung", label: "Bezeichnung", type: "text", value: "", required: true },
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