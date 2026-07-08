<template>
  <div class="d-flex align-items-center gap-4 flex-wrap">
    <div v-for="option in options" :key="option.value" class="form-check form-switch">
      <input class="form-check-input" type="checkbox" :id="'switch-' + option.value" :value="option.filter"
        v-model="modelValueLocal" @change="emitChange" />
      <label class="form-check-label" :for="'switch-' + option.value">{{ option.label }}</label>
    </div>
  </div>
</template>

<script>
export default {
  emits: ["update:modelValue"],
  props: {
    modelValue: Array,
    options: {
      type: Array,
      default() {
        return []
      },
      required: false,
    },
  },
  data() {
    return {
      modelValueLocal: this.modelValue || [],
    };
  },
  methods: {
    emitChange() {
      this.$emit("update:modelValue", this.modelValueLocal);
    },
  },
};
</script>
