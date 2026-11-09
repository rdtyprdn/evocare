const ROLE_KEYS = ['relawan', 'donatur', 'peserta'];
const MAX_QUESTIONS = 20;
let uid = 0;

const make = (q = {}) => ({
    key: ++uid,
    id: q.id ?? null,
    label: q.label ?? '',
    type: q.type ?? 'text',
    options: Array.isArray(q.options) ? q.options.join('\n') : (q.options ?? ''),
    required: !!q.required,
    roles: q.roles && q.roles.length ? [...q.roles] : [...ROLE_KEYS],
    answers: q.answers ?? 0,
});

export default (initial = [], presets = {}, categorySlugs = {}) => ({
    questions: initial.map(make),
    notice: null,
    error: null,
    max: MAX_QUESTIONS,
    categoryName() {
        const sel = document.getElementById('category_id');
        return sel?.selectedOptions?.[0]?.textContent?.trim() ?? '';
    },
    add() {
        this.notice = null;
        if (this.questions.length >= MAX_QUESTIONS) { this.error = 'Pertanyaan pendaftaran maksimal ' + MAX_QUESTIONS + ' butir.'; return; }
        this.error = null;
        this.questions.push(make());
        this.$nextTick(() => {
            const inputs = this.$el.querySelectorAll('[data-question-label]');
            inputs[inputs.length - 1]?.focus();
        });
    },
    remove(i) {
        const q = this.questions[i];
        if (q.answers > 0 && !window.confirm('Pertanyaan ini sudah dijawab ' + q.answers + ' pendaftar. Jawaban lama tetap tersimpan, tetapi pertanyaan tidak akan ditanyakan lagi. Hapus pertanyaan?')) return;
        this.questions.splice(i, 1);
        this.error = null;
    },
    move(i, d) {
        const j = i + d;
        if (j < 0 || j >= this.questions.length) return;
        const list = [...this.questions];
        [list[i], list[j]] = [list[j], list[i]];
        this.questions = list;
    },
    applyPresets() {
        this.error = null;
        const slug = categorySlugs[document.getElementById('category_id')?.value];
        const list = [...(presets[slug] ?? []), ...(presets.donatur ?? [])];
        const existing = new Set(this.questions.map((q) => q.label.trim().toLowerCase()));
        let added = 0;
        for (const p of list) {
            if (existing.has(p.label.toLowerCase())) continue;
            if (this.questions.length >= MAX_QUESTIONS) break;
            this.questions.push(make(p));
            existing.add(p.label.toLowerCase());
            added++;
        }
        const name = this.categoryName();
        this.notice = added
            ? added + ' pertanyaan rekomendasi' + (name ? ' untuk kategori ' + name : '') + ' ditambahkan. Silakan sesuaikan sebelum menyimpan.'
            : 'Semua pertanyaan rekomendasi' + (name ? ' untuk kategori ' + name : '') + ' sudah ada di daftar.';
    },
});
