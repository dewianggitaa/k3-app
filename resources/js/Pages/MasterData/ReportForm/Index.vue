<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { 
    Plus, Pencil, Trash2, Eye, Power, AlertTriangle,
    FileText, CheckCircle2, Clock, XCircle, ExternalLink,
} from 'lucide-vue-next';

import MainLayout from '@/Layouts/MainLayout.vue';
import Card from '@/Components/Card.vue';
import Modal from '@/Components/Modal.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    versions: { type: Array, default: () => [] },
    activeTab: { type: String, default: 'p3k' },
    needsRenewalAny: { type: Boolean, default: false },
    typesNeedingRenewal: { type: Array, default: () => [] },
});

const tabs = [
    { key: 'p3k', label: 'P3K' },
    { key: 'apar', label: 'APAR' },
    { key: 'hydrant', label: 'Hydrant' },
];

const showModal = ref(false);
const isEditing = ref(false);

const form = useForm({
    id: null,
    asset_type: props.activeTab,
    document_code: '',
    attachment_number: '',
    title: '',
    revision_number: 0,
    notes: '',
});

const switchTab = (tab) => {
    router.get(route('report-forms.index'), { tab }, { preserveState: true });
};

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.asset_type = props.activeTab;

    // Auto-suggest next revision number
    const activeVersion = props.versions.find(v => v.status === 'active');
    const latestDraft = props.versions.find(v => v.status === 'draft');
    const reference = latestDraft || activeVersion;
    
    if (reference) {
        form.document_code = reference.document_code;
        form.attachment_number = reference.attachment_number || '';
        form.title = reference.title;
        form.revision_number = reference.revision_number + 1;
    } else {
        // Default values for first version
        const defaults = {
            p3k: { code: 'F-I-HS-01-014-02/00', title: 'CATATAN PEMERIKSAAN KOTAK P3K' },
            apar: { code: 'F-I-HS-01-015-02/00', title: 'CATATAN PEMERIKSAAN APAR' },
            hydrant: { code: 'F-I-HS-01-016-02/00', title: 'CATATAN PEMERIKSAAN HYDRANT' },
        };
        const d = defaults[props.activeTab] || defaults.p3k;
        form.document_code = d.code;
        form.title = d.title;
    }

    showModal.value = true;
};

const openEditModal = (item) => {
    isEditing.value = true;
    form.id = item.id;
    form.asset_type = item.asset_type;
    form.document_code = item.document_code;
    form.attachment_number = item.attachment_number || '';
    form.title = item.title;
    form.revision_number = item.revision_number;
    form.notes = item.notes || '';
    showModal.value = true;
};

const submit = () => {
    const url = isEditing.value
        ? route('report-forms.update', form.id)
        : route('report-forms.store');
    const method = isEditing.value ? 'put' : 'post';

    form[method](url, {
        onSuccess: () => {
            showModal.value = false;
            Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Data tersimpan', timer: 1500, showConfirmButton: false });
        },
    });
};

const activateVersion = (item) => {
    const currentActive = props.versions.find(v => v.status === 'active');
    const message = currentActive
        ? `Versi aktif saat ini (Rev ${currentActive.revision_number}) akan dinonaktifkan dan digantikan.`
        : 'Versi ini akan menjadi versi aktif yang digunakan pada dokumen PDF.';

    Swal.fire({
        title: 'Aktifkan Versi Ini?',
        html: `<p class="text-sm text-gray-600">${message}</p><p class="text-sm text-gray-600 mt-2">Tanggal efektif akan diset ke <b>hari ini</b>.</p>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#16a34a',
        confirmButtonText: 'Ya, Aktifkan',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('report-forms.activate', item.id));
        }
    });
};

const deleteVersion = (item) => {
    Swal.fire({
        title: 'Hapus Draft?',
        text: 'Versi draft ini akan dihapus permanen.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Ya, Hapus',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('report-forms.destroy', item.id));
        }
    });
};

const previewVersion = (item) => {
    window.open(route('report-forms.preview', item.id), '_blank');
};

const getStatusBadge = (status) => {
    const styles = {
        draft: { class: 'bg-amber-100 text-amber-700 border-amber-200', icon: Clock, label: 'Draft' },
        active: { class: 'bg-emerald-100 text-emerald-700 border-emerald-200', icon: CheckCircle2, label: 'Aktif' },
        inactive: { class: 'bg-gray-100 text-gray-500 border-gray-200', icon: XCircle, label: 'Nonaktif' },
    };
    return styles[status] || styles.draft;
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

const tabNeedsRenewal = (tabKey) => {
    return props.typesNeedingRenewal.includes(tabKey);
};
</script>

<template>
    <Head title="Format Laporan" />

    <MainLayout>
        <template #header-title>
            <div class="flex items-center gap-4 px-4">
                <FileText class="w-5 h-5 text-primary" />
                <h2 class="font-bold text-lg text-ink leading-tight">
                    Format Laporan
                </h2>
            </div>
        </template>

        <div class="space-y-4">

            <!-- Tabs -->
            <Card no-padding class="p-2 overflow-visible">
                <div class="flex gap-1">
                    <button v-for="tab in tabs" :key="tab.key"
                        @click="switchTab(tab.key)"
                        :class="[
                            'relative px-5 py-2.5 rounded-lg text-sm font-bold transition-all',
                            activeTab === tab.key
                                ? 'bg-slate-800 text-white shadow-md'
                                : 'hover:bg-ghost text-ink-light'
                        ]"
                    >
                        {{ tab.label }}
                        <!-- Red dot for renewal needed -->
                        <span v-if="tabNeedsRenewal(tab.key)"
                            class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white animate-pulse"
                        ></span>
                    </button>
                </div>
            </Card>

            <!-- Renewal Warning Banner -->
            <div v-if="tabNeedsRenewal(activeTab)" 
                class="flex items-center gap-3 p-4 bg-amber-50 border border-amber-200 rounded-lg shadow-sm">
                <AlertTriangle class="w-5 h-5 text-amber-500 flex-shrink-0" />
                <div>
                    <p class="text-sm font-bold text-amber-800">Perlu Pembaruan Versi</p>
                    <p class="text-xs text-amber-600">Versi aktif sudah lebih dari 3 tahun. Pertimbangkan untuk membuat revisi baru.</p>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="flex justify-end">
                <button @click="openCreateModal"
                    class="flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-hover text-white rounded-lg text-sm font-bold transition-colors shadow-sm">
                    <Plus class="w-4 h-4" />
                    Buat Versi Baru
                </button>
            </div>

            <!-- Version Cards -->
            <div v-if="versions.length > 0" class="space-y-3">
                <Card v-for="version in versions" :key="version.id" no-padding
                    :class="[
                        'overflow-visible border-l-4 transition-all',
                        version.status === 'active' ? 'border-l-emerald-500' :
                        version.status === 'draft' ? 'border-l-amber-400' : 'border-l-gray-300'
                    ]"
                >
                    <div class="p-4 md:p-5">
                        <!-- Header Row -->
                        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 mb-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2.5 mb-1.5">
                                    <span :class="[
                                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border',
                                        getStatusBadge(version.status).class
                                    ]">
                                        <component :is="getStatusBadge(version.status).icon" class="w-3.5 h-3.5" />
                                        {{ getStatusBadge(version.status).label }}
                                    </span>
                                    <span class="text-xs font-mono bg-ghost text-ink-light px-2 py-0.5 rounded border border-ghost-hover">
                                        Rev {{ version.revision_number }}
                                    </span>
                                    <span v-if="version.needs_renewal" 
                                        class="inline-flex items-center gap-1 text-xs text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                                        <AlertTriangle class="w-3 h-3" />
                                        Perlu Update
                                    </span>
                                </div>
                                <h3 class="font-bold text-ink text-base">{{ version.title }}</h3>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                <button @click="previewVersion(version)"
                                    class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Preview PDF">
                                    <Eye class="w-4 h-4" />
                                </button>
                                <button v-if="version.status === 'draft'" @click="openEditModal(version)"
                                    class="p-2 text-primary hover:bg-primary/10 rounded-lg transition-colors" title="Edit">
                                    <Pencil class="w-4 h-4" />
                                </button>
                                <button v-if="version.status === 'draft'" @click="activateVersion(version)"
                                    class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors" title="Aktifkan">
                                    <Power class="w-4 h-4" />
                                </button>
                                <button v-if="version.status === 'draft'" @click="deleteVersion(version)"
                                    class="p-2 text-danger hover:bg-danger/10 rounded-lg transition-colors" title="Hapus">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <!-- Info Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
                            <div class="bg-ghost/50 rounded-lg px-3 py-2">
                                <span class="text-xs text-ink-light font-medium block mb-0.5">Kode Dokumen</span>
                                <span class="font-mono font-bold text-ink text-xs">{{ version.document_code }}</span>
                            </div>
                            <div class="bg-ghost/50 rounded-lg px-3 py-2">
                                <span class="text-xs text-ink-light font-medium block mb-0.5">Lampiran</span>
                                <span class="text-ink text-xs">{{ version.attachment_number || '-' }}</span>
                            </div>
                            <div class="bg-ghost/50 rounded-lg px-3 py-2">
                                <span class="text-xs text-ink-light font-medium block mb-0.5">Tanggal Efektif</span>
                                <span class="text-ink text-xs font-medium">{{ formatDate(version.effective_date) }}</span>
                            </div>
                            <div class="bg-ghost/50 rounded-lg px-3 py-2">
                                <span class="text-xs text-ink-light font-medium block mb-0.5">Dibuat</span>
                                <span class="text-ink text-xs">{{ formatDate(version.created_at) }}</span>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div v-if="version.notes" class="mt-3 text-xs text-ink-light bg-ghost/30 rounded-lg px-3 py-2 border border-ghost-hover">
                            <span class="font-bold">Catatan:</span> {{ version.notes }}
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Empty State -->
            <Card v-else no-padding>
                <div class="p-12 text-center">
                    <FileText class="w-12 h-12 text-ink-light/40 mx-auto mb-3" />
                    <h3 class="text-lg font-bold text-ink mb-1">Belum Ada Versi</h3>
                    <p class="text-sm text-ink-light mb-4">Buat versi pertama untuk tipe aset <b>{{ activeTab.toUpperCase() }}</b></p>
                    <button @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary-hover text-white rounded-lg text-sm font-bold transition-colors shadow-sm">
                        <Plus class="w-4 h-4" />
                        Buat Versi Pertama
                    </button>
                </div>
            </Card>

        </div>

        <!-- Create/Edit Modal -->
        <Modal :show="showModal" @close="showModal = false">
            <div class="p-5">
                <h3 class="text-lg font-bold text-ink mb-5 border-b pb-3 flex items-center gap-2">
                    <component :is="isEditing ? Pencil : Plus" class="w-5 h-5 text-primary" />
                    {{ isEditing ? 'Edit Versi Draft' : 'Buat Versi Baru' }}
                </h3>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-ink mb-1">Judul Laporan <span class="text-danger">*</span></label>
                        <input v-model="form.title" type="text" required
                            class="w-full rounded-md border-ghost-hover focus:border-primary focus:ring-primary text-sm"
                            placeholder="Contoh: CATATAN PEMERIKSAAN KOTAK P3K" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-ink mb-1">Kode Dokumen <span class="text-danger">*</span></label>
                            <input v-model="form.document_code" type="text" required
                                class="w-full rounded-md border-ghost-hover focus:border-primary focus:ring-primary text-sm font-mono"
                                placeholder="F-I-HS-01-014-02/00" />
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-ink mb-1">Nomor Revisi <span class="text-danger">*</span></label>
                            <input v-model="form.revision_number" type="number" min="0" required
                                class="w-full rounded-md border-ghost-hover focus:border-primary focus:ring-primary text-sm" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-ink mb-1">Lampiran <span class="text-ink-light font-normal">(opsional)</span></label>
                        <input v-model="form.attachment_number" type="text"
                            class="w-full rounded-md border-ghost-hover focus:border-primary focus:ring-primary text-sm"
                            placeholder="Contoh: Lampiran ke-2 atau Halaman ke-1" />
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-ink mb-1">Catatan Perubahan <span class="text-ink-light font-normal">(opsional)</span></label>
                        <textarea v-model="form.notes" rows="2"
                            class="w-full rounded-md border-ghost-hover focus:border-primary focus:ring-primary text-sm"
                            placeholder="Alasan pembuatan versi baru..."></textarea>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-700">
                        <p class="font-bold mb-1">ℹ️ Informasi</p>
                        <p>Versi baru akan dibuat sebagai <b>Draft</b>. Tanggal efektif akan otomatis terisi saat Anda mengaktifkan versi ini.</p>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="showModal = false"
                            class="px-4 py-2 bg-surface border border-ghost-hover text-ink rounded-md text-sm font-bold hover:bg-ghost transition-colors">
                            Batal
                        </button>
                        <button type="submit" :disabled="form.processing"
                            class="px-4 py-2 bg-primary text-white rounded-md text-sm font-bold hover:bg-primary-hover shadow-sm transition-colors disabled:opacity-50">
                            {{ isEditing ? 'Simpan Perubahan' : 'Buat Draft' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

    </MainLayout>
</template>
