const LIMITS = {
    photos: { max: 10, mb: 10, unit: 'foto', types: ['image/jpeg', 'image/png', 'image/webp'], typeMsg: 'Format foto harus JPG, PNG, atau WEBP.' },
    videos: { max: 3, mb: 30, unit: 'video', types: ['video/mp4', 'video/webm', 'video/quicktime'], typeMsg: 'Format video harus MP4, WEBM, atau MOV.' },
};
const TOTAL_MB = 120;
const MAX_ITEMS = 30;

const fmt = (b) => (b >= 1024 * 1024 ? (b / 1024 / 1024).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB');

export default (initialTab = 'photo', initialLinks = [], existing = 0) => ({
    tab: initialTab,
    existing,
    photos: [],
    videos: [],
    links: initialLinks.length ? initialLinks.map((v) => ({ v })) : [{ v: '' }],
    error: null,
    fmt,
    init() {
        const form = this.$el.closest('form');
        form?.addEventListener('submit', (e) => {
            const msg = this.validateAll();
            if (msg) { e.preventDefault(); e.stopImmediatePropagation(); this.error = msg; this.$el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
        }, true);
    },
    totalBytes() {
        return [...this.photos, ...this.videos].reduce((s, i) => s + i.file.size, 0);
    },
    itemCount() {
        return this.photos.length + this.videos.length + this.links.filter((l) => l.v.trim() !== '').length;
    },
    pick(field, e) {
        this.error = null;
        const cfg = LIMITS[field];
        const list = this[field];
        for (const f of [...e.target.files]) {
            if (!cfg.types.includes(f.type)) { this.error = cfg.typeMsg + ' (' + f.name + ')'; continue; }
            if (f.size > cfg.mb * 1024 * 1024) { this.error = 'Ukuran file terlalu besar. Maksimal ' + cfg.mb + ' MB per ' + cfg.unit + ' (' + f.name + ').'; continue; }
            if (list.length >= cfg.max) { this.error = 'Maksimal ' + cfg.max + ' ' + cfg.unit + ' sekali simpan.'; break; }
            list.push({ file: f, url: URL.createObjectURL(f) });
        }
        this.sync(field);
    },
    removeFile(field, i) {
        URL.revokeObjectURL(this[field][i].url);
        this[field].splice(i, 1);
        this.error = null;
        this.sync(field);
    },
    sync(field) {
        const dt = new DataTransfer();
        this[field].forEach((i) => dt.items.add(i.file));
        this.$refs[field].files = dt.files;
    },
    addLink() { this.links.push({ v: '' }); },
    removeLink(i) { this.links.splice(i, 1); if (!this.links.length) this.links.push({ v: '' }); },
    validateAll() {
        if (this.totalBytes() > TOTAL_MB * 1024 * 1024) return 'Total unggahan sekali simpan maksimal ' + TOTAL_MB + ' MB (saat ini ' + fmt(this.totalBytes()) + '). Kurangi jumlah file.';
        if (this.itemCount() + this.existing > MAX_ITEMS) return 'Galeri maksimal berisi ' + MAX_ITEMS + ' item (sudah ada ' + this.existing + ' item tersimpan).';
        return null;
    },
});
