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

        <label for="players">Hráči </label>
        <textarea id="players" v-model="playersText" rows="5" cols="40" placeholder="1,název,matrikaId1,matrikaId2,(id3,...) (na řádku)"></textarea>
        <label>Skupiny</label>
        <textarea v-model="groupsText" rows="4" cols="40" placeholder="název,počet hráčů (např A1,6) (na řádku)"></textarea>
        
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
          <button @click="genRotations">Vygeneruj podle skupin</button>
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
import { ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import slug from 'slug'
import { generateRotations } from '@/model/createTournament';
import { RoundNumberKey, RoundRotation } from '@/model/modelTypes';


const router = useRouter();

function createTournament() {
  if (!tournamentJson.value) return;
  const data = JSON.parse(tournamentJson.value);
  TournamentApi.createTournament(data.title, data.slug, tournamentJson.value,  prompt("zadejte heslo")!).then((id) => {
    console.debug('Tournament created');
    router.push({ name: 'admin-tournament-edit', params: { tournament: id } });
  }).catch((e) => {
    console.debug('Error creating tournament');
    console.error(e);
  });
}

// --- New logic for frontend run creation ---
const playersText = ref("");
const rotationsText = ref("");
const tdName = ref("");
const tdEmail = ref("");
const tournamentTitle = ref("");
const tournamentSlug = ref("");
const tournamentJson = ref<string | null>(null);
const groupsText = ref("");
const movementType = ref<'A'|'B'>('A');

watch(tournamentTitle, (newVal) => {
  if (newVal) 
    tournamentSlug.value = slug(tournamentTitle.value);
});

function parsePlayers(text: string) {
  const lines = text.split(/\r?\n/).filter(l => l.trim());
  const players: Record<number, any> = {};
  lines.forEach((line, idx) => {
    const parts = line.split(/,|\t/).map(s => s.trim());
    if (!parts[0]) return;
    const id = Number(parts[0]) || idx + 1;
    players[id] = {
      id,
      title: parts[1] || `Pair ${id}`,
      players: parts.slice(2).map(pid => ({ id: Number(pid) || pid }))
    };
  });
  return players;
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

function parseGroups(text: string) {
    // Each line: name,size
    return text.split(/\r?\n/).filter(l => l.trim()).map(line => {
      const [name, size] = line.split(/,|\t/).map(s => s.trim());
      return { name, size: Number(size) };
    });
  }

function getGroupsForTournament() {
  const groups = parseGroups(groupsText.value);
  const players = Object.keys(parsePlayers(playersText.value)).map(Number);
  let playerIndex = 0;
  return groups.map(g => {
    const groupPlayers = players.slice(playerIndex, playerIndex + g.size);
    playerIndex += g.size;
    return {
      name: g.name,
      players: groupPlayers
    };
  });
}

function genRotations() {
  const groups = parseGroups(groupsText.value);
  const _rotations = generateRotations(groups as any, movementType.value);
  rotationsText.value = _rotations.map(([round, table, ns, ew]) => `${round},${table},${ns},${ew}`).join('\n');
}

function generateTournament() {
  const players = parsePlayers(playersText.value);
  const rotations = parseRotations(rotationsText.value);
  const totalRounds = Object.keys(rotations).length;

  const tournament = {
    title: tournamentTitle.value,
    slug: tournamentSlug.value,
    totalRounds,
    groups: getGroupsForTournament(),
    rotations,
    players,
    td: { name: tdName.value, email: tdEmail.value },
    rounds: { 1: {
      date: new Date().toISOString().split('T')[0],
    } },
  };
  tournamentJson.value = JSON.stringify(tournament, null, 2);
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