<template>
  <div class="users-scan-page">
    <div class="dc-wrapper">
      <div class="dc-shell">
        <header class="dc-header">
          <div>
            <span class="dc-eyebrow"><span class="dc-dot"></span>Live · Gerbang Masuk</span>
            <h1>Scan / Cetak Tiket Masuk</h1>
            <p class="dc-sub">Pilih jenis kendaraan lalu cetak — QR langsung ter-download lengkap dengan keterangan. Member cukup scan kode.</p>
          </div>
          <div class="header-right-group">
            <div class="gate-pill"><span class="gate-dot"></span> Gate Masuk · Siap</div>
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
          <span class="step" :class="{ done: form.jenis_kendaraan }"><i>1</i> Pilih kendaraan</span>
          <span class="step-line"></span>
          <span class="step" :class="{ done: !!lastDownloadCode }"><i>2</i> Cetak &amp; Download QR</span>
          <span class="step-line"></span>
          <span class="step" :class="{ done: !!lastDownloadCode }"><i>3</i> Konfirmasi masuk</span>
        </div>

        <div class="dc-card dc-card-single">
          <div class="card-head">
            <h3>Data Kendaraan &amp; Member</h3>
            <span class="card-badge">Auto-download tiket QR</span>
          </div>

          <!-- Jenis kendaraan saja — plat dihapus dari scan masuk -->
          <div class="dc-field">
            <label>Jenis Kendaraan (Reguler)</label>
            <div class="seg">
              <button type="button" class="seg-btn" :class="{ active: form.jenis_kendaraan === 'motor' }" @click="form.jenis_kendaraan = 'motor'">
                <span class="seg-ico">🏍️</span>
                <span class="seg-t"><strong>Motor</strong><small>Rp 3.000 / jam</small></span>
              </button>
              <button type="button" class="seg-btn" :class="{ active: form.jenis_kendaraan === 'mobil' }" @click="form.jenis_kendaraan = 'mobil'">
                <span class="seg-ico">🚗</span>
                <span class="seg-t"><strong>Mobil</strong><small>Rp 5.000 / jam</small></span>
              </button>
            </div>
            <span class="hint">Plat nomor tidak perlu diisi saat masuk — diisi saat keluar.</span>
          </div>

          <button @click="handlePrintTiket" :disabled="loadingPrint" class="dc-btn dc-btn-primary w-full">
            <span v-if="loadingPrint" class="spin"></span><span v-else>🎫</span>
            {{ loadingPrint ? 'Memproses…' : 'Cetak Tiket & Download QR' }}
          </button>
          <p class="btn-hint">Tiket QR (lengkap dengan keterangan) langsung ter-download sebagai <code>Tiket-TKT-xxxxxx.png</code></p>

          <div v-if="lastDownloadCode" class="last-download">
            <span class="ld-label">Terakhir di-download:</span>
            <code class="ld-code">{{ lastDownloadCode }}</code>
            <button class="ld-btn" @click="downloadAgain" type="button">⬇ Download ulang</button>
          </div>

          <div class="dc-divider"><span>atau scan kartu member / tiket</span></div>

          <div class="dc-field" style="margin-bottom:8px">
            <label>Kode Tiket / Kode Member</label>
            <div class="scan-input-wrap">
              <span class="scan-ico">⌖</span>
              <input
                v-model="memberCode"
                @keyup.enter="handleScanMember"
                type="text"
                class="dc-input with-ico"
                placeholder="Tempel / scan kode — cth. TKT-... atau MBR-..."
                autocomplete="off"
              />
              <button v-if="memberCode" class="clear-btn inside" @click="memberCode = ''" type="button">×</button>
            </div>
            <span class="hint">Tekan <kbd>Enter</kbd> untuk konfirmasi &amp; download QR masuk</span>
          </div>
          <button @click="handleScanMember" :disabled="loadingScan" class="dc-btn dc-btn-secondary w-full">
            <span v-if="loadingScan" class="spin dark"></span><span v-else>✅</span>
            {{ loadingScan ? 'Memproses...' : 'Konfirmasi Masuk & Download QR' }}
          </button>
        </div>

        <!-- Canvas tersembunyi untuk generate QR mentah -->
        <canvas ref="hiddenQrCanvasRef" style="display:none" width="300" height="300"></canvas>
        <!-- Canvas tersembunyi untuk compose kartu tiket final (QR + keterangan) -->
        <canvas ref="ticketCanvasRef" style="display:none"></canvas>

        <div class="helper-row">
          <span>⌨️ <kbd>Enter</kbd> untuk konfirmasi &nbsp;·&nbsp; QR + keterangan tiket otomatis ter-download</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, nextTick, onMounted, onBeforeUnmount } from 'vue';
import QRCode from 'qrcode';

const { $api } = useNuxtApp();
const form = reactive({
  jenis_kendaraan: '' as '' | 'motor' | 'mobil'
});
const memberCode = ref('');
const loadingPrint = ref(false);
const loadingScan = ref(false);
const alertMessage = ref('');
const alertType = ref<'success' | 'error'>('success');
const lastDownloadCode = ref('');
const hiddenQrCanvasRef = ref<HTMLCanvasElement | null>(null);
const ticketCanvasRef = ref<HTMLCanvasElement | null>(null);

// Simpan detail tiket terakhir supaya "Download ulang" bisa compose ulang kartunya
const lastTicketMeta = reactive({
  code: '',
  isMember: false,
  jenisKendaraan: '' as '' | 'motor' | 'mobil',
  tarifPerJam: 0,
  waktuMasuk: '',
});

const jamNow = ref('');
let clockIv: any = null;
const tickClock = () => { jamNow.value = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }); };
onMounted(() => { tickClock(); clockIv = setInterval(tickClock, 30_000); });
onBeforeUnmount(() => { if (clockIv) clearInterval(clockIv); });

const TARIF_MOTOR = 3000;
const TARIF_MOBIL = 5000;

// Bungkus teks panjang otomatis ke beberapa baris supaya tidak keluar dari kartu
const wrapText = (ctx: CanvasRenderingContext2D, text: string, maxWidth: number): string[] => {
  const words = text.split(' ');
  const lines: string[] = [];
  let currentLine = words[0] || '';
  for (let i = 1; i < words.length; i++) {
    const testLine = currentLine + ' ' + words[i];
    if (ctx.measureText(testLine).width > maxWidth) {
      lines.push(currentLine);
      currentLine = words[i];
    } else {
      currentLine = testLine;
    }
  }
  if (currentLine) lines.push(currentLine);
  return lines;
};

const drawRoundedRect = (ctx: CanvasRenderingContext2D, x: number, y: number, w: number, h: number, r: number) => {
  ctx.beginPath();
  ctx.moveTo(x + r, y);
  ctx.arcTo(x + w, y, x + w, y + h, r);
  ctx.arcTo(x + w, y + h, x, y + h, r);
  ctx.arcTo(x, y + h, x, y, r);
  ctx.arcTo(x, y, x + w, y, r);
  ctx.closePath();
};

/**
 * Generate 1 gambar PNG berisi: header judul, QR code, dan blok keterangan
 * (kode, tipe, jenis kendaraan, tarif, waktu masuk, catatan) — lalu trigger download.
 */
const generateTicketImage = async (opts: {
  code: string;
  isMember: boolean;
  jenisKendaraan?: '' | 'motor' | 'mobil';
  tarifPerJam?: number;
  waktuMasuk?: string;
}) => {
  // 1) render QR mentah dulu ke canvas tersembunyi
  const qrCanvas = hiddenQrCanvasRef.value;
  if (!qrCanvas) throw new Error('Canvas QR tidak siap');
  await QRCode.toCanvas(qrCanvas, opts.code, {
    width: 300,
    margin: 1,
    color: { dark: '#0f172a', light: '#ffffff' }
  });

  // 2) siapkan konten teks keterangan
  const isMember = opts.isMember;
  const jenisLabel = opts.jenisKendaraan === 'mobil' ? 'Mobil' : opts.jenisKendaraan === 'motor' ? 'Motor' : '-';
  const tarifText = `Rp ${(opts.tarifPerJam || 0).toLocaleString('id-ID')}`;

  // 3) susun kartu di canvas final
  const canvas = ticketCanvasRef.value;
  if (!canvas) throw new Error('Canvas tiket tidak siap');
  const width = 420;
  const height = isMember ? 560 : 640;
  canvas.width = width;
  canvas.height = height;
  const ctx = canvas.getContext('2d');
  if (!ctx) throw new Error('Context canvas tidak tersedia');

  // Background kartu
  ctx.fillStyle = '#0f172a';
  ctx.fillRect(0, 0, width, height);

  // Panel putih (card)
  const pad = 20;
  drawRoundedRect(ctx, pad, pad, width - pad * 2, height - pad * 2, 18);
  ctx.fillStyle = '#ffffff';
  ctx.fill();

  let cursorY = pad + 30;

  // Header — judul sistem
  ctx.fillStyle = '#0f172a';
  ctx.font = '700 13px sans-serif';
  ctx.textAlign = 'center';
  ctx.fillText('SISTEM PARKIR', width / 2, cursorY);
  cursorY += 22;

  // Judul tiket
  ctx.font = '800 20px sans-serif';
  ctx.fillText(isMember ? 'KARTU AKSES MEMBER' : 'TIKET PARKIR MASUK', width / 2, cursorY);
  cursorY += 10;

  // garis pemisah
  cursorY += 14;
  ctx.strokeStyle = '#e2e8f0';
  ctx.lineWidth = 1;
  ctx.beginPath();
  ctx.moveTo(pad + 24, cursorY);
  ctx.lineTo(width - pad - 24, cursorY);
  ctx.stroke();
  cursorY += 24;

  // QR code di tengah, dengan bingkai tipis
  const qrSize = 190;
  const qrX = (width - qrSize) / 2;
  drawRoundedRect(ctx, qrX - 8, cursorY - 8, qrSize + 16, qrSize + 16, 12);
  ctx.strokeStyle = '#cbd5e1';
  ctx.lineWidth = 1.5;
  ctx.stroke();
  ctx.drawImage(qrCanvas, qrX, cursorY, qrSize, qrSize);
  cursorY += qrSize + 26;

  // Kode tiket besar
  ctx.font = '800 17px monospace';
  ctx.fillStyle = '#0f172a';
  ctx.fillText(opts.code, width / 2, cursorY);
  cursorY += 28;

  // garis pemisah kedua
  ctx.strokeStyle = '#e2e8f0';
  ctx.beginPath();
  ctx.moveTo(pad + 24, cursorY);
  ctx.lineTo(width - pad - 24, cursorY);
  ctx.stroke();
  cursorY += 26;

  // Blok keterangan (label kiri, value kanan)
  ctx.textAlign = 'left';
  const labelX = pad + 24;
  const valueX = width - pad - 24;
  const rowGap = 26;

  const drawRow = (label: string, value: string, valueColor = '#0f172a') => {
    ctx.font = '600 11.5px sans-serif';
    ctx.fillStyle = '#64748b';
    ctx.textAlign = 'left';
    ctx.fillText(label.toUpperCase(), labelX, cursorY);

    ctx.font = '700 13px sans-serif';
    ctx.fillStyle = valueColor;
    ctx.textAlign = 'right';
    ctx.fillText(value, valueX, cursorY);

    cursorY += rowGap;
  };

  drawRow('Tipe', isMember ? 'Member (Gratis)' : 'Reguler', isMember ? '#7c3aed' : '#2563eb');
  if (!isMember) {
    drawRow('Jenis Kendaraan', jenisLabel);
    drawRow('Tarif / Jam', tarifText);
  }
  drawRow('Waktu Masuk', opts.waktuMasuk || new Date().toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }));
  drawRow('Status', 'Aktif · Di Dalam Gerbang', '#059669');

  cursorY += 6;
  ctx.strokeStyle = '#e2e8f0';
  ctx.beginPath();
  ctx.moveTo(pad + 24, cursorY);
  ctx.lineTo(width - pad - 24, cursorY);
  ctx.stroke();
  cursorY += 24;

  // Catatan / instruksi kecil di bawah
  ctx.font = '500 11px sans-serif';
  ctx.fillStyle = '#64748b';
  ctx.textAlign = 'center';
  const noteText = isMember
    ? 'Simpan kartu ini. Tunjukkan / scan QR saat keluar gerbang — parkir gratis untuk member aktif.'
    : 'Simpan tiket ini baik-baik. Scan QR saat keluar untuk menghitung tarif otomatis. Tiket hilang dikenakan denda sesuai kebijakan.';
  const lines = wrapText(ctx, noteText, width - pad * 2 - 48);
  lines.forEach((line) => {
    ctx.fillText(line, width / 2, cursorY);
    cursorY += 16;
  });

  // 4) trigger download dari canvas final
  const link = document.createElement('a');
  const prefix = isMember ? 'Kartu-Member' : 'Tiket';
  link.download = `${prefix}-${opts.code}.png`;
  link.href = canvas.toDataURL('image/png');
  link.click();

  lastDownloadCode.value = opts.code;
};

const downloadAgain = async () => {
  if (!lastTicketMeta.code) return;
  await generateTicketImage({
    code: lastTicketMeta.code,
    isMember: lastTicketMeta.isMember,
    jenisKendaraan: lastTicketMeta.jenisKendaraan,
    tarifPerJam: lastTicketMeta.tarifPerJam,
    waktuMasuk: lastTicketMeta.waktuMasuk,
  });
  showAlert(`Tiket ${lastTicketMeta.code} di-download ulang.`, 'success');
};

const handlePrintTiket = async () => {
  if (!form.jenis_kendaraan) {
    showAlert('Mohon pilih jenis kendaraan terlebih dahulu!', 'error');
    return;
  }
  loadingPrint.value = true;
  alertMessage.value = '';
  try {
    const res: any = await $api.post('/parkir/masuk/tiket', {
      jenis_kendaraan: form.jenis_kendaraan
    });
    const responseData = res.data?.data || res.data;
    const nomorTiket = responseData?.kode_tiket;
    if (!nomorTiket) throw new Error('Tiket gagal dibuat');
    memberCode.value = nomorTiket;

    const tarifPerJam = form.jenis_kendaraan === 'mobil' ? TARIF_MOBIL : TARIF_MOTOR;
    const waktuMasuk = responseData?.waktu_masuk
      ? new Date(responseData.waktu_masuk).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
      : new Date().toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });

    lastTicketMeta.code = nomorTiket;
    lastTicketMeta.isMember = false;
    lastTicketMeta.jenisKendaraan = form.jenis_kendaraan;
    lastTicketMeta.tarifPerJam = tarifPerJam;
    lastTicketMeta.waktuMasuk = waktuMasuk;

    await generateTicketImage({
      code: nomorTiket,
      isMember: false,
      jenisKendaraan: form.jenis_kendaraan,
      tarifPerJam,
      waktuMasuk,
    });

    showAlert(`Tiket ${nomorTiket} berhasil — tiket QR (lengkap keterangan) otomatis ter-download. Tekan Konfirmasi untuk buka palang.`, 'success');
  } catch (e: any) {
    showAlert(e?.response?.data?.message || 'Gagal mencetak tiket masuk.', 'error');
  } finally {
    loadingPrint.value = false;
  }
};

const handleScanMember = async () => {
  const kodeInput = memberCode.value.trim();
  if (!kodeInput) {
    showAlert('Masukkan kode tiket atau member terlebih dahulu!', 'error');
    return;
  }
  loadingScan.value = true;
  alertMessage.value = '';
  try {
    const res: any = await $api.post('/parkir/masuk/member', { kode_member: kodeInput });
    const responseData = res.data?.data || res.data;
    const kodeHasil = (responseData?.kode_tiket || responseData?.kode_member || kodeInput).toString();
    const isMemberKode = !kodeHasil.toUpperCase().startsWith('TKT-');

    const waktuMasuk = responseData?.waktu_masuk
      ? new Date(responseData.waktu_masuk).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
      : new Date().toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });

    lastTicketMeta.code = kodeHasil;
    lastTicketMeta.isMember = isMemberKode;
    lastTicketMeta.jenisKendaraan = '';
    lastTicketMeta.tarifPerJam = 0;
    lastTicketMeta.waktuMasuk = waktuMasuk;

    await generateTicketImage({
      code: kodeHasil,
      isMember: isMemberKode,
      waktuMasuk,
    });

    showAlert(
      isMemberKode ? `Member ${kodeHasil} — masuk & tiket ter-download! 🚀` : `Tiket ${kodeHasil} — masuk & tiket ter-download! 🚀`,
      'success'
    );
    memberCode.value = '';
  } catch (e: any) {
    showAlert(e?.response?.data?.message || 'Gagal memproses kode masuk.', 'error');
  } finally {
    loadingScan.value = false;
  }
};

const showAlert = (msg: string, type: 'success' | 'error') => {
  alertMessage.value = msg;
  alertType.value = type;
  setTimeout(() => { alertMessage.value = ''; }, 4000);
};
</script>

<style scoped>
.users-scan-page { min-height: 100vh; background: radial-gradient(circle at 20% -10%, #3a4b7c 0%, #1e294b 45%, #111827 100%); font-family: 'Inter', -apple-system, sans-serif; color: #f3f4f6; }
.dc-wrapper { flex: 1; padding: 36px 24px 40px; overflow-y: auto; }
.dc-shell { max-width: 720px; margin: 0 auto; }
.dc-header { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; margin-bottom: 16px; flex-wrap: wrap; }
.dc-eyebrow { display: inline-flex; align-items: center; gap: 7px; font-size: 11.5px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #38bdf8; }
.dc-dot { width: 6px; height: 6px; border-radius: 50%; background: #38bdf8; box-shadow: 0 0 10px #38bdf8; animation: dc-pulse 2s infinite; }
@keyframes dc-pulse { 0%,100%{opacity:1} 50%{opacity:.35} }
.dc-header h1 { font-size: 26px; font-weight: 800; margin: 6px 0 4px; color: #ffffff; letter-spacing: -0.02em; }
.dc-sub { margin: 0; font-size: 13px; color: #94a3b8; line-height: 1.5; }
.header-right-group { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.gate-pill { display: inline-flex; align-items: center; gap: 8px; background: rgba(52,211,153,0.12); border: 1px solid rgba(52,211,153,0.28); color: #6ee7b7; font-size: 12px; font-weight: 700; padding: 9px 14px; border-radius: 999px; }
.gate-dot { width: 7px; height: 7px; border-radius: 50%; background: #34d399; box-shadow: 0 0 8px #34d399; animation: dc-pulse 1.4s infinite; }
.clock-pill { background: #0f172a; border: 1px solid #334155; color: #cbd5e1; font-size: 12px; font-weight: 700; padding: 9px 14px; border-radius: 999px; text-decoration: none; }
.dc-alert { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: 12px; font-size: 13px; font-weight: 600; margin-bottom: 14px; border: 1px solid transparent; }
.dc-alert.success { background: rgba(52,211,153,0.12); color: #6ee7b7; border-color: rgba(52,211,153,0.28); }
.dc-alert.error { background: rgba(248,113,113,0.12); color: #fca5a5; border-color: rgba(248,113,113,0.28); }
.alert-icon { width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; flex-shrink: 0; }
.dc-alert.success .alert-icon { background: rgba(52,211,153,0.2); color: #34d399; }
.dc-alert.error .alert-icon { background: rgba(248,113,113,0.2); color: #f87171; }
.dc-fade-enter-active, .dc-fade-leave-active { transition: opacity .2s ease, transform .2s ease; }
.dc-fade-enter-from, .dc-fade-leave-to { opacity: 0; transform: translateY(-6px); }
.steps { display: flex; align-items: center; gap: 10px; margin-bottom: 18px; flex-wrap: wrap; }
.step { display: inline-flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; color: #64748b; background: rgba(15,23,42,0.6); border: 1px solid #1e293b; padding: 7px 12px; border-radius: 999px; }
.step i { width: 20px; height: 20px; border-radius: 50%; background: #1e293b; border: 1px solid #334155; display: inline-flex; align-items: center; justify-content: center; font-style: normal; font-size: 11px; color: #94a3b8; }
.step.done { color: #38bdf8; border-color: rgba(56,189,248,0.35); background: rgba(56,189,248,0.08); }
.step.done i { background: #38bdf8; border-color: #38bdf8; color: #0f172a; }
.step-line { width: 18px; height: 2px; background: #1e293b; border-radius: 999px; }
.dc-card-single { background: #1e293b; border: 1px solid #334155; border-radius: 18px; padding: 22px; box-shadow: 0 20px 50px -20px rgba(0,0,0,0.55); position: relative; overflow: hidden; }
.dc-card-single::before { content: ""; position: absolute; inset: 0 0 auto 0; height: 1px; background: linear-gradient(90deg, transparent, rgba(56,189,248,0.35), transparent); opacity: .7; }
.card-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 18px; }
.dc-card h3 { font-size: 14px; font-weight: 800; margin: 0; color: #ffffff; letter-spacing: -0.01em; }
.card-badge { font-size: 10.5px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #34d399; background: rgba(52,211,153,0.12); border: 1px solid rgba(52,211,153,0.22); padding: 5px 10px; border-radius: 999px; }
.dc-field { margin-bottom: 16px; }
.dc-field label { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; color: #cbd5e1; margin-bottom: 8px; letter-spacing: .02em; }
.dc-input { width: 100%; padding: 12px 13px; border: 1.5px solid #334155; border-radius: 12px; font-size: 14px; outline: none; box-sizing: border-box; background: #0f172a; color: #f3f4f6; transition: border-color .15s, box-shadow .15s; }
.dc-input:focus { border-color: #38bdf8; box-shadow: 0 0 0 3px rgba(56,189,248,0.15); }
.dc-input::placeholder { color: #64748b; }
.with-ico { padding-left: 42px !important; }
.scan-input-wrap { position: relative; display: flex; align-items: center; }
.scan-ico { position: absolute; left: 12px; font-size: 16px; color: #38bdf8; }
.clear-btn { position: absolute; right: 10px; width: 28px; height: 28px; border-radius: 50%; border: 1px solid #334155; background: #1e293b; color: #94a3b8; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; font-size: 16px; line-height: 1; }
.clear-btn:hover { color: #e2e8f0; border-color: #475569; }
.clear-btn.inside { right: 10px; }
.seg { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.seg-btn { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 12px; border: 1.5px solid #334155; background: #0f172a; color: #cbd5e1; cursor: pointer; text-align: left; transition: all .15s; }
.seg-btn:hover { border-color: #475569; background: #111e32; }
.seg-btn.active { border-color: #38bdf8; background: rgba(56,189,248,0.12); color: #e0f2fe; box-shadow: 0 0 0 3px rgba(56,189,248,0.12); }
.seg-ico { width: 38px; height: 38px; border-radius: 10px; background: #1e293b; border: 1px solid #334155; display: inline-flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
.seg-btn.active .seg-ico { background: #38bdf8; border-color: #38bdf8; }
.seg-t { display: flex; flex-direction: column; line-height: 1.1; }
.seg-t strong { font-size: 13px; font-weight: 800; }
.seg-t small { font-size: 11.5px; color: #94a3b8; margin-top: 2px; }
.seg-btn.active .seg-t small { color: #bae6fd; }
.dc-btn { border: none; border-radius: 12px; padding: 12px 16px; font-size: 14px; font-weight: 800; cursor: pointer; transition: all .12s ease; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
.dc-btn-primary { background: linear-gradient(180deg, #3b82f6, #2563eb); color: #fff; box-shadow: 0 10px 24px rgba(37,99,235,0.35), 0 1px 0 rgba(255,255,255,0.18) inset; }
.dc-btn-primary:hover:not(:disabled) { filter: brightness(1.05); transform: translateY(-1px); }
.dc-btn-secondary { background: #0f172a; color: #e2e8f0; border: 1px solid #334155; }
.dc-btn-secondary:hover:not(:disabled) { background: #111e32; border-color: #475569; }
.w-full { width: 100%; }
.btn-hint { margin: 8px 2px 0; font-size: 11px; color: #64748b; text-align: center; }
.btn-hint code { background: #0f172a; border: 1px solid #1e293b; padding: 1px 6px; border-radius: 6px; color: #38bdf8; font-size: 11px; }
.dc-divider { display: flex; align-items: center; text-align: center; margin: 18px 0; color: #64748b; font-size: 11.5px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
.dc-divider::before, .dc-divider::after { content: ""; flex: 1; height: 1px; background: #334155; }
.dc-divider span { padding: 0 10px; background: #1e293b; border: 1px solid #334155; border-radius: 999px; }
.spin { width: 16px; height: 16px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.35); border-top-color: #fff; animation: spin .7s linear infinite; display: inline-block; }
.spin.dark { border-color: rgba(148,163,184,0.35); border-top-color: #e2e8f0; }
@keyframes spin { to { transform: rotate(360deg); } }
.last-download { display: flex; align-items: center; gap: 8px; margin-top: 10px; background: #0f172a; border: 1px solid #334155; padding: 10px 12px; border-radius: 12px; flex-wrap: wrap; }
.ld-label { font-size: 11px; font-weight: 700; color: #94a3b8; }
.ld-code { font-family: ui-monospace, monospace; font-size: 12px; font-weight: 800; color: #38bdf8; background: #020617; border: 1px solid #1e293b; padding: 4px 8px; border-radius: 8px; }
.ld-btn { margin-left: auto; background: #1e293b; border: 1px solid #334155; color: #e2e8f0; padding: 6px 10px; border-radius: 8px; font-size: 11px; font-weight: 700; cursor: pointer; }
.ld-btn:hover { border-color: #38bdf8; color: #fff; }
.helper-row { margin-top: 14px; text-align: center; font-size: 11.5px; color: #64748b; }
.helper-row kbd { background: #0f172a; border: 1px solid #334155; border-bottom-width: 2px; padding: 1px 6px; border-radius: 6px; color: #cbd5e1; font-size: 11px; }
</style>