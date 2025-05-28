
<template>
    <div class="flex">
        <div v-if="!roundData" class="flex">
            <div>
                Kolo nebylo nahráno.
            </div>
            <button type="button" @click="createRound">
                Založit kolo bez dat.
            </button>
        </div>
        <template v-else>
            <div class="flex-row" v-if="roundData.date">
                <span> Hráno {{ roundData.date }}</span>
                 <button type="button" @click="addOverwrite">Přidat</button>
            </div>
           
            <div v-if="!roundData.overwrites || roundData.overwrites.length === 0" class="flex">
                Žádné dohrávky nebo změny
            </div>
            <div v-else class="flex-flow">
                <OverwriteEditor class="overwrite" v-for="o, index in roundData.overwrites" :key="index" :overwrite="o"
                    :overwriteTypes="overwriteTypes" :tables="tables" :players="players" @remove="o => remove(o)" />
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { TournamentData } from '@/model/Tournament';
import TournamentFileParser from '@/parse/TournamentFileParser';
import { computed } from 'vue';
import OverwriteEditor from './OverwriteEditor.vue';
import { OverwrittenResult } from '@/model/Overwrites';

const props = defineProps({
    tournamentData: {
        type: Object as () => TournamentData,
        required: true
    },
    round: {
        type: Number,
        required: true
    },
});

const roundData = computed(() => props.tournamentData.rounds[props.round.toString()]);
const tables = computed(() => {
    if (!props.tournamentData.rotations[props.round]) return [];
    // @ts-ignore
    return Object.entries(props.tournamentData.rotations[props.round]).map(([table, rotation]) => {
        const label = table + ": "+  props.tournamentData.players[rotation.ns]?.title + "," + props.tournamentData.players[rotation.ew]?.title;
        return {
            table: Number.parseInt(table),
            label
        };
    }
    );
});
const players = computed( () => {
    return Object.values(props.tournamentData.players).map((p) => {
        return {
            id: p.id,
            title: p.title
        };
    });
});
function createRound() {
    const data = TournamentFileParser.createRoundData(props.round);
    props.tournamentData.rounds[props.round.toString()] = data;
}

const overwriteTypes = [
    { name: "postponed", label: "Dohrávka" },
    { name: "ignore", label: "Přátelák" },
    { name: "player", label: "Náhradník" },
    { name: "imp", label: "Impy +/-" },
    { name: "vp", label: "Přepsat VP" },
    { name: "adjust", label: "Adj/Pen VP" },
];

function addOverwrite() {
    if (!roundData.value) return;
    if (!roundData.value.overwrites) roundData.value.overwrites = [];
    roundData.value.overwrites.push({
        type: "postponed",
        table: 1,
    })
}

function remove(o: OverwrittenResult) {
    if (!roundData.value?.overwrites) return;
    console.log("remove", o);
    roundData.value.overwrites = roundData.value.overwrites?.filter((e) => e !== o);
}

</script>

<style scoped>
.flex {
    display: flex;
    flex-direction: column;
    gap: 10px;
    align-items: center;
    justify-content: center;
}

.flex-flow {
    display: flex;
    flex-direction: row;
    gap: 10px;
    align-items: flex-start;
    justify-content: flex-start;
    flex-wrap: wrap;

}

.short-num-input {
    width: 50px;
}

.overwrite {
    padding: 10px;
    background-color: white;
    border-radius: 5px;
    outline: 1px solid #1a881a;
}



.flex-row {
    display: flex;
    flex-direction: row;
    gap: 10px;
}
</style>