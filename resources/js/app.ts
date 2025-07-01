import "./bootstrap";
import "../css/drag-drop.css";

import { createApp } from "vue";
import App from "./src/App.vue";
import router from "./src/router";
import { createPinia } from "pinia";
import Error from "./src/components/ErrorMessage.vue";
import BaseInput from "./src/components/BaseInput.vue";
import BaseBtn from "./src/components/BaseBtn.vue";
import CustomPagination from "./src/components/CustomPagination.vue";
import VueApexCharts from "vue3-apexcharts";

createApp(App)
  .use(router)
  .use(createPinia())
  .component("apexchart", VueApexCharts)
  .component("Error", Error)
  .component("BaseInput", BaseInput)
  .component("BaseBtn", BaseBtn)
  .component("CustomPagination", CustomPagination)
  .mount("#app");
