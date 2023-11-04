import { createApp } from "vue";
import App from "./App.vue";

import router from "./router";
import axios from "axios";
import PrimeVue from 'primevue/config';
import "primevue/resources/themes/saga-green/theme.css";


axios.defaults.headers.common["Access-Control-Allow-Origin"] = "*";


createApp(App)
    .use(router)
    .use(PrimeVue)
    .mount("#app");
