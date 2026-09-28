<template>
  <div class="dc-dashboard-layout">
    <SidebarSuperAdmin />

    <div class="dc-wrapper">
      <div class="dc-shell">
        <header class="dc-header">
          <div>
            <span class="dc-eyebrow"><span class="dc-dot"></span>Live · Manajemen Personel</span>
            <h1>Kelola Petugas Parkir</h1>
            <p class="dc-subtext">Daftar akun petugas operasional. Super Admin hanya 1 akun — tidak dapat ditambah/duplikat.</p>
          </div>
          <div class="header-right-group">
            <button class="btn-primary-action" @click="showAddModal = true">+ Tambah Petugas</button>
            <div class="user-badge-top">👑 Super Administrator</div>
          </div>
        </header>

        <!-- Statistik -->
        <div class="stats-grid-top">
          <div class="dc-section-card stat-card-small">
            <span class="dc-stat-label">TOTAL PETUGAS</span>
            <div class="stat-value-row">
              <h2>{{ listPetugas.length }}</h2>
              <span class="badge-sub-info">Terdaftar di database</span>
            </div>
          </div>
          <div class="dc-section-card stat-card-small">
            <span class="dc-stat-label">SUPER ADMIN</span>
            <div class="stat-value-row">
              <h2>{{ superAdminCount }}</h2>
              <span class="badge-sub-info text-cyan">{{ superAdminCount === 1 ? 'Maksimal 1 ✓' : 'Akses penuh' }}</span>
            </div>
          </div>
          <div class="dc-section-card stat-card-small">
            <span class="dc-stat-label">PETUGAS</span>
            <div class="stat-value-row">
              <h2>{{ petugasCount }}</h2>
              <span class="badge-sub-info text-yellow">Operasional gerbang</span>
            </div>
          </div>
          <div class="dc-section-card stat-card-small">
            <span class="dc-stat-label">STATUS SISTEM</span>
            <div class="stat-value-row">
              <h2>OK</h2>
              <span class="badge-sub-info text-green">Sinkronisasi database</span>
            </div>
          </div>
        </div>

        <!-- Tabel -->
        <div class="dc-section-card" style="margin-top: 24px;">
          <div class="table-header-title">
            <div>
              <h3>Daftar Personel</h3>
              <p class="table-sub-desc">Super Admin tidak dapat dihapus & tidak dapat ditambah lagi. Klik Edit untuk ubah data petugas. Hapus hanya untuk role Petugas.</p>
            </div>
            <span class="data-count">Menampilkan {{ filteredPetugas.length }} petugas</span>
          </div>

          <div class="table-filter-bar">
            <input type="text" v-model="searchKeyword" placeholder="Cari nama, email, atau no. HP..." class="dc-input search-input" />
            <select class="dc-input select-input" v-model="filterRole">
              <option value="Semua Role">Semua Role</option>
              <option value="petugas">Petugas</option>
              <option value="super_admin">Super Admin</option>
            </select>
            <span v-if="isLoading" style="font-size:12px;color:#94a3b8;align-self:center;">Memuat...</span>
          </div>

          <div v-if="errorMsg" class="alert-error">{{ errorMsg }}</div>

          <div class="table-responsive">
            <table class="custom-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>NAMA PETUGAS</th>
                  <th>EMAIL</th>
                  <th>NO. HP</th>
                  <th>ROLE</th>
                  <th>AKSI</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="isLoading">
                  <td colspan="6" style="text-align:center;padding:20px;color:#94a3b8;">Memuat data petugas...</td>
                </tr>
                <tr v-else-if="filteredPetugas.length === 0">
                  <td colspan="6" style="text-align:center;padding:20px;color:#64748b;">
                    {{ listPetugas.length === 0 ? 'Belum ada data petugas.' : 'Tidak ada hasil pencarian.' }}
                  </td>
                </tr>
                <tr v-for="p in filteredPetugas" :key="p.id">
                  <td><strong class="text-cyan">#{{ p.id }}</strong></td>
                  <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                      <img v-if="p.foto_profil_url" :src="p.foto_profil_url" class="avatar-sm" alt="foto" />
                      <div v-else class="avatar-sm avatar-fallback">{{ (p.name || '?').charAt(0).toUpperCase() }}</div>
                      <span>{{ p.name }}</span>
                    </div>
                  </td>
                  <td>{{ p.email }}</td>
                  <td>{{ p.no_tlp || '-' }}</td>
                  <td><span class="dc-activity-badge" :class="p.role === 'super_admin' ? 'badge-cyan' : 'badge-green'">{{ p.role === 'super_admin' ? 'Super Admin' : 'Petugas' }}</span></td>
                  <td>
                    <div class="action-buttons-group">
                      <button class="btn-action-edit" @click="openEditModal(p)">Edit</button>
                      <button
                        class="btn-action-delete"
                        :disabled="deletingId === p.id || p.role === 'super_admin'"
                        :title="p.role === 'super_admin' ? 'Super Admin tidak dapat dihapus' : 'Hapus petugas'"
                        @click="hapusPetugas(p)"
                      >
                        {{ deletingId === p.id ? '...' : 'Hapus' }}
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Tambah Petugas -->
    <div v-if="showAddModal" class="modal-overlay" @click.self="showAddModal = false">
      <div class="modal-card">
        <div class="modal-header">
          <h3>Tambah Petugas Baru</h3>
          <button class="modal-close" @click="showAddModal = false">✕</button>
        </div>
        <p class="modal-desc">Akun baru otomatis role <strong>Petugas</strong>. Super Admin hanya 1 dan tidak dapat ditambah lagi.</p>

        <form @submit.prevent="submitAddPetugas" class="modal-form">
          <div class="form-group">
            <label>Nama Petugas *</label>
            <input v-model="form.name" type="text" placeholder="Contoh: Budi Santoso" required class="dc-input" />
          </div>
          <div class="form-group">
            <label>Email *</label>
            <input v-model="form.email" type="email" placeholder="budi@parkir.com" required class="dc-input" />
          </div>
          <div class="form-group">
            <label>No. HP</label>
            <input v-model="form.no_tlp" type="text" placeholder="0812xxxxxxx" class="dc-input" />
          </div>
          <div class="form-group">
            <label>Password *</label>
            <input v-model="form.password" type="password" placeholder="Minimal 6 karakter" required class="dc-input" />
          </div>

          <div v-if="formError" class="alert-error">{{ formError }}</div>
          <div v-if="formSuccess" class="alert-success">{{ formSuccess }}</div>

          <div class="modal-actions">
            <button type="button" class="btn-secondary" @click="showAddModal = false">Batal</button>
            <button type="submit" class="btn-primary-action" :disabled="isSubmitting">
              {{ isSubmitting ? 'Menyimpan...' : 'Simpan Petugas' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Edit Petugas -->
    <div v-if="showEditModal" class="modal-overlay" @click.self="closeEditModal">
      <div class="modal-card" style="max-width:520px;">
        <div class="modal-header">
          <h3>Edit Akun</h3>
          <button class="modal-close" @click="closeEditModal">✕</button>
        </div>
        <p class="modal-desc">Ubah data akun. Kosongkan password jika tidak ingin mengganti. Role Super Admin tidak dapat diubah.</p>

        <form @submit.prevent="submitEditPetugas" class="modal-form">
          <div class="form-group">
            <label>Nama Petugas *</label>
            <input v-model="editForm.name" type="text" required class="dc-input" />
          </div>
          <div class="form-group">
            <label>Email *</label>
            <input v-model="editForm.email" type="email" required class="dc-input" />
          </div>
          <div class="form-group">
            <label>No. HP</label>
            <input v-model="editForm.no_tlp" type="text" placeholder="0812xxxxxxx" class="dc-input" />
          </div>
          <div class="form-group">
            <label>Foto Profil</label>
            <div style="display:flex;align-items:center;gap:12px;">
              <img v-if="editPreviewUrl" :src="editPreviewUrl" class="avatar-preview" alt="preview" />
              <div v-else class="avatar-preview avatar-fallback" style="width:56px;height:56px;font-size:18px;">{{ (editForm.name || '?').charAt(0).toUpperCase() }}</div>
              <div>
                <input type="file" accept="image/*" @change="onEditFotoChange" class="dc-input" style="padding:6px;" />
                <div style="font-size:11px;color:#64748b;margin-top:4px;">JPG/PNG/WEBP maks 2MB. Kosong = tidak ganti.</div>
              </div>
            </div>
          </div>
          <div v-if="editingRole === 'super_admin'" class="alert-info">
            Akun ini Super Admin — role terkunci. Tidak dapat diubah menjadi Petugas dari sini.
          </div>
          <div class="form-group">
            <label>Password Baru <span style="font-weight:400;color:#94a3b8;">(kosongkan jika tidak ganti)</span></label>
            <input v-model="editForm.password" type="password" placeholder="Minimal 6 karakter, kosong = tidak diubah" class="dc-input" />
          </div>

          <div v-if="editError" class="alert-error">{{ editError }}</div>
          <div v-if="editSuccess" class="alert-success">{{ editSuccess }}</div>

          <div class="modal-actions">
            <button type="button" class="btn-secondary" @click="closeEditModal">Batal</button>
            <button type="submit" class="btn-primary-action" :disabled="isEditSubmitting">
              {{ isEditSubmitting ? 'Menyimpan...' : 'Simpan Perubahan' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, reactive } from 'vue';

const { $api } = useNuxtApp();

interface Petugas {
  id: number;
  name: string;
  email: string;
  no_tlp: string | null;
  foto_profil: string | null;
  foto_profil_url: string | null;
  role: string;
}

const listPetugas = ref<Petugas[]>([]);
const isLoading = ref(false);
const errorMsg = ref('');
const searchKeyword = ref('');
const filterRole = ref('Semua Role');
const deletingId = ref<number | null>(null);

const showAddModal = ref(false);
const isSubmitting = ref(false);
const formError = ref('');
const formSuccess = ref('');

const form = reactive({
  name: '',
  email: '',
  no_tlp: '',
  password: '',
});

const showEditModal = ref(false);
const editingId = ref<number | null>(null);
const editingRole = ref<string>('');
const isEditSubmitting = ref(false);
const editError = ref('');
const editSuccess = ref('');
const editForm = reactive({
  name: '',
  email: '',
  no_tlp: '',
  password: '',
});
const editFotoFile = ref<File | null>(null);
const editPreviewUrl = ref<string | null>(null);

const petugasCount = computed(() => listPetugas.value.filter(p => p.role === 'petugas').length);
const superAdminCount = computed(() => listPetugas.value.filter(p => p.role === 'super_admin').length);

const filteredPetugas = computed(() => {
  return listPetugas.value.filter(p => {
    const kw = searchKeyword.value.toLowerCase();
    const matchKeyword = !kw ||
      p.name.toLowerCase().includes(kw) ||
      p.email.toLowerCase().includes(kw) ||
      (p.no_tlp || '').toLowerCase().includes(kw);
    const matchRole = filterRole.value === 'Semua Role' || p.role === filterRole.value;
    return matchKeyword && matchRole;
  });
});

const fetchPetugas = async () => {
  isLoading.value = true;
  errorMsg.value = '';
  try {
    const res: any = await $api.get('/petugas');
    const data = res.data?.data ?? res.data ?? [];
    listPetugas.value = Array.isArray(data) ? data : [];
  } catch (e: any) {
    console.error('Gagal fetch petugas', e);
    errorMsg.value = e?.response?.data?.message || 'Gagal memuat data petugas. Pastikan backend jalan di http://localhost:8000';
  } finally {
    isLoading.value = false;
  }
};

const submitAddPetugas = async () => {
  formError.value = '';
  formSuccess.value = '';
  isSubmitting.value = true;
  try {
    const payload: any = {
      name: form.name,
      email: form.email,
      password: form.password,
    };
    if (form.no_tlp) payload.no_tlp = form.no_tlp;

    const res: any = await $api.post('/petugas', payload);
    formSuccess.value = res.data?.message || 'Petugas berhasil ditambahkan';
    form.name = '';
    form.email = '';
    form.no_tlp = '';
    form.password = '';
    await fetchPetugas();
    setTimeout(() => {
      showAddModal.value = false;
      formSuccess.value = '';
    }, 800);
  } catch (e: any) {
    console.error('Gagal tambah petugas', e);
    const errors = e?.response?.data?.errors;
    if (errors) {
      formError.value = Object.values(errors).flat().join(' ');
    } else {
      formError.value = e?.response?.data?.message || 'Gagal menambah petugas. Cek apakah email sudah dipakai.';
    }
  } finally {
    isSubmitting.value = false;
  }
};

const openEditModal = (p: Petugas) => {
  editingId.value = p.id;
  editingRole.value = p.role;
  editForm.name = p.name;
  editForm.email = p.email;
  editForm.no_tlp = p.no_tlp || '';
  editForm.password = '';
  editFotoFile.value = null;
  editPreviewUrl.value = p.foto_profil_url || null;
  editError.value = '';
  editSuccess.value = '';
  showEditModal.value = true;
};

const closeEditModal = () => {
  showEditModal.value = false;
  editingId.value = null;
  editingRole.value = '';
  editError.value = '';
  editSuccess.value = '';
  editFotoFile.value = null;
  editPreviewUrl.value = null;
};

const onEditFotoChange = (e: Event) => {
  const input = e.target as HTMLInputElement;
  const file = input.files?.[0] || null;
  editFotoFile.value = file;
  if (file) {
    const url = URL.createObjectURL(file);
    editPreviewUrl.value = url;
  }
};

const submitEditPetugas = async () => {
  if (editingId.value === null) return;
  editError.value = '';
  editSuccess.value = '';
  isEditSubmitting.value = true;
  try {
    const fd = new FormData();
    fd.append('name', editForm.name);
    fd.append('email', editForm.email);
    fd.append('no_tlp', editForm.no_tlp || '');
    if (editForm.password) fd.append('password', editForm.password);
    if (editFotoFile.value) fd.append('foto_profil', editFotoFile.value);
    // Laravel PUT via POST + _method spoof agar multipart jalan
    fd.append('_method', 'PUT');

    const res: any = await $api.post(`/petugas/${editingId.value}`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    editSuccess.value = res.data?.message || 'Petugas berhasil diperbarui';
    await fetchPetugas();
    setTimeout(() => {
      closeEditModal();
    }, 700);
  } catch (e: any) {
    console.error('Gagal edit petugas', e);
    const errors = e?.response?.data?.errors;
    if (errors) {
      editError.value = Object.values(errors).flat().join(' ');
    } else {
      editError.value = e?.response?.data?.message || 'Gagal menyimpan perubahan.';
    }
  } finally {
    isEditSubmitting.value = false;
  }
};

const hapusPetugas = async (p: Petugas) => {
  if (p.role === 'super_admin') {
    alert('Akun Super Admin tidak dapat dihapus.');
    return;
  }
  if (!confirm(`Hapus petugas ${p.name} (${p.email})?`)) return;
  deletingId.value = p.id;
  try {
    await $api.delete(`/petugas/${p.id}`);
    listPetugas.value = listPetugas.value.filter(x => x.id !== p.id);
  } catch (e: any) {
    alert(e?.response?.data?.message || 'Gagal menghapus petugas');
  } finally {
    deletingId.value = null;
  }
};

onMounted(() => {
  fetchPetugas();
});
</script>

<style scoped>
.dc-dashboard-layout {
  display: flex;
  min-height: 100vh;
  background: radial-gradient(circle at 20% -10%, #3a4b7c 0%, #1e294b 45%, #111827 100%);
  font-family: 'Inter', -apple-system, sans-serif;
  color: #f3f4f6;
}
.header-right-group { display: flex; align-items: center; gap: 14px; }
.user-badge-top { background-color: #1e293b; border: 1px solid #334155; padding: 9px 14px; border-radius: 10px; font-size: 12px; color: #cbd5e1; font-weight: 600; }
.btn-primary-action { background: #2563eb; color: #fff; border: none; padding: 10px 16px; border-radius: 10px; font-size: 12.5px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
.btn-primary-action:hover { background: #1d4ed8; }
.btn-primary-action:disabled { opacity: 0.6; cursor: not-allowed; }
.dc-wrapper { flex: 1; padding: 40px 24px; overflow-y: auto; }
.dc-shell { max-width: 1280px; margin: 0 auto; }
.dc-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; }
.dc-eyebrow { display: inline-flex; align-items: center; gap: 7px; font-size: 11.5px; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: #38bdf8; }
.dc-dot { width: 6px; height: 6px; border-radius: 50%; background: #38bdf8; box-shadow: 0 0 10px #38bdf8; }
.dc-header h1 { font-size: 25px; font-weight: 700; margin: 6px 0 2px; color: #ffffff; }
.dc-subtext { font-size: 13px; color: #94a3b8; margin: 0; }
.dc-section-card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 24px; box-shadow: 0 20px 50px -20px rgba(0,0,0,0.5); }
.stats-grid-top { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 16px; }
.stat-card-small { padding: 18px 20px !important; }
.stat-value-row { display: flex; align-items: baseline; justify-content: space-between; margin-top: 8px; }
.stat-value-row h2 { font-size: 26px; font-weight: 800; margin: 0; color: #fff; }
.badge-sub-info { font-size: 11.5px; font-weight: 600; color: #94a3b8; }
.table-filter-bar { display: grid; grid-template-columns: 2fr 1fr auto; gap: 12px; margin-bottom: 16px; }
@media(max-width: 992px) { .table-filter-bar { grid-template-columns: 1fr; } }
.table-header-title { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
.table-header-title h3 { font-size: 16px; font-weight: 700; margin: 0 0 4px; color: #ffffff; }
.table-sub-desc { font-size: 12px; color: #94a3b8; margin: 0; }
.data-count { font-size: 12px; color: #38bdf8; background: rgba(56, 189, 248, 0.1); padding: 6px 12px; border-radius: 8px; font-weight: 600; }
.table-responsive { width: 100%; overflow-x: auto; }
.custom-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 12.5px; }
.custom-table th { background-color: rgba(15, 23, 42, 0.6); color: #94a3b8; padding: 12px; font-weight: 700; border-bottom: 1px solid #334155; }
.custom-table td { padding: 12px; border-bottom: 1px solid #334155; color: #e2e8f0; }
.custom-table td small { color: #94a3b8; }
.dc-input { background: #0f172a; border: 1px solid #334155; color: #fff; padding: 10px 14px; border-radius: 10px; font-size: 13px; outline: none; width: 100%; box-sizing: border-box; }
.dc-input:focus { border-color: #38bdf8; }
.action-buttons-group { display: flex; gap: 6px; }
.btn-action-edit { background: #2563eb; color: #fff; border: none; padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; }
.btn-action-delete { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; }
.btn-action-delete:disabled { opacity: 0.4; cursor: not-allowed; }
.dc-activity-badge { font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 6px; display: inline-block; }
.badge-green { background: rgba(16, 185, 129, 0.15); color: #34d399; }
.badge-cyan { background: rgba(56, 189, 248, 0.15); color: #38bdf8; }
.badge-yellow { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
.text-cyan { color: #38bdf8; }
.text-yellow { color: #f59e0b; }
.text-green { color: #34d399; }
.avatar-sm { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; border: 1px solid #334155; flex-shrink: 0; }
.avatar-sm.avatar-fallback { display: flex; align-items: center; justify-content: center; background: #2563eb; color: #fff; font-size: 12px; font-weight: 700; }
.avatar-preview { width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 2px solid #334155; }

/* Modal */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.55); display: flex; align-items: center; justify-content: center; z-index: 100; padding: 20px; backdrop-filter: blur(4px); }
.modal-card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 24px; width: 100%; max-width: 480px; box-shadow: 0 20px 60px rgba(0,0,0,0.5); max-height: 90vh; overflow-y: auto; }
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
.modal-header h3 { font-size: 16px; font-weight: 700; color: #fff; margin: 0; }
.modal-close { background: #0f172a; border: 1px solid #334155; color: #94a3b8; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; }
.modal-desc { font-size: 12.5px; color: #94a3b8; margin: 0 0 18px; }
.modal-form { display: flex; flex-direction: column; gap: 14px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: 12.5px; font-weight: 600; color: #cbd5e1; }
.modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 6px; }
.btn-secondary { background: transparent; border: 1px solid #334155; color: #cbd5e1; padding: 10px 16px; border-radius: 10px; font-size: 12.5px; font-weight: 600; cursor: pointer; }
.alert-error { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #f87171; padding: 10px 14px; border-radius: 10px; font-size: 12.5px; margin-bottom: 12px; }
.alert-success { background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: #34d399; padding: 10px 14px; border-radius: 10px; font-size: 12.5px; margin-bottom: 12px; }
.alert-info { background: rgba(56,189,248,0.12); border: 1px solid rgba(56,189,248,0.25); color: #7dd3fc; padding: 10px 14px; border-radius: 10px; font-size: 12.5px; margin-bottom: 4px; }
</style>
