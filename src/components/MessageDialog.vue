<template>
  <div class="modal" tabindex="-1" style="display: block; background-color: rgba(0, 0, 0, 0.5)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">
            {{ title }}
          </h5>
          <button type="button" class="btn-close" @click="onClose" />
        </div>
        <div class="modal-body">
          <slot>
            <p style="white-space: pre-line">
              {{ message }}
            </p>
          </slot>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="onCancel">
            {{ cancelText }}
          </button>
          <button type="button" class="btn btn-primary" @click="onConfirm">
            {{ confirmText }}
          </button>
          <button v-if="optionText !== ''" type="button" class="btn btn-danger" @click="onOption">
            {{ optionText }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    title: {
      type: String,
      default: "Bestätigung",
    },
    message: {
      type: String,
      required: true,
    },
    confirmText: {
      type: String,
      default: "OK",
    },
    cancelText: {
      type: String,
      default: "Abbrechen",
    },
    optionText: {
      type: String,
      default: "",
    },
  },
  emits: ["confirm", "cancel", "option"],
  methods: {
    onConfirm() {
      this.$emit("confirm");
    },
    onCancel() {
      this.$emit("cancel");
    },
    onClose() {
      this.onCancel(); // Schließen entspricht Abbrechen
    },
    onOption() {
      this.$emit("option");
    },
  },
};
</script>

<style>
.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
}
</style>
