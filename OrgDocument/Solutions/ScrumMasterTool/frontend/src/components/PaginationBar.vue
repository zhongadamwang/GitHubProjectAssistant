<script setup>
/**
 * @component PaginationBar
 * @description Page-size dropdown, page navigation and a "Showing x–y of N" summary.
 *
 * @prop {number}        page       Current 1-based page.
 * @prop {number}        pageSize   Rows per page.
 * @prop {number}        total      Total rows across all pages.
 * @prop {number}        totalPages Total number of pages.
 * @prop {Array<number>} pageSizes  Selectable page sizes.
 * @emits update:page      Emitted with the newly chosen page number.
 * @emits update:pageSize  Emitted with the newly chosen page size.
 */
import { computed } from 'vue'

const props = defineProps({
  page: { type: Number, required: true },
  pageSize: { type: Number, required: true },
  total: { type: Number, required: true },
  totalPages: { type: Number, required: true },
  pageSizes: { type: Array, default: () => [10, 25, 50, 100] },
})

const emit = defineEmits(['update:page', 'update:pageSize'])

const from = computed(() => (props.total === 0 ? 0 : (props.page - 1) * props.pageSize + 1))
const to = computed(() => Math.min(props.page * props.pageSize, props.total))

// First, last, and a window of two pages around the current one; null = ellipsis
const pages = computed(() => {
  const shown = new Set([1, props.totalPages])
  for (let p = props.page - 2; p <= props.page + 2; p++) {
    if (p >= 1 && p <= props.totalPages) shown.add(p)
  }
  const sorted = [...shown].sort((a, b) => a - b)
  const result = []
  sorted.forEach((p, i) => {
    if (i > 0 && p - sorted[i - 1] > 1) result.push(null)
    result.push(p)
  })
  return result
})

function go(page) {
  if (page >= 1 && page <= props.totalPages && page !== props.page) {
    emit('update:page', page)
  }
}
</script>

<template>
  <div class="pagination">
    <label class="size">
      Rows per page
      <select :value="pageSize" @change="emit('update:pageSize', Number($event.target.value))">
        <option v-for="s in pageSizes" :key="s" :value="s">{{ s }}</option>
      </select>
    </label>

    <span class="summary">Showing {{ from }}–{{ to }} of {{ total }}</span>

    <nav class="pages" aria-label="Pagination">
      <button type="button" :disabled="page <= 1" @click="go(page - 1)">‹ Prev</button>
      <template v-for="(p, i) in pages" :key="i">
        <span v-if="p === null" class="ellipsis">…</span>
        <button
          v-else
          type="button"
          :class="{ active: p === page }"
          :aria-current="p === page ? 'page' : undefined"
          @click="go(p)"
        >
          {{ p }}
        </button>
      </template>
      <button type="button" :disabled="page >= totalPages" @click="go(page + 1)">Next ›</button>
    </nav>
  </div>
</template>

<style scoped>
.pagination {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem 1.5rem;
  padding: 0.75rem 0;
  font-size: 0.9rem;
  color: #444;
}
.size {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
}
.size select {
  padding: 0.25rem 0.5rem;
}
.pages {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}
.pages button {
  min-width: 2rem;
  padding: 0.3rem 0.6rem;
  border: 1px solid #ccc;
  border-radius: 4px;
  background: #fff;
  cursor: pointer;
}
.pages button.active {
  background: #4361ee;
  border-color: #4361ee;
  color: #fff;
  font-weight: 700;
}
.pages button:disabled {
  opacity: 0.45;
  cursor: default;
}
.ellipsis {
  padding: 0 0.25rem;
}
</style>
