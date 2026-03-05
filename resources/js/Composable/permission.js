export const PERMISSION_LABELS = {
    'view-users': 'Lihat Daftar User',
    'create-users': 'Tambah User Baru',
    'edit-users': 'Edit Data User',
    'delete-users': 'Hapus User',
    'toggle-user-status': 'Aktifkan/Nonaktifkan Akun',
    'manage-roles': 'Kelola Role & Hak Akses',

    'view-master-data': 'Lihat Data Master (Read-only)',
    'manage-buildings': 'Kelola Data Gedung',
    'manage-floors': 'Kelola Data Lantai',
    'manage-rooms': 'Kelola Data Ruangan',
    'manage-assets': 'Kelola Data Aset (P3K, APAR, Hydrant)',
    'manage-asset-mapping': 'Kelola Pemetaan Aset',
    'manage-checklist-parameters': 'Kelola Checklist Parameter',

    'view-schedules': 'Lihat Jadwal',
    'create-schedules': 'Buat Jadwal Baru',
    'delete-schedules': 'Hapus Jadwal',

    'manage-inspections': 'Monitoring Semua Inspeksi',
    'view-assigned-inspections': 'Lihat Tugas Inspeksi Saya',
    'execute-inspections': 'Kerjakan Inspeksi',
    'delete-inspections': 'Hapus Inspeksi',
    'edit-inspections': 'Edit Data Inspeksi',

    'create-p3k-usage': 'Input Pemakaian P3K',
    'create-p3k-restock': 'Input Penambahan Stok P3K',

    'view-dashboard': 'Akses Dashboard',
    'view-reports': 'Lihat Laporan K3',
    'export-reports': 'Export Laporan (PDF)',
    'view-pic-reports': 'Lihat Laporan PIC',
};

export const PERMISSION_GROUPS = [
    {
        category: 'Manajemen Pengguna',
        permissions: [
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            'toggle-user-status',
            'manage-roles',
        ]
    },
    {
        category: 'Data Master & Aset',
        permissions: [
            'view-master-data',
            'manage-buildings',
            'manage-floors',
            'manage-rooms',
            'manage-assets',
            'manage-asset-mapping',
            'manage-checklist-parameters',
        ]
    },
    {
        category: 'Penjadwalan',
        permissions: [
            'view-schedules',
            'create-schedules',
            'delete-schedules',
        ]
    },
    {
        category: 'Operasional Inspeksi',
        permissions: [
            'manage-inspections',
            'view-assigned-inspections',
            'execute-inspections',
            'edit-inspections',
            'delete-inspections',
            'create-p3k-usage',
            'create-p3k-restock',
        ]
    },
    {
        category: 'Laporan & Statistik',
        permissions: [
            'view-dashboard',
            'view-reports',
            'export-reports',
            'view-pic-reports',
        ]
    }
];

export const getLabel = (code) => {
    return PERMISSION_LABELS[code] || code;
};
