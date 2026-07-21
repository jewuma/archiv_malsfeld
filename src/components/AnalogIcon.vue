<template>
  <div v-if="item && item.count > 0" class="position-relative d-inline-block">
    <button class="btn btn-link p-0" @click.stop="$emit('toggle-expand')" :title="tooltip">
      <i :class="iconClass" class="fs-3"></i>
    </button>
    <span v-if="item.count > 1" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary"
      style="font-size:0.65rem">
      {{ item.count }}
    </span>
  </div>
</template>
<script>
export default {
  name: "AnalogIcon",
  emits: ["toggle-expand"],
  props: {
    item: {
      type: Object,
      required: true
    },
  },
  computed: {
    iconClass() {
      const type = this.item.type || 0;
      if ([1, 2, 17].includes(type)) return "bi bi-book";
      if (type === 2) return "bi bi-map";
      if (type === 7) return "bi bi-disc"
      if (type === 20) return "bi bi-camera"
      if (type === 21) return "bi bi-newspaper"
      return "bi bi-files";
    },
    tooltip() {
      return `${this.item.count} Obekte`;
    }
  },
} 
</script>