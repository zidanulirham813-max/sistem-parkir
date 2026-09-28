<template>
  <div class="dc-dashboard-layout">
    <SidebarSuperAdmin />
    <div class="dc-wrapper">
      <div class="dc-shell">
        <!-- Header -->
        <header class="dc-header">
          <div>
            <span class="dc-eyebrow"><span class="dc-dot"></span>Live · Data Member Parkir</span>
            <h1>Manajemen Member</h1>
            <p class="dc-subtext">Kelola data pendaftaran, pembayaran, dan masa aktif member.</p>
          </div>
          <div class="header-right-group">
            <div class="user-badge-top">👑 Super Administrator</div>
          </div>
        </header>

        <!-- Statistik -->
        <div class="stats-grid-top">
          <div class="dc-section-card stat-card-small">
            <span class="dc-stat-label">TOTAL MEMBER</span>
            <div class="stat-value-row">
              <h2>{{ listMember.length }}</h2>
              <span class="badge-sub-info">Terdaftar</span>
            </div>
          </div>
          <div class="dc-section-card stat-card-small">
            <span class="dc-stat-label">LUNAS</span>
            <div class="stat-value-row">
              <h2 class="text-green">{{ lunasCount }}</h2>
              <span class="badge-sub-info text-green">Aktif</span>
            </div>
          </div>
          <div class="dc-section-card stat-card-small">
            <span class="dc-stat-label">BELUM LUNAS</span>
            <div class="stat-value-row">
              <h2 class="text-yellow">{{ belumLunasCount }}</h2>
              <span class="badge-sub-info text-yellow">Menunggu bayar</span>
            </div>
          </div>
          <div class="dc-section-card stat-card-small">
            <span class="dc-stat-label">EXPIRED</span>
            <div class="stat-value-row">
              <h2 class="text-red">{{ expiredCount }}</h2>
              <span class="badge-sub-info">Perlu perpanjang</span>
            </div>
          </div>
        </div>

        <!-- Tabel -->
        <div class="dc-section-card" style="margin-top: 24px;">
          <div class="table-header-title">
            <div>
              <h3>Daftar Member</h3>
              <p class="table-sub-desc">Data diambil dari <code>GET /api/members</code>. Field sesuai tabel <code>members</code> (kode_member, nama_member, nama_perusahaan, status, tanggal_expired).</p>
            </div>
            <span class="data-count">Menampilkan {{ filteredMembers.length }} / {{ listMember.length }}</span>
          </div>

          <div class="table-filter-bar">
            <input type="text" v-model="searchKeyword" placeholder="Cari kode, nama member, atau perusahaan..." class="dc-input search-input" />
            <select class="dc-input select-input" v-model="filterStatus">
              <option value="Semua Status">Semua Status</option>
              <option value="lunas">Lunas</option>
              <option value="belum lunas">Belum Lunas</option>
              <option value="sudah expired">Sudah Expired</option>
            </select>
            <span v-if="isLoading" style="font-size:12px;color:#94a3b8;align-self:center;">Memuat...</span>
          </div>

          <div v-if="errorMsg" class="alert-error">{{ errorMsg }}</div>

          <div class="table-responsive">
            <table class="custom-table">
              <thead>
                <tr>
                  <th>KODE MEMBER</th>
                  <th>NAMA MEMBER</th>
                  <th>PERUSAHAAN</th>
                  <th>TOTAL HARGA</th>
                  <th>JUMLAH BAYAR</th>
                  <th>STATUS</th>
                  <th>EXPIRED</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="isLoading">
                  <td colspan="7" style="text-align:center;padding:20px;color:#94a3b8;">Memuat data member...</td>
                </tr>
                <tr v-else-if="filteredMembers.length === 0">
                  <td colspan="7" style="text-align:center;padding:20px;color:#64748b;">
                    {{ listMember.length === 0 ? 'Belum ada data member.' : 'Tidak ada hasil pencarian.' }}
                  </td>
                </tr>
                <tr v-for="m in filteredMembers" :key="m.id">
                  <td><strong class="text-cyan" style="font-family:monospace;">{{ m.kode_member }}</strong><div v-if="m.token" style="font-size:10px;color:#64748b;font-family:monospace;">{{ m.token.substring(0,8) }}…</div></td>
                  <td style="font-weight:600;">{{ m.nama_member }}</td>
                  <td>{{ m.nama_perusahaan || '-' }}</td>
                  <td>Rp {{ Number(m.total_harga || 0).toLocaleString('id-ID') }}</td>
                  <td>Rp {{ Number(m.jumlah_bayar || 0).toLocaleString('id-ID') }}</td>
                  <td>
                    <span class="dc-activity-badge" :class="badgeClass(m.status)">{{ m.status }}</span>
                  </td>
                  <td style="font-size:12px;color:#94a3b8;">{{ formatDate(m.tanggal_expired) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="table-footer-hint">
            Bug sebelumnya: kolom <code>nama</code> & <code>plat_nomor</code> tidak ada di tabel members (yang benar <code>nama_member</code> & <code>nama_perusahaan</code>), dan status selalu hardcode "Aktif". Sudah diperbaiki di atas.
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';

const { $api } = useNuxtApp();

interface Member {
  id: number;
  kode_member: string;
  token: string | null;
  nama_member: string;
  nama_perusahaan: string | null;
  total_harga: number;
  jumlah_bayar: number;
  kembalian: number;
  status: string;
  tanggal_expired: string | null;
  tanggal_bayar: string | null;
}

const listMember = ref<Member[]>([]);
const isLoading = ref(false);
const errorMsg = ref('');
const searchKeyword = ref('');
const filterStatus = ref('Semua Status');

const lunasCount = computed(() => listMember.value.filter(m => m.status === 'lunas').length);
const belumLunasCount = computed(() => listMember.value.filter(m => m.status === 'belum lunas').length);
const expiredCount = computed(() => listMember.value.filter(m => m.status === 'sudah expired').length);

const filteredMembers = computed(() => {
  return listMember.value.filter(m => {
    const kw = searchKeyword.value.toLowerCase();
    const matchKeyword = !kw ||
      (m.kode_member || '').toLowerCase().includes(kw) ||
      (m.nama_member || '').toLowerCase().includes(kw) ||
      (m.nama_perusahaan || '').toLowerCase().includes(kw);
    const matchStatus = filterStatus.value === 'Semua Status' || m.status === filterStatus.value;
    return matchKeyword && matchStatus;
  });
});

const badgeClass = (status: string) => {
  if (status === 'lunas') return 'badge-green';
  if (status === 'sudah expired') return 'badge-gray';
  return 'badge-yellow';
};

const formatDate = (val: string | null) => {
  if (!val) return '-';
  return new Date(val).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const fetchMember = async () => {
  isLoading.value = true;
  errorMsg.value = '';
  try {
    const res: any = await $api.get('/members');
    const data = res.data?.data ?? res.data ?? [];
    listMember.value = Array.isArray(data) ? data : [];
  } catch (e: any) {
    console.error('Gagal memuat data member', e);
    errorMsg.value = e?.response?.data?.message || 'Gagal memuat data member. Pastikan backend jalan di http://localhost:8000';
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  fetchMember();
});
</script>

<style scoped>
.dc-dashboard-layout { display: flex; min-height: 100vh; background: radial-gradient(circle at 20% -10%, #3a4b7c 0%, #1e294b 45%, #111827 100%); font-family: 'Inter', -apple-system, sans-serif; color: #f3f4f6; }
.header-right-group { display: flex; align-items: center; gap: 14px; }
.user-badge-top { background-color: #1e293b; border: 1px solid #334155; padding: 9px 14px; border-radius: 10px; font-size: 12px; color: #cbd5e1; font-weight: 600; }
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
.table-sub-desc code { background: #0f172a; padding: 1px 5px; border-radius: 4px; color: #38bdf8; font-size: 11px; }
.data-count { font-size: 12px; color: #38bdf8; background: rgba(56, 189, 248, 0.1); padding: 6px 12px; border-radius: 8px; font-weight: 600; }
.table-responsive { width: 100%; overflow-x: auto; }
.custom-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 12.5px; }
.custom-table th { background-color: rgba(15, 23, 42, 0.6); color: #94a3b8; padding: 12px; font-weight: 700; border-bottom: 1px solid #334155; }
.custom-table td { padding: 12px; border-bottom: 1px solid #334155; color: #e2e8f0; }
.dc-input { background: #0f172a; border: 1px solid #334155; color: #fff; padding: 10px 14px; border-radius: 10px; font-size: 13px; outline: none; width: 100%; box-sizing: border-box; }
.dc-input:focus { border-color: #38bdf8; }
.dc-activity-badge { font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 6px; display: inline-block; text-transform: capitalize; }
.badge-green { background: rgba(16, 185, 129, 0.15); color: #34d399; }
.badge-yellow { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
.badge-gray { background: rgba(100, 116, 139, 0.2); color: #94a3b8; }
.alert-error { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #f87171; padding: 10px 14px; border-radius: 10px; font-size: 12.5px; margin-bottom: 12px; }
.table-footer-hint { margin-top: 14px; font-size: 11.5px; color: #64748b; background: rgba(15,23,42,0.4); border: 1px dashed #334155; border-radius: 10px; padding: 10px 12px; }
.table-footer-hint code { background: #0f172a; padding: 1px 5px; border-radius: 4px; color: #38bdf8; font-size: 11px; }
.text-cyan { color: #38bdf8; }
.text-green { color: #34d399; }
.text-yellow { color: #f59e0b; }
.text-red { color: #f87171; }
</style>
