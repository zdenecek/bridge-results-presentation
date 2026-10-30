<template>
  <div>
    <div class="admin-head">
      <h2>Pozvánka nahoře na webu</h2>
    </div>
    <p class="muted">
      Zelený pruh nad výsledky, třeba s pozvánkou na Velkou cenu Prahy. Ukazuje se na všech veřejných stránkách,
      dokud je zapnutý, nejdéle do zadaného dne. Kdo ho křížkem skryje, uvidí ho znovu až u nové pozvánky.
    </p>

    <form v-if="form" class="admin-card admin-form" @submit.prevent="save">
      <label class="check"><input v-model="form.enabled" type="checkbox"> Zobrazovat na webu</label>
      <label class="wide">Nadpis <input v-model="form.title" :required="form.enabled" maxlength="255" placeholder="Velká cena Prahy 2026"></label>
      <label class="wide">Text <input v-model="form.text" maxlength="500" placeholder="Termín, místo, do kdy se hlásit"></label>
      <label class="grow">Odkaz <input v-model="form.url" type="url" maxlength="500" placeholder="https://www.bkpraha.cz/…"></label>
      <label>Text tlačítka <input v-model="form.linkLabel" maxlength="100" placeholder="Více informací" size="16"></label>
      <label>Zobrazovat do <input v-model="form.until" type="date"></label>
      <button class="button primary" type="submit" :disabled="busy">Uložit</button>
      <p v-if="message" :class="isError ? 'error' : 'muted'" class="wide">{{ message }}</p>
    </form>

    <template v-if="form?.title">
      <h3>Náhled</h3>
      <div class="preview">
        <SiteBanner :preview="form" />
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import BannerApi, { BannerSettings } from '@/api/BannerApi';
import SiteBanner from '@/components/SiteBanner.vue';
import { adminKey } from '@/utils/adminKey';
import { ref } from 'vue';

const form = ref<BannerSettings>();
const busy = ref(false);
const message = ref('');
const isError = ref(false);

BannerApi.getSettings(adminKey.value).then((data) => form.value = data).catch((e) => fail(e));

function fail(e: any) {
  isError.value = true;
  message.value = e?.response?.data?.message ?? e?.message ?? String(e);
}

async function save() {
  if (!form.value) return;
  busy.value = true;
  message.value = '';
  try {
    form.value = await BannerApi.save(adminKey.value, { ...form.value, until: form.value.until || null });
    isError.value = false;
    message.value = form.value.enabled ? 'Uloženo, pozvánka je na webu (do pěti minut).' : 'Uloženo, pozvánka je skrytá.';
  } catch (e) {
    fail(e);
  } finally {
    busy.value = false;
  }
}
</script>

<style scoped>
.admin-form label.check {
  flex-direction: row;
  align-items: center;
  gap: 8px;
  width: 100%;
  font-weight: 600;
}

.admin-form .grow {
  flex: 1;
  min-width: 240px;
}

.admin-form p.wide {
  width: 100%;
  margin: 0;
}

.preview {
  overflow: hidden;
  border-radius: 8px;
}
</style>
