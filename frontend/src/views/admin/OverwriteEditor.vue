<script setup lang="ts">
import { OverwrittenResult } from '@/model/Overwrites';
import { watch } from 'vue';

const props = defineProps({
    overwrite: {
        type: Object as () => OverwrittenResult,
        required: true
    },
    overwriteTypes: {
        type: Array as () => { name: string, label: string }[],
        required: true
    },
    tables: {
        type: Array as () => { label: string, table: number }[],
        required: true
    },
    players: {
        type: Array as () => { id: number, title: string }[],
        required: true
    }
});

function toggle(o: OverwrittenResult, key: string, defaultValue: any = '') {
    // @ts-ignore
    if (o[key] === undefined) {
        // @ts-ignore
        o[key] = defaultValue;
    } else {
        // @ts-ignore
        delete o[key];
    }
}

const emits = defineEmits(['remove']);

watch(props.overwrite, (newVal) => {
    if (newVal.type === 'adjust' ) {
        // @ts-ignore
        props.overwrite.table = undefined;
    }
    if (newVal.type !== 'adjust') {
        // @ts-ignore
        props.overwrite.participant = undefined;
    }
}, { deep: true });

function remove()
{
    emits('remove', props.overwrite);
}
</script>


<template>
    <div class="overwrite-element">
        <select v-model="overwrite.type" class="col-span-2">
            <option v-for="t in overwriteTypes" :key="t.name" :value="t.name">{{ t.label }}</option>
            <option value="" @click="remove">Smazat</option>
        </select>
           <template v-if="overwrite.type === 'adjust'">
            <select v-model="overwrite.participant" class="col-span-2">
                <option v-for="p in players" :key="p.id" :value="p.id">{{ p.title }}</option>
            </select>
            <label for="adjust-vp-adjustment">VP +/-</label>
            <input type="number" step="0.1" name="adjust-vp-adjustment"  v-model="overwrite.vpAdjustment">
            <label for="adjust-reason">Důvod</label>
            <input type="text" name="adjust-reason" v-model="overwrite.reason">
        </template> 
        <select v-else id="table" v-model="overwrite.table" class="col-span-2">
            <option v-for="t in tables" :key="t.table" :value="t.table">{{ t.label }}</option>
        </select>
        <template v-if="overwrite.type === 'postponed'">
            <button type="button" id="played-toggle" @click="() => { toggle(overwrite, 'imp_ns', 0); toggle(overwrite, 'imp_ew', 0) }">
                    {{ overwrite.imp_ns !== undefined ? 'Výsledek' : "Nehráno" }}</button>
            <div class="flex">
                
                <div v-if="overwrite.imp_ns !== undefined">
                    <input class="short-num-input" v-model="overwrite.imp_ns" type="number" placeholder="NS" />
                    <span>:</span>
                    <input class="short-num-input" v-model="overwrite.imp_ew" type="number" placeholder="NS" />
                </div>
            </div>
            <div>

            <label for="postponed-date-toggle">Datum</label>
            <button type="button" id="postponed-date-toggle" @click="() => toggle(overwrite, 'date', new Date().toISOString().substring(0,10))">
                    {{ overwrite.date !== undefined ? 'X' : "+" }}</button>
            </div>
            <div>
                <input v-if="overwrite.date !== undefined" type="date" v-model="overwrite.date">
            </div>
            <div>
            <label for="postponed-external-toggle">Odkaz</label>
            <button type="button" id="postponed-external-toggle" @click="() => toggle(overwrite, 'externalUrl')">
                    {{ overwrite.externalUrl !== undefined ? 'X' : "+" }}</button>
            </div>
            <div>
                <input class="url" v-if="overwrite.externalUrl !== undefined" type="text" v-model="overwrite.externalUrl">
            </div>
        </template>
        <template v-if="overwrite.type === 'vp'">
            <div>
            <label for="postponed-vp-ns-toggle">VP NS</label>
            <button type="button" id="postponed-vp-ns-toggle" @click="() => toggle(overwrite, 'vp_ns')">
                    {{ overwrite.vp_ns !== undefined ? 'X' : "+" }}</button>
            </div>
            <div>
                <input v-if="overwrite.vp_ns !== undefined" type="number" step="0.01" v-model="overwrite.vp_ns">
            </div>
            <div>
            <label for="postponed-vp-ew-toggle">VP EW</label>
            <button type="button" id="postponed-vp-ew-toggle" @click="() => toggle(overwrite, 'vp_ew')">
                    {{ overwrite.vp_ew !== undefined ? 'X' : "+" }}</button>
            </div>
            <div>
                <input v-if="overwrite.vp_ew !== undefined" type="number" step="0.01" v-model="overwrite.vp_ew">
            </div>
        </template>
        <template v-if="overwrite.type === 'imp'">
            <div>
            <label for="postponed-vp-ns-toggle">IMP NS &pm;</label>
            <button type="button" id="postponed-vp-ns-toggle" @click="() => toggle(overwrite, 'imp_diff_ns')">
                    {{ overwrite.imp_diff_ns !== undefined ? 'X' : "+" }}</button>
            </div>
            <div>
                <input v-if="overwrite.imp_diff_ns !== undefined" type="number" v-model="overwrite.imp_diff_ns">
            </div>
            <div>
            <label for="postponed-vp-ew-toggle">IMP EW &pm;</label>
            <button type="button" id="postponed-vp-ew-toggle" @click="() => toggle(overwrite, 'imp_diff_ew')">
                    {{ overwrite.imp_diff_ew !== undefined ? 'X' : "+" }}</button>
            </div>
            <div>
                <input v-if="overwrite.imp_diff_ew !== undefined" type="number" v-model="overwrite.imp_diff_ew">
            </div>
        </template>
        <template v-if="overwrite.type === 'player'">
            <div>
            <label for="postponed-vp-ns-toggle">NS název</label>
            <button type="button" id="postponed-vp-ns-toggle" @click="() => toggle(overwrite, 'ns')">
                    {{ overwrite.ns !== undefined ? 'X' : "+" }}</button>
            </div>
            <div>
                <input v-if="overwrite.ns !== undefined" type="text" v-model="overwrite.ns" placeholder="Novotný (N) - Novák">
            </div>
            <div>
            <label for="postponed-vp-ew-toggle">EW název</label>
            <button type="button" id="postponed-vp-ew-toggle" @click="() => toggle(overwrite, 'ew')">
                    {{ overwrite.ew !== undefined ? 'X' : "+" }}</button>
            </div>
            <div>
                <input v-if="overwrite.ew !== undefined" type="text" v-model="overwrite.ew" placeholder="Novotný (N) - Novák">
            </div>
        </template>
    </div>
</template>

<style scoped>
.overwrite-element {
    display: grid;
    grid-template-columns: minmax(100px, auto) minmax(100px, auto);
    gap: 0.5rem;
    width: 100%;
    max-width: 300px;
    align-items: center;
}

.col-span-2 {
    grid-column: span 2;
    width: 100%;
}

input, select {
    width: 100%;
    box-sizing: border-box;
}

.short-num-input {
    width: 45px;
}

.url {
    width: 100%;
    max-width: 200px;
}

.flex {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

button {
    white-space: nowrap;
}

label {
    white-space: nowrap;
    margin-right: 0.5rem;
}
</style>