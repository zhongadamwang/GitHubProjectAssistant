<script setup>
import { computed } from 'vue'

const props = defineProps({
  // 'on-track' | 'at-risk' | 'behind' | null
  health: {
    type: String,
    default: null,
  },
})

const label = computed(() => {
  switch (props.health) {
    case 'on-track': return 'On Track'
    case 'at-risk':  return 'At Risk'
    case 'behind':   return 'Behind'
    default:         return 'No Data'
  }
})
</script>

<template>
  <span class="badge" :class="health ?? 'no-data'">{{ label }}</span>
</template>

<style scoped>
.badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 999px;
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.03em;
}
.on-track { background: #d4edda; color: #155724; }
.at-risk   { background: #fff3cd; color: #856404; }
.behind    { background: #f8d7da; color: #721c24; }
.no-data   { background: #e9ecef; color: #6c757d; }
</style>
