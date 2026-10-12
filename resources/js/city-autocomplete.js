export default (initial = '', endpoint = '/api/cities') => ({
    text: initial,
    city: initial,
    items: [],
    active: -1,
    open: false,
    loading: false,
    seq: 0,
    allOption() {
        return { name: 'Semua kota', subtitle: 'Tampilkan kegiatan di seluruh Indonesia', all: true };
    },
    async fetchItems() {
        const q = this.text.trim();
        const id = ++this.seq;
        this.loading = true;
        try {
            const res = await fetch(`${endpoint}?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } });
            const json = await res.json();
            if (id !== this.seq) return; // abaikan respons lama
            this.items = q === '' ? [this.allOption(), ...json.data] : json.data;
            this.active = this.items.length ? 0 : -1;
            this.open = true;
        } finally {
            if (id === this.seq) this.loading = false;
        }
    },
    onInput() {
        this.city = this.text.trim(); // teks bebas tetap bisa dipakai sebagai filter
        this.fetchItems();
    },
    onFocus() {
        this.fetchItems();
    },
    move(step) {
        if (!this.open) { this.fetchItems(); return; }
        if (!this.items.length) return;
        this.active = (this.active + step + this.items.length) % this.items.length;
    },
    enter(e) {
        if (this.open && this.active >= 0 && this.items[this.active]) {
            e.preventDefault();
            this.select(this.items[this.active]);
        }
    },
    select(item) {
        this.text = item.all ? '' : item.name;
        this.city = item.all ? '' : item.name;
        this.open = false;
    },
});
