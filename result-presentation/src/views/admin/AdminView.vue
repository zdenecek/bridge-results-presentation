<template>
    <main-layout>
        <template #header>
            Admin
        </template>
        <template #default>
            <nav>
                <router-link :to="{ name: 'home' }">Domů</router-link>
              <router-link :to="{ name: 'admin-tournament-create' }">Vytvořit turnaj</router-link>
              <router-link :to="{ name: 'admin-tournament-show' }">Seznam turnajů</router-link>
            </nav>

            <router-view/>
        </template>

        <template #footer>

        </template>
    </main-layout>
</template>

<script setup lang="ts">
import MainLayout from '@/layouts/MainLayout.vue';
// import { ref } from 'vue';


</script>

<style></style>

