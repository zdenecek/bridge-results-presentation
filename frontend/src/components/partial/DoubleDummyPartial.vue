<template>
    <table>
      <tr>
        <th></th>
        <th>
          <SuitPartial suit="N" />
        </th>
        <th>
          <SuitPartial suit="S" />
        </th>
        <th>
          <SuitPartial suit="H" />
        </th>
        <th>
          <SuitPartial suit="D" />
        </th>
        <th>
          <SuitPartial suit="C" />
        </th>
      </tr>
      <tr v-for="[pos, i] in [['N', 0], ['S', 2], ['E', 1], ['W', 3]]" :key="i">
        <td>{{ pos }}</td>
        <td v-for="s in 5" :key="s">
          {{ dd[5 * (i as number) + s - 1] }}
        </td>
      </tr>
    </table>
</template>

<script setup lang="ts">
import SuitPartial from './SuitPartial.vue';

defineProps({
  dd: {
    type: Array,
    required: true,
  },
})
</script>

<style scoped>
table {
  border-collapse: collapse;
  font-size: 0.75rem;
  line-height: 1.2;
  color: var(--ink-muted, #5f6661);
}

th {
  padding: 2px 3px;
  border-bottom: 1px solid var(--rule, #e3e6e3);
}

/* Grey suits and figures: the coloured ones belong to the cards. */
th :deep(.suit) {
  color: var(--ink-muted, #5f6661);
}

td {
  padding: 1px 4px;
  text-align: center;
  font-variant-numeric: tabular-nums;
  color: #4a504c;
}

td:first-child {
  padding-left: 0;
  color: var(--ink-muted, #5f6661);
  font-weight: 600;
}
</style>
