<template>
  <div class="tv">
    <p v-if="state === 'error'" class="note">Turnaj se nepodařilo načíst.</p>
    <template v-else-if="tournament">
      <header>
        <h1>{{ tournament.title }}</h1>
        <p class="sub">Nasazení na {{ round }}. kolo<template v-if="date">, {{ date.toLocaleDateString('cs-CZ') }}</template></p>
      </header>

      <div class="groups">
        <div v-for="row, r in rows" :key="r" class="row">
          <section v-for="{ group } in row" :key="group.name" class="group">
            <h2>Skupina {{ group.name }}</h2>
            <table>
              <thead>
                <tr>
                  <th class="table-no">Stůl</th>
                  <th>NS</th>
                  <th>EW</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="seat in group.seats" :key="seat.table" :class="{ postponed: seat.postponed }">
                  <td class="table-no">{{ seat.table }}</td>
                  <td>{{ seat.ns }}</td>
                  <td>{{ seat.ew }}<span v-if="seat.postponed" class="flag">odloženo</span></td>
                </tr>
              </tbody>
            </table>
          </section>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import TournamentApi from '@/api/TournamentApi';
import { Tournament } from '@/model/Tournament';
import { computed, onBeforeUnmount, ref } from 'vue';
import { useRoute } from 'vue-router';
import { groupRows } from '@/utils/groupRows';

/*
 * The seating for the club's TV: no menu, no links, big type. Not linked from anywhere and needs no
 * password. Reloads itself, so a change made in the admin shows up without touching the TV.
 */
const REFRESH_MS = 2 * 60 * 1000;

const route = useRoute();
const tournament = ref<Tournament>();
const state = ref<'loading' | 'loaded' | 'error'>('loading');

function load() {
  TournamentApi.getTournament(String(route.params.tournament), true).then((data) => {
    tournament.value = new Tournament(data.data);
    state.value = 'loaded';
    document.title = `Nasazení – ${data.name}`;
  }).catch((e) => {
    console.error(e);
    if (!tournament.value) state.value = 'error';
  });
}
load();
const timer = window.setInterval(load, REFRESH_MS);
onBeforeUnmount(() => window.clearInterval(timer));

const round = computed(() => {
  const asked = Number.parseInt(String(route.params.round ?? ''));
  if (!isNaN(asked)) return asked;
  const t = tournament.value;
  return t ? Math.min(t.standing + 1, t.totalRounds) : 1;
});

const date = computed(() => tournament.value?.rounds.get(round.value)?.date);

const groups = computed(() => {
  const t = tournament.value;
  const rotation = t?.rotations.get(round.value);
  if (!t || !rotation) return [];
  return t.groups.map((group) => ({
    name: group.name,
    seats: Object.entries(rotation)
      .filter(([, { ns, ew }]) => group.players.includes(ns) || group.players.includes(ew))
      .map(([table, { ns, ew }]) => ({
        table,
        ns: t.getPair(ns)?.title ?? '',
        ew: t.getPair(ew)?.title ?? '',
        postponed: t.rounds.get(round.value)?.isTablePostponed(Number.parseInt(table)) ?? false,
      })),
  })).filter((g) => g.seats.length);
});

const rows = computed(() => groupRows(groups.value));
</script>

<style scoped>
.tv {
  min-height: 100vh;
  box-sizing: border-box;
  padding: 2.5vh 2vw 3vh;
  background: white;
  color: #111;
  font-family: 'Lexend', Verdana, sans-serif;
  font-size: clamp(16px, 1.25vw, 34px);
  text-align: center;
}

header {
  margin-bottom: 2.5vh;
}

h1 {
  margin: 0;
  font-size: 2.2em;
  line-height: 1.15;
  font-weight: 700;
}

.sub {
  margin: 0.3em 0 0;
  font-size: 1.35em;
  color: #1a881a;
  font-weight: 600;
}

/* A level per row, its parallel groups side by side. */
.groups {
  display: flex;
  flex-direction: column;
  gap: 2.2vh;
}

.row {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  align-items: flex-start;
  gap: 2.2vh 2.2vw;
}

.group {
  flex: 0 1 30em;
}

h2 {
  margin: 0 0 0.3em;
  font-size: 1.25em;
  font-weight: 700;
  text-align: left;
}

table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  line-height: 1.3;
}

th {
  padding: 0.25em 0.5em;
  border-bottom: 2px solid #111;
  color: #555;
  font-size: 0.8em;
  font-weight: 600;
}

td {
  padding: 0.35em 0.5em;
  border-bottom: 1px solid #ccc;
}

.table-no {
  width: 3.2em;
  text-align: center;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}

tr.postponed td {
  color: #777;
}

.flag {
  margin-left: 0.6em;
  padding: 0 0.4em;
  border: 1px solid #b07a00;
  border-radius: 4px;
  color: #b07a00;
  font-size: 0.75em;
  font-weight: 600;
  text-transform: uppercase;
}

.note {
  margin-top: 30vh;
  font-size: 1.5em;
}
</style>
