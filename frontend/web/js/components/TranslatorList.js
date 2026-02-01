import {formatLocalDate} from "../utils/date.js";

export default {
  name: 'TranslatorList',
  props: {
    items: {type: Array, required: true},
    loading: {type: Boolean, default: false},
    selectedId: {type: Number, default: null}
  },
  emits: ['refresh', 'select', 'delete'],

  methods: {
    selectItem(t) {
      this.$emit('select', t);
    },
    refresh() {
      this.$emit('refresh');
    },
    del(id) {
      this.$emit('delete', id);
    },

    formatLocalDate
  },

  template: `
    <div>
      <h3 style="margin:0;">Список переводчиков</h3>
      <button @click="refresh" style="margin:8px 0; width:100%;">Обновить</button>

      <div v-if="loading">Загрузка...</div>

      <div v-else>
        <div v-if="items.length === 0">Пока нет переводчиков</div>
        <ul class="list">
          <li
            v-for="t in items"
            :key="t.id"
            class="listItem"
            :class="{ active: selectedId === t.id }"
            @click="selectItem(t)"
          >
            <div><b>{{ t.name }}</b></div>
            <div class="small">
              {{ Number(t.work_mode) === 1 ? 'Будни' : 'Выходные' }} • до {{ formatLocalDate(t.bookable_until) }}
            </div>
            <div style="margin-top:6px;">
              <button @click.stop="del(t.id)">Удалить</button>
            </div>
          </li>
        </ul>
      </div>
    </div>
  `
};