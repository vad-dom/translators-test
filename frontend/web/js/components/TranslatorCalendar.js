import {api} from '../api.js';
import {formatLocalDate} from "../utils/date.js";

export default {
  name: 'TranslatorCalendar',
  props: {translator: {type: Object, required: true}},
  emits: ['info', 'error'],

  setup(props, {emit}) {
    const {ref, watch, computed} = Vue;

    const dates = ref([]);
    const offset = ref(0);
    const pageSize = ref(30);
    const isEnd = ref(false);
    const loading = ref(false);

    const header = computed(() => {
      const t = props.translator;
      const mode = Number(t.work_mode);
      const until = formatLocalDate(t.bookable_until);
      return `${t.name} — ${mode === 1 ? 'работает в будни' : 'работает в выходные'}, до ${until}`;
    });

    async function reload() {
      dates.value = [];
      offset.value = 0;
      isEnd.value = false;
      await loadMore();
    }

    async function loadMore() {
      if (loading.value || isEnd.value) return;
      loading.value = true;
      emit('error', '');
      emit('info', '');
      try {
        const res = await api.calendar({id: props.translator.id, offset: offset.value, limit: pageSize.value});
        const chunk = res.dates || [];
        dates.value = chunk;
        isEnd.value = !!res.is_end;
        offset.value += chunk.length;
      } catch (e) {
        emit('error', 'Не удалось загрузить календарь');
      } finally {
        loading.value = false;
      }
    }

    async function bookDay(date) {
      emit('error', '');
      emit('info', '');
      try {
        await api.book({translator_id: props.translator.id, date});
        const item = dates.value.find(x => x.date === date);
        if (item) item.busy = true;
        emit('info', 'Забронировано: ' + formatLocalDate(date));
      } catch (e) {
        if (e.response && e.response.status === 409) {
          emit('error', e.response.data?.message || 'Кто-то уже занял эту дату. Обновите страницу и попробуйте снова.');
          return;
        }
        emit('error', 'Не удалось забронировать');
      }
    }

    async function unbookDay(date) {
      emit('error', '');
      emit('info', '');
      try {
        await api.unbook({translator_id: props.translator.id, date});
        const item = dates.value.find(x => x.date === date);
        if (item) item.busy = false;
        emit('info', 'Отменено: ' + formatLocalDate(date));
      } catch (e) {
        emit('error', 'Не удалось отменить бронь');
      }
    }

    watch(() => props.translator?.id, reload, {immediate: true});

    return {dates, pageSize, isEnd, loading, header, reload, loadMore, bookDay, unbookDay, formatLocalDate};
  },

  template: `
    <div>
      <h3 style="margin-top:0;">Календарь</h3>
      <div style="margin-bottom:8px;"><b>{{ header }}</b></div>

      <div class="row" style="margin-bottom:8px;">
        <button @click="loadMore" :disabled="loading || isEnd">Загрузить следующие ({{ pageSize }} дней)</button>
        <button @click="reload" :disabled="loading">С начала (сегодня)</button>
        <span v-if="loading" class="small">Загрузка...</span>
        <span v-if="isEnd" class="small">Достигнут конец диапазона</span>
      </div>

      <div v-for="d in dates" :key="d.date" class="calendarRow" :style="{opacity: d.allowed ? 1 : 0.5}">
        <div>
          <div>
            <b>{{ formatLocalDate(d.date) }}</b> <span class="small">{{ d.is_weekend ? 'выходной' : 'будний' }}</span>
          </div>
          <div class="small">
            {{ d.allowed ? 'Может работать в этот день' : 'Нерабочий день для этого переводчика' }}
          </div>
        </div>

        <div>
          <template v-if="d.allowed">
            <span v-if="d.busy" class="badgeBusy">занят</span>
            <span v-else class="badgeFree">свободен</span>
  
            <button v-if="!d.busy" @click="bookDay(d.date)" class="bookButton">Забронировать</button>
            <button v-if="d.busy" @click="unbookDay(d.date)" class="bookButton">Отменить</button>
          </template>
        </div>
      </div>
    </div>
  `
};