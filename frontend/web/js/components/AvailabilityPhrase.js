import {api} from '../api.js';

export default {
  name: 'AvailabilityPhrase',

  setup() {
    const {ref} = Vue;
    const today = new Date().toISOString().slice(0, 10); // UTC дата используется для упрощения
    const date = ref(today);
    const phrase = ref('');

    async function load() {
      phrase.value = '';
      try {
        phrase.value = await api.availability(date.value);
      } catch (e) {
        phrase.value = 'Ошибка получения фразы';
      }
    }

    return {date, phrase, load};
  },

  template: `
    <div class="phraseCard">
      <h4 style="margin:0 0 6px 0;">Фраза из модуля по API</h4>
      <div class="row" style="padding: 12px;">
        <input v-model="date" type="date">
        <button @click="load">Получить фразу</button>
        <span v-if="phrase">{{ phrase }}</span>
      </div>
    </div>
  `
};