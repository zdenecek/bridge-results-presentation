<template>
  <div class="flex flex-column gap-4">
    <h2>Vytvořit turnaj</h2>
    <div class="fields">
      <label for="tournamentTitle">Název turnaje</label>
      <input id="tournamentTitle" v-model="tournamentTitle" type="text" />
      <label for="tournamentSlug">Slug (název v url adrese)</label>
      <input id="tournamentSlug" v-model="tournamentSlug" type="text" />
      <label for="tdName">Jméno TD</label>
      <input id="tdName" v-model="tdName" type="text" />
      <label for="tdEmail">Email TD</label>
      <input id="tdEmail" v-model="tdEmail" type="email" />
      <label for="firstRoundDate">Datum 1. kola</label>
      <input id="firstRoundDate" v-model="firstRoundDate" type="date" />

      <h3 class="span">Nasazení</h3>
      <label for="seeding">Dvojice</label>
      <div class="flex flex-column gap-small">
        <textarea id="seeding" v-model="seedingText" rows="12" cols="70"
          placeholder="skupina, dvojice, hráč 1, hráč 2, … (na řádku)
A1, Vozábal - Klemš, Vozábal David 1868, Klemš Erik 2696
A1, Hnátová - Erde, Hnátová Daniela 187, Erde Alexander 39
…
B3, pauza"></textarea>
        <div class="small">
          Oddělovač je tabulátor, středník nebo čárka – jde vložit přímo ze Sheetu.
          Hráč je <em>Příjmení Jméno</em>, matriční číslo se píše za jméno (<em>Klemš Erik 2696</em>).
          Řádek <em>pauza</em> vytvoří ve skupině volné místo.
        </div>
        <div class="horizontal">
          <button type="button" @click="fillPlayerIds" :disabled="lookupRunning">
            {{ lookupRunning ? 'Hledám…' : 'Doplnit ID hráčů z minulých turnajů' }}
          </button>
        </div>
        <div v-if="lookupMessage" class="small">{{ lookupMessage }}</div>
      </div>

      <label>Skupiny</label>
      <div class="small">{{ groupSummary }}</div>

      <h3 class="span">Rozpisy</h3>
      <label>Typ skupinovky (propozice)</label>
      <div class="radio-group">
        <label>
          <input type="radio" v-model="movementType" value="A">Skupinovka A
        </label>
        <label>
          <input type="radio" v-model="movementType" value="B">Skupinovka B
        </label>
      </div>

      <div>
        <label for="rotations">Rozpisy</label>
        <button type="button" @click="genRotations">Vygeneruj podle skupin</button>
      </div>

      <textarea id="rotations" v-model="rotationsText" rows="5" cols="40" placeholder="kolo,stul,ns,ew (na řádku)"></textarea>

    </div>
    <button @click="generateTournament">Vygenerovat turnaj</button>

    <div v-if="tournamentJson" class="flex flex-column gap-4">
      <h4>Vygenerovaný JSON (pro kontrolu)</h4>
      <textarea v-model="tournamentJson" rows="20" cols="80"></textarea>

      <button @click="createTournament">Vytvořit turnaj</button>
    </div>

  </div>
</template>

<script setup lang="ts">
// @ts-ignore

import TournamentApi from '@/api/TournamentApi';
import { computed, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import slug from 'slug'
import { generateRotations } from '@/model/createTournament';
import { Pair, Player, RoundNumberKey, RoundRotation } from '@/model/modelTypes';

const router = useRouter();

const seedingText = ref("");
const rotationsText = ref("");
const tdName = ref("");
const tdEmail = ref("");
const tournamentTitle = ref("");
const tournamentSlug = ref("");
const tournamentJson = ref<string | null>(null);
const movementType = ref<'A' | 'B'>('A');
const firstRoundDate = ref(new Date().toISOString().split('T')[0]);
const lookupRunning = ref(false);
const lookupMessage = ref("");

watch(tournamentTitle, (newVal) => {
  if (newVal)
    tournamentSlug.value = slug(tournamentTitle.value);
});

const BYE_TITLES = ['pauza', 'bye', '-'];

interface SeedingLine {
  group: string;
  title: string;
  isBye: boolean;
  players: Player[];
}

function parsePlayer(token: string): Player {
  const match = token.match(/^(.*?)[\s(#]*(\d+)\)?$/);
  const name = (match ? match[1] : token)!.trim();
  return { id: match ? match[2]! : '', name, club: '' } as Player;
}

function parseSeeding(text: string): SeedingLine[] {
  return text.split(/\r?\n/)
    .map(line => line.split(/\t|;|,/).map(s => s.trim()))
    .filter(parts => parts[0])
    .map(parts => {
      const title = parts[1] ?? '';
      return {
        group: parts[0]!,
        title,
        isBye: BYE_TITLES.includes(title.toLowerCase()),
        players: parts.slice(2).filter(p => p).map(parsePlayer),
      };
    });
}

const seeding = computed(() => parseSeeding(seedingText.value));

const groups = computed(() => {
  const result: { name: string, size: number }[] = [];
  for (const line of seeding.value) {
    const existing = result.find(g => g.name === line.group);
    if (existing) existing.size++;
    else result.push({ name: line.group, size: 1 });
  }
  return result;
});

const groupSummary = computed(() => {
  if (!groups.value.length) return 'zatím nic nevloženo';
  const pairs = seeding.value.filter(l => !l.isBye).length;
  return groups.value.map(g => `${g.name}: ${g.size}`).join(', ') + ` (${pairs} dvojic)`;
});

/**
 * Looks up matrika ids for players that were entered without one, by name,
 * in already published tournaments (newest first).
 */
async function fillPlayerIds() {
  const missing = new Set(seeding.value.flatMap(l => l.players).filter(p => !p.id).map(p => p.name));
  if (!missing.size) {
    lookupMessage.value = 'Všichni hráči už mají ID.';
    return;
  }

  lookupRunning.value = true;
  lookupMessage.value = '';
  const found = new Map<string, Player>();
  try {
    const tournaments = (await TournamentApi.getTournaments()).reverse();
    for (const entry of tournaments) {
      if (!missing.size) break;
      const data = (await TournamentApi.getTournament(entry.slug)).data;
      for (const pair of Object.values(data?.players ?? {}) as Pair[]) {
        for (const player of pair.players ?? []) {
          if (!player.name || !player.id || !missing.has(player.name)) continue;
          found.set(player.name, player);
          missing.delete(player.name);
        }
      }
    }
  } catch (e) {
    console.error(e);
    lookupMessage.value = 'Chyba při načítání minulých turnajů.';
    lookupRunning.value = false;
    return;
  }

  seedingText.value = seedingText.value.split(/\r?\n/).map(line => {
    const parts = line.split(/\t|;|,/);
    if (parts.length < 3) return line;
    return parts.map((part, index) => {
      if (index < 2) return part;
      const player = found.get(parsePlayer(part.trim()).name);
      return player ? part.replace(/\s*$/, ` ${player.id}`) : part;
    }).join(line.includes('\t') ? '\t' : ',');
  }).join('\n');

  lookupRunning.value = false;
  lookupMessage.value = missing.size
    ? `Doplněno ${found.size}, nenalezeno: ${Array.from(missing).join(', ')}`
    : `Doplněno ${found.size} hráčů.`;
}

function parseRotations(text: string) {
  // Group into { roundNum: {tableNum: {ns, ew}} }
  const lines = text.split(/\r?\n/).filter(l => l.trim()).map(line => line.split(/,|\t/).map(s => Number.parseInt(s)));
  const grouped: Record<RoundNumberKey, RoundRotation> = {};
  for (const [round, table, ns, ew] of lines) {
    // @ts-ignore
    if (!grouped[round]) grouped[round] = {};
    // @ts-ignore
    grouped[round][table] = { ns, ew };
  }
  return grouped;
}

function genRotations() {
  const _rotations = generateRotations(groups.value, movementType.value);
  rotationsText.value = _rotations.map(([round, table, ns, ew]) => `${round},${table},${ns},${ew}`).join('\n');
}

function generateTournament() {
  const players: Record<number, any> = {};
  const groupPlayers: Record<string, number[]> = {};

  seeding.value.forEach((line, index) => {
    const id = index + 1;
    if (line.isBye) {
      players[id] = { id, isBye: true, title: 'pauza', players: [] };
      return;
    }
    players[id] = {
      id,
      title: line.title || line.players.map(p => p.name.split(' ')[0]).join(' - '),
      players: line.players.map(p => ({
        ...(p.id ? { id: p.id } : {}),
        name: p.name,
      })),
    };
    (groupPlayers[line.group] ??= []).push(id);
  });

  const rotations = parseRotations(rotationsText.value);

  const tournament = {
    title: tournamentTitle.value,
    slug: tournamentSlug.value,
    totalRounds: Object.keys(rotations).length,
    groups: groups.value.map(g => ({ name: g.name, players: groupPlayers[g.name] ?? [] })),
    rotations,
    players,
    td: { name: tdName.value, email: tdEmail.value },
    rounds: { 1: { date: firstRoundDate.value } },
  };
  tournamentJson.value = JSON.stringify(tournament, null, 2);
}

function createTournament() {
  if (!tournamentJson.value) return;
  const data = JSON.parse(tournamentJson.value);
  TournamentApi.createTournament(data.title, data.slug, data, prompt("zadejte heslo")!).then((id) => {
    console.debug('Tournament created');
    router.push({ name: 'admin-tournament-edit', params: { tournament: id } });
  }).catch((e) => {
    console.debug('Error creating tournament');
    console.error(e);
  });
}

</script>

<style lang="scss">
label {
    margin-right: 1em;
}

form {
    display: flex;
    flex-direction: column;
    gap: 10px;
    align-items: center;
}

.fields {
    display: inline-grid;
    grid-template-columns: 1fr 2fr;
    gap: 10px;

    .span {
      grid-column: 1 / 3;
    }
}

</style>
