const TYPES = ['image/jpeg', 'image/png', 'image/webp'];
const MAX = 2 * 1024 * 1024;

export default (uploaded = null, serverError = null) => ({
    uploaded,
    preview: null,
    remove: false,
    url: '',
    error: serverError,
    init() {
        const u = new URL(window.location.href);
        if (u.searchParams.has('upload_error')) {
            u.searchParams.delete('upload_error');
            history.replaceState(null, '', u.pathname + u.search + u.hash);
        }
        this.url = this.$el.querySelector('#image_url')?.value ?? '';
    },
    shown() {
        // Prioritas: file baru > gambar unggahan tersimpan > URL gambar
        if (this.preview) return this.preview;
        if (this.uploaded && !this.remove) return this.uploaded;
        return /^https?:\/\/\S+$/.test(this.url) ? this.url : null;
    },
    check(e) {
        const f = e.target.files[0];
        this.error = null;
        this.preview = null;
        if (!f) return;
        if (!TYPES.includes(f.type)) { this.error = 'Format gambar harus JPG, PNG, atau WEBP.'; e.target.value = ''; return; }
        if (f.size > MAX) { this.error = 'Ukuran file terlalu besar. Maksimal 2 MB.'; e.target.value = ''; return; }
        this.preview = URL.createObjectURL(f);
    },
    submit(e) {
        if (this.error) e.preventDefault();
    },
});
