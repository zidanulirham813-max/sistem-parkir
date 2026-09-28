<template>
  <div class="dc-dashboard-layout">
    <Sidebar />
    <div class="dc-wrapper">
      <div class="dc-shell">
        <header class="dc-header">
          <div>
            <span class="dc-eyebrow"><span class="dc-dot"></span>Keuangan</span>
            <h1>Daftar Tagihan Member (Belum Lunas)</h1>
          </div>
        </header>

        <div class="dc-card-modern">
          <table class="dc-table">
            <thead>
              <tr>
                <th>Nama Member</th>
                <th>Bulan Tagihan</th>
                <th>Jumlah</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="tagihan in listTagihan" :key="tagihan.id">
                <td><strong>{{ tagihan.nama }}</strong></td>
                <td>{{ tagihan.bulan }}</td>
                <td>Rp {{ Number(tagihan.jumlah).toLocaleString('id-ID') }}</td>
                <td>
                  <span class="dc-badge aktif">
                    {{ tagihan.status }}
                  </span>
                </td>
              </tr>
              <tr v-if="listTagihan.length === 0 && !loading">
                <td colspan="4" class="dc-empty-text">Semua tagihan member sudah lunas ✨</td>
              </tr>
              <tr v-if="loading">
                <td colspan="4" class="dc-empty-text">Memuat data tagihan...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';

const { $api } = useNuxtApp();
const listTagihan = ref<any[]>([]);
const loading = ref(false);

const fetchTagihan = async () => {
  loading.value = true;
  try {
    const res: any = await $api.get('/members');
    const data = res.data?.data || res.data || [];

    // Mapping dan filter ketat hanya menampilkan data yang belum lunas
    listTagihan.value = data
      .map((item: any) => ({
        id: item.id ?? item.id_member,
        nama: item.nama_member || item.nama || item.name || 'Tanpa Nama',
        bulan: item.bulan_tagihan || item.bulan || 'September 2026',
        jumlah: item.biaya || item.jumlah || item.tagihan || 150000,
        status: item.status_pembayaran ?? item.status ?? item.payment_status ?? 'Belum Lunas'
      }))
      .filter((item: any) => {
        const statusStr = String(item.status).toLowerCase();
        // Hanya ambil yang mengandung kata 'belum' atau tidak sama persis dengan 'lunas'
        return statusStr.includes('belum') || statusStr !== 'lunas';
      });

  } catch (error) {
    console.error('Gagal memuat data member:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchTagihan();
});
</script>

<style scoped>
.dc-dashboard-layout { display: flex; min-height: 100vh; background: radial-gradient(circle at 20% -10%, #3a4b7c 0%, #1e294b 45%, #111827 100%); font-family: 'Inter', sans-serif; color: #f3f4f6; }
.dc-wrapper { flex: 1; padding: 40px 24px; overflow-y: auto; }
.dc-shell { max-width: 1000px; margin: 0 auto; }
.dc-header { margin-bottom: 28px; }
.dc-eyebrow { display: inline-flex; align-items: center; gap: 7px; font-size: 11.5px; font-weight: 600; text-transform: uppercase; color: #38bdf8; }
.dc-dot { width: 6px; height: 6px; border-radius: 50%; background: #38bdf8; }
.dc-header h1 { font-size: 24px; font-weight: 700; color: #fff; margin-top: 4px; }
.dc-card-modern { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.4); }
.dc-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px; }
.dc-table th { padding: 12px; color: #94a3b8; border-bottom: 1px solid #334155; font-weight: 600; }
.dc-table td { padding: 14px 12px; border-bottom: 1px solid rgba(51, 65, 85, 0.4); color: #e2e8f0; }
.dc-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.dc-badge.aktif { background: rgba(248, 113, 113, 0.15); color: #f87171; }
.dc-empty-text { text-align: center; color: #64748b; padding: 24px !important; }
</style>