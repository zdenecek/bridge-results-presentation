<template>
  <button type="button" class="follow-button" :class="{ on: active, small }" :aria-pressed="active" @click="$emit('toggle')">
    <svg viewBox="0 0 20 20" aria-hidden="true">
      <path d="M10 2.2l2.4 5 5.4.7-4 3.8 1 5.4L10 14.5l-4.8 2.6 1-5.4-4-3.8 5.4-.7z"
            :fill="active ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
    </svg>
    <span>{{ active ? activeLabel : label }}</span>
  </button>
</template>

<script setup lang="ts">
defineProps<{ active: boolean; label: string; activeLabel: string; small?: boolean }>();
defineEmits<{ toggle: [] }>();
</script>

<style scoped>
.follow-button {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 12px 5px 9px;
  border: 1px solid #cfd8cf;
  border-radius: 999px;
  background: white;
  color: var(--felt, #1a881a);
  font: inherit;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
}

.follow-button:hover {
  border-color: var(--felt, #1a881a);
  background: #eef5ee;
}

.follow-button.on {
  background: var(--followed, #fff4c2);
  border-color: var(--followed-edge, #e0a800);
  color: #6b4e00;
}

.follow-button svg {
  width: 16px;
  height: 16px;
}

.follow-button.small {
  padding: 2px 9px 2px 6px;
  font-size: 0.8125rem;
}

.follow-button.small svg {
  width: 14px;
  height: 14px;
}
</style>
