<template>
  <div class="dc-dashboard-layout">
    <SidebarSuperAdmin />

    <div class="dc-wrapper">
      <div class="dc-shell" ref="reportRef">
        <header class="dc-header">
          <div>
            <span class="dc-eyebrow"><span class="dc-dot"></span>Live · Panel Kontrol Parkir</span>
            <h1>Laporan Sistem Parkir</h1>
            <p class="dc-subtext">Sistem Pengelolaan Parkir &amp; Keanggotaan Member</p>
          </div>
          <div class="header-right-group" data-html2canvas-ignore>
            <div class="user-badge-top">👑 Super Administrator</div>
            <button class="btn-download-pdf" @click="downloadPDF" :disabled="isExportingPdf">
              {{ isExportingPdf ? '⏳ Membuat PDF...' : '⬇️ Unduh PDF' }}
            </button>
          </div>
        </header>

        <!-- Baris Statistik Utama -->
        <div class="stats-grid-top">
          <div class="dc-section-card stat-card-small">
            <span class="dc-stat-label">TOTAL KENDARAAN</span>
            <div class="stat-value-row">
              <h2>{{ stats.total_kendaraan }}</h2>
              <span class="badge-sub-info">{{ stats.reguler_count }} Reguler • {{ stats.member_count }} Member</span>
            </div>
          </div>

          <div class="dc-section-card stat-card-small">
            <span class="dc-stat-label">SEDANG PARKIR</span>
            <div class="stat-value-row">
              <h2>{{ stats.sedang_parkir }}</h2>
              <span class="badge-sub-info text-yellow">AKTIF di dalam gerbang</span>
            </div>
          </div>

          <div class="dc-section-card stat-card-small">
            <span class="dc-stat-label">KENDARAAN SELESAI</span>
            <div class="stat-value-row">
              <h2>{{ stats.selesai_keluar }}</h2>
              <span class="badge-sub-info text-cyan">Sudah checkout keluar</span>
            </div>
          </div>

          <div class="dc-section-card stat-card-small">
            <span class="dc-stat-label">TOTAL TRANSAKSI</span>
            <div class="stat-value-row">
              <h2>{{ stats.total_kendaraan }}</h2>
              <span class="badge-sub-info text-green">Seluruh riwayat tercatat</span>
            </div>
          </div>
        </div>

        <!-- Pendapatan & Status -->
        <div class="middle-grid-row">
          <div class="dc-section-card revenue-card">
            <span class="dc-stat-label">TOTAL PENDAPATAN (PARKIR + IURAN MEMBER)</span>
            <div class="revenue-main-val">Rp {{ stats.total_pendapatan.toLocaleString('id-ID') }}</div>
            <div class="revenue-breakdown">
              <span class="rev-pill">🅿️ Parkir: Rp {{ stats.pendapatan_parkir.toLocaleString('id-ID') }}</span>
              <span class="rev-pill rev-pill-member">👥 Iuran Member: Rp {{ stats.pendapatan_member.toLocaleString('id-ID') }}</span>
            </div>
            <div class="rev-footnote">Member bayar = langsung menambah pendapatan. Parkir member tetap gratis di palang, tapi iurannya tetap dihitung.</div>
          </div>

          <div class="dc-section-card status-mini-card">
            <span class="dc-stat-label">KOMPOSISI</span>
            <div class="status-flex-val">
              <h3>{{ stats.reguler_count }} / {{ stats.member_count }}</h3>
              <span class="sub-desc">Reguler / Member</span>
            </div>
          </div>

          <div class="dc-section-card status-mini-card">
            <span class="dc-stat-label">RATA-RATA TARIF</span>
            <div class="status-flex-val">
              <h3>Rp {{ rataRataTarif.toLocaleString('id-ID') }}</h3>
              <span class="sub-desc">Per transaksi reguler selesai</span>
            </div>
          </div>
        </div>

        <!-- Grafik -->
        <div class="charts-grid-row">
          <div class="dc-section-card chart-box">
            <div class="chart-header-top">
              <div>
                <h4>Kendaraan Masuk vs Keluar</h4>
                <p>Arus kendaraan aktif di area parkir vs telah checkout keluar</p>
              </div>
              <div class="chart-toggle-group" data-html2canvas-ignore>
                <span :class="{ 'active-toggle': chart1Type === 'bar' }" @click="chart1Type = 'bar'">Bar</span>
                <span :class="{ 'active-toggle': chart1Type === 'line' }" @click="chart1Type = 'line'">Line</span>
                <span :class="{ 'active-toggle': chart1Type === 'pie' }" @click="chart1Type = 'pie'">Pie</span>
              </div>
            </div>

            <div v-if="chart1Type === 'bar'" class="chart-visual-area">
              <div class="bar-column">
                <div class="bar-fill orange" :style="{ height: barHeight(stats.sedang_parkir, [stats.sedang_parkir, stats.selesai_keluar]) }"></div>
                <span>Sedang Parkir ({{ stats.sedang_parkir }})</span>
              </div>
              <div class="bar-column">
                <div class="bar-fill cyan" :style="{ height: barHeight(stats.selesai_keluar, [stats.sedang_parkir, stats.selesai_keluar]) }"></div>
                <span>Selesai ({{ stats.selesai_keluar }})</span>
              </div>
            </div>

            <div v-else-if="chart1Type === 'line'" class="chart-visual-area line-mode">
              <div class="line-graph-sim">
                <div class="line-point p1"><span>Parkir: {{ stats.sedang_parkir }}</span></div>
                <div class="line-point p2"><span>Selesai: {{ stats.selesai_keluar }}</span></div>
                <svg class="svg-line" viewBox="0 0 100 50" preserveAspectRatio="none">
                  <path :d="masukKeluarLinePath" stroke="#38bdf8" stroke-width="3" fill="none" />
                </svg>
              </div>
            </div>

            <div v-else class="chart-visual-area pie-mode">
              <div class="pie-chart-sim" :style="{ background: masukKeluarPieGradient }">
                <div class="pie-center-hole"><span>Arus</span></div>
              </div>
            </div>

            <div class="chart-legend-bottom">
              <span class="legend-item"><span class="dot-orange"></span> Sedang Parkir: {{ stats.sedang_parkir }}</span>
              <span class="legend-item"><span class="dot-cyan"></span> Selesai Keluar: {{ stats.selesai_keluar }}</span>
            </div>
          </div>

          <div class="dc-section-card chart-box">
            <div class="chart-header-top">
              <div>
                <h4>Komposisi Reguler vs Member</h4>
                <p>Perbandingan jumlah transaksi tiket reguler dan member</p>
              </div>
              <div class="chart-toggle-group" data-html2canvas-ignore>
                <span :class="{ 'active-toggle': chart2Type === 'bar' }" @click="chart2Type = 'bar'">Bar</span>
                <span :class="{ 'active-toggle': chart2Type === 'line' }" @click="chart2Type = 'line'">Line</span>
                <span :class="{ 'active-toggle': chart2Type === 'pie' }" @click="chart2Type = 'pie'">Pie</span>
              </div>
            </div>

            <div v-if="chart2Type === 'bar'" class="chart-visual-area">
              <div class="bar-column">
                <div class="bar-fill blue" :style="{ height: barHeight(stats.reguler_count, [stats.reguler_count, stats.member_count]) }"></div>
                <span>Reguler ({{ stats.reguler_count }})</span>
              </div>
              <div class="bar-column">
                <div class="bar-fill green" :style="{ height: barHeight(stats.member_count, [stats.reguler_count, stats.member_count]) }"></div>
                <span>Member ({{ stats.member_count }})</span>
              </div>
            </div>

            <div v-else-if="chart2Type === 'line'" class="chart-visual-area line-mode">
              <div class="line-graph-sim">
                <div class="line-point p1"><span>Reguler</span></div>
                <div class="line-point p3"><span>Member</span></div>
                <svg class="svg-line" viewBox="0 0 100 50" preserveAspectRatio="none">
                  <path :d="komposisiLinePath" stroke="#10b981" stroke-width="3" fill="none" />
                </svg>
              </div>
            </div>

            <div v-else class="chart-visual-area pie-mode">
              <div class="pie-chart-sim revenue-pie" :style="{ background: komposisiPieGradient }">
                <div class="pie-center-hole"><span>{{ persenMember }}%</span></div>
              </div>
            </div>

            <div class="chart-legend-bottom">
              <span class="legend-item"><span class="dot-blue"></span> Reguler: {{ stats.reguler_count }}</span>
              <span class="legend-item"><span class="dot-green"></span> Member: {{ stats.member_count }}</span>
            </div>
          </div>
        </div>

        <!-- Tabel Rincian -->
        <div class="dc-section-card" style="margin-top: 24px;">
          <div class="table-header-title">
            <div>
              <h3>Rincian Data Laporan Terpadu</h3>
              <p class="table-sub-desc">Daftar transaksi parkir reguler dan member, bersumber langsung dari database.</p>
            </div>
            <span class="data-count">Menampilkan {{ filteredLaporan.length }} data</span>
          </div>

          <div class="table-filter-bar" data-html2canvas-ignore>
            <input type="text" v-model="searchKeyword" placeholder="Cari kode, plat nomor..." class="dc-input search-input" />
            <select class="dc-input select-input" v-model="filterStatus">
              <option>Semua Status</option>
              <option>Aktif/Parkir</option>
              <option>Selesai</option>
            </select>
          </div>

          <div class="table-responsive">
            <table class="custom-table">
              <thead>
                <tr>
                  <th>KODE</th>
                  <th>TIPE</th>
                  <th>PLAT NOMOR</th>
                  <th>WAKTU MASUK</th>
                  <th>WAKTU KELUAR</th>
                  <th>TARIF/BIAYA</th>
                  <th>STATUS</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="isLoading">
                  <td colspan="7" style="text-align: center; padding: 20px; color: #94a3b8;">Memuat data laporan...</td>
                </tr>
                <tr v-else-if="filteredLaporan.length === 0">
                  <td colspan="7" style="text-align: center; padding: 20px; color: #94a3b8;">Tidak ada data ditemukan.</td>
                </tr>
                <tr v-for="item in filteredLaporan" :key="item.id">
                  <td><strong :class="isMemberKode(item.kode_tiket) ? 'text-yellow' : 'text-cyan'">{{ item.kode_tiket }}</strong></td>
                  <td>{{ isMemberKode(item.kode_tiket) ? 'Member' : 'Reguler' }}</td>
                  <td>{{ item.plat_nomor || '-' }}</td>
                  <td>{{ formatTanggal(item.waktu_masuk) }}</td>
                  <td>{{ item.waktu_keluar ? formatTanggal(item.waktu_keluar) : '-' }}</td>
                  <td>Rp {{ Number(item.tarif || 0).toLocaleString('id-ID') }}</td>
                  <td><span class="dc-activity-badge" :class="item.status === 'Selesai' ? 'badge-green' : 'badge-yellow'">{{ item.status === 'Selesai' ? '🟢 Selesai' : '🟡 Sedang Parkir' }}</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Log Aktivitas -->
        <div class="dc-section-card" style="margin-top: 24px;">
          <div class="table-header-title">
            <div>
              <h3>Log Aktivitas Gerbang Terbaru</h3>
              <p class="table-sub-desc">Transaksi terbaru yang tercatat di sistem.</p>
            </div>
          </div>

          <div class="log-list-container">
            <div v-if="recentLogs.length === 0" style="text-align: center; padding: 15px; color: #94a3b8; font-size: 12.5px;">
              Belum ada aktivitas tercatat.
            </div>
            <div v-for="log in recentLogs" :key="log.id" class="log-item">
              <span class="log-badge" :class="log.status === 'Selesai' ? 'green' : 'yellow'">
                {{ log.status === 'Selesai' ? 'KELUAR' : 'MASUK' }}
              </span>
              <div class="log-info">
                <strong>{{ log.kode_tiket }}</strong>
                <p>{{ isMemberKode(log.kode_tiket) ? 'Member' : 'Reguler' }} — {{ log.plat_nomor || '-' }}</p>
              </div>
              <span class="log-time">{{ formatTanggal(log.status === 'Selesai' ? log.waktu_keluar : log.waktu_masuk) }}</span>
            </div>
          </div>
        </div>

        <p class="pdf-generated-note" data-pdf-only>
          Laporan dibuat otomatis pada {{ formatTanggal(new Date().toISOString()) }}
        </p>

      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';

interface TiketItem {
  id: number;
  kode_tiket: string;
  status: string;
  waktu_masuk: string;
  waktu_keluar: string | null;
  jenis: string;
  plat_nomor: string;
  tarif: number;
}

const { $api } = useNuxtApp();

const chart1Type = ref<'bar' | 'line' | 'pie'>('bar');
const chart2Type = ref<'bar' | 'line' | 'pie'>('bar');
const isLoading = ref(false);
const searchKeyword = ref('');
const filterStatus = ref('Semua Status');
const listTiket = ref<TiketItem[]>([]);
const listMemberForRevenue = ref<any[]>([]);
const pendapatanMemberTotal = ref(0);

// --- export PDF ---
const reportRef = ref<HTMLElement | null>(null);
const isExportingPdf = ref(false);

const downloadPDF = async () => {
  if (!reportRef.value || isExportingPdf.value) return;
  isExportingPdf.value = true;
  try {
    // html2pdf.js dimuat secara dinamis (client-only) agar aman dipakai di Nuxt/SSR.
    // Pastikan sudah terpasang: npm install html2pdf.js
    const html2pdf = (await import('html2pdf.js')).default;

    const tanggalFile = new Date().toISOString().slice(0, 10);
    const opt = {
      margin: [8, 6, 8, 6],
      filename: `laporan-parkir-${tanggalFile}.pdf`,
      image: { type: 'jpeg', quality: 0.98 },
      html2canvas: {
        scale: 2,
        backgroundColor: '#111827',
        useCORS: true,
      },
      jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
      pagebreak: { mode: ['avoid-all', 'css', 'legacy'] },
    };

    await html2pdf().set(opt).from(reportRef.value).save();
  } catch (error) {
    console.error('Gagal membuat PDF:', error);
    alert('Gagal membuat PDF. Pastikan library "html2pdf.js" sudah terpasang (npm install html2pdf.js).');
  } finally {
    isExportingPdf.value = false;
  }
};

const isMemberKode = (kode: string) => (kode || '').startsWith('MBR-');

const fetchLaporanData = async () => {
  isLoading.value = true;
  try {
    const res: any = await $api.get('/tiket');
    listTiket.value = res.data?.data || res.data || [];
  } catch (error) {
    console.error('Gagal mengambil data laporan:', error);
  } finally {
    isLoading.value = false;
  }
};

const fetchMemberRevenue = async () => {
  try {
    const res: any = await $api.get('/members');
    const data = res.data?.data ?? res.data ?? [];
    listMemberForRevenue.value = Array.isArray(data) ? data : [];
    // hanya yang lunas yang dihitung sebagai pendapatan iuran
    pendapatanMemberTotal.value = listMemberForRevenue.value
      .filter((m: any) => m.status === 'lunas')
      .reduce((sum: number, m: any) => sum + Number(m.jumlah_bayar || 0), 0);
  } catch {
    // jika gagal, biarkan 0 — card tetap tampil parkir saja
  }
};

const stats = computed(() => {
  const all = listTiket.value;
  const selesai = all.filter(t => t.status === 'Selesai');
  const reguler = all.filter(t => !isMemberKode(t.kode_tiket));
  const member = all.filter(t => isMemberKode(t.kode_tiket));
  const pendapatanParkir = selesai.reduce((sum, t) => sum + Number(t.tarif || 0), 0);
  const pendapatanMember = pendapatanMemberTotal.value;
  const totalPendapatan = pendapatanParkir + pendapatanMember;

  return {
    total_kendaraan: all.length,
    reguler_count: reguler.length,
    member_count: member.length,
    sedang_parkir: all.filter(t => t.status !== 'Selesai').length,
    selesai_keluar: selesai.length,
    total_pendapatan: totalPendapatan,
    pendapatan_parkir: pendapatanParkir,
    pendapatan_member: pendapatanMember,
    // alias lama biar tidak break komponen lain
    pendapatan_reguler: pendapatanParkir,
  };
});

const rataRataTarif = computed(() => {
  const regulerSelesai = listTiket.value.filter(t => t.status === 'Selesai' && !isMemberKode(t.kode_tiket));
  if (regulerSelesai.length === 0) return 0;
  const total = regulerSelesai.reduce((sum, t) => sum + Number(t.tarif || 0), 0);
  return Math.round(total / regulerSelesai.length);
});

const persenMember = computed(() => {
  const total = stats.value.reguler_count + stats.value.member_count;
  if (total === 0) return 0;
  return Math.round((stats.value.member_count / total) * 100);
});

const barHeight = (value: number, all: number[]) => {
  const max = Math.max(...all, 1);
  return `${Math.max(6, Math.round((value / max) * 100))}%`;
};

const masukKeluarLinePath = computed(() => {
  const max = Math.max(stats.value.sedang_parkir, stats.value.selesai_keluar, 1);
  const y1 = 40 - (stats.value.sedang_parkir / max) * 30;
  const y2 = 40 - (stats.value.selesai_keluar / max) * 30;
  return `M 20 ${y1} L 80 ${y2}`;
});

const komposisiLinePath = computed(() => {
  const max = Math.max(stats.value.reguler_count, stats.value.member_count, 1);
  const y1 = 40 - (stats.value.reguler_count / max) * 30;
  const y2 = 40 - (stats.value.member_count / max) * 30;
  return `M 20 ${y1} L 80 ${y2}`;
});

const masukKeluarPieGradient = computed(() => {
  const total = stats.value.sedang_parkir + stats.value.selesai_keluar;
  const pct = total === 0 ? 180 : (stats.value.sedang_parkir / total) * 360;
  return `conic-gradient(#f59e0b 0deg ${pct}deg, #06b6d4 ${pct}deg 360deg)`;
});

const komposisiPieGradient = computed(() => {
  const pct = 360 - (persenMember.value / 100) * 360;
  return `conic-gradient(#3b82f6 0deg ${pct}deg, #10b981 ${pct}deg 360deg)`;
});

const filteredLaporan = computed(() => {
  return listTiket.value.filter(item => {
    const kw = searchKeyword.value.toLowerCase();
    const matchKeyword = !kw ||
      (item.kode_tiket || '').toLowerCase().includes(kw) ||
      (item.plat_nomor || '').toLowerCase().includes(kw);
    const matchStatus = filterStatus.value === 'Semua Status' || item.status === filterStatus.value;
    return matchKeyword && matchStatus;
  });
});

const recentLogs = computed(() => {
  return [...listTiket.value]
    .sort((a, b) => new Date(b.waktu_keluar || b.waktu_masuk).getTime() - new Date(a.waktu_keluar || a.waktu_masuk).getTime())
    .slice(0, 6);
});

const formatTanggal = (val: string | null) => {
  if (!val) return '-';
  return new Date(val).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

onMounted(() => {
  fetchLaporanData();
  fetchMemberRevenue();
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

.header-right-group {
  display: flex;
  align-items: center;
  gap: 14px;
}

.user-badge-top {
  background-color: #1e293b;
  border: 1px solid #334155;
  padding: 9px 14px;
  border-radius: 10px;
  font-size: 12px;
  color: #cbd5e1;
  font-weight: 600;
}

.btn-download-pdf {
  background: #2563eb;
  border: 1px solid #2563eb;
  color: #fff;
  padding: 9px 16px;
  border-radius: 10px;
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
  white-space: nowrap;
}
.btn-download-pdf:hover { background: #1d4ed8; }
.btn-download-pdf:disabled { opacity: 0.6; cursor: not-allowed; }

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
  align-items: flex-end;
  margin-bottom: 24px; 
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
}

.dc-header h1 { 
  font-size: 25px; 
  font-weight: 700; 
  margin: 6px 0 2px; 
  color: #ffffff; 
}

.dc-subtext {
  font-size: 13px;
  color: #94a3b8;
  margin: 0;
}

.dc-section-card {
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 20px 50px -20px rgba(0,0,0,0.5);
}

.stats-grid-top {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
  margin-bottom: 16px;
}

.stat-card-small {
  padding: 18px 20px !important;
}

.stat-value-row {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  margin-top: 8px;
}

.stat-value-row h2 {
  font-size: 26px;
  font-weight: 800;
  margin: 0;
  color: #fff;
}

.badge-sub-info {
  font-size: 11.5px;
  font-weight: 600;
  color: #94a3b8;
}

.middle-grid-row {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr;
  gap: 16px;
  margin-bottom: 16px;
}

@media(max-width: 992px) {
  .middle-grid-row {
    grid-template-columns: 1fr;
  }
}

.revenue-card {
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
  border: 1px solid #3b82f6 !important;
}

.revenue-main-val {
  font-size: 30px;
  font-weight: 800;
  color: #38bdf8;
  margin: 10px 0;
}

.revenue-breakdown {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.rev-pill {
  background: rgba(30, 58, 138, 0.4);
  border: 1px solid #1e40af;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 11.5px;
  color: #93c5fd;
  font-weight: 600;
}
.rev-pill-member {
  background: rgba(16, 185, 129, 0.15);
  border-color: rgba(16, 185, 129, 0.3);
  color: #6ee7b7;
}
.rev-footnote {
  margin-top: 10px;
  font-size: 11px;
  color: #64748b;
  line-height: 1.5;
}

.status-mini-card .status-flex-val {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  margin-top: 10px;
}

.status-flex-val h3 {
  font-size: 22px;
  margin: 0;
  font-weight: 700;
  color: #fff;
}

.sub-desc {
  font-size: 11px;
  color: #94a3b8;
}

.charts-grid-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

@media(max-width: 992px) {
  .charts-grid-row {
    grid-template-columns: 1fr;
  }
}

.chart-box {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.chart-header-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.chart-header-top h4 {
  margin: 0 0 4px;
  font-size: 15px;
  font-weight: 700;
  color: #fff;
}

.chart-header-top p {
  margin: 0;
  font-size: 11.5px;
  color: #94a3b8;
}

.chart-toggle-group {
  background: #0f172a;
  border: 1px solid #334155;
  padding: 3px;
  border-radius: 8px;
  display: flex;
  gap: 4px;
  font-size: 11px;
  color: #94a3b8;
  font-weight: 600;
}

.chart-toggle-group span {
  padding: 3px 8px;
  border-radius: 6px;
  cursor: pointer;
  transition: background 0.2s;
}

.chart-toggle-group span:hover {
  color: #fff;
}

.chart-toggle-group .active-toggle {
  background: #2563eb;
  color: #fff;
}

.chart-visual-area {
  height: 140px;
  display: flex;
  justify-content: space-around;
  align-items: flex-end;
  background: rgba(15, 23, 42, 0.4);
  border-radius: 10px;
  padding: 10px;
  border: 1px dashed #334155;
  position: relative;
  overflow: hidden;
}

.line-mode {
  display: flex;
  align-items: center;
  justify-content: center;
}

.line-graph-sim {
  width: 100%;
  height: 100%;
  position: relative;
}

.svg-line {
  width: 100%;
  height: 100%;
  position: absolute;
  top: 0;
  left: 0;
}

.line-point {
  position: absolute;
  width: 8px;
  height: 8px;
  background: #38bdf8;
  border-radius: 50%;
  z-index: 2;
}

.line-point span {
  position: absolute;
  font-size: 10px;
  color: #94a3b8;
  top: -16px;
  left: -12px;
  white-space: nowrap;
}

.line-point.p1 { top: 70%; left: 20%; }
.line-point.p2 { top: 30%; left: 80%; }
.line-point.p3 { top: 20%; left: 80%; background: #10b981; }

.pie-mode {
  display: flex;
  align-items: center;
  justify-content: center;
}

.pie-chart-sim {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.pie-center-hole {
  width: 55px;
  height: 55px;
  background: #1e293b;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  font-weight: 700;
  color: #94a3b8;
}

.bar-column {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  height: 100%;
  justify-content: flex-end;
  width: 60px;
}

.bar-column span {
  font-size: 10.5px;
  color: #94a3b8;
  font-weight: 600;
}

.bar-fill {
  width: 100%;
  border-radius: 6px 6px 0 0;
  transition: height 0.3s ease;
}

.bar-fill.orange { background: #f59e0b; }
.bar-fill.cyan { background: #06b6d4; }
.bar-fill.blue { background: #3b82f6; }
.bar-fill.green { background: #10b981; }

.chart-legend-bottom {
  display: flex;
  gap: 16px;
  font-size: 11.5px;
  color: #94a3b8;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 6px;
}

.legend-item span[class^="dot-"] {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.dot-orange { background: #f59e0b; }
.dot-cyan { background: #06b6d4; }
.dot-blue { background: #3b82f6; }
.dot-green { background: #10b981; }

.table-filter-bar {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 12px;
  margin-bottom: 16px;
}

@media(max-width: 992px) {
  .table-filter-bar {
    grid-template-columns: 1fr;
  }
}

.table-header-title {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 16px;
}

.table-header-title h3 {
  font-size: 16px;
  font-weight: 700;
  margin: 0 0 4px;
  color: #ffffff;
}

.table-sub-desc {
  font-size: 12px;
  color: #94a3b8;
  margin: 0;
}

.data-count {
  font-size: 12px;
  color: #38bdf8;
  background: rgba(56, 189, 248, 0.1);
  padding: 6px 12px;
  border-radius: 8px;
  font-weight: 600;
}

.table-responsive {
  width: 100%;
  overflow-x: auto;
}

.custom-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 12.5px;
}

.custom-table th {
  background-color: rgba(15, 23, 42, 0.6);
  color: #94a3b8;
  padding: 12px;
  font-weight: 700;
  border-bottom: 1px solid #334155;
}

.custom-table td {
  padding: 12px;
  border-bottom: 1px solid #334155;
  color: #e2e8f0;
}

.dc-input {
  background: #0f172a;
  border: 1px solid #334155;
  color: #fff;
  padding: 10px 14px;
  border-radius: 10px;
  font-size: 13px;
  outline: none;
  width: 100%;
  box-sizing: border-box;
}

.dc-input:focus {
  border-color: #38bdf8;
}

.dc-activity-badge {
  font-size: 11px;
  font-weight: 600;
  padding: 4px 10px;
  border-radius: 6px;
  display: inline-block;
}

.badge-yellow {
  background: rgba(245, 158, 11, 0.15);
  color: #f59e0b;
}

.badge-green {
  background: rgba(16, 185, 129, 0.15);
  color: #34d399;
}

.log-list-container {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.log-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid #334155;
  padding: 12px 16px;
  border-radius: 10px;
}

.log-info {
  flex: 1;
  margin: 0 16px;
}

.log-info strong {
  font-size: 13px;
  color: #fff;
}

.log-info p {
  margin: 2px 0 0;
  font-size: 12px;
  color: #94a3b8;
}

.log-badge {
  font-size: 10.5px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 6px;
}

.log-badge.green {
  background: rgba(16, 185, 129, 0.15);
  color: #34d399;
}

.log-badge.yellow {
  background: rgba(245, 158, 11, 0.15);
  color: #f59e0b;
}

.log-time {
  font-size: 11.5px;
  color: #64748b;
  font-weight: 600;
}

.text-cyan { color: #38bdf8; }
.text-yellow { color: #f59e0b; }
.text-green { color: #34d399; }
.text-orange { color: #fb923c; }

.pdf-generated-note {
  display: none;
}
</style>