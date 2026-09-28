<template>
  <div class="dc-dashboard-layout">
    <Sidebar />

    <div class="dc-wrapper">
      <div class="dc-shell">
        <header class="dc-header">
          <div>
            <span class="dc-eyebrow"><span class="dc-dot dot-out"></span>Live · Gerbang Keluar</span>
            <h1>Scan Keluar Kendaraan</h1>
            <p class="dc-sub">Scan barcode tiket / kartu member — tarif dihitung otomatis, palang terbuka setelah bayar.</p>
          </div>
          <div class="header-right-group">
            <div class="gate-pill out"><span class="gate-dot out"></span> Gate Keluar · Siap</div>
            <div class="clock-pill">🕒 {{ jamNow }}</div>
          </div>
        </header>

        <transition name="dc-fade">
          <div v-if="alertMessage" :class="['dc-alert', alertType]">
            <span class="alert-icon">{{ alertType === 'success' ? '✓' : '!' }}</span>
            <span>{{ alertMessage }}</span>
          </div>
        </transition>

        <div class="steps">
          <span class="step" :class="{ done: !!exitCode.trim() }"><i>1</i> Scan kode</span>
          <span class="step-line"></span>
          <span class="step" :class="{ done: !!detailParkir }"><i>2</i> Cek tarif</span>
          <span class="step-line"></span>
          <span class="step" :class="{ done: paidOk }"><i>3</i> Bayar &amp; buka</span>
        </div>

        <div class="dc-scan-container">
          <div class="dc-scan-card-modern">
            <div class="card-top">
              <span class="card-badge">PEMBAYARAN KELUAR</span>
              <span class="secure-pill">🔒 Transaksi aman</span>
            </div>
            <h2>Scan Barcode Tiket Keluar</h2>
            <p class="dc-card-desc">Tempel / scan tiket reguler (<code>TKT-…</code>) atau kartu member (<code>MBR-…</code>) untuk menghitung tarif dan membuka gerbang.</p>

            <div class="dc-input-group">
              <label for="exit-code">Nomor Tiket / Kode Member</label>
              <div class="dc-input-wrapper">
                <span class="dc-input-icon">🎫</span>
                <input
                  id="exit-code"
                  v-model="exitCode"
                  @keyup.enter="handleScanKeluar"
                  type="text"
                  placeholder="Scan barcode di sini…"
                  autocomplete="off"
                  ref="inputExitRef"
                  class="with-ico"
                />
                <button v-if="exitCode" class="clear-btn" type="button" @click="exitCode = ''; detailParkir = null">×</button>
              </div>
              <small class="dc-input-hint">Arahkan scanner ke barcode lalu tekan <kbd>Enter</kbd> — atau klik tombol di bawah.</small>
            </div>

            <div class="dc-action-group">
              <button @click="handleScanKeluar" class="dc-btn-process" :disabled="loadingProcess">
                <span v-if="loadingProcess" class="spin"></span>
                <span v-else>🔍</span>
                {{ loadingProcess ? 'Memeriksa Data…' : 'Proses Keluar & Cek Tarif' }}
              </button>
            </div>

            <transition name="dc-fade">
              <div v-if="detailParkir" class="dc-result-box">
                <div class="result-head">
                  <span class="result-title">Rincian Parkir</span>
                  <span class="chip" :class="isMember ? 'chip-member' : 'chip-reguler'">{{ isMember ? 'MEMBER · GRATIS' : 'REGULER' }}</span>
                </div>

                <div class="kode-row">
                  <span class="kode-label">Kode</span>
                  <code class="kode-val">{{ kodeTampil }}</code>
                  <button class="copy-btn" @click="copyKode" :title="copied ? 'Disalin!' : 'Salin'">{{ copied ? '✓' : '⎘' }}</button>
                </div>

                <!-- Plat Nomor — tampil di hasil, dan WAJIB diisi untuk reguler sebelum bayar -->
                <div class="dc-input-group" :class="{ 'has-error': platError }">
                  <label for="exit-plat">Plat Nomor <span class="req">*</span> <span class="opt" style="font-weight:400">wajib — konfirmasi plat asli</span></label>
                  <div class="dc-input-wrapper">
                    <span class="dc-input-icon">🚗</span>
                    <input
                      id="exit-plat"
                      v-model="exitPlat"
                      type="text"
                      placeholder="Contoh: B 1234 ABC"
                      autocomplete="off"
                      class="with-ico plat-input"
                      @input="exitPlat = exitPlat.toUpperCase(); platError = ''"
                    />
                    <button v-if="exitPlat" class="clear-btn" type="button" @click="exitPlat = ''; platError = ''">×</button>
                  </div>
                  <small v-if="platError" class="field-error">{{ platError }}</small>
                  <small v-else class="dc-input-hint">Wajib isi plat asli kendaraan — untuk member dicocokkan dengan data member.</small>
                </div>

                <template v-if="!isMember">
                  <div class="dc-result-row">
                    <span>Jenis Kendaraan</span>
                    <strong>{{ jenisKendaraanLabel }}</strong>
                  </div>
                  <div class="dc-result-row">
                    <span>Durasi Parkir</span>
                    <strong>{{ durasiJam }} Jam</strong>
                  </div>
                  <div class="dc-result-row">
                    <span>Tarif / Jam</span>
                    <strong>Rp {{ tarifPerJam.toLocaleString('id-ID') }}</strong>
                  </div>
                </template>
                <template v-else>
                  <div class="dc-result-row">
                    <span>Keanggotaan</span>
                    <strong class="text-member">Aktif · Bebas biaya</strong>
                  </div>
                </template>

                <div class="dc-result-row dc-result-total" :class="{ 'is-free': isMember }">
                  <span>Total Bayar</span>
                  <span class="dc-price">
                    {{ isMember ? 'Rp 0 · Gratis Member ✨' : `Rp ${totalBayar.toLocaleString('id-ID')}` }}
                  </span>
                </div>

                <!-- Input Jumlah Dibayar + Kembalian — hanya untuk reguler -->
                <div v-if="!isMember" class="dc-input-group dc-bayar-group" :class="{ 'has-error': bayarError }">
                  <label for="exit-bayar">Jumlah Dibayar <span class="req">*</span></label>
                  <div class="dc-input-wrapper">
                    <span class="dc-input-icon">💵</span>
                    <input
                      id="exit-bayar"
                      v-model.number="exitBayarInput"
                      type="number"
                      min="0"
                      step="500"
                      placeholder="0"
                      autocomplete="off"
                      class="with-ico bayar-input"
                      @input="bayarError = ''"
                    />
                  </div>

                  <div class="quick-nominal-row">
                    <button
                      v-for="nominal in quickNominals"
                      :key="nominal"
                      type="button"
                      class="quick-nominal-btn"
                      @click="exitBayarInput = nominal; bayarError = ''"
                    >
                      Rp {{ nominal.toLocaleString('id-ID') }}
                    </button>
                    <button type="button" class="quick-nominal-btn is-pas" @click="exitBayarInput = totalBayar; bayarError = ''">
                      Uang Pas
                    </button>
                  </div>

                  <small v-if="bayarError" class="field-error">{{ bayarError }}</small>

                  <div class="kembalian-box" :class="{ 'is-negative': kembalian < 0 }">
                    <span>Kembalian</span>
                    <strong>
                      {{ exitBayarInput === null || exitBayarInput === '' ? '—' : `Rp ${Math.abs(kembalian).toLocaleString('id-ID')}` }}
                      <span v-if="exitBayarInput !== null && exitBayarInput !== '' && kembalian < 0" class="kurang-label">(kurang)</span>
                    </strong>
                  </div>
                </div>

                <button @click="processPayment" class="dc-btn-pay" :disabled="loadingPay">
                  <span v-if="loadingPay" class="spin"></span>
                  <span v-else>🚀</span>
                  {{ loadingPay ? 'Membuka Pintu…' : (isMember ? 'Buka Pintu Otomatis (Member)' : 'Konfirmasi Pembayaran & Buka Pintu') }}
                </button>
                <p class="pay-hint">{{ isMember ? 'Tidak perlu bayar — palang akan terbuka otomatis.' : 'Pastikan jumlah dibayar mencukupi sebelum membuka palang.' }}</p>
              </div>
            </transition>

            <div v-if="!detailParkir" class="tips-box">
              <div class="tips-head">💡 Tips scanner</div>
              <ul class="tips">
                <li><span class="tip-dot blue"></span> Reguler: scan kode <code>TKT-…</code> dari tiket masuk</li>
                <li><span class="tip-dot purple"></span> Member: scan kartu <code>MBR-…</code> — langsung gratis</li>
                <li><span class="tip-dot green"></span> Jika kode tidak terbaca, ketik manual lalu <kbd>Enter</kbd></li>
              </ul>
            </div>
          </div>
        </div>

        <div class="helper-row">
          <span>⌨️ <kbd>Enter</kbd> cek tarif &nbsp;·&nbsp; 📋 salin kode &nbsp;·&nbsp; Fokus otomatis ke input setelah bayar</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';

const { $api } = useNuxtApp();
const exitCode = ref('');
const exitPlat = ref('');
const platError = ref('');
const detailParkir = ref<any>(null);
const loadingProcess = ref(false);
const loadingPay = ref(false);
const alertMessage = ref('');
const alertType = ref<'success' | 'error'>('success');
const inputExitRef = ref<HTMLInputElement | null>(null);
const paidOk = ref(false);
const copied = ref(false);
let copiedTimer: any = null;

// Jumlah dibayar & kembalian (khusus reguler)
const exitBayarInput = ref<number | ''>('');
const bayarError = ref('');
const quickNominals = [5000, 10000, 20000, 50000, 100000];

const jamNow = ref('');
let clockIv: any = null;
const tickClock = () => { jamNow.value = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }); };

const isMember = computed(() => !!detailParkir.value?.is_member);

const jenisKendaraan = computed(() => {
  const raw = (detailParkir.value?.jenis_kendaraan || '').toString().toLowerCase();
  return raw.includes('mobil') ? 'mobil' : 'motor';
});
const jenisKendaraanLabel = computed(() => (jenisKendaraan.value === 'mobil' ? 'Mobil' : 'Motor'));
const durasiJam = computed(() => detailParkir.value?.durasi_jam ?? detailParkir.value?.durasi ?? 1);
const tarifPerJam = computed(() => detailParkir.value?.tarif_per_jam ?? detailParkir.value?.tarif ?? 0);
const totalBayar = computed(() => detailParkir.value?.total_biaya ?? detailParkir.value?.total_bayar ?? detailParkir.value?.total ?? 0);
const kodeTampil = computed(() => detailParkir.value?.kode_tiket || detailParkir.value?.kode_member || detailParkir.value?.kode || exitCode.value.trim() || '-');

// Kembalian = jumlah dibayar - total tagihan. Negatif berarti kurang bayar.
const kembalian = computed(() => {
  const bayar = Number(exitBayarInput.value) || 0;
  return bayar - totalBayar.value;
});

const handleScanKeluar = async () => {
  const code = exitCode.value.trim();
  if (!code) {
    showAlert('Masukkan kode tiket / member dulu.', 'error');
    return;
  }

  loadingProcess.value = true;
  alertMessage.value = '';
  detailParkir.value = null;
  paidOk.value = false;
  platError.value = '';
  exitPlat.value = '';
  bayarError.value = '';
  exitBayarInput.value = '';

  try {
    const res: any = await $api.post('/scan/keluar/cek', { kode: code });
    detailParkir.value = res.data?.data || res.data;
    // prefill plat dari data tiket jika ada (untuk reguler yang sudah punya plat di sistem lama)
    const existingPlat: string = detailParkir.value?.plat_nomor || detailParkir.value?.plat || '';
    // kalau plat masih kode tiket/member (belum real plat), kosongkan biar petugas isi
    const isRealPlat = existingPlat && !existingPlat.startsWith('TKT-') && !existingPlat.startsWith('MBR-') && existingPlat !== '-';
    exitPlat.value = isRealPlat ? existingPlat : '';

    showAlert(
      detailParkir.value.is_member
        ? 'Kartu member dikenali — tarif gratis, siap buka palang.'
        : 'Tiket valid — isi plat nomor & jumlah dibayar untuk selesaikan pembayaran.',
      'success'
    );
  } catch (e: any) {
    detailParkir.value = null;
    showAlert(e?.response?.data?.message || 'Tiket atau member tidak ditemukan / sudah keluar.', 'error');
  } finally {
    loadingProcess.value = false;
  }
};

const processPayment = async () => {
  if (!detailParkir.value) return;

  // Validasi plat untuk reguler
  if (!exitPlat.value.trim()) {
    platError.value = 'Plat nomor wajib diisi.';
    showAlert('Plat nomor wajib diisi saat keluar (reguler).', 'error');
    return;
  }

  // Validasi jumlah dibayar — hanya untuk reguler (member gratis, tidak perlu isi)
  if (!isMember.value) {
    const bayar = Number(exitBayarInput.value);
    if (exitBayarInput.value === '' || isNaN(bayar) || bayar <= 0) {
      bayarError.value = 'Jumlah dibayar wajib diisi.';
      showAlert('Masukkan jumlah uang yang dibayarkan.', 'error');
      return;
    }
    if (bayar < totalBayar.value) {
      bayarError.value = `Kurang Rp ${(totalBayar.value - bayar).toLocaleString('id-ID')}. Jumlah dibayar belum mencukupi tarif.`;
      showAlert('Jumlah dibayar belum mencukupi tarif.', 'error');
      return;
    }
  }

  loadingPay.value = true;
  platError.value = '';
  try {
    const payload: any = {
      kode: exitCode.value.trim(),
      plat_nomor: exitPlat.value.trim() || undefined,
    };

    if (!isMember.value) {
      payload.jumlah_bayar = Number(exitBayarInput.value);
      payload.kembalian = kembalian.value;
    }

    await $api.post('/scan/keluar/bayar', payload);

    const kembalianText = !isMember.value && kembalian.value > 0
      ? ` Kembalian: Rp ${kembalian.value.toLocaleString('id-ID')}.`
      : '';

    showAlert(
      isMember.value
        ? 'Akses member berhasil! Pintu terbuka. ✨'
        : `Pembayaran berhasil! Pintu terbuka.${kembalianText} ✨`,
      'success'
    );
    paidOk.value = true;
    setTimeout(() => { paidOk.value = false; }, 2500);
    exitCode.value = '';
    exitPlat.value = '';
    exitBayarInput.value = '';
    detailParkir.value = null;
    inputExitRef.value?.focus();
  } catch (e: any) {
    const msg: string = e?.response?.data?.message || 'Gagal memproses pembayaran keluar.';
    // jika backend komplain plat, tampilkan di field
    if (/plat/i.test(msg)) platError.value = msg;
    if (/bayar|jumlah/i.test(msg)) bayarError.value = msg;
    showAlert(msg, 'error');
  } finally {
    loadingPay.value = false;
  }
};

const copyKode = async () => {
  try {
    await navigator.clipboard.writeText(kodeTampil.value);
    copied.value = true;
    if (copiedTimer) clearTimeout(copiedTimer);
    copiedTimer = setTimeout(() => (copied.value = false), 1400);
  } catch {}
};

const showAlert = (msg: string, type: 'success' | 'error') => {
  alertMessage.value = msg;
  alertType.value = type;
  setTimeout(() => { alertMessage.value = ''; }, 4000);
};

onMounted(() => {
  tickClock();
  clockIv = setInterval(tickClock, 30_000);
  inputExitRef.value?.focus();
});
onBeforeUnmount(() => {
  if (clockIv) clearInterval(clockIv);
  if (copiedTimer) clearTimeout(copiedTimer);
});
</script>

<style scoped>
.dc-dashboard-layout { display: flex; min-height: 100vh; background: radial-gradient(circle at 20% -10%, #3a4b7c 0%, #1e294b 45%, #111827 100%); font-family: 'Inter', -apple-system, sans-serif; color: #f3f4f6; }
.dc-wrapper { flex: 1; padding: 36px 24px 40px; overflow-y: auto; }
.dc-shell { max-width: 880px; margin: 0 auto; }
.dc-header { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; margin-bottom: 16px; flex-wrap: wrap; }
.dc-eyebrow { display: inline-flex; align-items: center; gap: 7px; font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #38bdf8; }
.dc-dot { width: 6px; height: 6px; border-radius: 50%; background: #38bdf8; box-shadow: 0 0 10px #38bdf8; }
.dc-dot.dot-out { background: #f97316; box-shadow: 0 0 10px #f97316; }
.dc-header h1 { font-size: 26px; font-weight: 800; margin: 6px 0 4px; color: #ffffff; letter-spacing: -0.02em; }
.dc-sub { margin: 0; font-size: 13px; color: #94a3b8; line-height: 1.5; }
.header-right-group { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.gate-pill { display: inline-flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 800; padding: 9px 14px; border-radius: 999px; border: 1px solid transparent; }
.gate-pill.out { background: rgba(249,115,22,0.12); border-color: rgba(249,115,22,0.28); color: #fdba74; }
.gate-dot { width: 7px; height: 7px; border-radius: 50%; background: #34d399; box-shadow: 0 0 8px #34d399; animation: dc-pulse 1.4s infinite; }
.gate-dot.out { background: #fb923c; box-shadow: 0 0 8px #fb923c; }
@keyframes dc-pulse { 0%,100%{opacity:1} 50%{opacity:.35} }
.clock-pill { background: #0f172a; border: 1px solid #334155; color: #cbd5e1; font-size: 12px; font-weight: 700; padding: 9px 14px; border-radius: 999px; }
.dc-alert { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: 12px; font-size: 13px; font-weight: 600; margin-bottom: 14px; border: 1px solid transparent; }
.dc-alert.success { background: rgba(52,211,153,0.12); color: #6ee7b7; border-color: rgba(52,211,153,0.28); }
.dc-alert.error { background: rgba(248,113,113,0.12); color: #fca5a5; border-color: rgba(248,113,113,0.28); }
.alert-icon { width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; flex-shrink: 0; }
.dc-alert.success .alert-icon { background: rgba(52,211,153,0.2); color: #34d399; }
.dc-alert.error .alert-icon { background: rgba(248,113,113,0.2); color: #f87171; }
.steps { display: flex; align-items: center; gap: 10px; margin-bottom: 18px; flex-wrap: wrap; }
.step { display: inline-flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; color: #64748b; background: rgba(15,23,42,0.6); border: 1px solid #1e293b; padding: 7px 12px; border-radius: 999px; }
.step i { width: 20px; height: 20px; border-radius: 50%; background: #1e293b; border: 1px solid #334155; display: inline-flex; align-items: center; justify-content: center; font-style: normal; font-size: 11px; color: #94a3b8; }
.step.done { color: #38bdf8; border-color: rgba(56,189,248,0.35); background: rgba(56,189,248,0.08); }
.step.done i { background: #38bdf8; border-color: #38bdf8; color: #0f172a; }
.step-line { width: 18px; height: 2px; background: #1e293b; border-radius: 999px; }
.dc-scan-container { display: flex; justify-content: center; }
.dc-scan-card-modern { width: 100%; max-width: 560px; background: #1e293b; border: 1px solid #334155; border-radius: 20px; padding: 28px; box-shadow: 0 20px 50px -20px rgba(0,0,0,0.55); position: relative; overflow: hidden; }
.dc-scan-card-modern::before { content: ""; position: absolute; inset: 0 0 auto 0; height: 1px; background: linear-gradient(90deg, transparent, rgba(249,115,22,0.35), transparent); opacity: .7; }
.card-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px; }
.card-badge { display: inline-block; background: rgba(249,115,22,0.12); color: #fdba74; border: 1px solid rgba(249,115,22,0.22); padding: 6px 12px; border-radius: 999px; font-size: 10.5px; font-weight: 800; letter-spacing: .06em; }
.secure-pill { font-size: 11px; font-weight: 700; color: #94a3b8; background: #0f172a; border: 1px solid #1e293b; padding: 5px 10px; border-radius: 999px; }
.dc-scan-card-modern h2 { font-size: 19px; font-weight: 800; color: #ffffff; margin: 0 0 6px; letter-spacing: -0.015em; text-align: left; }
.dc-card-desc { font-size: 12.5px; line-height: 1.6; color: #94a3b8; margin: 0 0 20px; text-align: left; }
.dc-card-desc code { background: #0f172a; border: 1px solid #1e293b; padding: 1px 6px; border-radius: 6px; color: #38bdf8; font-size: 11px; }
.dc-input-group { text-align: left; margin-bottom: 16px; }
.dc-input-group.has-error input { border-color: #f87171 !important; box-shadow: 0 0 0 3px rgba(248,113,113,0.15) !important; }
.field-error { display: block; font-size: 11.5px; color: #f87171; margin-top: 6px; font-weight: 600; }
.req { color: #f87171; }
.opt { color: #64748b; font-weight: 600; font-size: 11px; }
.dc-input-group label { display: block; font-size: 12px; font-weight: 700; color: #cbd5e1; margin-bottom: 8px; letter-spacing: .02em; }
.dc-input-wrapper { position: relative; display: flex; align-items: center; }
.dc-input-icon { position: absolute; left: 12px; font-size: 16px; color: #fb923c; pointer-events: none; }
.with-ico { padding-left: 42px !important; }
.plat-input { letter-spacing: .12em; font-weight: 800; text-transform: uppercase; font-size: 15px !important; }
.dc-input-wrapper input { width: 100%; background: #0f172a; border: 1.5px solid #334155; border-radius: 12px; padding: 13px 42px 13px 42px; color: #ffffff; font-size: 14px; outline: none; transition: border-color 0.15s ease, box-shadow 0.15s ease; box-sizing: border-box; font-weight: 600; letter-spacing: .02em; }
.dc-input-wrapper input:focus { border-color: #fb923c; box-shadow: 0 0 0 3px rgba(251,146,60,0.18); }
.dc-input-wrapper input::placeholder { color: #64748b; font-weight: 500; }
.clear-btn { position: absolute; right: 10px; width: 28px; height: 28px; border-radius: 50%; border: 1px solid #334155; background: #1e293b; color: #94a3b8; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; font-size: 16px; line-height: 1; }
.clear-btn:hover { color: #e2e8f0; border-color: #475569; }
.dc-input-hint { display: block; font-size: 11.5px; color: #64748b; margin-top: 7px; line-height: 1.5; }
.dc-input-hint kbd { background: #0f172a; border: 1px solid #334155; border-bottom-width: 2px; padding: 1px 6px; border-radius: 6px; font-size: 11px; color: #cbd5e1; }
.dc-action-group { margin-bottom: 4px; }
.dc-btn-process, .dc-btn-pay { width: 100%; border: none; border-radius: 12px; padding: 13px 16px; font-size: 14px; font-weight: 800; color: #ffffff; cursor: pointer; transition: background-color 0.15s ease, transform 0.1s ease, filter .15s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
.dc-btn-process { background: linear-gradient(180deg, #fb923c, #f97316); box-shadow: 0 10px 24px rgba(249,115,22,0.35), 0 1px 0 rgba(255,255,255,0.18) inset; }
.dc-btn-process:hover:not(:disabled) { filter: brightness(1.06); transform: translateY(-1px); }
.dc-btn-pay { background: linear-gradient(180deg, #10b981, #059669); margin-top: 14px; box-shadow: 0 10px 24px rgba(5,150,105,0.32), 0 1px 0 rgba(255,255,255,0.14) inset; }
.dc-btn-pay:hover:not(:disabled) { filter: brightness(1.05); transform: translateY(-1px); }
.dc-btn-process:disabled, .dc-btn-pay:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
.spin { width: 16px; height: 16px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.35); border-top-color: #fff; animation: spin .7s linear infinite; display: inline-block; }
@keyframes spin { to { transform: rotate(360deg); } }
.dc-result-box { background: radial-gradient(120% 120% at 10% 0%, rgba(56,189,248,0.08), transparent 55%), #0f172a; border: 1px solid #334155; border-radius: 16px; padding: 16px; margin-top: 18px; text-align: left; overflow: hidden; position: relative; }
.dc-result-box::before { content: ""; position: absolute; left: -10px; top: 50%; width: 20px; height: 20px; border-radius: 50%; background: #1e293b; border: 1px solid #334155; }
.dc-result-box::after { content: ""; position: absolute; right: -10px; top: 50%; width: 20px; height: 20px; border-radius: 50%; background: #1e293b; border: 1px solid #334155; }
.result-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 12px; }
.result-title { font-size: 12px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #94a3b8; }
.chip { font-size: 10px; font-weight: 900; letter-spacing: .08em; padding: 5px 9px; border-radius: 999px; border: 1px solid transparent; }
.chip-reguler { color: #38bdf8; background: rgba(56,189,248,0.12); border-color: rgba(56,189,248,0.25); }
.chip-member { color: #a78bfa; background: rgba(167,139,250,0.14); border-color: rgba(167,139,250,0.28); }
.kode-row { display: flex; align-items: center; gap: 8px; background: #020617; border: 1px solid #1e293b; padding: 10px 12px; border-radius: 12px; margin-bottom: 14px; }
.kode-label { font-size: 10px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #64748b; }
.kode-val { flex: 1; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 14px; font-weight: 800; color: #e2e8f0; letter-spacing: .06em; word-break: break-all; }
.copy-btn { width: 30px; height: 30px; border-radius: 8px; border: 1px solid #334155; background: #0f172a; color: #94a3b8; cursor: pointer; flex-shrink: 0; }
.copy-btn:hover { color: #fff; border-color: #475569; }
.dc-result-row { display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: #94a3b8; padding: 7px 0; gap: 12px; }
.dc-result-row strong { color: #ffffff; font-weight: 700; }
.text-member { color: #a78bfa !important; }
.dc-result-total { margin-top: 8px; padding-top: 14px; border-top: 1px dashed #334155; font-size: 15px; }
.dc-result-total .dc-price { color: #34d399; font-weight: 800; font-size: 16px; letter-spacing: -0.01em; }
.dc-result-total.is-free .dc-price { color: #a78bfa; }

/* Input Jumlah Dibayar + Kembalian */
.dc-bayar-group { margin-top: 4px; margin-bottom: 14px; }
.bayar-input { font-weight: 800; font-size: 16px !important; }
.quick-nominal-row { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
.quick-nominal-btn {
  background: #0f172a;
  border: 1px solid #334155;
  color: #cbd5e1;
  font-size: 11.5px;
  font-weight: 700;
  padding: 6px 10px;
  border-radius: 8px;
  cursor: pointer;
  transition: all .12s ease;
}
.quick-nominal-btn:hover { background: #1e293b; border-color: #475569; color: #fff; }
.quick-nominal-btn.is-pas {
  color: #34d399;
  border-color: rgba(52,211,153,0.3);
  background: rgba(52,211,153,0.08);
}
.quick-nominal-btn.is-pas:hover { background: rgba(52,211,153,0.15); }

.kembalian-box {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: rgba(52,211,153,0.08);
  border: 1px solid rgba(52,211,153,0.25);
  border-radius: 10px;
  padding: 10px 14px;
  margin-top: 10px;
  font-size: 13px;
  color: #94a3b8;
  font-weight: 600;
}
.kembalian-box strong { color: #34d399; font-size: 15px; font-weight: 800; }
.kembalian-box.is-negative { background: rgba(248,113,113,0.08); border-color: rgba(248,113,113,0.28); }
.kembalian-box.is-negative strong { color: #f87171; }
.kurang-label { font-size: 10.5px; font-weight: 700; margin-left: 4px; }

.pay-hint { margin: 8px 0 0; font-size: 11.5px; color: #64748b; text-align: center; }
.tips-box { margin-top: 18px; background: rgba(15,23,42,0.45); border: 1px dashed #334155; border-radius: 14px; padding: 14px; text-align: left; }
.tips-head { font-size: 12px; font-weight: 800; color: #e2e8f0; margin-bottom: 10px; }
.tips { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 8px; font-size: 12px; color: #94a3b8; line-height: 1.5; }
.tips li { display: flex; align-items: center; gap: 8px; background: #0f172a; border: 1px solid #1e293b; padding: 8px 10px; border-radius: 10px; }
.tips code { background: #020617; border: 1px solid #1e293b; padding: 1px 6px; border-radius: 6px; color: #38bdf8; font-size: 11px; }
.tips kbd { background: #020617; border: 1px solid #334155; border-bottom-width: 2px; padding: 1px 6px; border-radius: 6px; color: #cbd5e1; font-size: 11px; }
.tip-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.tip-dot.blue { background: #38bdf8; box-shadow: 0 0 8px rgba(56,189,248,0.5); }
.tip-dot.purple { background: #a78bfa; box-shadow: 0 0 8px rgba(167,139,250,0.5); }
.tip-dot.green { background: #34d399; box-shadow: 0 0 8px rgba(52,211,153,0.5); }
.helper-row { margin-top: 14px; text-align: center; font-size: 11.5px; color: #64748b; }
.helper-row kbd { background: #0f172a; border: 1px solid #334155; border-bottom-width: 2px; padding: 1px 6px; border-radius: 6px; color: #cbd5e1; font-size: 11px; }
.dc-fade-enter-active, .dc-fade-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.dc-fade-enter-from, .dc-fade-leave-to { opacity: 0; transform: translateY(-6px); }
</style>