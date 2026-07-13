<template>
  <StammdatenForm title="Quellen verwalten" :fields="fields" :api-routes="{
    get: '/Objekttypen/getAll',
    save: '/Objekttypen/save',
    delete: '/Objekttypen/delete',
    update: '/Objekttypen/update',
  }" :pre-save-check="validateFields" />
</template>
<script>
import StammdatenForm from "./StammdatenForm.vue";
export default {
  name: "ObjekttypenManager",
  components: { StammdatenForm },
  data() {
    return {
      fields: [
        { name: "id", label: "Id", type: "number", value: "", required: false, hidden: true },
        {
          name: "analog_digital", label: "Analog/Digital", type: "select", options: [
            { display: "Analog", value: "A" },
            { display: "Digital", value: "D" },
          ], value: "", required: true
        },
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