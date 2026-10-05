<template>
    <fieldset class="session">
        <legend>Dohrávka {{ session.id }}</legend>
        <div class="row">
            <label :for="'date-' + session.id">Datum</label>
            <input :id="'date-' + session.id" type="date" v-model="session.date">
            <label :for="'pbn-' + session.id">PBN z Jacka</label>
            <input :id="'pbn-' + session.id" type="file" accept=".pbn" @change="loadPbn">
        </div>
        <div class="row">
            <span v-if="scored.boardNumbers.length">
                {{ Object.keys(session.boards).length }} rozdání, {{ scored.fieldTables }} stolů Jacka
            </span>
            <span v-else class="error">Nahrajte PBN s výsledky z Jacka.</span>
            <span v-if="pbnSkipped" class="warning">{{ pbnSkipped }} her v PBN bez výsledku bylo vynecháno.</span>
        </div>

        <div v-for="(match, index) in session.matches" :key="index" class="match">
            <div class="row">
                <label>Kolo</label>
                <select v-model.number="match.round">
                    <option v-for="r in tournamentData.totalRounds" :key="r" :value="r">{{ r }}.</option>
                </select>
                <label>Stůl</label>
                <select v-model.number="match.table">
                    <option v-for="t in tables(match.round)" :key="t.table" :value="t.table">{{ t.label }}</option>
                </select>
                <label><input type="checkbox" v-model="match.swapped"> NS pár z rozpisu seděl EW</label>
                <button type="button" @click="removeMatch(index)">Smazat zápis</button>
            </div>
            <div class="row top">
                <div class="slip">
                    <button type="button" :disabled="reading[index]" @click="pickPhoto(index)">
                        {{ reading[index] ? 'Čtu lísteček…' : 'Načíst z fotky' }}
                    </button>
                    <input :id="photoInputId(index)" type="file" accept="image/*" hidden
                        @change="(e) => readPhoto(match, index, e)">
                    <textarea v-model="match.slip" rows="30" :placeholder="placeholder"></textarea>
                </div>
                <div class="report">
                    <div v-if="photoErrors[index]" class="error">{{ photoErrors[index] }}</div>
                    <div v-if="photoModels[index]" class="warning">
                        Přečteno z fotky ({{ photoModels[index] }}). Barvy zkontrolujte podle lístečku,
                        chyba v barvě se ve skóre nemusí projevit.
                    </div>
                    <div v-if="!tournamentData.rounds[match.round]" class="error">{{ match.round }}. kolo není založené.</div>
                    <div v-for="issue in parsed[index]?.issues" :key="issue.line" :class="issue.error ? 'error' : 'warning'">
                        ř. {{ issue.line }} „{{ issue.text }}“: {{ issue.message }}
                    </div>
                    <div v-if="missing(index).length" class="warning">Chybí rozdání: {{ missing(index).join(', ') }}</div>
                    <template v-if="scored.matchResults[index]?.lines.length">
                        <div class="total">
                            {{ pairTitle(match, 'ns') }} – {{ pairTitle(match, 'ew') }}:
                            IMP {{ scored.matchResults[index]!.imp_ns }} : {{ scored.matchResults[index]!.imp_ew }},
                            VP {{ vp(index).ns.toFixed(2) }} : {{ vp(index).ew.toFixed(2) }}
                        </div>
                        <details>
                            <summary>Rozpis po rozdáních</summary>
                            <table>
                                <tr><th>#</th><th>Závazek</th><th>Výsl.</th><th>Body NS</th><th>Průměr</th><th>IMP NS</th></tr>
                                <tr v-for="line in scored.matchResults[index]!.lines" :key="line.deal">
                                    <td>{{ line.deal }}</td>
                                    <td>{{ line.contract }} {{ line.declarer }}</td>
                                    <td>{{ line.result }}</td>
                                    <td>{{ line.points }}</td>
                                    <td>{{ scored.averages.get(line.deal) }}</td>
                                    <td>{{ line.imp }}</td>
                                </tr>
                            </table>
                        </details>
                    </template>
                </div>
            </div>
        </div>

        <div class="row">
            <button type="button" @click="addMatch">Přidat zápis</button>
            <button type="button" @click="emit('remove')">Smazat dohrávku</button>
        </div>
    </fieldset>
</template>

<script setup lang="ts">
import { TournamentData } from '@/model/Tournament';
import { PostponedMatchData, PostponedSession, PostponedSessionData, writeSessionToRounds } from '@/model/PostponedSession';
import { ResultOverwritePostponed } from '@/model/Overwrites';
import { calculateVP } from '@/model/VP';
import { parseJackPbn } from '@/parse/JackPbnParser';
import { parseSlip } from '@/parse/SlipParser';
import TournamentApi from '@/api/TournamentApi';
import { photoToJpegBase64 } from '@/utils/image';
import { Ref, computed, inject, reactive, ref, watch } from 'vue';

const props = defineProps({
    tournamentData: {
        type: Object as () => TournamentData,
        required: true
    },
    session: {
        type: Object as () => PostponedSessionData,
        required: true
    },
});
const emit = defineEmits(['remove']);

const placeholder = "Jeden řádek na rozdání, barvy C D H S (S = piky), skóre se dopočítá:\n1 4SW -1\n2 6DW+1\n3 3NTS =\n144SxW-2  (rozdání 14)\n5 pass\n\nU přepisu z fotky (AI) přidejte skóre z lístečku pro kontrolu:\n2 6♦W +1 940";
const pbnSkipped = ref(0);
const password = inject<Ref<string>>('adminPassword', ref(''));
const reading = reactive<Record<number, boolean>>({});
const photoErrors = reactive<Record<number, string>>({});
const photoModels = reactive<Record<number, string>>({});

const parsed = computed(() => props.session.matches.map((m) => parseSlip(m.slip, props.session.boards)));
watch(parsed, (all) => all.forEach((p, i) => {
    const match = props.session.matches[i];
    if (match && JSON.stringify(match.results) !== JSON.stringify(p.results)) match.results = p.results;
}), { immediate: true });

const scored = computed(() => new PostponedSession(props.session));
watch(scored, (s) => writeSessionToRounds(props.tournamentData, s));

async function loadPbn(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;
    const jack = parseJackPbn(await file.text());
    props.session.boards = jack.boards;
    props.session.field = jack.field;
    pbnSkipped.value = jack.skipped;
}

function photoInputId(index: number) {
    return `photo-${props.session.id}-${index}`;
}

function pickPhoto(index: number) {
    (document.getElementById(photoInputId(index)) as HTMLInputElement | null)?.click();
}

async function readPhoto(match: PostponedMatchData, index: number, event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = '';
    if (!file) return;
    if (match.slip.trim() && !window.confirm('Přepsat zapsaný lísteček textem z fotky?')) return;
    if (!password.value) password.value = window.prompt('Heslo k úpravám turnaje') ?? '';
    if (!password.value) return;

    reading[index] = true;
    photoErrors[index] = '';
    photoModels[index] = '';
    try {
        const { text, model } = await TournamentApi.transcribeSlip(await photoToJpegBase64(file), 'image/jpeg', password.value);
        match.slip = text;
        photoModels[index] = model;
    } catch (e: any) {
        photoErrors[index] = e.response?.status === 401
            ? 'Špatné heslo'
            : `Fotku se nepodařilo přečíst: ${e.response?.data?.message ?? e.message}`;
    } finally {
        reading[index] = false;
    }
}

function tables(round: number) {
    const rotation = props.tournamentData.rotations[round.toString()] ?? {};
    const postponed = (props.tournamentData.rounds[round.toString()]?.overwrites ?? [])
        .filter((o): o is ResultOverwritePostponed => o.type === 'postponed')
        .map((o) => o.table);
    return Object.entries(rotation).map(([table, seating]) => ({
        table: Number.parseInt(table),
        label: `${table}: ${props.tournamentData.players[seating.ns]?.title} – ${props.tournamentData.players[seating.ew]?.title}`
            + (postponed.includes(Number.parseInt(table)) ? ' (odloženo)' : ''),
    }));
}

function pairTitle(match: PostponedMatchData, seat: 'ns' | 'ew') {
    const seating = props.tournamentData.rotations[match.round.toString()]?.[match.table.toString()];
    return seating ? props.tournamentData.players[seating[seat]]?.title : '?';
}

function vp(index: number) {
    const r = scored.value.matchResults[index]!;
    return calculateVP(r.imp_ns - r.imp_ew);
}

function missing(index: number): number[] {
    const played = new Set(parsed.value[index]?.results.map((r) => r.deal));
    return scored.value.boardNumbers.filter((b) => !played.has(b));
}

function addMatch() {
    const round = Number.parseInt(Object.keys(props.tournamentData.rounds)[0] ?? '1');
    props.session.matches.push({ round, table: 1, slip: '', results: [] });
}

function removeMatch(index: number) {
    props.session.matches.splice(index, 1);
}
</script>

<style scoped>
.session {
    display: flex;
    flex-direction: column;
    gap: 10px;
    background-color: white;
    border: 1px solid #1a881a;
    border-radius: 5px;
    text-align: left;
}

.match {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding-top: 10px;
    border-top: 1px solid #ccc;
}

.row {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}

.top {
    align-items: flex-start;
}

.slip {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

textarea {
    width: 220px;
    font-family: monospace;
    border: 1px solid black;
}

.report {
    display: flex;
    flex-direction: column;
    gap: 4px;
    flex: 1;
}

.error {
    color: #b00020;
}

.warning {
    color: #a65f00;
}

.total {
    font-weight: bold;
}

td, th {
    padding: 0 6px;
    text-align: right;
}
</style>
