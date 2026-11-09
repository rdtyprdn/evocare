// Form pendaftaran: pertanyaan tambahan berganti sesuai peran yang dipilih.
export default (initialRole = 'relawan', questionRoles = {}) => ({
    role: initialRole,
    questionRoles,
    shows(id) {
        return (this.questionRoles[id] ?? []).includes(this.role);
    },
    visibleCount() {
        return Object.keys(this.questionRoles).filter((id) => this.shows(id)).length;
    },
});
