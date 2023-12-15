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
                                <input type="date" v-model="data.rounds[round].date" v-if="data.rounds[round]" />
                                <button type="button" v-else @click="() => createRound(round)">Vytvořit prázdné</button>
                            </div>
                            <input multiple="true" accept=".txt,.csv,.pbn" :id="'fileinput-' + round" type="file"
                                @input="(e) => addFilesToRound(round, e as InputEvent)">
                        </template>
                    </template>
                </div>
            </tab-panel>
            <tab-panel v-for="r in tournamentData.totalRounds" :key="r" :header="r + '. kolo'">
                <tournament-round-editor :tournament-data="tournamentData" :round="r"></tournament-round-editor>
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
import { ref, watchEffect } from 'vue';
import TournamentFileParser from '@/parse/TournamentFileParser';
import TournamentRoundEditor from '@/views/admin/TournamentRoundEditor.vue';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';

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

    for (let i = 0; i < files.length; i++) {
        try {

            data.value = await TournamentFileParser.applyFile(files[i], data.value, round);
        }
        catch (e) {
            console.debug('Error parsing file' + files[i].name);
            console.error(e);
        }
    }
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