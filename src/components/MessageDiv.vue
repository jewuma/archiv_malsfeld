<template>
  <transition name="fade">
    <div v-if="visible" class="alert special-height position-fixed d-flex justify-content-between align-items-center"
      :class="[message.isError ? 'alert-danger' : 'alert-success']" role="alert">
      <span>{{ message.text }}</span>
      <button class="btn btn-light" @click.prevent="close">OK</button>
    </div>
  </transition>
</template>

<script>
export default {
  props: {
    message: {
      type: Object,
      required: true,
    },
  },
  emits: ["ok"],
  data() {
    return {
      visible: true,
    };
  },
  watch: {
    "message.isError": {
      handler(newValue) {
        // Wenn kein Fehler und Nachricht sichtbar, nach 1 Sekunde ausblenden
        if (!newValue) {
          setTimeout(() => {
            this.close();
          }, 1000);
        }
      },
      immediate: true, // Sofortiger Watch-Trigger
    },
  },
  methods: {
    close() {
      this.visible = false;
      this.$emit("ok"); // Sendet das OK-Ereignis zurück
    },
  },
};
</script>

<style scoped>
.special-height {
  padding: 0.5em 1.5em;
  /* Mehr Innenabstand für eine bessere Optik */
  min-height: 50px;
  /* Flexiblere Mindesthöhe */
  z-index: 1000;
}

.position-fixed {
  position: fixed;
  top: 10px;
  left: 50%;
  transform: translateX(-50%);
  width: auto;
  text-align: left;
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
  border-radius: 8px;
  display: flex;
  align-items: center;
  word-break: break-word;
  z-index: 9999;
  justify-content: space-between;
  width: 90%;
}

.alert {
  font-size: 1rem;
  line-height: 1.5;
  color: #5f5f5f;
}

.alert-danger {
  background-color: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

.alert-success {
  background-color: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.btn {
  margin-left: 10px;
  font-size: 0.9rem;
  padding: 0.3rem 1rem;
  background-color: #dce2dd;
  color: #333333;
  border-radius: 5px;
  border: 2px solid #ccc;
  transition:
    background-color 0.3s ease,
    color 0.3s ease;
}

.btn:hover {
  background-color: #e0e0e0;
  color: #555;
}



.btn-light {
  margin-left: auto;
  /* Stellt sicher, dass der Button nach rechts verschoben wird */
}

.fade-enter-active,
.fade-leave-active {
  transition:
    opacity 0.5s,
    transform 0.5s;
}

.fade-enter,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-20px);
  /* Schöneres Ein- und Ausblenden */
}
</style>
