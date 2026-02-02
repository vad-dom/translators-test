import TranslatorForm from './TranslatorForm.js';
import TranslatorList from './TranslatorList.js';
import TranslatorCalendar from './TranslatorCalendar.js';
import AvailabilityPhrase from './AvailabilityPhrase.js';
import {api} from '../api.js';

export default {
  name: 'App',
  components: {TranslatorForm, TranslatorList, TranslatorCalendar, AvailabilityPhrase},

  setup() {
    const {ref, onMounted} = Vue;

    const translators = ref([]);
    const loadingTranslators = ref(false);
    const selected = ref(null);

    const info = ref('');
    const error = ref('');

    async function loadTranslators() {
      error.value = '';
      loadingTranslators.value = true;
      try {
        translators.value = await api.listTranslators();
      } catch (e) {
        error.value = 'Не удалось загрузить список переводчиков';
      } finally {
        loadingTranslators.value = false;
      }
    }

    async function onCreateTranslator(payload) {
      info.value = '';
      error.value = '';
      try {
        await api.createTranslator(payload);
        info.value = 'Переводчик добавлен';
        await loadTranslators();
      } catch (e) {
        error.value = 'Не удалось добавить переводчика';
      }
    }

    async function onDeleteTranslator(id) {
      info.value = '';
      error.value = '';
      try {
        await api.deleteTranslator(id);
        if (selected.value && selected.value.id === id) selected.value = null;
        info.value = 'Переводчик удалён';
        await loadTranslators();
      } catch (e) {
        error.value = 'Не удалось удалить переводчика';
      }
    }

    function onSelectTranslator(t) {
      info.value = '';
      error.value = '';
      selected.value = t;
    }

    function onCalendarInfo(msg) {
      info.value = msg || '';
    }

    function onCalendarError(msg) {
      error.value = msg || '';
    }

    onMounted(loadTranslators);

    return {
      translators, loadingTranslators, selected,
      info, error,
      loadTranslators, onCreateTranslator, onDeleteTranslator, onSelectTranslator,
      onCalendarInfo, onCalendarError
    };
  },

  template: `
    <div class="container">
      <h1 class="h1">Бронирование переводчиков</h1>

      <div class="card" style="margin-bottom:16px;">
        <TranslatorForm @create="onCreateTranslator" />
        <div v-if="error" class="msg msgErr">{{ error }}</div>
        <div v-else-if="info" class="msg msgOk">{{ info }}</div>
        <div v-else class="msg msgMuted">Здесь можно увидеть статус выполнения операций</div>
      </div>

      <div class="grid">
        <div class="card">
          <TranslatorList
            :items="translators"
            :loading="loadingTranslators"
            :selectedId="selected ? selected.id : null"
            @refresh="loadTranslators"
            @select="onSelectTranslator"
            @delete="onDeleteTranslator"
          />
        </div>

        <div class="card">
          <TranslatorCalendar
            v-if="selected"
            :translator="selected"
            @info="onCalendarInfo"
            @error="onCalendarError"
          />
          <div v-else>Выберите переводчика слева</div>

          <div class="hr"></div>

          <AvailabilityPhrase />
        </div>
      </div>
    </div>
  `
};