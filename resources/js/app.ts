import './bootstrap';

import { createApp } from 'vue'
import App from './src/App.vue'
import router from './src/router';
import ToastPlugin from 'vue-toast-notification';
// Import one of the available themes
//import 'vue-toast-notification/dist/theme-default.css';
import 'vue-toast-notification/dist/theme-bootstrap.css';
import { createPinia } from 'pinia';

createApp(App)
    .use(router)
    .use(createPinia())
    .use(ToastPlugin)
    .mount("#app")