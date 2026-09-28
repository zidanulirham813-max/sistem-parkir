<template>
  <div class="dc-dashboard-layout">
    <Sidebar />
    <div class="dc-wrapper">
      <div class="dc-shell">
        <header class="dc-header">
          <div>
            <span class="dc-eyebrow"><span class="dc-dot"></span>Manajemen Data</span>
            <h1>Daftar Tiket Parkir</h1>
          </div>
        </header>

        <div class="dc-card-modern">
          <!-- Search & Filter -->
          <div class="table-filter-bar">
            <input
              type="text"
              v-model="searchKeyword"
              placeholder="Cari kode tiket, plat nomor..."
              class="dc-input"
            />
            <select class="dc-input" v-model="filterStatus">
              <option>Semua Status</option>
              <option>Aktif/Parkir</option>
              <option>Selesai</option>
            </select>
          </div>

          <!-- Table -->
          <div class="table-responsive">
            <table class="dc-table">
              <thead>
                <tr>
                  <th>No. Tiket</th>
                  <th>Plat Nomor</th>
                  <th>Kendaraan</th>
                  <th>Waktu Masuk</th>
                  <th>Waktu Keluar</th>
                  <th>Status</th>
                  <th>Total Bayar</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <!-- Loading State -->
                <tr v-if="isLoading">
                  <td colspan="8" class="state-cell">Memuat data...</td>
                </tr>
                <!-- Empty State -->
                <tr v-else-if="filteredTiket.length === 0">
                  <td colspan="8" class="state-cell">
                    {{ listTiket.length === 0 ? 'Belum ada data tiket.' : 'Tidak ada data yang cocok dengan filter.' }}
                  </td>
                </tr>
                <!-- Data Rows -->
                <tr v-else v-for="item in filteredTiket" :key="item.id">
                  <td><strong>{{ item.kode_tiket }}</strong></td>
                  <td>{{ item.plat_nomor || '-' }}</td>
                  <td>{{ item.kendaraan || '-' }}</td>
                  <td>{{ formatTanggal(item.waktu_masuk) }}</td>
                  <td>{{ item.waktu_keluar ? formatTanggal(item.waktu_keluar) : '-' }}</td>
                  <td>
                    <span :class="['dc-badge', item.status === 'Selesai' ? 'selesai' : 'aktif']">
                      {{ item.status === 'Selesai' ? 'Selesai' : 'Aktif / Parkir' }}
                    </span>
                  </td>
                  <td>Rp {{ Number(item.tarif || 0).toLocaleString('id-ID') }}</td>
                  <td>
                    <button
                      v-if="item.status === 'Selesai'"
                      class="btn-hapus"
                      :disabled="deletingId === item.id"
                      @click="hapusTiket(item)"
                    >
                      {{ deletingId === item.id ? 'Menghapus…' : 'Hapus' }}
                    </button>
                    <span v-else class="text-muted">-</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Footer info -->
          <div class="table-footer" v-if="!isLoading">
            <span>Menampilkan {{ filteredTiket.length }} dari {{ listTiket.length }} data</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';

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

const isLoading = ref(false);
const listTiket = ref<TiketItem[]>([]);
const searchKeyword = ref('');
const filterStatus = ref('Semua Status');
const deletingId = ref<number | null>(null);

const formatTanggal = (val: string) => {
  if (!val) return '-';
  return new Date(val).toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const filteredTiket = computed(() => {
  return listTiket.value.filter(item => {
    const kw = searchKeyword.value.toLowerCase();
    const matchKeyword = !kw ||
      (item.kode_tiket || '').toLowerCase().includes(kw) ||
      (item.plat_nomor || '').toLowerCase().includes(kw);

    const matchStatus = filterStatus.value === 'Semua Status' || item.status === filterStatus.value;

    return matchKeyword && matchStatus;
  });
});

const fetchTiket = async () => {
  isLoading.value = true;
  try {
    const res: any = await $api.get('/tiket');
    listTiket.value = res.data?.data || res.data || [];
  } catch (e) {
    console.error('Gagal memuat data tiket:', e);
    listTiket.value = [];
  } finally {
    isLoading.value = false;
  }
};

const hapusTiket = async (item: TiketItem) => {
  if (!confirm(`Hapus tiket ${item.kode_tiket}?`)) return;
  deletingId.value = item.id;
  try {
    await $api.delete(`/tiket/${item.id}`);
    listTiket.value = listTiket.value.filter(t => t.id !== item.id);
  } catch (e) {
    console.error('Gagal menghapus tiket:', e);
    alert('Gagal menghapus tiket.');
  } finally {
    deletingId.value = null;
  }
};

onMounted(() => {
  fetchTiket();
});
</script>

<style scoped>
/* Layout */
.dc-dashboard-layout { display: flex; min-height: 100vh; background: radial-gradient(circle at 20% -10%, #3a4b7c 0%, #1e294b 45%, #111827 100%); font-family: 'Inter', sans-serif; color: #f3f4f6; }
.dc-wrapper { flex: 1; padding: 40px 24px; overflow-y: auto; }
.dc-shell { max-width: 1100px; margin: 0 auto; }

/* Header */
.dc-header { margin-bottom: 28px; }
.dc-eyebrow { display: inline-flex; align-items: center; gap: 7px; font-size: 11.5px; font-weight: 600; text-transform: uppercase; color: #38bdf8; }
.dc-dot { width: 6px; height: 6px; border-radius: 50%; background: #38bdf8; box-shadow: 0 0 10px #38bdf8; }
.dc-header h1 { font-size: 24px; font-weight: 700; color: #fff; margin-top: 4px; }

/* Card */
.dc-card-modern { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.4); }

/* Filter Bar */
.table-filter-bar { display: grid; grid-template-columns: 2fr 1fr; gap: 12px; margin-bottom: 20px; }
@media(max-width: 768px) { .table-filter-bar { grid-template-columns: 1fr; } }
.dc-input { background: #0f172a; border: 1px solid #334155; color: #fff; padding: 10px 14px; border-radius: 10px; font-size: 13px; outline: none; width: 100%; box-sizing: border-box; }
.dc-input:focus { border-color: #38bdf8; }

/* Table */
.table-responsive { width: 100%; overflow-x: auto; }
.dc-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px; }
.dc-table th { padding: 12px; color: #94a3b8; border-bottom: 1px solid #334155; font-weight: 600; }
.dc-table td { padding: 14px 12px; border-bottom: 1px solid rgba(51, 65, 85, 0.4); color: #e2e8f0; }

/* State cells */
.state-cell { text-align: center; padding: 32px 12px !important; color: #94a3b8; font-size: 14px; }

/* Badge */
.dc-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.dc-badge.aktif { background: rgba(56, 189, 248, 0.15); color: #38bdf8; }
.dc-badge.selesai { background: rgba(52, 211, 153, 0.15); color: #34d399; }

/* Footer */
.table-footer { margin-top: 16px; font-size: 12px; color: #94a3b8; text-align: right; }

/* Tombol Hapus */
.btn-hapus { background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.3); padding: 6px 14px; border-radius: 8px; font-size: 11.5px; font-weight: 600; cursor: pointer; transition: background 0.2s, border-color 0.2s; }
.btn-hapus:hover { background: rgba(239,68,68,0.3); border-color: rgba(239,68,68,0.5); }
.btn-hapus:disabled { opacity: 0.5; cursor: not-allowed; }
.text-muted { color: #475569; font-size: 12px; }
</style>
