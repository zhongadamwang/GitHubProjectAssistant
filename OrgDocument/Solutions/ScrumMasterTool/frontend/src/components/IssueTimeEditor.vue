<script setup>
import { ref } from 'vue'
import { useProjectStore } from '../stores/projectStore.js'

const props = defineProps({
  issue: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['saved'])
const store = useProjectStore()

const estimated = ref(props.issue.estimated_hours ?? 0)
const remaining = ref(props.issue.remaining_hours ?? 0)
const actual    = ref(props.issue.actual_hours ?? 0)

const flashClass = ref('')

async function save() {
  try {
    await store.saveIssueTime(props.issue.id, {
      estimated_hours: Number(estimated.value),
      remaining_hours: Number(remaining.value),
      actual_hours:    Number(actual.value),
    })
    flashClass.value = 'flash-success'
    emit('saved', props.issue.id)
  } catch {
    flashClass.value = 'flash-error'
    // Restore previous values from store (optimistic rollback already done)
    const reverted = store.issues.find((i) => i.id === props.issue.id)
    if (reverted) {
      estimated.value = reverted.estimated_hours ?? 0
      remaining.value = reverted.remaining_hours ?? 0
      actual.value    = reverted.actual_hours ?? 0
    }
  } finally {
    setTimeout(() => { flashClass.value = '' }, 800)
  }
}
</script>

<template>
  <span class="time-editor" :class="flashClass">
    <input
      v-model.number="estimated"
      type="number"
      min="0"
      step="0.5"
      title="Estimated hours"
      @blur="save"
      @keydown.enter.prevent="save"
    />
    /
    <input
      v-model.number="remaining"
      type="number"
      min="0"
      step="0.5"
      title="Remaining hours"
      @blur="save"
      @keydown.enter.prevent="save"
    />
    /
    <input
      v-model.number="actual"
      type="number"
      min="0"
      step="0.5"
      title="Actual hours"
      @blur="save"
      @keydown.enter.prevent="save"
    />
  </span>
</template>

<style scoped>
.time-editor {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  transition: background 0.3s;
  border-radius: 4px;
  padding: 0.1rem 0.25rem;
}
.time-editor input {
  width: 55px;
  padding: 0.2rem 0.3rem;
  border: 1px solid #ccc;
  border-radius: 3px;
  font-size: 0.85rem;
  text-align: right;
}
.time-editor input:focus { outline: none; border-color: #4361ee; }
.flash-success { background: rgba(40, 167, 69, 0.15); }
.flash-error   { background: rgba(214, 40, 40, 0.15); }
</style>
