<template>
    <div class="sessions">
        <p class="small">
            Dohrávky hrané na rozdáních odehraných Jackem. Průměr rozdání se počítá z výsledků Jacka a všech zápisů
            dohrávky, IMPy se samy propíšou do dohrávky v příslušném kole.
        </p>
        <PostponedSessionEditor v-for="session in sessions" :key="session.id" :session="session"
            :tournament-data="tournamentData" @remove="remove(session)" />
        <button type="button" @click="add">Nová dohrávka</button>
    </div>
</template>

<script setup lang="ts">
import { TournamentData } from '@/model/Tournament';
import { PostponedSession, PostponedSessionData, writeSessionToRounds } from '@/model/PostponedSession';
import { computed } from 'vue';
import PostponedSessionEditor from './PostponedSessionEditor.vue';

const props = defineProps({
    tournamentData: {
        type: Object as () => TournamentData,
        required: true
    },
});

const sessions = computed(() => props.tournamentData.postponedSessions ?? []);

function add() {
    const date = new Date().toISOString().substring(0, 10);
    const taken = new Set(sessions.value.map((s) => s.id));
    let id = date;
    for (let i = 2; taken.has(id); i++) id = `${date}-${i}`;
    props.tournamentData.postponedSessions = [
        ...sessions.value,
        { id, date, boards: {}, field: [], matches: [] },
    ];
}

function remove(session: PostponedSessionData) {
    if (!window.confirm(`Smazat dohrávku ${session.id}?`)) return;
    writeSessionToRounds(props.tournamentData, new PostponedSession({ ...session, matches: [] }));
    props.tournamentData.postponedSessions = sessions.value.filter((s) => s.id !== session.id);
}
</script>

<style scoped>
.sessions {
    display: flex;
    flex-direction: column;
    gap: 20px;
    align-items: stretch;
}

.small {
    font-size: 0.8em;
}
</style>
