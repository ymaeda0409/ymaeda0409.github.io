import { createApp } from 'vue';
import App from './App.vue';
import { makeI18n } from '../shared/i18n';

createApp(App).use(makeI18n()).mount('#kitchen-app');
