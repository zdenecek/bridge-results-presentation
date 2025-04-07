<script setup lang="ts">

import { Tournament } from '@/model/Tournament';
import { Ref, inject, ref, computed, ComputedRef } from 'vue';

import TournamentGroupCrosstable from '@/components/TournamentGroupCrosstable.vue';
import TournamentGroupTotals from '@/components/TournamentGroupTotals.vue';
import { Group } from '@/model/modelTypes';
const tournament = inject('tournament') as Ref<Tournament | undefined>;

const showCrossTables = ref(false);
const showCups = ref(false);
const showSubtitle = ref(true);
const firstThreeBold = ref(true);
const columnLayout = ref(false);
const logos = ref(2);
const logoHeight = ref("2.5cm");

const dateString = computed(() => {
  const t = tournament.value;
  if (!t) return '';
  const firstRound = t.rounds.get(1);
  const lastRound = t.getRound(t.rounds.size);
  return `${firstRound?.date?.toLocaleDateString('cs')} - ${lastRound?.date?.toLocaleDateString('cs')}`;
});

const subtitle = computed(() => `Hráno ve dnech ${dateString.value} na ${tournament.value?.rounds.size} kol, vedl ${tournament.value?.td?.name}`)
const customText = ref("");

// groups are grouped by first two letters eg. A21 with A22
const applySubgroups = ref(true);
type GroupWithIndex = Group & { index: number };

const subgroups: ComputedRef<GroupWithIndex[][]> = computed(() => {
  if (!tournament.value) return [];

  const groupsWithIndex = tournament.value.groups.map((g, i) => ({ ...g, index: i } as GroupWithIndex ));

  if (applySubgroups.value)
    // group by only in modern browsers
    // @ts-ignore
    return Object.values(Object.groupBy(groupsWithIndex, i => i.name.slice(0, 2))) as GroupWithIndex[][]

  return groupsWithIndex.map(g => [g]);
})

const faintColor = ref("");

</script>

<template>


  <div v-if="tournament" class="flex flex-column justify-center align-center print-container"
    :class="{ [faintColor]: true, 'first-three-bold': firstThreeBold }">

    <div class="no-print config">
      <h3>Print Config</h3>
      <input type="checkbox" id="cross" v-model="showCrossTables" />
      <label for="cross">Show cross tables</label>

      <input type="checkbox" id="cups" v-model="showCups" />
      <label for="cups">Show cups</label>

      <input type="checkbox" id="subtitle" v-model="showSubtitle" />
      <label for="subtitle">Show subtitle </label>


      <input type="checkbox" id="ffb" v-model="firstThreeBold" />
      <label for="ffb">First three bold </label>

      <input type="text" v-model="customText" placeholder="Custom subtitle" />




      <div class="flex gap-small">
        <input type="radio" id="column-layout" v-model="columnLayout" :value="true" />
        <label for="column-layout">Column layout</label>

        <input type="radio" v-model="columnLayout" :value="false" />
        <label for="row-layout">Row layout</label>

        <input type="checkbox" id="subgroup" v-model="applySubgroups" />
        <label>Apply subgroups (A2x etc)</label>
      </div>

      <div class="flex gap-small">
        <input type="radio" v-model="logos" :value="0" />
        <label>No logo</label>

        <input type="radio" v-model="logos" :value="1" />
        <label>One logo</label>

        <input type="radio" v-model="logos" :value="2" />
        <label>Two logos</label>
      </div>
      <div class="flex gap-small" v-show="logos > 0">
        <label>Logo height</label> <input v-model="logoHeight" />
      </div>
      <div class="flex gap-small">
        <label>Style</label>
        <input type="radio" v-model="faintColor" value="blue" />
        <label>Blue</label>

        <input type="radio" v-model="faintColor" value="orange" />
        <label>Orange</label>

        <input type="radio" v-model="faintColor" value="green" />
        <label>Green</label>

        <input type="radio" v-model="faintColor" value="" />
        <label>None</label>
      </div>

      <div class="flex" >
        Note: print on Google Chrome
      </div>
    </div>
    <img src="@/assets/logo.png" class="logo logo-left" :style="{ height: logoHeight }" v-if="logos > 0">
    <img src="@/assets/logo.png" class="logo logo-right" :style="{ height: logoHeight }" v-if="logos > 1">

    <div v-show="showSubtitle" class="date-box">
      <span>{{ customText === '' ? subtitle : customText }}</span>
    </div>
    <div :class="{ 'flex-column': columnLayout, 'flex-wrap': !columnLayout }" class="flex gap-small align-start">
      <div v-for="subgroup, i in subgroups" :key="i" class="flex gap-small align-start print-no-break">
        <div v-for="group in subgroup" :key="group.index"
          class="flex flex-column gap-small align-start print-no-break">
          <h2>Skupina {{ group.name }}</h2>

          <div class="flex align-start">
            <TournamentGroupTotals :show-cups="showCups" :tournament="tournament" :groupIndex="group.index" />
            <TournamentGroupCrosstable v-show="showCrossTables" :tournament="tournament" class="fix"
              :groupIndex="group.index" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss">
.align-start {
  align-items: start;
}

.flex-wrap {
  flex-wrap: wrap;
  justify-content: center;
}

.content .container {
  max-width: 100%;
}


.print-container {
  margin-top: auto;
  gap: 10px;


  .col-name {
    width: 280px !important;
  }


  .config {
    margin: 20px;
    padding: 10px;
    border: 1px solid black;
  }

  .date-box {
    font-size: 0.8em;
    margin-top: -20px;
  }

  &.blue {

    --primary-color: darkblue;

    table tr:nth-child(even) {
      background-color: #E0F7FA !important;
    }
  }

  &.orange {

    --primary-color: #CC5500;

    table tr:nth-child(even) {
      background-color: #ffead6 !important;
    }
  }

  &.green {
    --primary-color: green;

    table tr:nth-child(even) {
      background-color: #E0FFE0 !important;
    }
  }

  &.first-three-bold {
    tr:nth-child(4)  td:nth-child(2) {
      font-weight: bold;
    }

    tr:nth-child(2) td:nth-child(2) {
      font-weight: bold;
    }

    tr:nth-child(3)  td:nth-child(2) {
      font-weight: bold;
    }
  }

  .empty {
    background-color: unset !important;
  }

  .cup+a {
    font-weight: bold;
  }


  .logo {
    position: absolute;
    top: 0;
  }

  .logo-left {
    left: 0;
  }

  .logo-right {
    right: 0;
  }

  // this is just so the table rows dont align 
  .fix td {
    font-size: 0.8em;
  }

  .first-three-bold {
    tr:nth-child(1) td {
      font-weight: bold;
    }

    tr:nth-child(2) td {
      font-weight: bold;
    }

    tr:nth-child(3) td {
      font-weight: bold;
    }
  }

  .empty {
    background-color: unset !important;
  }

  .cup+a {
    font-weight: bold;
  }
}


@media print {


  .no-print {
    display: none;
    visibility: collapse;
  }


}
</style>