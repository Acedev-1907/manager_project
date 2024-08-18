import './bootstrap';

import { createApp } from 'vue'
import App from './src/App.vue'
import router from './src/router';
import ToastPlugin from 'vue-toast-notification';
// Import one of the available themes
//import 'vue-toast-notification/dist/theme-default.css';
import 'vue-toast-notification/dist/theme-bootstrap.css';

createApp(App)
    .use(router)
    .use(ToastPlugin)
    .mount("#app")