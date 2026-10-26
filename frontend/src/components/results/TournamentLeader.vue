<template>
  <p v-if="!leader" class="lead">Načítám…</p>
  <template v-else>
    <p v-if="leader.round === 0" class="lead">Výsledky prvního kola zatím nejsou.</p>
    <template v-else>
      <p class="lead">
        {{ leader.finished ? 'Vítězem je' : `Po ${leader.round}. kole${leader.date ? ' hraném ' + leader.date : ''} vede` }}
        {{ leader.names.length > 1 ? 'dvojice' : 'pár' }}
      </p>
      <p class="winner-line">
        <span v-if="leader.finished" class="trophy" aria-hidden="true">🏆</span>
        <winner-names :winner="{ kind: 'pair', entries: leader.names.map((players) => ({ players })) }" />
      </p>
    </template>

    <p v-if="leader.next" class="next">
      {{ leader.next.round }}. kolo se hraje <time :datetime="leader.next.iso">{{ leader.next.date }}</time>
    </p>

    <div class="links">
      <router-link :to="{ name: 'tournament-results', params: { tournament: slug } }">Prezentace</router-link>
      <router-link v-if="leader.next" :to="{ name: 'seatings', params: { tournament: slug, round: leader.next.round } }">
        Nasazení {{ leader.next.round }}. kola
      </router-link>
    </div>
  </template>
</template>

<script setup lang="ts">
import TournamentApi from '@/api/TournamentApi';
import { ResultsPlayer } from '@/api/ResultsApi';
import WinnerNames from '@/components/results/WinnerNames.vue';
import { Tournament } from '@/model/Tournament';
import { ref, watch } from 'vue';

const props = defineProps<{ slug: string }>();
const leader = ref<{
  round: number;
  date: string;
  finished: boolean;
  names: ResultsPlayer[][];
  next: { round: number; date: string; iso: string } | null;
}>();

/** "Vozábal David" as the seeding stores names: the last word is the first name. */
function split(name: string): ResultsPlayer {
  const parts = name.trim().split(/\s+/);
  const first = parts.length > 1 ? parts.pop()! : '';
  return { first, last: parts.join(' ') };
}

/** The first round after the standing that has a date set; rounds are created ahead with their dates. */
function nextRound(tournament: Tournament) {
  for (let round = tournament.standing + 1; round <= tournament.totalRounds; round++) {
    const date = tournament.getRound(round)?.date;
    if (date) {
      const iso = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
      return { round, date: date.toLocaleDateString('cs-CZ'), iso };
    }
  }
  return null;
}

watch(() => props.slug, async (slug) => {
  const entry = await TournamentApi.getTournament(slug);
  const tournament = new Tournament(entry.data);
  const round = tournament.standing;
  const group = tournament.groups[0];
  const results = (group?.players ?? []).map((pair) => tournament.getPairResult(pair));
  const best = Math.min(...results.map((r) => r.rank.toOrdinal()));
  const date = tournament.getRound(round)?.date;
  leader.value = {
    round,
    date: date ? date.toLocaleDateString('cs-CZ') : '',
    finished: tournament.isFinished,
    names: results.filter((r) => r.rank.toOrdinal() === best)
      .map((r) => (tournament.getPair(r.pair)?.players ?? []).map((p) => split(p.name))),
    next: tournament.isFinished ? null : nextRound(tournament),
  };
}, { immediate: true });
</script>

<style scoped>
.lead {
  margin: 0;
  color: var(--ink-muted);
  font-size: 0.875rem;
}

.winner-line {
  margin: 2px 0 0;
}

.trophy {
  margin-right: 6px;
}

.next {
  margin: 10px 0 0;
  font-size: 0.9375rem;
}

.next time {
  font-weight: 600;
}

.links {
  display: flex;
  flex-wrap: wrap;
  gap: 4px 20px;
  margin-top: 12px;
  padding-top: 10px;
  border-top: 1px solid var(--rule);
  font-size: 0.9375rem;
}
</style>
