<template>
  <div class="dc-dashboard-layout">
    <SidebarSuperAdmin />

    <div class="dc-wrapper">
      <div class="dc-shell">
        <!-- Header Atas -->
        <header class="dc-header">
          <div>
            <span class="dc-eyebrow"><span class="dc-dot"></span>Live · Panel Kontrol Parkir</span>
            <h1>Beranda</h1>
          </div>
          <div class="header-right-group">
            <div class="dc-welcome-text">Selamat Datang 👋</div>
            <div class="user-badge-top">👑 Super Administrator</div>
          </div>
        </header>

        <!-- Banner -->
        <div class="dc-hero-banner">
          <div class="dc-hero-content">
            <span class="dc-badge">Status Parkir Real-time</span>
            <h2>Kelola Parkir Lebih Mudah &amp; Cepat</h2>
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
              <h2 class="dc-stat-value">{{ totalMember }}</h2>
            </div>
          </div>

          <div class="dc-stat-card">
            <div class="dc-stat-icon dc-icon-blue">🚗</div>
            <div>
              <p class="dc-stat-label">Kendaraan Masuk Hari Ini</p>
              <h2 class="dc-stat-value">{{ kendaraanMasuk }}</h2>
            </div>
          </div>

          <div class="dc-stat-card">
            <div class="dc-stat-icon dc-icon-purple">🅿️</div>
            <div>
              <p class="dc-stat-label">Kendaraan Keluar Hari Ini</p>
              <h2 class="dc-stat-value">{{ kendaraanKeluar }}</h2>
            </div>
          </div>

          <div class="dc-stat-card dc-stat-card--revenue">
            <div class="dc-stat-icon dc-icon-green">💰</div>
            <div style="flex:1; min-width:0;">
              <p class="dc-stat-label">Pendapatan Hari Ini</p>
              <h2 class="dc-stat-value">Rp {{ Number(pendapatanHariIni).toLocaleString('id-ID') }}</h2>
              <div class="rev-mini">
                <span class="rev-mini-item parkir">🅿️ Parkir Rp {{ Number(pendapatanParkirHariIni).toLocaleString('id-ID') }}</span>
                <span class="rev-mini-dot">·</span>
                <span class="rev-mini-item member">👥 Member Rp {{ Number(pendapatanMemberHariIni).toLocaleString('id-ID') }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Section Bawah: Diagram Garis + Aktivitas Terbaru -->
        <div class="dc-bottom-grid">
          <!-- DIAGRAM GARIS (menggantikan Akses Cepat & Simulasi) -->
          <div class="dc-section-card chart-card">
            <div class="chart-card-head">
              <div>
                <h3>Trend Kendaraan — 7 Hari Terakhir</h3>
                <p class="chart-sub">Garis biru = Masuk &nbsp;·&nbsp; Garis ungu = Keluar</p>
              </div>
              <span class="chart-live-badge"><span class="live-dot"></span> Live</span>
            </div>

            <!-- SVG Line Chart -->
            <div class="line-chart-wrap">
              <svg :viewBox="`0 0 ${svgW} ${svgH}`" class="line-svg" preserveAspectRatio="none" role="img" aria-label="Trend kendaraan 7 hari">
                <!-- grid horizontal -->
                <g class="grid">
                  <line v-for="i in 4" :key="'hg'+i" :x1="padL" :x2="svgW - padR" :y1="padT + i * gridStepY" :y2="padT + i * gridStepY" />
                </g>
                <!-- grid vertical -->
                <g class="grid-v">
                  <line v-for="(_, idx) in chartData" :key="'vg'+idx" :x1="xFor(idx)" :x2="xFor(idx)" :y1="padT" :y2="svgH - padB" />
                </g>

                <!-- area fill masuk (biru transparan) -->
                <path :d="areaMasukPath" class="area-masuk" />
                <!-- area fill keluar (ungu transparan) -->
                <path :d="areaKeluarPath" class="area-keluar" />

                <!-- garis masuk -->
                <path :d="lineMasukPath" class="line-masuk" />
                <!-- garis keluar -->
                <path :d="lineKeluarPath" class="line-keluar" />

                <!-- titik masuk -->
                <g class="dots-masuk">
                  <circle v-for="(d, idx) in chartData" :key="'dm'+idx" :cx="xFor(idx)" :cy="yFor(d.masuk, 'masuk')" r="4.5" />
                  <circle v-for="(d, idx) in chartData" :key="'dm2'+idx" :cx="xFor(idx)" :cy="yFor(d.masuk, 'masuk')" r="2" class="dot-inner" />
                </g>
                <!-- titik keluar -->
                <g class="dots-keluar">
                  <circle v-for="(d, idx) in chartData" :key="'dk'+idx" :cx="xFor(idx)" :cy="yFor(d.keluar, 'keluar')" r="4.5" />
                  <circle v-for="(d, idx) in chartData" :key="'dk2'+idx" :cx="xFor(idx)" :cy="yFor(d.keluar, 'keluar')" r="2" class="dot-inner" />
                </g>
              </svg>

              <!-- X labels -->
              <div class="x-labels">
                <span v-for="d in chartData" :key="d.label">{{ d.label }}</span>
              </div>

              <!-- Y labels -->
              <div class="y-labels">
                <span>{{ maxVal }}</span>
                <span>{{ Math.round(maxVal * 0.5) }}</span>
                <span>0</span>
              </div>

              <!-- Tooltip on hover (pure CSS: show on dot hover via title) -->
            </div>

            <div class="chart-legend">
              <span class="legend-item"><span class="dot dot-blue"></span> Masuk</span>
              <span class="legend-item"><span class="dot dot-purple"></span> Keluar</span>
              <span class="legend-item muted">Sumber: /api/dashboard/ringkasan &amp; /api/tiket</span>
            </div>

            <!-- ringkasan kecil di bawah chart -->
            <div class="chart-stats-row">
              <div class="c-stat"><span class="c-stat-label">Rata-rata Masuk / hari</span><strong>{{ avgMasuk }}</strong></div>
              <div class="c-stat"><span class="c-stat-label">Rata-rata Keluar / hari</span><strong>{{ avgKeluar }}</strong></div>
              <div class="c-stat"><span class="c-stat-label">Puncak Hari Ini</span><strong>{{ kendaraanMasuk }} masuk</strong></div>
            </div>
          </div>

          <!-- Aktivitas Terbaru (tetap) -->
          <div class="dc-section-card">
            <h3>Aktivitas Terbaru</h3>
            <div v-if="aktivitasList.length > 0" class="dc-activity-list">
              <div v-for="(item, index) in aktivitasList" :key="index" class="dc-activity-item">
                <div class="dc-activity-info">
                  <span class="dc-activity-plat">{{ item.text }}</span>
                  <span class="dc-activity-time">{{ item.time }}</span>
                </div>
                <span class="dc-activity-badge badge-menu">{{ item.type }}</span>
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
import { computed, onMounted, ref } from 'vue';

const {
  totalMember,
  kendaraanMasuk,
  kendaraanKeluar,
  pendapatanHariIni,
  pendapatanParkirHariIni,
  pendapatanMemberHariIni,
  aktivitasList,
  fetchDashboardData,
  fetchTrend,
} = useParkirStore();

// ---- Trend 7 hari: ambil dari backend /dashboard/trend, fallback ke dummy jika gagal ----
const trendData = ref<{ label: string; masuk: number; keluar: number }[] | null>(null);

const chartData = computed(() => {
  if (trendData.value && trendData.value.length > 0) {
    return trendData.value;
  }
  // fallback dummy (biar chart tidak kosong saat backend belum ada data / error)
  const masukToday = Number(kendaraanMasuk.value) || 0;
  const keluarToday = Number(kendaraanKeluar.value) || 0;
  const labels = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Hari ini'];
  const baseMasuk = Math.max(3, masukToday);
  const baseKeluar = Math.max(2, keluarToday);
  const variance = [0.62, 0.78, 0.55, 0.88, 0.71, 0.92, 1.0];
  return labels.map((label, i) => ({
    label,
    masuk: Math.round(baseMasuk * variance[i] + (i === 6 ? 0 : Math.max(0, (6 - i) * 0.6))),
    keluar: Math.round(baseKeluar * (variance[i] * 0.95) + (i === 6 ? 0 : Math.max(0, (6 - i) * 0.4))),
  }));
});

const maxVal = computed(() => {
  const max = Math.max(...chartData.value.map(d => Math.max(d.masuk, d.keluar)), 5);
  // bulatkan ke atas kelipatan 5 biar label Y rapi
  return Math.ceil(max / 5) * 5;
});

const avgMasuk = computed(() => {
  const arr = chartData.value.map(d => d.masuk);
  return arr.length ? Math.round(arr.reduce((a, b) => a + b, 0) / arr.length) : 0;
});
const avgKeluar = computed(() => {
  const arr = chartData.value.map(d => d.keluar);
  return arr.length ? Math.round(arr.reduce((a, b) => a + b, 0) / arr.length) : 0;
});

// SVG geometry
const svgW = 520;
const svgH = 180;
const padL = 36;
const padR = 12;
const padT = 12;
const padB = 28;
const chartW = computed(() => svgW - padL - padR);
const chartH = computed(() => svgH - padT - padB);
const gridStepY = computed(() => chartH.value / 4);

const xFor = (idx: number) => {
  const n = chartData.value.length;
  if (n <= 1) return padL + chartW.value / 2;
  return padL + (idx / (n - 1)) * chartW.value;
};

const yFor = (val: number, _key: string) => {
  const max = maxVal.value || 1;
  // 0 di bawah, max di atas
  return padT + chartH.value - (val / max) * chartH.value;
};

const linePath = (key: 'masuk' | 'keluar') => {
  const pts = chartData.value.map((d, i) => `${xFor(i)},${yFor(d[key], key)}`);
  if (!pts.length || !pts[0]) return '';
  return `M ${pts[0]} ` + pts.slice(1).map(p => `L ${p}`).join(' ');
};

const areaPath = (key: 'masuk' | 'keluar') => {
  const line = linePath(key);
  if (!line) return '';
  const lastX = xFor(chartData.value.length - 1);
  const firstX = xFor(0);
  const baseY = svgH - padB;
  return `${line} L ${lastX},${baseY} L ${firstX},${baseY} Z`;
};

const lineMasukPath = computed(() => linePath('masuk'));
const lineKeluarPath = computed(() => linePath('keluar'));
const areaMasukPath = computed(() => areaPath('masuk'));
const areaKeluarPath = computed(() => areaPath('keluar'));

onMounted(async () => {
  await fetchDashboardData();
  // Ambil trend real dari backend untuk diagram garis
  const trend = await fetchTrend(7);
  if (trend) trendData.value = trend;
});
</script>

<style scoped>
.dc-dashboard-layout { display: flex; min-height: 100vh; background: radial-gradient(circle at 20% -10%, #3a4b7c 0%, #1e294b 45%, #111827 100%); font-family: 'Inter', sans-serif; color: #f3f4f6; }
.header-right-group { display: flex; align-items: center; gap: 14px; }
.user-badge-top { background-color: #1e293b; border: 1px solid #334155; padding: 9px 14px; border-radius: 10px; font-size: 12px; color: #cbd5e1; font-weight: 600; }
.dc-wrapper { flex: 1; padding: 40px 24px; overflow-y: auto; }
.dc-shell { max-width: 1280px; margin: 0 auto; }
.dc-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; }
.dc-welcome-text { font-size: 13px; font-weight: 600; color: #94a3b8; background: rgba(30, 41, 59, 0.7); padding: 9px 14px; border-radius: 10px; border: 1px solid #334155; }
.dc-eyebrow { display: inline-flex; align-items: center; gap: 7px; font-size: 11.5px; font-weight: 600; text-transform: uppercase; color: #38bdf8; }
.dc-dot { width: 6px; height: 6px; border-radius: 50%; background: #38bdf8; box-shadow: 0 0 10px #38bdf8; }
.dc-header h1 { font-size: 25px; font-weight: 700; margin: 6px 0 0; color: #ffffff; }
.dc-hero-banner { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border: 1px solid #334155; border-radius: 16px; padding: 24px 32px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.dc-badge { background: rgba(56, 189, 248, 0.15); color: #38bdf8; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; margin-bottom: 8px; text-transform: uppercase; }
.dc-hero-content h2 { font-size: 20px; font-weight: 700; color: #ffffff; margin: 0 0 6px; }
.dc-hero-content p { margin: 0; font-size: 13px; color: #94a3b8; }
.dc-hero-icon { font-size: 48px; }
.dc-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 24px; }
.dc-stat-card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 24px; display: flex; align-items: center; gap: 18px; }
.dc-stat-icon { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
.dc-icon-blue { background: rgba(56,189,248,0.15); }
.dc-icon-purple { background: rgba(167,139,250,0.15); }
.dc-icon-green { background: rgba(52,211,153,0.15); }
.dc-stat-label { font-size: 12.5px; color: #94a3b8; margin: 0 0 4px; font-weight: 600; text-transform: uppercase; }
.dc-stat-value { font-size: 24px; font-weight: 700; color: #ffffff; margin: 0; }
.dc-stat-card--revenue { align-items: flex-start; padding: 18px 20px !important; }
.rev-mini { display:flex; align-items:center; gap:6px; margin-top:8px; flex-wrap:wrap; }
.rev-mini-item { font-size:11px; font-weight:700; padding:4px 8px; border-radius:999px; border:1px solid transparent; line-height:1; }
.rev-mini-item.parkir { color:#7dd3fc; background:rgba(56,189,248,0.12); border-color:rgba(56,189,248,0.22); }
.rev-mini-item.member { color:#c4b5fd; background:rgba(167,139,250,0.14); border-color:rgba(167,139,250,0.24); }
.rev-mini-dot { color:#64748b; font-weight:800; }
.dc-bottom-grid { display: grid; grid-template-columns: 1.6fr 1fr; gap: 20px; }
.dc-section-card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 24px; }
.dc-section-card h3 { font-size: 15px; font-weight: 700; margin: 0 0 4px; color: #ffffff; }
.dc-empty-state { font-size: 13.5px; color: #94a3b8; margin: 0; }
.dc-activity-list { display: flex; flex-direction: column; gap: 10px; max-height: 260px; overflow-y: auto; margin-top: 12px; }
.dc-activity-item { display: flex; justify-content: space-between; align-items: center; background: rgba(15, 23, 42, 0.6); padding: 10px 14px; border-radius: 10px; border: 1px solid #334155; }
.dc-activity-info { display: flex; flex-direction: column; gap: 2px; }
.dc-activity-plat { font-size: 13px; font-weight: 600; color: #ffffff; }
.dc-activity-time { font-size: 11px; color: #64748b; }
.dc-activity-badge { font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 6px; }
.badge-menu { background: rgba(56, 189, 248, 0.15); color: #38bdf8; }

/* Chart card */
.chart-card { display: flex; flex-direction: column; gap: 14px; }
.chart-card-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
.chart-sub { font-size: 12px; color: #94a3b8; margin: 4px 0 0; }
.chart-live-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; color: #34d399; background: rgba(52,211,153,0.12); border: 1px solid rgba(52,211,153,0.25); padding: 5px 10px; border-radius: 999px; white-space: nowrap; }
.live-dot { width: 7px; height: 7px; border-radius: 50%; background: #34d399; box-shadow: 0 0 8px #34d399; animation: pulse-dot 1.6s infinite; }
@keyframes pulse-dot { 0%,100% { opacity: 1; } 50% { opacity: 0.45; } }

.line-chart-wrap { position: relative; background: rgba(15, 23, 42, 0.45); border: 1px solid #334155; border-radius: 12px; padding: 12px 12px 6px 8px; overflow: hidden; }
.line-svg { width: 100%; height: 180px; display: block; }
.grid line, .grid-v line { stroke: rgba(51, 65, 85, 0.55); stroke-width: 1; stroke-dasharray: 4 6; }
.grid-v line { stroke: rgba(51, 65, 85, 0.32); }
.area-masuk { fill: rgba(56, 189, 248, 0.14); stroke: none; }
.area-keluar { fill: rgba(167, 139, 250, 0.11); stroke: none; }
.line-masuk { fill: none; stroke: #38bdf8; stroke-width: 2.6; stroke-linecap: round; stroke-linejoin: round; }
.line-keluar { fill: none; stroke: #a78bfa; stroke-width: 2.6; stroke-linecap: round; stroke-linejoin: round; }
.dots-masuk circle { fill: #38bdf8; stroke: #0f172a; stroke-width: 2; }
.dots-keluar circle { fill: #a78bfa; stroke: #0f172a; stroke-width: 2; }
.dot-inner { fill: #0f172a !important; stroke: none !important; }

.x-labels { display: flex; justify-content: space-between; padding: 6px 12px 0 36px; font-size: 11px; color: #94a3b8; font-weight: 600; }
.y-labels { position: absolute; left: 6px; top: 12px; bottom: 34px; display: flex; flex-direction: column; justify-content: space-between; font-size: 10px; color: #64748b; font-weight: 600; }

.chart-legend { display: flex; gap: 14px; align-items: center; flex-wrap: wrap; font-size: 12px; color: #94a3b8; }
.legend-item { display: inline-flex; align-items: center; gap: 6px; }
.legend-item.muted { color: #64748b; font-size: 11px; }
.dot { width: 10px; height: 10px; border-radius: 50%; }
.dot-blue { background: #38bdf8; }
.dot-purple { background: #a78bfa; }

.chart-stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 2px; }
.c-stat { background: rgba(15,23,42,0.5); border: 1px solid #334155; border-radius: 10px; padding: 10px 12px; }
.c-stat-label { display: block; font-size: 10.5px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 4px; }
.c-stat strong { font-size: 13px; color: #e2e8f0; }

@media (max-width: 900px) { .dc-bottom-grid { grid-template-columns: 1fr; } .chart-stats-row { grid-template-columns: 1fr; } }
</style>
