<script setup>
import { computed } from 'vue'

const props = defineProps({
  // ISO 8601 string of last successful sync datetime
  lastSyncAt: {
    type: String,
    default: null,
  },
  lastStatus: {
    type: String,
    default: null,
  },
})

const status = computed(() => {
  if (props.lastStatus === 'failed') return 'error'
  if (!props.lastSyncAt) return 'unknown'
  const ageMs = Date.now() - new Date(props.lastSyncAt).getTime()
  return ageMs < 30 * 60 * 1000 ? 'ok' : 'stale'
})

const label = computed(() => {
  switch (status.value) {
    case 'ok':      return 'Synced recently'
    case 'stale':   return 'Sync overdue (> 30 min)'
    case 'error':   return 'Last sync failed'
    default:        return 'Never synced'
  }
})
</script>

<template>
  <span class="sync-status" :class="status">
    <span class="dot" />
    {{ label }}
  </span>
</template>

<style scoped>
.sync-status {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.9rem;
  font-weight: 600;
  padding: 0.3rem 0.8rem;
  border-radius: 999px;
}
.dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}
.ok      { background: #d4edda; color: #155724; }
.ok .dot { background: #28a745; }
.stale      { background: #fff3cd; color: #856404; }
.stale .dot { background: #ffc107; }
.error       { background: #f8d7da; color: #721c24; }
.error .dot  { background: #dc3545; }
.unknown       { background: #e9ecef; color: #6c757d; }
.unknown .dot  { background: #adb5bd; }
</style>
