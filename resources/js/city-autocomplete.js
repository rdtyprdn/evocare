export default (initial = '', endpoint = '/api/cities') => ({
    text: initial,
    city: initial,
    items: [],
    active: -1,
    open: false,
    seq: 0,
    close() {
        this.open = false;
        this.active = -1;
    },
    async fetchItems() {
        const q = this.text.trim();
        const id = ++this.seq;
        if (q === '') { // kosong = semua kota, tanpa dropdown
            this.items = [];
            this.close();
            return;
        }
        const res = await fetch(`${endpoint}?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } });
        const json = await res.json();
        if (id !== this.seq || this.text.trim() === '') return; // abaikan respons lama
        this.items = json.data;
        this.open = this.items.length > 0;
        this.active = this.open ? 0 : -1;
    },
    onInput() {
        this.city = this.text.trim(); // teks bebas tetap bisa dipakai sebagai filter
        if (this.city === '') { this.seq++; this.items = []; this.close(); return; }
        this.fetchItems();
    },
    move(step) {
        if (!this.open) { if (this.text.trim() !== '') this.fetchItems(); return; }
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
        this.text = item.name;
        this.city = item.name;
        this.close();
    },
});
