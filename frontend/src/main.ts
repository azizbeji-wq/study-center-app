import { createApp } from 'vue';
import App from './app/App.vue';
import './styles/index.css';
import router from './app/router';
import { createPinia } from 'pinia';

const app = createApp(App);
app.use(createPinia());
app.use(router);
app.mount('#app');
