<template>
  <div class="modal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,.5)">
    <div ref="dialog" :class="['modal-dialog', { 'modal-xl': xl }]" :style="dialogStyle">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white" @mousedown="startDrag">
          <h5 class="modal-title">
            {{ title }}
          </h5>

          <button type="button" class="btn-close" @mousedown.stop @click="onClose" />
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
  name: "MessageDialog",

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
    xl: {
      type: Boolean,
      default: false,
    },
  },

  emits: ["confirm", "cancel", "option"],

  data() {
    return {
      posX: 0,
      posY: 0,
      dragging: false,
      offsetX: 0,
      offsetY: 0,
    };
  },

  computed: {
    dialogStyle() {
      return {
        left: this.posX + "px",
        top: this.posY + "px",
      };
    },
  },

  mounted() {
    this.$nextTick(() => {
      const dialog = this.$refs.dialog;
      const rect = dialog.getBoundingClientRect();

      this.posX = (window.innerWidth - rect.width) / 2;
      this.posY = 80;
    });
  },

  beforeUnmount() {
    document.removeEventListener("mousemove", this.onDrag);
    document.removeEventListener("mouseup", this.stopDrag);
  },

  methods: {
    startDrag(event) {
      // Linke Maustaste
      if (event.button !== 0) return;

      this.dragging = true;

      this.offsetX = event.clientX - this.posX;
      this.offsetY = event.clientY - this.posY;

      document.addEventListener("mousemove", this.onDrag);
      document.addEventListener("mouseup", this.stopDrag);
    },

    onDrag(event) {
      if (!this.dragging) return;

      this.posX = event.clientX - this.offsetX;
      this.posY = event.clientY - this.offsetY;
    },

    stopDrag() {
      this.dragging = false;

      document.removeEventListener("mousemove", this.onDrag);
      document.removeEventListener("mouseup", this.stopDrag);
    },

    onConfirm() {
      this.$emit("confirm");
    },

    onCancel() {
      this.$emit("cancel");
    },

    onClose() {
      this.onCancel();
    },

    onOption() {
      this.$emit("option");
    },
  },
};
</script>

<style scoped>
.modal {
  position: fixed;
  inset: 0;
  display: block;
  z-index: 1050;
}

.modal-dialog {
  position: fixed;
  margin: 0;
}

.modal-header {
  cursor: move;
  user-select: none;
}

.btn-close {
  cursor: pointer;
}
</style>