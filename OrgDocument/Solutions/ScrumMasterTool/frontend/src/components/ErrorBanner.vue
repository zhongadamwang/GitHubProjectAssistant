<template>
  <Transition name="error-banner">
    <div v-if="message" class="error-banner" role="alert" aria-live="assertive">
      <span class="error-banner__message">{{ message }}</span>
      <button class="error-banner__close" aria-label="Dismiss" @click="dismiss">✕</button>
    </div>
  </Transition>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'

const message = ref('')
let dismissTimer = null

function show(msg) {
  message.value = msg
  // Auto-dismiss after 8 seconds
  clearTimeout(dismissTimer)
  dismissTimer = setTimeout(dismiss, 8000)
}

function dismiss() {
  message.value = ''
  clearTimeout(dismissTimer)
}

function onAppError(event) {
  show(event.detail?.message ?? 'An unexpected error occurred.')
}

onMounted(() => {
  window.addEventListener('app-error', onAppError)
})

onBeforeUnmount(() => {
  window.removeEventListener('app-error', onAppError)
  clearTimeout(dismissTimer)
})
</script>

<style scoped>
.error-banner {
  position: fixed;
  top: 1rem;
  left: 50%;
  transform: translateX(-50%);
  z-index: 9999;
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem 1.25rem;
  background-color: #fee2e2;
  border: 1px solid #fca5a5;
  border-radius: 0.5rem;
  color: #b91c1c;
  max-width: 600px;
  width: calc(100% - 2rem);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.error-banner__message {
  flex: 1;
  font-size: 0.9rem;
}

.error-banner__close {
  background: none;
  border: none;
  cursor: pointer;
  color: #b91c1c;
  font-size: 1rem;
  line-height: 1;
  padding: 0;
  flex-shrink: 0;
}

.error-banner__close:hover {
  color: #7f1d1d;
}

/* Transition */
.error-banner-enter-active,
.error-banner-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}

.error-banner-enter-from,
.error-banner-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(-0.5rem);
}
</style>
