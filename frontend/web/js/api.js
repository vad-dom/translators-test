export const api = {
    async listTranslators() {
        const res = await axios.get('/api/translator/list');
        return res.data.items || [];
    },

    async createTranslator(payload) {
        const res = await axios.post('/api/translator/create', payload);
        return res.data.item;
    },

    async deleteTranslator(id) {
        await axios.post('/api/translator/delete?id=' + encodeURIComponent(id));
    },

    async calendar({ id, offset, limit }) {
        const res = await axios.get('/api/translator/calendar', { params: { id, offset, limit } });
        return res.data;
    },

    async book({ translator_id, date }) {
        const res = await axios.post('/api/translator/book', { translator_id, date });
        return res.data;
    },

    async unbook({ translator_id, date }) {
        const res = await axios.post('/api/translator/unbook', { translator_id, date });
        return res.data;
    },

    async availability(date) {
        const res = await axios.get('/api/translator/availability', { params: { date } });
        return res.data.phrase;
    }
};