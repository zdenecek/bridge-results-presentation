<template>
    <div class="flex flex-column align-center gap-small">
        <TabView class="tabs">
            <tab-panel header="Přehled">
                <div class="grid">
                    <div> Kolo </div>
                    <div> Výsledky </div>
                    <div> Rozdání </div>
                    <div> Průměry </div>
                    <div> Datum kola </div>
                    <div> Nahrát soubory </div>
                    <div></div>
                    <div class="small"> Opis_Rozdani_Excel </div>
                    <div class="small"> .pbn </div>
                    <div class="small"> Opis_Rozdani </div>
                    <div></div>
                    <div class="small">jeden či více souborů</div>
                    <template v-if="data?.rounds">
                        <template v-for="round in data?.totalRounds ?? 0" :key="round">

                            <div> {{ round }}</div>
                            <div class="horizontal">
                                <checkmark class="tick" :value="results(round) > 1" />
                                <span v-show="results(round)">
                                    {{ results(round) }}
                                </span>
                            </div>
                            <checkmark class="tick" :value="deals(round) > 1" />
                            <checkmark class="tick" :value="averages(round) > 1" />
                            <div>
                                <input type="date" v-if="data.rounds[round]" v-model="// @ts-ignore
                                  data.rounds[round].date" />
                                <button type="button" v-else @click="() => createRound(round)">Vytvořit prázdné</button>
                            </div>
                            <div><input multiple="true" accept=".txt,.csv,.pbn" :id="'fileinput-' + round" type="file"
                                @input="(e) => addFilesToRound(round, e as InputEvent)">
                            <button type="button" v-if="hasAverages(round)" @click="copyAverages(round)">Kopírovat průměry</button>
                            </div>
                      
                        </template>
                    </template>
                </div>
                <div v-if="ddStatus">{{ ddStatus }}</div>
            </tab-panel>
            <tab-panel v-for="r in tournamentData.totalRounds" :key="r" :header="r + '. kolo'">
                <tournament-round-editor :tournament-data="tournamentData" :round="r"></tournament-round-editor>
            </tab-panel>
            <tab-panel header="Dohrávky">
                <postponed-sessions-editor :tournament-data="tournamentData"></postponed-sessions-editor>
            </tab-panel>
        </TabView>

        <div class="horizontal">
            <input id="advanced" type="checkbox" v-model="showData">
            <label for="advanced">Pokročilý režim</label>
        </div>
        <div class="flex" v-show="showData">
            <checkmark class="tick" :value="jsonState" />
            <textarea v-model="dataJson" @input="update"> </textarea>
        </div>
    </div>
</template>


<script setup lang="ts">
import Checkmark from '@/components/partial/CheckmarkPartial.vue';
import { TournamentData } from '@/model/Tournament';
import type { Board } from '@/model/Board';
import { computed, ref, watch, watchEffect } from 'vue';
import TournamentFileParser from '@/parse/TournamentFileParser';
import TournamentRoundEditor from '@/views/admin/TournamentRoundEditor.vue';
import PostponedSessionsEditor from '@/views/admin/PostponedSessionsEditor.vue';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import { analyseBoards, ddsWorker, missingAnalysis, needsAnalysis } from '@/utils/ddAnalysis';

function results(round: number): number {
    return data.value?.rounds[round]?.boardResults?.length ?? 0;
}
function deals(round: number): number {
    const boards = data.value?.rounds?.[round]?.boards;
    if (!boards) return 0;
    return Object.keys(boards).length;
}
function averages(round: number): number {
    const averages = data.value?.rounds?.[round]?.averages;
    if (!averages) return 0;
    return Object.keys(averages).length;
}
const data = ref({} as TournamentData);

const showData = ref(false);
const dataJson = ref('');

const jsonState = ref(true);

const emit = defineEmits(['change'])

const props = defineProps({
    tournamentData: {
        type: Object as () => TournamentData,
        required: true
    }
});

function createRound(round: number) {
    const data = TournamentFileParser.createRoundData(round);
    props.tournamentData.rounds[round.toString()] = data;
}

watchEffect(() => {
    dataJson.value = JSON.stringify(props.tournamentData, null, 2);
    data.value = props.tournamentData;
    jsonState.value = true;
});

function update() {
    try {
        data.value = JSON.parse(dataJson.value);
        jsonState.value = true;
    } catch (e) {
        console.debug('Error parsing json');
        console.error(e);
        jsonState.value = false;
    }
}

watchEffect(() => {
    emit('change', data.value);
});



async function addFilesToRound(round: number, event: InputEvent) {
    const files = (event.target as HTMLInputElement).files;
    if (!files) {
        return;
    }

    for (const file of files) {
        if (!file) continue;
        try {
            data.value = await TournamentFileParser.applyFile(file, data.value, round);
        }
        catch (e) {
            console.debug('Error parsing file ' + file.name);
            console.error(e);
        }
    }
}

const missingDd = computed(() => missingAnalysis(data.value?.rounds));
const ddStatus = ref('');
let ddRunning = false;
// A board that failed once is not retried, so a bad deal cannot loop.
const ddAttempted = new WeakSet<Board>();

/** Computes the double dummy table and par of boards whose PBN came without them. */
async function runDd() {
    if (ddRunning) return;
    ddRunning = true;
    let analysed = 0;
    let failed = 0;
    try {
        for (;;) {
            const boards = Object.values(data.value.rounds ?? {})
                .flatMap((round) => Object.values(round.boards ?? {}))
                .filter((board) => needsAnalysis(board) && !ddAttempted.has(board));
            if (boards.length === 0) break;
            boards.forEach((board) => ddAttempted.add(board));
            ddStatus.value = 'Načítám double dummy…';
            failed += await analyseBoards(boards, await ddsWorker(),
                (done, total) => ddStatus.value = `Počítám double dummy: ${done}/${total}`);
            analysed += boards.length;
        }
        if (analysed > 0) {
            ddStatus.value = failed
                ? `Double dummy: ${failed} rozdání se nepodařilo spočítat, ostatní ulož.`
                : `Double dummy spočítán u ${analysed} rozdání, ulož změny.`;
        }
    } catch (e) {
        console.error(e);
        ddStatus.value = 'Double dummy se nepodařilo načíst.';
    } finally {
        ddRunning = false;
    }
}

watch(missingDd, (missing) => {
    if (missing > 0) void runDd();
}, { immediate: true });

function hasAverages(round: number): boolean {
    return data.value?.rounds?.[round.toString()]?.averages !== undefined;
}

function copyAverages(round: number) {
    const averages = data.value?.rounds?.[round]?.averages;
    if (!averages) return;
    const str = Object.entries(averages).map(([k,v]) => `${k}\t${v}`).join('\n');
    const input = document.createElement('textarea');
    input.value = str;
    document.body.appendChild(input);
    input.select();
    document.execCommand('copy');
    document.body.removeChild(input);
}


</script>


<style scoped>
.tabs {
    width: 1000px;
}

.horizontal {
    display: flex;
    flex-direction: row;
    gap: 10px;
    align-items: center;
}

.grid {
    display: inline-grid;
    grid-template-columns: repeat(4, 1fr) auto auto;
    gap: 10px;
    align-items: center;
    justify-items: center;
    margin-bottom: 20px;
}

.tick {
    width: 30px;
    height: 30px;
}

textarea {
    width: 600px;
    height: 400px;

    border: 1px solid black;
}

.small {
    font-size: 10px;
}
</style>