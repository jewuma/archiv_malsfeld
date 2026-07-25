<template>
  <div class="stat-card shadow-sm" :data-variant="variant">

    <div class="icon">
      <i :class="['bi', icon]"></i>
    </div>

    <div>
      <div class="title">
        {{ title }}
      </div>

      <div class="value">
        {{ formattedValue }}
      </div>
    </div>

  </div>
</template>


<script>
export default {

  name: 'StatCard',

  props: {
    title: String,
    value: {
      type: Number,
      default: 0
    },
    icon: String,
    variant: {
      type: String,
      default: 'secondary'
    }
  },
  computed: {
    formattedValue() {
      if (typeof this.value !== 'number' || Number.isNaN(this.value)) {
        return '0'
      }
      return this.value.toLocaleString('de-DE')
    }
  },
}
</script>


<style scoped>
.stat-card {
  height: 100%;
  display: flex;
  align-items: center;
  gap: 0.75rem;

  padding: 0.9rem;

  border-radius: 0.75rem;
  background: white;
  border-left: 4px solid #6c757d;
  transition: transform 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
}

.stat-card[data-variant="success"] {
  border-left-color: #198754;
}

.stat-card[data-variant="primary"] {
  border-left-color: #0d6efd;
}

.stat-card[data-variant="info"] {
  border-left-color: #0dcaf0;
}

.stat-card[data-variant="warning"] {
  border-left-color: #ffc107;
}

.stat-card[data-variant="danger"] {
  border-left-color: #dc3545;
}

.icon {
  width: 45px;
  height: 45px;

  border-radius: 50%;

  display: flex;
  align-items: center;
  justify-content: center;

  font-size: 1.4rem;
  flex-shrink: 0;
}

.stat-card[data-variant="success"] .icon {
  background: #e7f5ec;
  color: #198754;
}

.stat-card[data-variant="primary"] .icon {
  background: #e7f1ff;
  color: #0d6efd;
}

.stat-card[data-variant="info"] .icon {
  background: #e6f6f8;
  color: #0dcaf0;
}

.stat-card[data-variant="warning"] .icon {
  background: #fff8e1;
  color: #ffc107;
}

.stat-card[data-variant="danger"] .icon {
  background: #ffe5e8;
  color: #dc3545;
}

.stat-card:not([data-variant="success"]):not([data-variant="primary"]):not([data-variant="info"]):not([data-variant="warning"]):not([data-variant="danger"]) .icon {
  background: #e9ecef;
  color: #495057;
}

.title {
  color: #6c757d;
  font-size: 0.8rem;
  font-weight: 500;
  white-space: normal;
}

.value {
  font-size: 1.6rem;
  font-weight: 700;
  color: #212529;
}
</style>