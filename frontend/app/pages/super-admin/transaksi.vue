<template>
  <div class="dc-dashboard-layout">
    <SidebarSuperAdmin />

    <div class="dc-wrapper">
      <div class="dc-shell">
        <header class="dc-header">
          <div>
            <span class="dc-eyebrow"><span class="dc-dot"></span>Live · Panel Kontrol Parkir</span>
            <h1>Kelola Transaksi</h1>
            <p class="dc-subtext">Transaksi & Member — plat wajib untuk semua</p>
          </div>
          <div class="header-right-group">
            <div class="user-badge-top">👑 Super Administrator</div>
          </div>
        </header>

        <!-- Kartu Ringkasan -->
        <div class="stats-grid-top">
          <div class="dc-section-card stat-card-small">
            <span class="dc-stat-label">TOTAL TRANSAKSI BULAN INI</span>
            <div class="stat-value-row">
              <h2>{{ totalTransaksiBulanIni }}</h2>
              <span class="badge-sub-info">{{ namaBulanIni }}</span>
            </div>
          </div>

          <div class="dc-section-card stat-card-small stat-card-revenue">
            <span class="dc-stat-label">PENDAPATAN BULAN INI</span>
            <div class="stat-value-row">
              <h2>Rp {{ pendapatanBulanIni.toLocaleString('id-ID') }}</h2>
              <span class="badge-sub-info text-green">Parkir + Iuran</span>
            </div>
            <div class="rev-mini">
              <span class="rev-mini-item parkir">🅿️ Rp {{ pendapatanParkirBulanIni.toLocaleString('id-ID') }}</span>
              <span class="rev-mini-dot">·</span>
              <span class="rev-mini-item member">👥 Rp {{ pendapatanMemberBulanIni.toLocaleString('id-ID') }}</span>
            </div>
          </div>

          <div class="dc-section-card stat-card-small">
            <span class="dc-stat-label">SEDANG PARKIR</span>
            <div class="stat-value-row">
              <h2>{{ sedangParkirCount }}</h2>
              <span class="badge-sub-info text-yellow">Aktif di gerbang</span>
            </div>
          </div>

          <div class="dc-section-card stat-card-small">
            <span class="dc-stat-label">REGULER / MEMBER</span>
            <div class="stat-value-row">
              <h2>{{ regulerCount }} / {{ memberCount }}</h2>
              <span class="badge-sub-info">Total keseluruhan</span>
            </div>
          </div>
        </div>

        <!-- Grafik -->
        <div class="charts-grid-row">
          <div class="dc-section-card chart-box">
            <div class="chart-header-top">
              <div>
                <h4>Transaksi Bulan Ini vs Bulan Lalu</h4>
                <p>Perbandingan jumlah transaksi antar bulan</p>
              </div>
              <div class="chart-toggle-group">
                <span :class="{ 'active-toggle': chart1Type === 'bar' }" @click="chart1Type = 'bar'">Bar</span>
                <span :class="{ 'active-toggle': chart1Type === 'line' }" @click="chart1Type = 'line'">Line</span>
                <span :class="{ 'active-toggle': chart1Type === 'pie' }" @click="chart1Type = 'pie'">Pie</span>
              </div>
            </div>

            <div v-if="chart1Type === 'bar'" class="chart-visual-area">
              <div class="bar-column">
                <div class="bar-fill cyan" :style="{ height: barHeight(totalTransaksiBulanLalu, [totalTransaksiBulanLalu, totalTransaksiBulanIni]) }"></div>
                <span>Bulan Lalu ({{ totalTransaksiBulanLalu }})</span>
              </div>
              <div class="bar-column">
                <div class="bar-fill orange" :style="{ height: barHeight(totalTransaksiBulanIni, [totalTransaksiBulanLalu, totalTransaksiBulanIni]) }"></div>
                <span>Bulan Ini ({{ totalTransaksiBulanIni }})</span>
              </div>
            </div>

            <div v-else-if="chart1Type === 'line'" class="chart-visual-area line-mode">
              <div class="line-graph-sim">
                <div class="line-point p1"><span>Lalu: {{ totalTransaksiBulanLalu }}</span></div>
                <div class="line-point p2"><span>Ini: {{ totalTransaksiBulanIni }}</span></div>
                <svg class="svg-line" viewBox="0 0 100 50" preserveAspectRatio="none">
                  <path :d="trendLinePath" stroke="#38bdf8" stroke-width="3" fill="none" />
                </svg>
              </div>
            </div>

            <div v-else class="chart-visual-area pie-mode">
              <div class="pie-chart-sim" :style="{ background: bulanPieGradient }">
                <div class="pie-center-hole"><span>Bulan</span></div>
              </div>
            </div>

            <div class="chart-legend-bottom">
              <span class="legend-item"><span class="dot-cyan"></span> Bulan Lalu: {{ totalTransaksiBulanLalu }}</span>
              <span class="legend-item"><span class="dot-orange"></span> Bulan Ini: {{ totalTransaksiBulanIni }}</span>
            </div>
          </div>

          <div class="dc-section-card chart-box">
            <div class="chart-header-top">
              <div>
                <h4>Komposisi Reguler vs Member</h4>
                <p>Perbandingan jumlah transaksi tiket reguler dan member</p>
              </div>
              <div class="chart-toggle-group">
                <span :class="{ 'active-toggle': chart2Type === 'bar' }" @click="chart2Type = 'bar'">Bar</span>
                <span :class="{ 'active-toggle': chart2Type === 'line' }" @click="chart2Type = 'line'">Line</span>
                <span :class="{ 'active-toggle': chart2Type === 'pie' }" @click="chart2Type = 'pie'">Pie</span>
              </div>
            </div>

            <div v-if="chart2Type === 'bar'" class="chart-visual-area">
              <div class="bar-column">
                <div class="bar-fill blue" :style="{ height: barHeight(regulerCount, [regulerCount, memberCount]) }"></div>
                <span>Reguler ({{ regulerCount }})</span>
              </div>
              <div class="bar-column">
                <div class="bar-fill green" :style="{ height: barHeight(memberCount, [regulerCount, memberCount]) }"></div>
                <span>Member ({{ memberCount }})</span>
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
                <div class="pie-center-hole"><span>{{ komposisiMemberPersen }}%</span></div>
              </div>
            </div>

            <div class="chart-legend-bottom">
              <span class="legend-item"><span class="dot-blue"></span> Reguler: {{ regulerCount }}</span>
              <span class="legend-item"><span class="dot-green"></span> Member: {{ memberCount }}</span>
            </div>
          </div>
        </div>

        <!-- Tabel Transaksi -->
        <div class="dc-section-card" style="margin-top: 24px;">
          <div class="table-header-title">
            <div>
              <h3>Daftar Transaksi</h3>
              <p class="table-sub-desc">Seluruh riwayat tiket parkir, bisa dicari dan dihapus.</p>
            </div>
            <span class="data-count">Menampilkan {{ filteredTiket.length }} data</span>
          </div>

          <div class="table-filter-bar">
            <input type="text" v-model="searchKeyword" placeholder="Cari kode tiket, plat nomor..." class="dc-input search-input" />
            <select class="dc-input select-input" v-model="filterStatus">
              <option>Semua Status</option>
              <option>Aktif/Parkir</option>
              <option>Selesai</option>
            </select>
            <select class="dc-input select-input" v-model="filterTipe">
              <option>Semua Tipe</option>
              <option>Reguler</option>
              <option>Member</option>
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
                  <th>TARIF</th>
                  <th>STATUS</th>
                  <th>AKSI</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="isLoading">
                  <td colspan="8" style="text-align:center; padding:20px; color:#94a3b8;">Memuat data...</td>
                </tr>
                <tr v-else-if="filteredTiket.length === 0">
                  <td colspan="8" style="text-align:center; padding:20px; color:#94a3b8;">Tidak ada transaksi ditemukan.</td>
                </tr>
                <tr v-for="item in filteredTiket" :key="item.id">
                  <td><strong :class="isKodeMember(item.kode_tiket) ? 'text-yellow' : 'text-cyan'">{{ item.kode_tiket }}</strong></td>
                  <td>{{ isKodeMember(item.kode_tiket) ? 'Member' : 'Reguler' }}</td>
                  <td>{{ item.plat_nomor || '-' }}</td>
                  <td>{{ formatTanggal(item.waktu_masuk) }}</td>
                  <td>{{ item.waktu_keluar ? formatTanggal(item.waktu_keluar) : '-' }}</td>
                  <td>Rp {{ Number(item.tarif || 0).toLocaleString('id-ID') }}</td>
                  <td>
                    <span class="dc-activity-badge" :class="item.status === 'Selesai' ? 'badge-green' : 'badge-yellow'">
                      {{ item.status === 'Selesai' ? '🟢 Selesai' : '🟡 Aktif/Parkir' }}
                    </span>
                  </td>
                  <td>
                    <button class="btn-action-delete" :disabled="deletingId === item.id" @click="hapusTransaksi(item)">
                      {{ deletingId === item.id ? 'Menghapus…' : 'Hapus' }}
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";

const { $api } = useNuxtApp();

interface TiketItem {
  id: number;
  kode_tiket: string;
  status: string;
  waktu_masuk: string;
  waktu_keluar: string | null;
  jenis: string;
  kendaraan: string;
  plat_nomor: string;
  tarif: number;
}

interface MemberItem {
  id: number;
  kode_member: string;
  nama_member: string;
  nama_perusahaan: string;
  plat_nomor: string;
  total_harga: number;
  jumlah_bayar: number;
  status: string;
  tanggal_bayar: string | null;
  tanggal_expired: string | null;
}

// --- tiket state ---
const isLoading = ref(false);
const listTiket = ref<TiketItem[]>([]);
const searchKeyword = ref('');
const filterStatus = ref('Semua Status');
const filterTipe = ref('Semua Tipe');
const chart1Type = ref<'bar' | 'line' | 'pie'>('bar');
const chart2Type = ref<'bar' | 'line' | 'pie'>('bar');
const deletingId = ref<number | null>(null);

// --- member data (read-only, used for revenue stat only; CRUD UI removed) ---
const listMember = ref<MemberItem[]>([]);
const pendapatanMemberBulanIni = ref(0);

const isKodeMember = (kode: string) => (kode || '').startsWith('MBR-');

const now = new Date();
const bulanIni = now.getMonth();
const tahunIni = now.getFullYear();
const bulanLaluDate = new Date(tahunIni, bulanIni - 1, 1);
const namaBulanIni = now.toLocaleString('id-ID', { month: 'long', year: 'numeric' });

const isDalamBulan = (tanggal: string, bulan: number, tahun: number) => {
  if (!tanggal) return false;
  const d = new Date(tanggal);
  return d.getMonth() === bulan && d.getFullYear() === tahun;
};

const formatTanggal = (val: string | null) => {
  if (!val) return '-';
  return new Date(val).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

// --- fetch tiket ---
const fetchTiket = async () => {
  isLoading.value = true;
  try {
    const res: any = await $api.get('/tiket');
    listTiket.value = res.data?.data || res.data || [];
  } catch (e) {
    console.error('Gagal memuat data tiket:', e);
  } finally {
    isLoading.value = false;
  }
};

const hapusTransaksi = async (item: TiketItem) => {
  if (!confirm(`Hapus transaksi ${item.kode_tiket}?`)) return;
  deletingId.value = item.id;
  try {
    await $api.delete(`/tiket/${item.id}`);
    listTiket.value = listTiket.value.filter(t => t.id !== item.id);
  } catch (e) {
    alert('Gagal menghapus transaksi.');
  } finally {
    deletingId.value = null;
  }
};

// --- fetch member (only for revenue stat, no CRUD) ---
const fetchMembers = async () => {
  try {
    const res: any = await $api.get('/members');
    const data = res.data?.data ?? res.data ?? [];
    listMember.value = Array.isArray(data) ? data : [];
    pendapatanMemberBulanIni.value = listMember.value
      .filter((m: any) => m.status === 'lunas' && isDalamBulan(m.tanggal_bayar || '', bulanIni, tahunIni))
      .reduce((sum: number, m: any) => sum + Number(m.jumlah_bayar || 0), 0);
  } catch (e) {
    console.error('Gagal memuat data member:', e);
  }
};

// --- derived stats ---
const totalTransaksiBulanIni = computed(() =>
  listTiket.value.filter(t => isDalamBulan(t.waktu_masuk, bulanIni, tahunIni)).length
);

const totalTransaksiBulanLalu = computed(() =>
  listTiket.value.filter(t => isDalamBulan(t.waktu_masuk, bulanLaluDate.getMonth(), bulanLaluDate.getFullYear())).length
);

const pendapatanParkirBulanIni = computed(() =>
  listTiket.value
    .filter(t => t.status === 'Selesai' && isDalamBulan(t.waktu_keluar || '', bulanIni, tahunIni))
    .reduce((sum, t) => sum + Number(t.tarif || 0), 0)
);
const pendapatanBulanIni = computed(() => pendapatanParkirBulanIni.value + pendapatanMemberBulanIni.value);

const sedangParkirCount = computed(() => listTiket.value.filter(t => t.status !== 'Selesai').length);
const regulerCount = computed(() => listTiket.value.filter(t => !isKodeMember(t.kode_tiket)).length);
const memberCount = computed(() => listTiket.value.filter(t => isKodeMember(t.kode_tiket)).length);

const komposisiMemberPersen = computed(() => {
  const total = regulerCount.value + memberCount.value;
  if (total === 0) return 0;
  return Math.round((memberCount.value / total) * 100);
});

const barHeight = (value: number, all: number[]) => {
  const max = Math.max(...all, 1);
  const pct = Math.max(6, Math.round((value / max) * 100));
  return `${pct}%`;
};

const trendLinePath = computed(() => {
  const max = Math.max(totalTransaksiBulanLalu.value, totalTransaksiBulanIni.value, 1);
  const y1 = 40 - (totalTransaksiBulanLalu.value / max) * 30;
  const y2 = 40 - (totalTransaksiBulanIni.value / max) * 30;
  return `M 20 ${y1} L 80 ${y2}`;
});

const komposisiLinePath = computed(() => {
  const max = Math.max(regulerCount.value, memberCount.value, 1);
  const y1 = 40 - (regulerCount.value / max) * 30;
  const y2 = 40 - (memberCount.value / max) * 30;
  return `M 20 ${y1} L 80 ${y2}`;
});

const bulanPieGradient = computed(() => {
  const total = totalTransaksiBulanLalu.value + totalTransaksiBulanIni.value;
  const pct = total === 0 ? 180 : (totalTransaksiBulanLalu.value / total) * 360;
  return `conic-gradient(#06b6d4 0deg ${pct}deg, #f59e0b ${pct}deg 360deg)`;
});

const komposisiPieGradient = computed(() => {
  const pct = 360 - (komposisiMemberPersen.value / 100) * 360;
  return `conic-gradient(#3b82f6 0deg ${pct}deg, #10b981 ${pct}deg 360deg)`;
});

const filteredTiket = computed(() => {
  return listTiket.value.filter(item => {
    const kw = searchKeyword.value.toLowerCase();
    const matchKeyword = !kw ||
      (item.kode_tiket || '').toLowerCase().includes(kw) ||
      (item.plat_nomor || '').toLowerCase().includes(kw);

    const matchStatus = filterStatus.value === 'Semua Status' || item.status === filterStatus.value;

    const tipe = isKodeMember(item.kode_tiket) ? 'Member' : 'Reguler';
    const matchTipe = filterTipe.value === 'Semua Tipe' || tipe === filterTipe.value;

    return matchKeyword && matchStatus && matchTipe;
  });
});

onMounted(() => {
  fetchTiket();
  fetchMembers();
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
.dc-wrapper { flex: 1; padding: 40px 24px; overflow-y: auto; }
.dc-shell { max-width: 1280px; margin: 0 auto; }
.dc-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; }
.dc-eyebrow { display: inline-flex; align-items: center; gap: 7px; font-size: 11.5px; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: #38bdf8; }
.dc-dot { width: 6px; height: 6px; border-radius: 50%; background: #38bdf8; box-shadow: 0 0 10px #38bdf8; }
.dc-header h1 { font-size: 25px; font-weight: 700; margin: 6px 0 2px; color: #fff; }
.dc-subtext { font-size: 13px; color: #94a3b8; margin: 0; }

.dc-section-card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 24px; box-shadow: 0 20px 50px -20px rgba(0,0,0,0.5); }
.stats-grid-top { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 16px; }
.stat-card-small { padding: 18px 20px !important; }
.stat-value-row { display: flex; align-items: baseline; justify-content: space-between; margin-top: 8px; }
.stat-value-row h2 { font-size: 24px; font-weight: 800; margin: 0; color: #fff; }
.badge-sub-info { font-size: 11.5px; font-weight: 600; color: #94a3b8; }
.stat-card-revenue { flex-direction: column; align-items: flex-start !important; gap: 6px !important; }
.rev-mini { display: flex; align-items: center; gap: 6px; margin-top: 4px; }
.rev-mini-item { font-size: 10.5px; font-weight: 700; padding: 3px 7px; border-radius: 999px; }
.rev-mini-item.parkir { background: rgba(56, 189, 248, 0.15); color: #38bdf8; }
.rev-mini-item.member { background: rgba(16, 185, 129, 0.15); color: #34d399; }
.rev-mini-dot { color: #64748b; font-weight: 800; }

.charts-grid-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
@media(max-width: 992px) { .charts-grid-row { grid-template-columns: 1fr; } }
.chart-box { display: flex; flex-direction: column; gap: 16px; }
.chart-header-top { display: flex; justify-content: space-between; align-items: flex-start; }
.chart-header-top h4 { margin: 0 0 4px; font-size: 15px; font-weight: 700; color: #fff; }
.chart-header-top p { margin: 0; font-size: 11.5px; color: #94a3b8; }
.chart-toggle-group { background: #0f172a; border: 1px solid #334155; padding: 3px; border-radius: 8px; display: flex; gap: 4px; font-size: 11px; color: #94a3b8; font-weight: 600; }
.chart-toggle-group span { padding: 3px 8px; border-radius: 6px; cursor: pointer; transition: background 0.2s; }
.chart-toggle-group span:hover { color: #fff; }
.chart-toggle-group .active-toggle { background: #2563eb; color: #fff; }
.chart-visual-area { height: 140px; display: flex; justify-content: space-around; align-items: flex-end; background: rgba(15,23,42,0.4); border-radius: 10px; padding: 10px; border: 1px dashed #334155; position: relative; overflow: hidden; }
.line-mode { display: flex; align-items: center; justify-content: center; }
.line-graph-sim { width: 100%; height: 100%; position: relative; }
.svg-line { width: 100%; height: 100%; position: absolute; top: 0; left: 0; }
.line-point { position: absolute; width: 8px; height: 8px; background: #38bdf8; border-radius: 50%; z-index: 2; }
.line-point span { position: absolute; font-size: 10px; color: #94a3b8; top: -16px; left: -12px; white-space: nowrap; }
.line-point.p1 { top: 70%; left: 20%; }
.line-point.p2 { top: 30%; left: 80%; }
.line-point.p3 { top: 20%; left: 80%; background: #10b981; }
.pie-mode { display: flex; align-items: center; justify-content: center; }
.pie-chart-sim { width: 100px; height: 100px; border-radius: 50%; position: relative; display: flex; align-items: center; justify-content: center; }
.pie-center-hole { width: 55px; height: 55px; background: #1e293b; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700; color: #94a3b8; }
.bar-column { display: flex; flex-direction: column; align-items: center; gap: 6px; height: 100%; justify-content: flex-end; width: 70px; }
.bar-column span { font-size: 10.5px; color: #94a3b8; font-weight: 600; }
.bar-fill { width: 100%; border-radius: 6px 6px 0 0; transition: height 0.3s ease; }
.bar-fill.orange { background: #f59e0b; }
.bar-fill.cyan { background: #06b6d4; }
.bar-fill.blue { background: #3b82f6; }
.bar-fill.green { background: #10b981; }
.chart-legend-bottom { display: flex; gap: 16px; font-size: 11.5px; color: #94a3b8; }
.legend-item { display: flex; align-items: center; gap: 6px; }
.legend-item span[class^="dot-"] { width: 8px; height: 8px; border-radius: 50%; }
.dot-orange { background: #f59e0b; } .dot-cyan { background: #06b6d4; } .dot-blue { background: #3b82f6; } .dot-green { background: #10b981; }

.table-filter-bar { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 12px; margin-bottom: 16px; }
@media(max-width: 992px) { .table-filter-bar { grid-template-columns: 1fr; } }
.table-header-title { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
.table-header-title h3 { font-size: 16px; font-weight: 700; margin: 0 0 4px; color: #fff; }
.table-sub-desc { font-size: 12px; color: #94a3b8; margin: 0; }
.data-count { font-size: 12px; color: #38bdf8; background: rgba(56,189,248,0.1); padding: 6px 12px; border-radius: 8px; font-weight: 600; }
.table-responsive { width: 100%; overflow-x: auto; }
.custom-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 12.5px; }
.custom-table th { background-color: rgba(15,23,42,0.6); color: #94a3b8; padding: 12px; font-weight: 700; border-bottom: 1px solid #334155; }
.custom-table td { padding: 12px; border-bottom: 1px solid #334155; color: #e2e8f0; }
.dc-input { background: #0f172a; border: 1px solid #334155; color: #fff; padding: 10px 14px; border-radius: 10px; font-size: 13px; outline: none; width: 100%; box-sizing: border-box; }
.dc-input:focus { border-color: #38bdf8; }
.btn-action-delete { background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.3); padding: 6px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; }
.btn-action-delete:disabled { opacity: 0.5; cursor: not-allowed; }
.dc-activity-badge { font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 6px; display: inline-block; }
.badge-green { background: rgba(16,185,129,0.15); color: #34d399; }
.badge-yellow { background: rgba(245,158,11,0.15); color: #f59e0b; }
.badge-gray { background: rgba(100, 116, 139, 0.15); color: #94a3b8; }
.text-cyan { color: #38bdf8; } .text-yellow { color: #f59e0b; } .text-green { color: #34d399; }

.dc-plate {
  font-family: monospace;
  font-weight: 700;
  font-size: 12px;
  color: #0f172a;
  background: #fbbf24;
  padding: 3px 8px;
  border-radius: 5px;
  letter-spacing: 0.03em;
}

.btn-primary-action {
  background: #2563eb;
  border: 1px solid #2563eb;
  color: #fff;
  padding: 10px 18px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
}
.btn-primary-action:hover { background: #1d4ed8; }
.btn-primary-action:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-action-edit {
  background: rgba(56, 189, 248, 0.15);
  color: #38bdf8;
  border: 1px solid rgba(56, 189, 248, 0.3);
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
}
.btn-action-edit:hover { background: rgba(56, 189, 248, 0.25); }
</style>