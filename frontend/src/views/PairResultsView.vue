<template>
  <div>
    <router-link :to="{ name: 'tournament-results' }" class="back">{{ $t('tournament.backStandings') }}</router-link>
    <div class="title-row">
      <h2 class="page-title">{{ pair?.title }}</h2>
      <follow-button :active="following" :label="$t('settings.followPair')" :active-label="$t('settings.followingPair')"
                     @toggle="setFollowedPair(tournamentKey, following ? undefined : pairNum)" />
    </div>
    <section class="card table-card pair-card">
      <tournament-pair-results v-if="tournament" :pair="pairNum" :tournament="tournament" />
    </section>
  </div>
</template>

<script setup lang="ts">
import TournamentPairResults from '@/components/TournamentPairResults.vue';
import FollowButton from '@/components/partial/FollowButton.vue';
import { Tournament } from '@/model/Tournament';
import { Ref, computed, inject } from 'vue';
import { useRoute } from 'vue-router';
import { followedPair, setFollowedPair } from '@/settings';

const route = useRoute();
const tournament = inject('tournament') as Ref<Tournament | undefined>;

  const pairNum = computed( ( ) => parseInt(route.params['pair'] as string));
const pair = computed( () =>  tournament.value?.getPair(pairNum.value));

const tournamentKey = computed(() => String(route.params.tournament));
const following = computed(() => followedPair(tournamentKey.value) === pairNum.value);

</script>

<style scoped>
.title-row {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  align-items: center;
  gap: 6px 14px;
  margin-bottom: 6px;
}

.title-row .page-title {
  margin: 0;
}
</style>
