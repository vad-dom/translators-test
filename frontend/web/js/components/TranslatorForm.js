export default {
  name: 'TranslatorForm',
  emits: ['create'],

  setup(_, {emit}) {
    const {ref, computed} = Vue;
    const today = new Date().toISOString().slice(0, 10);

    const name = ref('');
    const work_mode = ref(1);
    const bookable_until = ref(today);

    const canSubmit = computed(() => name.value.trim().length > 0 && !!bookable_until.value);

    function submit() {
      if (!canSubmit.value) return;
      emit('create', {
        name: name.value.trim(),
        work_mode: Number(work_mode.value),
        bookable_until: bookable_until.value
      });
      name.value = '';
    }

    return {name, work_mode, bookable_until, canSubmit, submit};
  },

  template: `
    <div>
      <h3>Новый переводчик</h3>
      <div class="row" style="padding: 12px;">
        <input v-model="name" placeholder="Имя" style="min-width:220px;">
        <select v-model.number="work_mode">
          <option :value="1">Работает в будни</option>
          <option :value="2">Работает в выходные</option>
        </select>
        <input v-model="bookable_until" type="date">
        <div class="small">
          максимальная дата, до которой можно бронировать
        </div>
        <button :disabled="!canSubmit" @click="submit">Добавить</button>
      </div>
    </div>
  `
};