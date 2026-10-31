import { createApp } from "vue";
import App from "./App.vue";

import router from "./router";
import { i18n } from "./i18n";
import axios from "axios";
import PrimeVue from 'primevue/config';
import "primevue/resources/themes/saga-green/theme.css";


axios.defaults.headers.common["Access-Control-Allow-Origin"] = "*";



createApp(App)
    .use(i18n)
    .use(router)
    .use(PrimeVue)
    .mount("#app");
