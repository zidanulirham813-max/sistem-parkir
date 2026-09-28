<template>
  <div class="dc-dashboard-layout">
    <Sidebar />
    <div class="dc-wrapper">
      <div class="dc-shell">
        <header class="dc-header">
          <div>
            <span class="dc-eyebrow"><span class="dc-dot"></span>Live · Panel Kontrol Parkir</span>
            <h1>Edit Data Member</h1>
            <p v-if="member" style="font-size:12px;color:#94a3b8;margin:6px 0 0">Kode: <strong style="color:#38bdf8">{{ member.kode_member }}</strong> · Status: <strong>{{ member.status }}</strong></p>
          </div>
          <NuxtLink to="/petugas/member" class="dc-btn dc-btn-ghost">← Kembali</NuxtLink>
        </header>

        <div v-if="loading" class="dc-card dc-loading">Memuat data…</div>

        <div v-else class="dc-card dc-form-card">
          <div v-if="member?.status === 'lunas'" class="dc-info">Member sudah <strong>lunas</strong> — data identitas & plat tetap bisa diedit. Pembayaran terkunci Rp 150.000.</div>
          <form @submit.prevent="saveMember">
            <div class="dc-field">
              <label>Nama Member *</label>
              <input v-model="form.nama_member" type="text" required class="dc-input" placeholder="Nama lengkap" />
            </div>
            <div class="dc-field">
              <label>Nama Perusahaan *</label>
              <input v-model="form.nama_perusahaan" type="text" required class="dc-input" placeholder="Nama perusahaan" />
            </div>
            <div class="dc-field">
              <label>Plat Nomor *</label>
              <input v-model="form.plat_nomor" type="text" required class="dc-input" placeholder="B 1234 ABC" @input="form.plat_nomor = form.plat_nomor.toUpperCase()" />
              <p class="dc-field-hint">Wajib & unik. Contoh: B 1234 ABC</p>
            </div>
            <div class="dc-field">
              <label>Harga Member (Rp)</label>
              <div class="dc-locked-field is-absolute">
                <span class="dc-locked-value">Rp {{ Number(form.total_harga || 150000).toLocaleString("id-ID") }}</span>
                <span class="dc-locked-badge">Absolute</span>
              </div>
              <p class="dc-field-hint">Harga tetap Rp 150.000 — tidak dapat diubah.</p>
            </div>
            <div v-if="errorMsg" class="dc-alert-error">{{ errorMsg }}</div>
            <div v-if="successMsg" class="dc-alert-success">{{ successMsg }}</div>
            <div class="dc-footer">
              <NuxtLink to="/petugas/member" class="dc-btn dc-btn-ghost">Batal</NuxtLink>
              <button type="submit" :disabled="saving" class="dc-btn dc-btn-primary">
                {{ saving ? "Menyimpan…" : "Simpan Perubahan" }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";

const route = useRoute();
const router = useRouter();
const { $api } = useNuxtApp();

const id = route.params.id;
const loading = ref(true);
const saving = ref(false);
const member = ref<any>(null);
const errorMsg = ref("");
const successMsg = ref("");
const form = ref({
  nama_member: "",
  nama_perusahaan: "",
  plat_nomor: "",
  total_harga: 150000,
});

const fetchMember = async () => {
  loading.value = true;
  try {
    const res = await $api.get(`/members/${id}`);
    member.value = res.data.data || res.data;
    form.value.nama_member = member.value.nama_member || "";
    form.value.nama_perusahaan = member.value.nama_perusahaan || "";
    form.value.plat_nomor = member.value.plat_nomor || "";
    form.value.total_harga = member.value.total_harga || 150000;
  } catch (e: any) {
    errorMsg.value = e?.response?.data?.message || "Gagal memuat data member.";
  } finally {
    loading.value = false;
  }
};

const saveMember = async () => {
  errorMsg.value = "";
  successMsg.value = "";
  if (!form.value.plat_nomor.trim()) {
    errorMsg.value = "Plat nomor wajib diisi.";
    return;
  }
  saving.value = true;
  try {
    await $api.put(`/members/${id}`, {
      nama_member: form.value.nama_member,
      nama_perusahaan: form.value.nama_perusahaan,
      plat_nomor: form.value.plat_nomor.toUpperCase(),
    });
    successMsg.value = "Berhasil diperbarui.";
    setTimeout(() => router.push("/petugas/member"), 600);
  } catch (e: any) {
    const data = e?.response?.data;
    if (data?.errors) {
      errorMsg.value = Object.values(data.errors).flat().join(" ");
    } else {
      errorMsg.value = data?.message || "Gagal menyimpan perubahan.";
    }
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  fetchMember();
});
</script>

<style scoped>
.dc-dashboard-layout { display:flex; min-height:100vh; background:radial-gradient(circle at 20% -10%,#3a4b7c 0%,#1e294b 45%,#111827 100%); font-family:Inter,sans-serif; color:#f3f4f6; }
.dc-wrapper{ flex:1; padding:40px 24px; overflow-y:auto; }
.dc-shell{ max-width:640px; margin:0 auto; }
.dc-header{ display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:24px; }
.dc-eyebrow{ display:inline-flex; align-items:center; gap:7px; font-size:11.5px; font-weight:600; letter-spacing:.08em; text-transform:uppercase; color:#38bdf8; }
.dc-dot{ width:6px; height:6px; border-radius:50%; background:#38bdf8; box-shadow:0 0 10px #38bdf8; animation:dc-pulse 2s infinite; }
@keyframes dc-pulse{0%,100%{opacity:1}50%{opacity:.35}}
.dc-header h1{ font-size:25px; font-weight:700; margin:6px 0 0; color:#fff; }
.dc-btn{ border:none; border-radius:10px; padding:11px 18px; font-size:14px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; transition:all .12s; }
.dc-btn-primary{ background:linear-gradient(135deg,#fbbf24,#d97706); color:#1f1f1f; box-shadow:0 6px 18px rgba(251,191,36,.3); }
.dc-btn-primary:disabled{ opacity:.6; cursor:not-allowed; }
.dc-btn-ghost{ background:#334155; color:#e2e8f0; border:1px solid #475569; }
.dc-btn-ghost:hover{ background:#475569; }
.dc-card{ background:#1e293b; border:1px solid #334155; border-radius:16px; box-shadow:0 20px 50px -20px rgba(0,0,0,.5); }
.dc-loading{ padding:40px; text-align:center; color:#94a3b8; }
.dc-form-card{ padding:28px; }
.dc-field{ margin-bottom:16px; }
.dc-field label{ display:block; font-size:12.5px; font-weight:600; color:#e2e8f0; margin-bottom:6px; }
.dc-input{ width:100%; padding:10px 13px; border:1.5px solid #475569; border-radius:10px; font-size:14px; outline:none; box-sizing:border-box; background:#0f172a; color:#fff; }
.dc-input:focus{ border-color:#fbbf24; box-shadow:0 0 0 3px rgba(251,191,36,.2); }
.dc-locked-field{ display:flex; align-items:center; justify-content:space-between; gap:12px; padding:10px 13px; border:1.5px solid #334155; border-radius:10px; background:#0f172a; }
.dc-field-hint{ margin:6px 0 0; font-size:11px; color:#64748b; }
.dc-locked-value{ font-size:14px; font-weight:700; color:#e2e8f0; }
.dc-locked-badge{ display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:999px; font-size:10.5px; font-weight:700; text-transform:uppercase; background:rgba(251,191,36,.15); color:#fbbf24; }
.dc-footer{ display:flex; justify-content:flex-end; gap:8px; margin-top:20px; }
.dc-info{ background:rgba(56,189,248,.12); border:1px solid rgba(56,189,248,.25); color:#7dd3fc; padding:10px 14px; border-radius:10px; font-size:12.5px; margin-bottom:16px; }
.dc-alert-error{ background:rgba(239,68,68,.12); border:1px solid rgba(239,68,68,.3); color:#f87171; padding:10px 14px; border-radius:10px; font-size:12.5px; margin-bottom:12px; }
.dc-alert-success{ background:rgba(16,185,129,.12); border:1px solid rgba(16,185,129,.3); color:#34d399; padding:10px 14px; border-radius:10px; font-size:12.5px; margin-bottom:12px; }
</style>
