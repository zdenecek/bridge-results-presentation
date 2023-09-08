<template>
    <div>
      <h2>Vytvořit turnaj</h2>

      <form @submit.prevent="submit">
        <div class="fields">
        <label for="key">Heslo</label>
        <input id="key" type="password" v-model="password" required>
        <label for="title">Název turnaje</label>
        <input id="title" type="text" v-model="title" required>
        <label for="title">slug (název v url adrese)</label>
        <input id="title" type="text" v-model="slug" required>
        
        </div>
        <input type="submit">
      </form>
    </div>
</template>

<script setup lang="ts">
import TournamentApi from '@/api/TournamentApi';
import { ref  } from 'vue';
import { useRouter } from 'vue-router';

const title = ref("");
const slug = ref("");
const password = ref("");

const router = useRouter();

function submit() {
  TournamentApi.createTournament(title.value, slug.value, password.value).then((id) => {
    console.debug('Tournament created');
    router.push({ name: 'admin-tournament-edit', params: { tournament: id } });

  }).catch((e) => {
    console.debug('Error creating tournament');
    console.error(e);
  });
}

</script>

<style>
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
    grid-template-columns: 2fr 3fr;
    gap: 10px;
}
</style>