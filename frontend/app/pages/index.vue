<template>
  <div class="dc-dashboard-layout">
    <Sidebar />
    
    <div class="dc-wrapper">
      <div class="dc-shell">
        <!-- Header Atas -->
        <header class="dc-header">
          <div>
            <span class="dc-eyebrow"><span class="dc-dot"></span>Live · Panel Kontrol Parkir</span>
            <h1>Beranda</h1>
          </div>
          <div class="dc-welcome-text">Selamat Datang 👋</div>
        </header>

        <!-- Banner "Kelola Parkir Lebih Mudah & Cepat" -->
        <div class="dc-hero-banner">
          <div class="dc-hero-content">
            <span class="dc-badge">Status Parkir Real-time</span>
            <h2>Kelola Parkir Lebih Mudah & Cepat</h2>
            <p>Pantau member, transaksi parkir, pembayaran, dan laporan dari satu dashboard.</p>
          </div>
          <div class="dc-hero-icon">🚗</div>
        </div>

        <!-- Grid 4 Stat Card -->
        <div class="dc-grid">
          <div class="dc-stat-card">
            <div class="dc-stat-icon dc-icon-blue">👥</div>
            <div>
              <p class="dc-stat-label">Total Member</p>
              <h2 class="dc-stat-value">{{ loading ? '…' : (ringkasan.total_member || 0) }}</h2>
            </div>
          </div>

          <div class="dc-stat-card">
            <div class="dc-stat-icon dc-icon-blue">🚗</div>
            <div>
              <p class="dc-stat-label">Kendaraan Masuk Hari Ini</p>
              <h2 class="dc-stat-value">{{ loading ? '…' : (ringkasan.kendaraan_masuk_hari_ini || 0) }}</h2>
            </div>
          </div>

          <div class="dc-stat-card">
            <div class="dc-stat-icon dc-icon-purple">🅿️</div>
            <div>
              <p class="dc-stat-label">Kendaraan Keluar Hari Ini</p>
              <h2 class="dc-stat-value">{{ loading ? '…' : (ringkasan.kendaraan_keluar_hari_ini || 0) }}</h2>
            </div>
          </div>

          <div class="dc-stat-card dc-stat-card--revenue">
            <div class="dc-stat-icon dc-icon-green">💰</div>
            <div style="flex:1; min-width:0;">
              <p class="dc-stat-label">Pendapatan Hari Ini</p>
              <h2 class="dc-stat-value">Rp {{ loading ? '…' : Number(ringkasan.pendapatan_hari_ini || 0).toLocaleString('id-ID') }}</h2>
              <div class="rev-mini">
                <span class="rev-mini-item parkir">🅿️ Parkir Rp {{ Number(ringkasan.pendapatan_parkir_hari_ini || 0).toLocaleString('id-ID') }}</span>
                <span class="rev-mini-dot">·</span>
                <span class="rev-mini-item member">👥 Member Rp {{ Number(ringkasan.pendapatan_member_hari_ini || 0).toLocaleString('id-ID') }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Section Bawah: Akses Cepat & Aktivitas Terbaru -->
        <div class="dc-bottom-grid">
          <div class="dc-section-card">
            <h3>Akses Cepat</h3>
            <div class="dc-action-buttons">
              <NuxtLink to="/petugas/scan-masuk" class="btn-primary">📥 Scan / Cetak Tiket Masuk</NuxtLink>
              <NuxtLink to="/petugas/scan-keluar" class="btn-secondary">📤 Proses Keluar Parkir</NuxtLink>
            </div>
          </div>

          <div class="dc-section-card">
            <h3>Aktivitas Terbaru</h3>
            <div v-if="aktivitasList.length > 0" class="dc-activity-list">
              <div v-for="(item, index) in aktivitasList" :key="index" class="dc-activity-item">
                <div class="dc-activity-info">
                  <span class="dc-activity-plat">{{ item.plat_nomor }}</span>
                  <span class="dc-activity-time">{{ item.waktu }}</span>
                </div>
                <span class="dc-activity-badge badge-menu">
                  {{ item.status }}
                </span>
              </div>
            </div>
            <p v-else class="dc-empty-state">Belum ada aktivitas kendaraan terbaru hari ini.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';

const { $api } = useNuxtApp();
const loading = ref(false);
const ringkasan = ref({
  total_member: 0,
  kendaraan_masuk_hari_ini: 0,
  kendaraan_keluar_hari_ini: 0,
  pendapatan_hari_ini: 0,
  pendapatan_parkir_hari_ini: 0,
  pendapatan_member_hari_ini: 0,
});
const aktivitasList = ref<any[]>([]);

const fetchRingkasan = async () => {
  loading.value = true;
  try {
    const res: any = await $api.get('/dashboard/ringkasan');
    const data = res.data?.data || res.data;
    ringkasan.value = {
      total_member: data.total_member || 0,
      kendaraan_masuk_hari_ini: data.kendaraan_masuk_hari_ini || 0,
      kendaraan_keluar_hari_ini: data.kendaraan_keluar_hari_ini || 0,
      pendapatan_hari_ini: data.pendapatan_hari_ini || 0,
      pendapatan_parkir_hari_ini: data.pendapatan_parkir_hari_ini ?? data.pendapatan_hari_ini ?? 0,
      pendapatan_member_hari_ini: data.pendapatan_member_hari_ini ?? 0,
    };
  } catch (e) {
    console.error('Gagal memuat ringkasan dashboard:', e);
  } finally {
    loading.value = false;
  }
};

const loadAktivitasLokal = () => {
  if (typeof window !== 'undefined') {
    const lokalAktivitas = JSON.parse(localStorage.getItem('riwayat_aktivitas') || '[]');
    aktivitasList.value = lokalAktivitas;
  }
};

onMounted(() => {
  fetchRingkasan();
  loadAktivitasLokal();
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

.dc-wrapper { 
  flex: 1; 
  padding: 40px 24px; 
  overflow-y: auto; 
}

.dc-shell { 
  max-width: 1280px; 
  margin: 0 auto; 
}

.dc-header { 
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px; 
}

.dc-welcome-text {
  font-size: 14px;
  font-weight: 600;
  color: #94a3b8;
  background: rgba(30, 41, 59, 0.7);
  padding: 8px 16px;
  border-radius: 12px;
  border: 1px solid #334155;
}

.dc-eyebrow { 
  display: inline-flex; 
  align-items: center; 
  gap: 7px; 
  font-size: 11.5px; 
  font-weight: 600; 
  letter-spacing: 0.08em; 
  text-transform: uppercase; 
  color: #38bdf8; 
}

.dc-dot { 
  width: 6px; 
  height: 6px; 
  border-radius: 50%; 
  background: #38bdf8; 
  box-shadow: 0 0 10px #38bdf8; 
  animation: dc-pulse 2s infinite; 
}

@keyframes dc-pulse { 
  0%, 100% { opacity: 1; } 
  50% { opacity: .35; } 
}

.dc-header h1 { 
  font-size: 25px; 
  font-weight: 700; 
  margin: 6px 0 0; 
  letter-spacing: -0.01em; 
  color: #ffffff; 
}

.dc-hero-banner {
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
  border: 1px solid #334155;
  border-radius: 16px;
  padding: 24px 32px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  box-shadow: 0 20px 50px -20px rgba(0,0,0,0.5);
}

.dc-badge {
  background: rgba(56, 189, 248, 0.15);
  color: #38bdf8;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
  display: inline-block;
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.dc-hero-content h2 {
  font-size: 20px;
  font-weight: 700;
  color: #ffffff;
  margin: 0 0 6px;
}

.dc-hero-content p {
  margin: 0;
  font-size: 13px;
  color: #94a3b8;
}

.dc-hero-icon {
  font-size: 48px;
}

.dc-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}

.dc-stat-card {
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 16px;
  padding: 24px;
  display: flex;
  align-items: center;
  gap: 18px;
  box-shadow: 0 20px 50px -20px rgba(0,0,0,0.5);
}

.dc-stat-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  flex-shrink: 0;
}

.dc-icon-blue { background: rgba(56,189,248,0.15); }
.dc-icon-purple { background: rgba(167,139,250,0.15); }
.dc-icon-green { background: rgba(52,211,153,0.15); }

.dc-stat-label { 
  font-size: 12.5px; 
  color: #94a3b8; 
  margin: 0 0 4px; 
  font-weight: 600; 
  text-transform: uppercase; 
  letter-spacing: 0.03em; 
}

.dc-stat-value { 
  font-size: 24px; 
  font-weight: 700; 
  color: #ffffff; 
  margin: 0; 
}

.dc-bottom-grid {
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: 20px;
}

.dc-section-card {
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 20px 50px -20px rgba(0,0,0,0.5);
}

.dc-section-card h3 {
  font-size: 15px;
  font-weight: 700;
  margin: 0 0 16px;
  color: #ffffff;
}

.dc-action-buttons {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.btn-primary {
  background: #2563eb;
  color: white;
  text-decoration: none;
  padding: 12px 16px;
  border-radius: 10px;
  font-size: 13.5px;
  font-weight: 600;
  text-align: center;
  display: block;
}

.btn-secondary {
  background: rgba(255, 255, 255, 0.05);
  color: #cbd5e1;
  border: 1px solid #334155;
  text-decoration: none;
  padding: 12px 16px;
  border-radius: 10px;
  font-size: 13.5px;
  font-weight: 600;
  text-align: center;
  display: block;
}

.dc-empty-state {
  font-size: 13.5px;
  color: #94a3b8;
  margin: 0;
}

.dc-activity-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-height: 140px;
  overflow-y: auto;
}

.dc-activity-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: rgba(15, 23, 42, 0.6);
  padding: 10px 14px;
  border-radius: 10px;
  border: 1px solid #334155;
}

.dc-activity-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.dc-activity-plat {
  font-size: 13px;
  font-weight: 600;
  color: #ffffff;
}

.dc-activity-time {
  font-size: 11px;
  color: #64748b;
}

.dc-activity-badge {
  font-size: 11px;
  font-weight: 600;
  padding: 3px 8px;
  border-radius: 6px;
}

.badge-menu {
  background: rgba(56, 189, 248, 0.15);
  color: #38bdf8;
}

.dc-stat-card--revenue { align-items:flex-start; padding:18px 20px !important; }
.rev-mini { display:flex; align-items:center; gap:6px; margin-top:8px; flex-wrap:wrap; }
.rev-mini-item { font-size:11px; font-weight:700; padding:4px 8px; border-radius:999px; border:1px solid transparent; line-height:1; }
.rev-mini-item.parkir { color:#7dd3fc; background:rgba(56,189,248,0.12); border-color:rgba(56,189,248,0.22); }
.rev-mini-item.member { color:#c4b5fd; background:rgba(167,139,250,0.14); border-color:rgba(167,139,250,0.24); }
.rev-mini-dot { color:#64748b; font-weight:800; }

@media (max-width: 768px) {
  .dc-bottom-grid {
    grid-template-columns: 1fr;
  }
}
</style>