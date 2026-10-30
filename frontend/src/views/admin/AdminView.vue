<template>
  <main-layout>
    <template #header>
      Administrace
    </template>
    <template #default>
      <div class="admin-shell">
        <div class="admin-bar">
          <div class="admin-tabs">
            <router-link v-for="tab in tabs" :key="tab.name" :to="{ name: tab.name }"
                         :class="{ active: tab.matches.some((m) => String(route.name ?? '').startsWith(m)) }">
              {{ tab.label }}
            </router-link>
          </div>
          <div class="admin-meta">
            <router-link :to="{ name: 'home' }">Na web</router-link>
            <button v-if="adminKey" class="link-button" @click="lock">Odhlásit</button>
          </div>
        </div>

        <router-view v-if="adminKey" />

        <form v-else class="admin-card unlock" @submit.prevent="submit">
          <h3>Přihlášení</h3>
          <label for="admin-key">Heslo k úpravám</label>
          <input id="admin-key" v-model="password" type="password" required autocomplete="current-password">
          <label class="remember">
            <input v-model="remember" type="checkbox"> Zapamatovat na tomto zařízení
          </label>
          <p v-if="error" class="error">{{ error }}</p>
          <button class="button primary" type="submit" :disabled="busy">Pokračovat</button>
        </form>
      </div>
    </template>
  </main-layout>
</template>

<script setup lang="ts">
import MainLayout from '@/layouts/MainLayout.vue';
import { adminKey, lock, unlock } from '@/utils/adminKey';
import { ref } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const tabs = [
  { name: 'admin-tournament-show', label: 'Skupinovky', matches: ['admin-tournament'] },
  { name: 'admin-bavlnka', label: 'Bavlnka', matches: ['admin-bavlnka'] },
  { name: 'admin-prebor', label: 'Přebory', matches: ['admin-prebor'] },
  { name: 'admin-results', label: 'Výsledky', matches: ['admin-results'] },
  { name: 'admin-banner', label: 'Pozvánka', matches: ['admin-banner'] },
];

const password = ref('');
const remember = ref(false);
const busy = ref(false);
const error = ref('');

async function submit() {
  busy.value = true;
  error.value = '';
  try {
    await unlock(password.value, remember.value);
    password.value = '';
  } catch (e: any) {
    error.value = e?.response?.status === 401 ? 'Špatné heslo' : 'Nepodařilo se ověřit heslo';
  } finally {
    busy.value = false;
  }
}
</script>

<style>
/* Shared by all admin pages. */
.admin-shell {
  text-align: left;
}

.admin-bar {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: flex-end;
  gap: 10px;
  border-bottom: 1px solid #d8dee4;
  margin-bottom: 24px;
}

.admin-tabs {
  display: flex;
  gap: 4px;
}

.admin-tabs a {
  padding: 8px 14px;
  border-bottom: 3px solid transparent;
  margin-bottom: -1px;
  color: #2c3e50;
  font-weight: 600;
}

.admin-tabs a.active {
  border-bottom-color: var(--primary-color);
  color: var(--primary-color);
}

.admin-meta {
  display: flex;
  gap: 16px;
  padding-bottom: 8px;
  font-size: 0.9rem;
}

.admin-shell h2 {
  margin-bottom: 0.5em;
}

.admin-card {
  border: 1px solid #d8dee4;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 20px;
  background: white;
}

.admin-card > h3:first-child {
  margin-top: 0;
}

.admin-head {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  margin-bottom: 16px;
}

.admin-head h2,
.admin-head h3 {
  margin: 0;
}

.admin-shell .muted {
  color: #6a737d;
  font-size: 0.9rem;
}

.admin-shell .error {
  color: rgb(197, 25, 25);
}

.admin-shell .button {
  padding: 5px 12px;
  cursor: pointer;
}

.admin-shell .button.primary {
  background: var(--primary-color);
  border-color: var(--primary-color);
  color: white;
  font-weight: 600;
}

.admin-shell .button:disabled {
  opacity: 0.6;
  cursor: default;
}

.link-button {
  background: none;
  border: none;
  padding: 0;
  font: inherit;
  color: var(--primary-color);
  cursor: pointer;
}

.link-button:hover {
  text-decoration: underline;
}

.link-button.danger {
  color: rgb(197, 25, 25);
}

.link-button:disabled {
  color: #999;
  cursor: default;
  text-decoration: none;
}

.row-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 4px 12px;
  white-space: nowrap;
  font-size: 0.9rem;
}

.admin-shell .table {
  width: 100%;
}

.admin-shell .table-scroll {
  width: 100%;
}

.admin-shell .table th {
  text-align: left;
  background: #f6f8fa;
}

.admin-shell .table td,
.admin-shell .table th {
  border: none;
  border-bottom: 1px solid #e1e4e8;
  padding: 6px 8px;
  vertical-align: top;
}

.admin-shell details > summary {
  cursor: pointer;
  color: var(--primary-color);
  margin: 8px 0;
}

.admin-form {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: 10px 16px;
}

.admin-form label {
  display: flex;
  flex-direction: column;
  gap: 2px;
  font-size: 0.9rem;
}

.admin-form label.wide {
  flex-basis: 100%;
}

.admin-form label.wide input {
  width: 100%;
}

.admin-shell input,
.admin-shell select,
.admin-shell textarea {
  max-width: 100%;
  padding: 4px 6px;
  border: 1px solid #c4ccd4;
  border-radius: 4px;
  font: inherit;
}

.admin-shell textarea {
  font-family: monospace;
  width: 100%;
}

.admin-shell input[type=checkbox],
.admin-shell input[type=radio] {
  padding: 0;
}

.unlock {
  max-width: 360px;
  margin: 40px auto;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.unlock .remember {
  font-size: 0.9rem;
}
</style>
