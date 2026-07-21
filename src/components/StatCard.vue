<template>
  <div class="stat-card shadow-sm">

    <div class="icon">
      <i :class="['bi', icon]"></i>
    </div>

    <div>
      <div class="title">
        {{ title }}
      </div>

      <div class="value">
        {{ value.toLocaleString('de-DE') }}
      </div>
    </div>

  </div>
</template>


<script>
export default {

  name: 'StatCard',

  props: {
    title: String,
    value: Number,
    icon: String,
    variant: {
      type: String,
      default: 'secondary'
    }
  }

}
</script>


<style scoped>
.stat-card {
  height: 100%;
  display: flex;
  align-items: center;
  gap: 1rem;

  padding: 1.25rem;

  border-radius: 1rem;
  background: white;
}


.icon {
  width: 55px;
  height: 55px;

  border-radius: 50%;

  display: flex;
  align-items: center;
  justify-content: center;

  font-size: 1.6rem;

  background: #f1f3f5;
}


.title {
  color: #6c757d;
  font-size: .9rem;
}


.value {
  font-size: 2rem;
  font-weight: 700;
}
</style>