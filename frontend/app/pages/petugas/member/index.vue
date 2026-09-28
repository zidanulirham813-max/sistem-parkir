<template>
  <div class="dc-dashboard-layout">
    <Sidebar />
    <div class="dc-wrapper">
      <div class="dc-shell">
        <header class="dc-header">
          <div>
            <span class="dc-eyebrow"><span class="dc-dot"></span>Live · Panel Kontrol Parkir</span>
            <h1>Member &amp; Iuran Bulanan</h1>
          </div>
          <button @click="openModalAdd" class="dc-btn dc-btn-primary">+ Registrasi Member</button>
        </header>

        <!-- Kartu Ringkasan -->
        <div class="dc-stats-grid">
          <div class="dc-stat-card">
            <div class="dc-stat-icon is-total">👥</div>
            <div class="dc-stat-info">
              <span class="dc-stat-label">Total Member</span>
              <h2 class="dc-stat-value">{{ totalMember }}</h2>
            </div>
          </div>

          <div class="dc-stat-card">
            <div class="dc-stat-icon is-lunas-icon">✅</div>
            <div class="dc-stat-info">
              <span class="dc-stat-label">Member Lunas</span>
              <h2 class="dc-stat-value text-lunas">{{ totalLunas }}</h2>
            </div>
          </div>

          <div class="dc-stat-card">
            <div class="dc-stat-icon is-belum-icon">⚠️</div>
            <div class="dc-stat-info">
              <span class="dc-stat-label">Belum Lunas</span>
              <h2 class="dc-stat-value text-belum">{{ totalBelumLunas }}</h2>
            </div>
          </div>
        </div>

        <!-- Search & Filter -->
        <div class="dc-toolbar">
          <div class="dc-search-wrapper">
            <span class="dc-search-icon">🔍</span>
            <input
              v-model="searchQuery"
              type="text"
              class="dc-search-input"
              placeholder="Cari nama, kode, perusahaan, atau plat nomor..."
            />
            <button v-if="searchQuery" class="dc-search-clear" type="button" @click="searchQuery = ''">×</button>
          </div>

          <div class="dc-filter-group">
            <button
              v-for="opt in statusFilterOptions"
              :key="opt.value"
              class="dc-filter-chip"
              :class="{ 'is-active': statusFilter === opt.value }"
              @click="statusFilter = opt.value"
              type="button"
            >
              {{ opt.label }}
            </button>
          </div>
        </div>

        <div class="dc-card">
          <div class="dc-table-scroll">
            <table class="dc-table">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Kode / Token</th>
                  <th>Nama Member</th>
                  <th>Perusahaan</th>
                  <th>Plat Nomor</th>
                  <th>Total Harga</th>
                  <th>Jumlah Bayar</th>
                  <th>Status</th>
                  <th>Expired</th>
                  <th class="text-right">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loading"><td colspan="10" class="dc-empty">Memuat data…</td></tr>
                <tr v-else-if="members.length === 0"><td colspan="10" class="dc-empty">Belum ada data member.</td></tr>
                <tr v-else-if="filteredMembers.length === 0"><td colspan="10" class="dc-empty">Tidak ada member yang cocok dengan pencarian/filter.</td></tr>
                <tr v-for="(item, index) in filteredMembers" :key="item.id" class="dc-row">
                  <td class="dc-muted">{{ String(index + 1).padStart(2, "0") }}</td>
                  <td>
                    <span class="dc-code">{{ item.kode_member }}</span>
                    <div class="dc-token">{{ item.token ? item.token.substring(0, 8) + "…" : "—" }}</div>
                  </td>
                  <td class="dc-strong">{{ item.nama_member }}</td>
                  <td class="dc-muted">{{ item.nama_perusahaan }}</td>
                  <td><span class="dc-plate">{{ item.plat_nomor || "-" }}</span></td>
                  <td>Rp {{ Number(item.total_harga || 0).toLocaleString("id-ID") }}</td>
                  <td class="dc-strong dc-amount">Rp {{ Number(item.jumlah_bayar || 0).toLocaleString("id-ID") }}</td>
                  <td>
                    <span :class="['dc-badge', item.status === 'lunas' ? 'is-lunas' : (item.status === 'sudah expired' ? 'is-expired' : 'is-belum')]">
                      <span class="dc-badge-dot"></span>{{ item.status }}
                    </span>
                  </td>
                  <td class="dc-muted">{{ formatDate(item.tanggal_expired) }}</td>
                  <td>
                    <div class="dc-actions">
                      <button @click="openModalDetail(item)" class="dc-icon-btn" title="QR">QR</button>
                      <button
                        @click="!isLunas(item) && openModalBayar(item)"
                        :disabled="isLunas(item)"
                        class="dc-icon-btn dc-success"
                        :class="{ 'dc-disabled': isLunas(item) }"
                        :title="isLunas(item) ? 'Sudah lunas — tidak bisa bayar lagi' : 'Bayar'"
                      >Bayar</button>
                      <button @click="goEdit(item.id)" class="dc-icon-btn" title="Edit">Edit</button>
                      <button
                        @click="!isLunas(item) && deleteMember(item.id)"
                        :disabled="isLunas(item)"
                        class="dc-icon-btn dc-danger"
                        :class="{ 'dc-disabled': isLunas(item) }"
                        :title="isLunas(item) ? 'Member sudah lunas, tidak bisa dihapus' : 'Hapus'"
                      >Hapus</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Modal Registrasi -->
      <div v-if="showModal" class="dc-overlay">
        <div class="dc-modal">
          <h3>Registrasi Member</h3>
          <p class="dc-modal-sub">Plat nomor wajib &amp; unik. Harga iuran <strong>absolute Rp 150.000</strong> tidak dapat diubah.</p>
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
              <input v-model="form.plat_nomor" @input="form.plat_nomor = form.plat_nomor.toUpperCase()" type="text" required class="dc-input" placeholder="B 1234 ABC" />
            </div>
            <div class="dc-field">
              <label>Harga Member</label>
              <div class="dc-locked-field is-absolute">
                <span class="dc-locked-value">Rp 150.000</span>
                <span class="dc-locked-badge">Absolute</span>
              </div>
            </div>
            <div class="dc-field">
              <label>Pembayaran Awal</label>
              <div class="dc-locked-field is-absolute">
                <span class="dc-locked-value">Rp 150.000</span>
                <span class="dc-locked-badge">Lunas otomatis</span>
              </div>
              <p class="dc-field-hint">Otomatis lunas Rp 150.000 — tidak dapat diubah saat registrasi.</p>
            </div>
            <div v-if="formError" class="dc-alert-error">{{ formError }}</div>
            <div class="dc-footer">
              <button type="button" @click="showModal = false" class="dc-btn dc-btn-ghost">Batal</button>
              <button type="submit" :disabled="saving" class="dc-btn dc-btn-primary">{{ saving ? "Menyimpan…" : "Simpan" }}</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal Bayar -->
      <div v-if="showModalBayar" class="dc-overlay">
        <div class="dc-modal">
          <h3>Pembayaran Iuran</h3>
          <p class="dc-modal-sub">{{ activeMember?.nama_member }} · {{ activeMember?.plat_nomor }}</p>
          <form @submit.prevent="submitPembayaran">
            <div class="dc-field">
              <label>Tarif Iuran (1 Bulan)</label>
              <div class="dc-locked-field is-absolute">
                <span class="dc-locked-value">Rp {{ TARIF_MEMBER.toLocaleString('id-ID') }}</span>
                <span class="dc-locked-badge">Absolute</span>
              </div>
            </div>

            <div class="dc-field">
              <label>Uang Diterima *</label>
              <input
                v-model.number="uangDiterima"
                type="number"
                :min="TARIF_MEMBER"
                step="1000"
                required
                class="dc-input"
                placeholder="Masukkan jumlah uang diterima"
              />
              <p class="dc-field-hint">Bisa lebih dari Rp {{ TARIF_MEMBER.toLocaleString('id-ID') }}, kembalian dihitung otomatis.</p>
            </div>

            <div class="dc-kembalian-box" :class="{ 'is-kurang': kembalianKurang }">
              <span>Kembalian</span>
              <strong>Rp {{ Math.max(0, kembalian).toLocaleString('id-ID') }}</strong>
            </div>
            <p v-if="kembalianKurang" class="dc-alert-error">Uang diterima kurang dari tarif iuran (Rp {{ TARIF_MEMBER.toLocaleString('id-ID') }}).</p>

            <div v-if="bayarError" class="dc-alert-error">{{ bayarError }}</div>
            <div class="dc-footer">
              <button type="button" @click="showModalBayar = false" class="dc-btn dc-btn-ghost">Batal</button>
              <button type="submit" :disabled="saving || kembalianKurang" class="dc-btn dc-btn-primary">{{ saving ? "Memproses…" : "Bayar & Perpanjang" }}</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal Invoice -->
      <div v-if="showModalInvoice" class="dc-overlay">
        <div class="dc-modal dc-invoice-modal">
          <div class="dc-invoice-check">✅</div>
          <h3 class="text-center">Pembayaran Berhasil</h3>
          <p class="dc-modal-sub text-center">Invoice iuran member telah dibuat.</p>

          <div class="dc-invoice-box">
            <div class="dc-invoice-row"><span>Kode Member</span><strong>{{ invoiceData?.kode_member }}</strong></div>
            <div class="dc-invoice-row"><span>Nama</span><strong>{{ invoiceData?.nama_member }}</strong></div>
            <div class="dc-invoice-row"><span>Perusahaan</span><strong>{{ invoiceData?.nama_perusahaan || '-' }}</strong></div>
            <div class="dc-invoice-row"><span>Plat Nomor</span><strong>{{ invoiceData?.plat_nomor }}</strong></div>
            <div class="dc-invoice-row"><span>Tanggal Bayar</span><strong>{{ invoiceData?.tanggal }}</strong></div>
            <div class="dc-invoice-row"><span>Berlaku s/d</span><strong>{{ invoiceData?.expired }}</strong></div>
            <div class="dc-invoice-divider"></div>
            <div class="dc-invoice-row"><span>Iuran Bulanan</span><strong>Rp {{ invoiceData?.tarif.toLocaleString('id-ID') }}</strong></div>
            <div class="dc-invoice-row"><span>Uang Diterima</span><strong>Rp {{ invoiceData?.uang_diterima.toLocaleString('id-ID') }}</strong></div>
            <div class="dc-invoice-row dc-invoice-total"><span>Kembalian</span><strong>Rp {{ invoiceData?.kembalian.toLocaleString('id-ID') }}</strong></div>
          </div>

          <div class="dc-footer">
            <button type="button" @click="showModalInvoice = false" class="dc-btn dc-btn-ghost">Tutup</button>
            <button type="button" @click="cetakInvoice" class="dc-btn dc-btn-primary">🖨️ Cetak Invoice</button>
          </div>
        </div>
      </div>

      <!-- Modal QR -->
      <div v-if="showModalDetail" class="dc-overlay">
        <div class="dc-modal text-center">
          <h3>QR Code Member</h3>
          <p class="dc-modal-sub">{{ detailData?.nama_member }} · {{ detailData?.plat_nomor }}</p>
          <div class="dc-qr">
            <img v-if="detailData?.qr && (detailData.qr.startsWith('data:image') || detailData.qr.startsWith('http'))" :src="detailData.qr" alt="QR" />
            <canvas v-else ref="qrMemberCanvasRef"></canvas>
          </div>
          <div class="dc-detail-box">
            <div><span class="dc-muted">Kode</span><strong>{{ detailData?.kode_member }}</strong></div>
            <div><span class="dc-muted">Plat</span><strong>{{ detailData?.plat_nomor }}</strong></div>
            <div><span class="dc-muted">Status</span><strong>{{ detailData?.status }}</strong></div>
            <div><span class="dc-muted">Expired</span><strong>{{ formatDate(detailData?.tanggal_expired) }}</strong></div>
          </div>
          <button @click="showModalDetail = false" class="dc-btn dc-btn-primary w-full">Tutup</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from "vue";
import { useRouter } from "vue-router";
import QRCode from "qrcode";

const { $api } = useNuxtApp();
const router = useRouter();

const TARIF_MEMBER = 150000;

const members = ref<any[]>([]);
const loading = ref(false);
const saving = ref(false);

const showModal = ref(false);
const showModalBayar = ref(false);
const showModalDetail = ref(false);
const showModalInvoice = ref(false);

const activeMember = ref<any>(null);
const detailData = ref<any>(null);
const invoiceData = ref<any>(null);
const qrMemberCanvasRef = ref<HTMLCanvasElement | null>(null);

const form = ref({ nama_member: "", nama_perusahaan: "", plat_nomor: "", jumlah_bayar: TARIF_MEMBER });
const formError = ref("");

// --- Pembayaran ---
const uangDiterima = ref<number>(TARIF_MEMBER);
const bayarError = ref("");
const kembalian = computed(() => Number(uangDiterima.value || 0) - TARIF_MEMBER);
const kembalianKurang = computed(() => Number(uangDiterima.value || 0) < TARIF_MEMBER);

// --- Search & Filter ---
const searchQuery = ref("");
const statusFilter = ref("semua"); // 'semua' | 'lunas' | 'belum lunas' | 'sudah expired'

const statusFilterOptions = [
  { value: "semua", label: "Semua" },
  { value: "lunas", label: "Lunas" },
  { value: "belum lunas", label: "Belum Lunas" },
  { value: "sudah expired", label: "Sudah Expired" },
];

const filteredMembers = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();

  return members.value.filter((m) => {
    const matchStatus = statusFilter.value === "semua" || m.status === statusFilter.value;

    const matchSearch =
      !q ||
      (m.nama_member || "").toLowerCase().includes(q) ||
      (m.kode_member || "").toLowerCase().includes(q) ||
      (m.nama_perusahaan || "").toLowerCase().includes(q) ||
      (m.plat_nomor || "").toLowerCase().includes(q);

    return matchStatus && matchSearch;
  });
});

const formatDate = (val: string) => !val ? "-" : new Date(val).toLocaleDateString("id-ID");
const formatDateTime = (val: string | Date) => new Date(val).toLocaleString("id-ID", { day: "2-digit", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" });
const isLunas = (item: any) => item.status === "lunas";

// --- Ringkasan statistik member (tetap dihitung dari SEMUA member, bukan hasil filter) ---
const totalMember = computed(() => members.value.length);
const totalLunas = computed(() => members.value.filter((m) => m.status === "lunas").length);
const totalBelumLunas = computed(() => members.value.filter((m) => m.status !== "lunas").length);

const fetchMembers = async () => {
  loading.value = true;
  try {
    const res = await $api.get("/members");
    members.value = res.data.data;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const openModalAdd = () => {
  form.value = { nama_member: "", nama_perusahaan: "", plat_nomor: "", jumlah_bayar: TARIF_MEMBER };
  formError.value = "";
  showModal.value = true;
};

const saveMember = async () => {
  if (!form.value.plat_nomor.trim()) {
    formError.value = "Plat nomor wajib diisi.";
    return;
  }
  formError.value = "";
  saving.value = true;
  try {
    await $api.post("/members", { ...form.value, plat_nomor: form.value.plat_nomor.toUpperCase(), jumlah_bayar: TARIF_MEMBER });
    showModal.value = false;
    fetchMembers();
  } catch (e: any) {
    const msg = e?.response?.data?.errors ? Object.values(e.response.data.errors).flat().join(" ") : e?.response?.data?.message;
    formError.value = msg || "Gagal menyimpan data.";
  } finally {
    saving.value = false;
  }
};

const goEdit = (id: number) => {
  router.push(`/petugas/member/edit/${id}`);
};

const openModalBayar = (item: any) => {
  activeMember.value = item;
  uangDiterima.value = TARIF_MEMBER;
  bayarError.value = "";
  showModalBayar.value = true;
};

const submitPembayaran = async () => {
  if (!activeMember.value) return;
  if (Number(uangDiterima.value || 0) < TARIF_MEMBER) {
    bayarError.value = `Uang diterima minimal Rp ${TARIF_MEMBER.toLocaleString('id-ID')}.`;
    return;
  }
  bayarError.value = "";
  saving.value = true;
  try {
    // Tarif iuran tetap absolute; kelebihan uang diterima adalah kembalian tunai, bukan menambah nilai iuran.
    await $api.put(`/members/${activeMember.value.id}/pembayaran`, { jumlah_bayar: TARIF_MEMBER });

    const waktuBayar = new Date();
    let expiredBaru = "-";
    try {
      await fetchMembers();
      const updated = members.value.find((m) => m.id === activeMember.value.id);
      expiredBaru = updated ? formatDate(updated.tanggal_expired) : "-";
    } catch {
      expiredBaru = "-";
    }

    invoiceData.value = {
      kode_member: activeMember.value.kode_member,
      nama_member: activeMember.value.nama_member,
      nama_perusahaan: activeMember.value.nama_perusahaan,
      plat_nomor: activeMember.value.plat_nomor,
      tanggal: formatDateTime(waktuBayar),
      expired: expiredBaru,
      tarif: TARIF_MEMBER,
      uang_diterima: Number(uangDiterima.value),
      kembalian: Math.max(0, kembalian.value),
    };

    showModalBayar.value = false;
    showModalInvoice.value = true;
  } catch (e: any) {
    bayarError.value = e?.response?.data?.message || "Gagal memproses pembayaran.";
  } finally {
    saving.value = false;
  }
};

const cetakInvoice = () => {
  const d = invoiceData.value;
  if (!d) return;
  const win = window.open("", "_blank", "width=380,height=640");
  if (!win) {
    alert("Popup diblokir browser. Izinkan popup untuk mencetak invoice.");
    return;
  }
  win.document.write(`
    <html>
      <head>
        <title>Invoice - ${d.kode_member}</title>
        <style>
          * { box-sizing: border-box; }
          body { font-family: 'Courier New', monospace; padding: 20px; color: #111; font-size: 13px; }
          h2 { text-align: center; margin: 0 0 4px; font-size: 16px; }
          .sub { text-align: center; color: #555; font-size: 11px; margin-bottom: 16px; }
          table { width: 100%; border-collapse: collapse; margin-top: 8px; }
          td { padding: 4px 0; vertical-align: top; }
          .right { text-align: right; }
          hr { border: none; border-top: 1px dashed #999; margin: 10px 0; }
          .total td { font-weight: bold; font-size: 15px; padding-top: 8px; }
        </style>
      </head>
      <body>
        <h2>INVOICE PEMBAYARAN</h2>
        <div class="sub">Iuran Member Parkir</div>
        <hr />
        <table>
          <tr><td>Kode Member</td><td class="right">${d.kode_member}</td></tr>
          <tr><td>Nama</td><td class="right">${d.nama_member}</td></tr>
          <tr><td>Perusahaan</td><td class="right">${d.nama_perusahaan || "-"}</td></tr>
          <tr><td>Plat Nomor</td><td class="right">${d.plat_nomor}</td></tr>
          <tr><td>Tanggal Bayar</td><td class="right">${d.tanggal}</td></tr>
          <tr><td>Berlaku s/d</td><td class="right">${d.expired}</td></tr>
        </table>
        <hr />
        <table>
          <tr><td>Iuran Bulanan</td><td class="right">Rp ${d.tarif.toLocaleString("id-ID")}</td></tr>
          <tr><td>Uang Diterima</td><td class="right">Rp ${d.uang_diterima.toLocaleString("id-ID")}</td></tr>
          <tr class="total"><td>Kembalian</td><td class="right">Rp ${d.kembalian.toLocaleString("id-ID")}</td></tr>
        </table>
        <hr />
        <div class="sub">Terima kasih</div>
        <script>window.onload = () => { window.print(); };<\/script>
      </body>
    </html>
  `);
  win.document.close();
};

const openModalDetail = async (item: any) => {
  try {
    const res = await $api.get(`/members/${item.id}`);
    detailData.value = res.data?.data || res.data;
    showModalDetail.value = true;
    await nextTick();
    if (qrMemberCanvasRef.value && detailData.value) {
      const textToQR = detailData.value.token || detailData.value.kode_member || String(item.id);
      await QRCode.toCanvas(qrMemberCanvasRef.value, textToQR, { width: 168, margin: 1, color: { dark: "#000000", light: "#ffffff" } });
    }
  } catch (e) {
    console.error(e);
    detailData.value = item;
    showModalDetail.value = true;
    await nextTick();
    if (qrMemberCanvasRef.value) {
      await QRCode.toCanvas(qrMemberCanvasRef.value, item.token || item.kode_member, { width: 168, margin: 1, color: { dark: "#000000", light: "#ffffff" } });
    }
  }
};

const deleteMember = async (id: number) => {
  if (!confirm("Hapus member ini?")) return;
  try {
    await $api.delete(`/members/${id}`);
    fetchMembers();
  } catch (e: any) {
    alert(e?.response?.data?.message || "Gagal menghapus data.");
  }
};

onMounted(() => {
  fetchMembers();
});
</script>

<style scoped>
.dc-dashboard-layout { display: flex; min-height: 100vh; background: radial-gradient(circle at 20% -10%, #3a4b7c 0%, #1e294b 45%, #111827 100%); font-family: "Inter", -apple-system, sans-serif; color: #f3f4f6; }
.dc-wrapper { flex: 1; padding: 40px 24px; overflow-y: auto; }
.dc-shell { max-width: 1280px; margin: 0 auto; }
.dc-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; }
.dc-eyebrow { display: inline-flex; align-items: center; gap: 7px; font-size: 11.5px; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: #38bdf8; }
.dc-dot { width: 6px; height: 6px; border-radius: 50%; background: #38bdf8; box-shadow: 0 0 10px #38bdf8; animation: dc-pulse 2s infinite; }
@keyframes dc-pulse { 0%,100%{opacity:1} 50%{opacity:.35} }
.dc-header h1 { font-size: 25px; font-weight: 700; margin: 6px 0 0; color: #fff; }
.dc-btn { border: none; border-radius: 10px; padding: 11px 18px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all .12s ease; }
.dc-btn-primary { background: linear-gradient(135deg, #fbbf24, #d97706); color: #1f1f1f; box-shadow: 0 6px 18px rgba(251,191,36,.3); }
.dc-btn-primary:hover { filter: brightness(1.08); }
.dc-btn-ghost { background: #334155; color: #e2e8f0; border: 1px solid #475569; }
.dc-btn-ghost:hover { background: #475569; }

/* Kartu Ringkasan */
.dc-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 20px; }
.dc-stat-card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 20px; display: flex; align-items: center; gap: 14px; box-shadow: 0 20px 50px -20px rgba(0,0,0,.5); }
.dc-stat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.dc-stat-icon.is-total { background: rgba(56,189,248,.15); }
.dc-stat-icon.is-lunas-icon { background: rgba(52,211,153,.15); }
.dc-stat-icon.is-belum-icon { background: rgba(248,113,113,.15); }
.dc-stat-info { display: flex; flex-direction: column; gap: 4px; }
.dc-stat-label { font-size: 11.5px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: #94a3b8; }
.dc-stat-value { font-size: 26px; font-weight: 800; margin: 0; color: #fff; line-height: 1; }
.dc-stat-value.text-lunas { color: #34d399; }
.dc-stat-value.text-belum { color: #f87171; }

/* Search & Filter Toolbar */
.dc-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px;
  margin-bottom: 18px;
}
.dc-search-wrapper {
  position: relative;
  flex: 1 1 280px;
  min-width: 220px;
  display: flex;
  align-items: center;
}
.dc-search-icon {
  position: absolute;
  left: 13px;
  font-size: 14px;
  color: #64748b;
  pointer-events: none;
}
.dc-search-input {
  width: 100%;
  background: #1e293b;
  border: 1.5px solid #334155;
  border-radius: 10px;
  padding: 11px 36px 11px 38px;
  color: #fff;
  font-size: 13.5px;
  outline: none;
  box-sizing: border-box;
  transition: border-color .15s ease, box-shadow .15s ease;
}
.dc-search-input::placeholder { color: #64748b; }
.dc-search-input:focus { border-color: #fbbf24; box-shadow: 0 0 0 3px rgba(251,191,36,.15); }
.dc-search-clear {
  position: absolute;
  right: 8px;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  border: none;
  background: #334155;
  color: #cbd5e1;
  font-size: 15px;
  line-height: 1;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}
.dc-search-clear:hover { background: #475569; color: #fff; }

.dc-filter-group {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}
.dc-filter-chip {
  background: #1e293b;
  border: 1px solid #334155;
  color: #94a3b8;
  padding: 9px 14px;
  border-radius: 999px;
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
  transition: all .12s ease;
  white-space: nowrap;
}
.dc-filter-chip:hover { border-color: #475569; color: #e2e8f0; }
.dc-filter-chip.is-active {
  background: #fbbf24;
  border-color: #fbbf24;
  color: #1f1f1f;
}

.dc-card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 50px -20px rgba(0,0,0,.5); }
.dc-table-scroll { overflow-x: auto; }
.dc-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
.dc-table thead th { text-align: left; padding: 14px 18px; font-weight: 600; font-size: 11.5px; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; background: #172033; border-bottom: 1px solid #334155; }
.dc-table td { padding: 14px 18px; border-bottom: 1px solid #27354f; vertical-align: middle; color: #e2e8f0; }
.dc-row:hover td { background: #26354d; }
.dc-strong { font-weight: 600; color: #fff; }
.dc-amount { color: #34d399; }
.dc-muted { color: #94a3b8; }
.dc-empty { text-align: center; padding: 40px 0; color: #94a3b8; }
.dc-code { font-family: "JetBrains Mono", monospace; font-weight: 600; font-size: 12px; background: #27354f; color: #38bdf8; padding: 3px 8px; border-radius: 6px; border: 1px solid #3b82f6; }
.dc-token { font-size: 11px; color: #94a3b8; margin-top: 3px; font-family: monospace; }
.dc-plate { font-family: monospace; font-weight: 700; letter-spacing: .06em; background: #0f172a; border: 1px solid #334155; padding: 3px 8px; border-radius: 6px; color: #e2e8f0; }
.dc-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: capitalize; }
.dc-badge-dot { width: 6px; height: 6px; border-radius: 50%; }
.is-lunas { background: rgba(52,211,153,.18); color: #34d399; }
.is-lunas .dc-badge-dot { background: #34d399; }
.is-expired { background: rgba(148,163,184,.18); color: #cbd5e1; }
.is-expired .dc-badge-dot { background: #cbd5e1; }
.is-belum { background: rgba(248,113,113,.18); color: #f87171; }
.is-belum .dc-badge-dot { background: #f87171; }
.dc-actions { display: flex; gap: 6px; justify-content: flex-end; }
.dc-icon-btn { border: 1px solid #475569; background: #2b384e; color: #e2e8f0; padding: 6px 11px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; }
.dc-icon-btn:hover { background: #3b4d6b; }
.dc-success { color: #34d399; border-color: #059669; background: rgba(5,150,105,.1); }
.dc-danger { color: #f87171; border-color: #dc2626; background: rgba(220,38,38,.1); }
.dc-disabled, .dc-disabled:hover { background: #1e293b !important; color: #64748b !important; border-color: #334155 !important; cursor: not-allowed !important; opacity: .6; }
.dc-overlay { position: fixed; inset: 0; background: rgba(15,23,42,.7); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 50; padding: 16px; }
.dc-modal { background: #1e293b; border: 1px solid #475569; border-radius: 18px; width: 100%; max-width: 460px; padding: 28px; box-shadow: 0 24px 70px -10px rgba(0,0,0,.6); }
.dc-modal h3 { margin: 0 0 4px; font-size: 18px; font-weight: 700; color: #fff; }
.dc-modal-sub { color: #94a3b8; font-size: 13px; margin: 0 0 18px; }
.dc-field { margin-bottom: 16px; }
.dc-field label { display: block; font-size: 12.5px; font-weight: 600; color: #e2e8f0; margin-bottom: 6px; }
.dc-input { width: 100%; padding: 10px 13px; border: 1.5px solid #475569; border-radius: 10px; font-size: 14px; outline: none; box-sizing: border-box; background: #0f172a; color: #fff; }
.dc-input:focus { border-color: #fbbf24; box-shadow: 0 0 0 3px rgba(251,191,36,.2); }
.dc-locked-field { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 13px; border: 1.5px solid #334155; border-radius: 10px; background: #0f172a; }
.dc-locked-field.is-absolute { border-style: solid; }
.dc-locked-value { font-size: 14px; font-weight: 700; color: #e2e8f0; }
.dc-locked-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 999px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; background: rgba(251,191,36,.15); color: #fbbf24; }
.dc-field-hint { margin: 6px 0 0; font-size: 11px; color: #64748b; }
.dc-alert-error { background: rgba(239,68,68,.12); border: 1px solid rgba(239,68,68,.3); color: #f87171; padding: 10px 14px; border-radius: 10px; font-size: 12.5px; margin-bottom: 8px; }
.dc-footer { display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px; }
.dc-qr { background: #0f172a; border: 1px solid #475569; border-radius: 14px; padding: 18px; margin: 6px 0 18px; display: inline-block; }
.dc-qr canvas { display: block; }
.dc-detail-box { text-align: left; background: #0f172a; border: 1px solid #475569; border-radius: 12px; padding: 14px 16px; font-size: 13.5px; margin-bottom: 20px; }
.dc-detail-box > div { display: flex; align-items: center; justify-content: space-between; padding: 6px 0; color: #f3f4f6; }
.w-full{width:100%}

/* Kembalian */
.dc-kembalian-box {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: rgba(52,211,153,.1);
  border: 1.5px solid rgba(52,211,153,.35);
  border-radius: 10px;
  padding: 12px 14px;
  margin-bottom: 12px;
  font-size: 13.5px;
  color: #d1fae5;
}
.dc-kembalian-box strong { font-size: 16px; color: #34d399; }
.dc-kembalian-box.is-kurang { background: rgba(248,113,113,.1); border-color: rgba(248,113,113,.35); color: #fecaca; }
.dc-kembalian-box.is-kurang strong { color: #f87171; }

/* Invoice */
.dc-invoice-modal { max-width: 420px; }
.dc-invoice-check { text-align: center; font-size: 34px; margin-bottom: 4px; }
.text-center { text-align: center; }
.dc-invoice-box {
  background: #0f172a;
  border: 1px solid #334155;
  border-radius: 12px;
  padding: 16px 18px;
  margin: 16px 0 4px;
}
.dc-invoice-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 5px 0;
  font-size: 13px;
  color: #e2e8f0;
}
.dc-invoice-row span { color: #94a3b8; }
.dc-invoice-divider { border-top: 1px dashed #334155; margin: 8px 0; }
.dc-invoice-total strong { color: #34d399; font-size: 16px; }
</style>