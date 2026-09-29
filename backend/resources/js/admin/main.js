import { createApp } from 'vue';
import App from './App.vue';
import { makeI18n } from '../shared/i18n';
import { adminMessages } from './messages';

createApp(App).use(makeI18n(undefined, adminMessages)).mount('#admin-app');
