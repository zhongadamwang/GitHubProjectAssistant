<script setup>
defineProps({
  iterations: {
    type: Array,
    default: () => [],
  },
  modelValue: {
    type: String,
    default: null,
  },
})

const emit = defineEmits(['update:modelValue'])
</script>

<template>
  <div class="sprint-selector">
    <label for="sprint-select">Sprint</label>
    <select
      id="sprint-select"
      :value="modelValue"
      @change="emit('update:modelValue', $event.target.value || null)"
    >
      <option value="">Latest</option>
      <option v-for="it in iterations" :key="it" :value="it">{{ it }}</option>
    </select>
  </div>
</template>

<style scoped>
.sprint-selector {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
label {
  font-weight: 600;
  font-size: 0.9rem;
  color: #444;
}
select {
  padding: 0.4rem 0.6rem;
  border: 1px solid #ccc;
  border-radius: 4px;
  font-size: 0.9rem;
}
</style>
