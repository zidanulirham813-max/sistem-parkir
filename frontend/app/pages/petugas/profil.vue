<template>
  <div class="dc-dashboard-layout">
    <Sidebar />

    <div class="dc-wrapper">
      <div class="dc-shell">
        <header class="dc-header">
          <div>
            <span class="dc-eyebrow"><span class="dc-dot"></span>Akun Saya</span>
            <h1>Edit Akun</h1>
            <p class="dc-subtext">Ubah email, password, nomor HP, dan foto profil kamu. Data tersimpan ke tabel <code>users</code>.</p>
          </div>
          <div class="header-right-group">
            <div class="user-badge-top">👤 Petugas</div>
          </div>
        </header>

        <div v-if="loadingMe" class="dc-section-card" style="text-align:center;color:#94a3b8;">Memuat profil...</div>

        <div v-else class="profil-grid">
          <!-- Kartu Foto -->
          <div class="dc-section-card profil-foto-card">
            <h3>Foto Profil</h3>
            <div class="foto-wrap">
              <img v-if="previewUrl" :src="previewUrl" class="avatar-lg" alt="foto profil" />
              <img v-else-if="form.foto_profil_url" :src="form.foto_profil_url" class="avatar-lg" alt="foto profil" />
              <div v-else class="avatar-lg avatar-fallback">{{ (form.name || '?').charAt(0).toUpperCase() }}</div>
            </div>
            <label class="btn-file">
              <input type="file" accept="image/*" @change="onFotoChange" hidden />
              <span>📷 Ganti Foto</span>
            </label>
            <p class="foto-hint">JPG / PNG / WEBP, maks 2MB</p>
            <div v-if="fotoFile" class="foto-name">{{ fotoFile.name }} <button class="link-inline" @click="clearFoto">hapus</button></div>
          </div>

          <!-- Form Edit Akun -->
          <div class="dc-section-card">
            <h3>Data Akun</h3>
            <p class="card-desc">Email dipakai untuk login & lupa password (OTP via WA ke no_tlp).</p>

            <form @submit.prevent="handleSave" class="profil-form">
              <div class="form-group">
                <label>Nama *</label>
                <input v-model="form.name" type="text" required class="dc-input" placeholder="Nama lengkap" />
              </div>
              <div class="form-group">
                <label>Email *</label>
                <input v-model="form.email" type="email" required class="dc-input" placeholder="email@contoh.com" />
              </div>
              <div class="form-group">
                <label>No. HP (untuk OTP WhatsApp)</label>
                <input v-model="form.no_tlp" type="text" class="dc-input" placeholder="0812xxxxxxx" />
                <span class="field-hint">Format: 08xx / 628xx / +62 8xx — dipakai untuk kirim OTP lupa password via Fonnte.</span>
              </div>

              <div class="divider"></div>
              <h4 style="margin:0;color:#e2e8f0;font-size:14px;">Ganti Password <span style="font-weight:400;color:#94a3b8;">(opsional)</span></h4>
              <p class="field-hint">Kosongkan jika tidak ingin ganti password.</p>

              <div class="form-group">
                <label>Password Baru</label>
                <input v-model="form.password" type="password" placeholder="Minimal 6 karakter" class="dc-input" />
              </div>
              <div class="form-group">
                <label>Konfirmasi Password Baru</label>
                <input v-model="form.password_confirmation" type="password" placeholder="Ulangi password baru" class="dc-input" />
              </div>

              <div v-if="errorMsg" class="alert-error">{{ errorMsg }}</div>
              <div v-if="successMsg" class="alert-success">{{ successMsg }}</div>

              <div class="form-actions">
                <button type="submit" class="btn-primary-action" :disabled="saving">
                  {{ saving ? 'Menyimpan...' : 'Simpan Perubahan' }}
                </button>
              </div>
            </form>
          </div>
        </div>

        <div class="info-box">
          <strong>Info:</strong> Role tidak dapat diubah dari sini. Hubungi Super Admin untuk ubah role. Akun Super Admin hanya 1 dan tidak dapat dihapus.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';

const { $api } = useNuxtApp();

const loadingMe = ref(true);
const saving = ref(false);
const errorMsg = ref('');
const successMsg = ref('');
const fotoFile = ref<File | null>(null);
const previewUrl = ref<string | null>(null);

const form = reactive({
  name: '',
  email: '',
  no_tlp: '',
  foto_profil_url: null as string | null,
  password: '',
  password_confirmation: '',
});

const fetchMe = async () => {
  loadingMe.value = true;
  errorMsg.value = '';
  try {
    const res: any = await $api.get('/me');
    const u = res.data?.data || res.data;
    if (u) {
      form.name = u.name || '';
      form.email = u.email || '';
      form.no_tlp = u.no_tlp || '';
      form.foto_profil_url = u.foto_profil_url || null;
    }
  } catch (e: any) {
    const msg = e?.response?.data?.message || e?.response?.status === 401 ? 'Sesi habis — silakan login ulang.' : 'Gagal memuat profil.';
    errorMsg.value = msg + ' (Pastikan token ada: cek Login -> localStorage token)';
    console.error('fetchMe gagal', e);
  } finally {
    loadingMe.value = false;
  }
};

const onFotoChange = (e: Event) => {
  const input = e.target as HTMLInputElement;
  const file = input.files?.[0] || null;
  fotoFile.value = file;
  if (file) {
    previewUrl.value = URL.createObjectURL(file);
  } else {
    previewUrl.value = null;
  }
};

const clearFoto = () => {
  fotoFile.value = null;
  previewUrl.value = null;
};

const handleSave = async () => {
  errorMsg.value = '';
  successMsg.value = '';

  if (!form.name || !form.email) {
    errorMsg.value = 'Nama dan email wajib diisi.';
    return;
  }
  if (form.password && form.password.length < 6) {
    errorMsg.value = 'Password minimal 6 karakter.';
    return;
  }
  if (form.password && form.password !== form.password_confirmation) {
    errorMsg.value = 'Konfirmasi password tidak cocok.';
    return;
  }

  saving.value = true;
  try {
    const fd = new FormData();
    fd.append('name', form.name);
    fd.append('email', form.email);
    fd.append('no_tlp', form.no_tlp || '');
    if (form.password) {
      fd.append('password', form.password);
      fd.append('password_confirmation', form.password_confirmation);
    }
    if (fotoFile.value) fd.append('foto_profil', fotoFile.value);

    const res: any = await $api.post('/me', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    successMsg.value = res.data?.message || 'Profil berhasil diperbarui.';
    const u = res.data?.data;
    if (u) {
      form.foto_profil_url = u.foto_profil_url || form.foto_profil_url;
    }
    fotoFile.value = null;
    previewUrl.value = null;
    form.password = '';
    form.password_confirmation = '';
    // sync ke localStorage user untuk sidebar/header
    try {
      const raw = localStorage.getItem('user');
      if (raw && u) {
        const cur = JSON.parse(raw);
        localStorage.setItem('user', JSON.stringify({ ...cur, name: u.name, email: u.email, foto_profil_url: u.foto_profil_url }));
      } else if (u) {
        localStorage.setItem('user', JSON.stringify(u));
      }
    } catch {}
  } catch (e: any) {
    const data = e?.response?.data;
    if (data?.errors) {
      errorMsg.value = Object.values(data.errors).flat().join(' ');
    } else {
      errorMsg.value = data?.message || 'Gagal menyimpan profil.';
    }
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  fetchMe();
});
</script>

<style scoped>
.dc-dashboard-layout { display: flex; min-height: 100vh; background: radial-gradient(circle at 20% -10%, #3a4b7c 0%, #1e294b 45%, #111827 100%); font-family: 'Inter', sans-serif; color: #f3f4f6; }
.header-right-group { display: flex; align-items: center; gap: 14px; }
.user-badge-top { background-color: #1e293b; border: 1px solid #334155; padding: 9px 14px; border-radius: 10px; font-size: 12px; color: #cbd5e1; font-weight: 600; }
.dc-wrapper { flex: 1; padding: 40px 24px; overflow-y: auto; }
.dc-shell { max-width: 1100px; margin: 0 auto; }
.dc-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; }
.dc-eyebrow { display: inline-flex; align-items: center; gap: 7px; font-size: 11.5px; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: #38bdf8; }
.dc-dot { width: 6px; height: 6px; border-radius: 50%; background: #38bdf8; box-shadow: 0 0 10px #38bdf8; }
.dc-header h1 { font-size: 25px; font-weight: 700; margin: 6px 0 2px; color: #ffffff; }
.dc-subtext { font-size: 13px; color: #94a3b8; margin: 0; }
.dc-subtext code { background: #0f172a; padding: 1px 5px; border-radius: 4px; color: #38bdf8; font-size: 11px; }
.dc-section-card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 24px; box-shadow: 0 20px 50px -20px rgba(0,0,0,0.5); }
.dc-section-card h3 { font-size: 15px; font-weight: 700; margin: 0 0 12px; color: #ffffff; }
.card-desc { font-size: 12.5px; color: #94a3b8; margin: 0 0 16px; }

.profil-grid { display: grid; grid-template-columns: 300px 1fr; gap: 20px; }
@media(max-width: 900px) { .profil-grid { grid-template-columns: 1fr; } }

.profil-foto-card { text-align: center; }
.foto-wrap { display: flex; justify-content: center; margin-bottom: 16px; }
.avatar-lg { width: 110px; height: 110px; border-radius: 50%; object-fit: cover; border: 3px solid #334155; }
.avatar-fallback { display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: #fff; font-size: 36px; font-weight: 800; }
.btn-file { display: inline-block; background: #0f172a; border: 1px solid #334155; color: #e2e8f0; padding: 8px 14px; border-radius: 10px; font-size: 12.5px; font-weight: 600; cursor: pointer; }
.btn-file:hover { border-color: #38bdf8; }
.foto-hint { font-size: 11px; color: #64748b; margin: 10px 0 0; }
.foto-name { font-size: 11.5px; color: #94a3b8; margin-top: 8px; }
.link-inline { background: none; border: none; color: #38bdf8; font-weight: 600; cursor: pointer; font-size: 11.5px; }

.profil-form { display: flex; flex-direction: column; gap: 14px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: 12.5px; font-weight: 600; color: #cbd5e1; }
.field-hint { font-size: 11.5px; color: #64748b; }
.divider { height: 1px; background: #334155; margin: 6px 0; }
.dc-input { background: #0f172a; border: 1px solid #334155; color: #fff; padding: 10px 14px; border-radius: 10px; font-size: 13px; outline: none; width: 100%; box-sizing: border-box; }
.dc-input:focus { border-color: #38bdf8; }
.form-actions { display: flex; justify-content: flex-end; margin-top: 6px; }
.btn-primary-action { background: #2563eb; color: #fff; border: none; padding: 10px 18px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; }
.btn-primary-action:disabled { opacity: 0.6; cursor: not-allowed; }
.alert-error { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #f87171; padding: 10px 14px; border-radius: 10px; font-size: 12.5px; }
.alert-success { background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: #34d399; padding: 10px 14px; border-radius: 10px; font-size: 12.5px; }
.info-box { margin-top: 20px; font-size: 12.5px; color: #94a3b8; background: rgba(15,23,42,0.45); border: 1px dashed #334155; border-radius: 12px; padding: 12px 14px; }
.info-box strong { color: #e2e8f0; }
</style>
