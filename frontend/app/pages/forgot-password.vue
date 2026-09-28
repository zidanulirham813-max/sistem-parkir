<template>
  <div class="login-layout">
    <div class="login-card">
      <div class="login-header">
        <div class="logo-badge">P</div>
        <h2>Lupa Password</h2>
        <p v-if="step === 'email'">Masukkan email — kode OTP akan dikirim ke <strong>nomor WhatsApp</strong> yang terdaftar pada email tersebut</p>
        <p v-else-if="step === 'otp'">Masukkan <strong>kode OTP 6 digit</strong> yang dikirim ke WhatsApp</p>
        <p v-else-if="step === 'password'">Buat <strong>password baru</strong> untuk akun kamu</p>
        <p v-else>Password berhasil diperbarui</p>
      </div>

      <!-- STEP INDICATOR -->
      <div class="steps">
        <div class="step" :class="{ active: step === 'email', done: step !== 'email' }">
          <span class="step-num">1</span><span>Email</span>
        </div>
        <div class="step-line" :class="{ done: step !== 'email' }"></div>
        <div class="step" :class="{ active: step === 'otp', done: step === 'password' || step === 'done' }">
          <span class="step-num">2</span><span>Kode OTP</span>
        </div>
        <div class="step-line" :class="{ done: step === 'password' || step === 'done' }"></div>
        <div class="step" :class="{ active: step === 'password', done: step === 'done' }">
          <span class="step-num">3</span><span>Password Baru</span>
        </div>
        <div class="step-line" :class="{ done: step === 'done' }"></div>
        <div class="step" :class="{ active: step === 'done' }">
          <span class="step-num">✓</span><span>Selesai</span>
        </div>
      </div>

      <!-- STEP 1: KIRIM OTP -->
      <form v-if="step === 'email'" @submit.prevent="handleSendOtp" class="login-form">
        <div class="form-group">
          <label>Email terdaftar</label>
          <input
            v-model="email"
            type="email"
            placeholder="admin@gmail.com"
            required
            class="input-control"
            :disabled="loading"
          />
        </div>

        <div v-if="errorMessage" class="error-alert">{{ errorMessage }}</div>

        <button type="submit" class="btn-submit" :disabled="loading || cooldown > 0">
          {{ cooldown > 0 ? `Tunggu ${cooldown} detik...` : (loading ? 'Mengirim ke WhatsApp...' : 'Kirim OTP ke WhatsApp') }}
        </button>

        <div class="back-link">
          <NuxtLink to="/login">Kembali ke Login</NuxtLink>
        </div>

        <div class="help-box">
          <strong>Belum dapat OTP?</strong> Pastikan nomor HP (<code>no_tlp</code> di tabel <code>users</code>) sudah terisi untuk email tersebut. Kelola di <NuxtLink to="/super-admin/petugas">Kelola Petugas</NuxtLink>.
          Format: <code>08xx</code> / <code>628xx</code> / <code>+62 8xx</code>.
        </div>
      </form>

      <!-- STEP 2: INPUT OTP SAJA -->
      <form v-else-if="step === 'otp'" @submit.prevent="handleContinueToPassword" class="login-form">
        <div class="otp-info">
          <div class="otp-info-icon">📱</div>
          <div>
            <div class="otp-info-title">Kode OTP dikirim ke WhatsApp</div>
            <div class="otp-info-sub">
              Email: <strong>{{ email }}</strong>
              <span v-if="phoneMasked"> · WA: <strong>{{ phoneMasked }}</strong></span>
              <button type="button" class="link-inline" @click="backToEmail">ganti email</button>
            </div>
          </div>
        </div>

        <div v-if="devOtp" class="dev-otp-box">
          <div class="dev-label">DEV OTP (karena FONNTE belum aktif / untuk testing):</div>
          <div class="dev-code">{{ devOtp }}</div>
          <button type="button" class="btn-use-otp" @click="token = devOtp">Pakai OTP ini</button>
        </div>

        <!-- Countdown expiry -->
        <div class="expiry-box" :class="{ expired: isOtpExpired }">
          <span v-if="!isOtpExpired">⏳ Kode berlaku selama <strong>{{ expiryDisplay }}</strong></span>
          <span v-else>⚠️ Kode OTP sudah <strong>kadaluarsa</strong>. Silakan kirim ulang.</span>
        </div>

        <div class="form-group">
          <label>Kode OTP dari WhatsApp *</label>
          <input
            v-model="token"
            type="text"
            inputmode="numeric"
            maxlength="6"
            placeholder="contoh: 482913"
            required
            class="input-control otp-input"
            :disabled="loading || isOtpExpired"
          />
          <span class="field-hint">6 digit angka yang dikirim ke WA</span>
        </div>

        <div v-if="errorMessage" class="error-alert">{{ errorMessage }}</div>

        <button type="submit" class="btn-submit" :disabled="loading || isOtpExpired || token.trim().length < 6">
          Lanjutkan ke Ganti Password
        </button>

        <div class="resend-row">
          <span>Tidak dapat OTP / kode kadaluarsa?</span>
          <button type="button" class="link-inline" :disabled="loading || cooldown > 0" @click="handleSendOtp">
            {{ cooldown > 0 ? `Kirim ulang (${cooldown}s)` : 'Kirim ulang OTP' }}
          </button>
        </div>

        <div class="back-link">
          <NuxtLink to="/login">Kembali ke Login</NuxtLink>
        </div>
      </form>

      <!-- STEP 3: GANTI PASSWORD -->
      <form v-else-if="step === 'password'" @submit.prevent="handleResetPassword" class="login-form">
        <div class="otp-info">
          <div class="otp-info-icon">🔑</div>
          <div>
            <div class="otp-info-title">Kode OTP terverifikasi</div>
            <div class="otp-info-sub">
              Kode: <strong>{{ maskedToken }}</strong>
              <button type="button" class="link-inline" @click="backToOtp">ubah kode</button>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label>Password Baru *</label>
          <input
            v-model="password"
            type="password"
            placeholder="Minimal 6 karakter"
            required
            minlength="6"
            class="input-control"
            :disabled="loading"
          />
        </div>

        <div class="form-group">
          <label>Konfirmasi Password Baru *</label>
          <input
            v-model="password_confirmation"
            type="password"
            placeholder="Ulangi password baru"
            required
            minlength="6"
            class="input-control"
            :disabled="loading"
          />
        </div>

        <div v-if="errorMessage" class="error-alert">
          {{ errorMessage }}
          <div v-if="showBackToOtpHint" class="hint-inline">
            <button type="button" class="link-inline" @click="backToOtpAndResend">Kode kadaluarsa? Kirim ulang OTP</button>
          </div>
        </div>

        <button type="submit" class="btn-submit" :disabled="loading">
          {{ loading ? 'Memproses...' : 'Ubah Password' }}
        </button>

        <div class="back-link">
          <NuxtLink to="/login">Kembali ke Login</NuxtLink>
        </div>
      </form>

      <!-- STEP 4: SELESAI -->
      <div v-else class="login-form">
        <div class="success-alert" style="text-align:center;">
          <div style="font-size:28px; margin-bottom:8px;">🎉</div>
          <div><strong>Password berhasil diubah!</strong></div>
          <div style="margin-top:6px; color:#a7f3d0;">Silakan login dengan password baru kamu.</div>
        </div>
        <NuxtLink to="/login" class="btn-submit" style="display:block; text-align:center; text-decoration:none; line-height: 20px;">Kembali ke Login</NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onBeforeUnmount } from 'vue';

const { $api } = useNuxtApp();

const step = ref<'email' | 'otp' | 'password' | 'done'>('email');
const email = ref('');
const token = ref('');
const password = ref('');
const password_confirmation = ref('');

const loading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const phoneMasked = ref('');
const waSent = ref(false);
const devOtp = ref('');
const expiryMinutes = ref(5); // fallback default, disinkronkan dari response backend
const showBackToOtpHint = ref(false);

// ===== Cooldown resend OTP (detik) =====
const cooldown = ref(0);
let cooldownTimer: ReturnType<typeof setInterval> | null = null;

const startCooldown = (seconds: number) => {
  if (cooldownTimer) clearInterval(cooldownTimer);
  cooldown.value = Math.max(0, Math.floor(seconds));
  if (cooldown.value <= 0) return;

  cooldownTimer = setInterval(() => {
    cooldown.value -= 1;
    if (cooldown.value <= 0 && cooldownTimer) {
      clearInterval(cooldownTimer);
      cooldownTimer = null;
    }
  }, 1000);
};

// ===== Countdown expiry OTP (detik) =====
const expirySecondsLeft = ref(0);
let expiryTimer: ReturnType<typeof setInterval> | null = null;

const startExpiryCountdown = (totalSeconds: number) => {
  if (expiryTimer) clearInterval(expiryTimer);
  expirySecondsLeft.value = Math.max(0, Math.floor(totalSeconds));
  if (expirySecondsLeft.value <= 0) return;

  expiryTimer = setInterval(() => {
    expirySecondsLeft.value -= 1;
    if (expirySecondsLeft.value <= 0 && expiryTimer) {
      clearInterval(expiryTimer);
      expiryTimer = null;
    }
  }, 1000);
};

const isOtpExpired = computed(() => expirySecondsLeft.value <= 0);

const expiryDisplay = computed(() => {
  const m = Math.floor(expirySecondsLeft.value / 60);
  const s = expirySecondsLeft.value % 60;
  return `${m}:${s.toString().padStart(2, '0')}`;
});

const maskedToken = computed(() => {
  const t = token.value.trim();
  if (t.length <= 2) return t;
  return t.slice(0, 1) + '••••' + t.slice(-1);
});

onBeforeUnmount(() => {
  if (cooldownTimer) clearInterval(cooldownTimer);
  if (expiryTimer) clearInterval(expiryTimer);
});

// Prefill dari query jika dibuka via link (misal /forgot-password?email=...)
const route = useRoute();
onMounted(() => {
  if (route.query.email) email.value = String(route.query.email);
});

const handleSendOtp = async () => {
  if (!email.value) {
    errorMessage.value = 'Email wajib diisi.';
    return;
  }
  if (cooldown.value > 0) return; // guard tambahan

  loading.value = true;
  errorMessage.value = '';
  successMessage.value = '';
  showBackToOtpHint.value = false;

  try {
    const res: any = await $api.post('/forgot-password', { email: email.value });
    successMessage.value = res.data?.message || 'Kode OTP telah dikirim ke WhatsApp.';
    phoneMasked.value = res.data?.phone_masked || '';
    waSent.value = !!res.data?.wa_sent;
    devOtp.value = res.data?.dev_otp || '';
    if (res.data?.expires_in_minutes) expiryMinutes.value = res.data.expires_in_minutes;

    // mulai cooldown resend + countdown expiry
    startCooldown(res.data?.resend_cooldown_seconds ?? 60);
    startExpiryCountdown(expiryMinutes.value * 60);

    // reset token lama kalau ini resend (kode baru berbeda dari yang lama)
    token.value = '';

    step.value = 'otp';
    errorMessage.value = '';
    window.scrollTo({ top: 0, behavior: 'smooth' });
  } catch (error: any) {
    console.error('Kirim OTP gagal:', error);
    const status = error?.response?.status;
    const data = error?.response?.data;

    if (status === 429) {
      errorMessage.value = data?.message || 'Terlalu cepat meminta OTP baru, coba lagi sebentar.';
      if (data?.retry_after_seconds) startCooldown(data.retry_after_seconds);
    } else {
      errorMessage.value = data?.message || 'Gagal mengirim OTP. Pastikan email benar dan nomor HP sudah terdaftar.';
    }
    if (data?.phone_masked) phoneMasked.value = data.phone_masked;
  } finally {
    loading.value = false;
  }
};

const handleContinueToPassword = () => {
  errorMessage.value = '';

  if (!token.value || token.value.trim().length < 6) {
    errorMessage.value = 'Kode OTP harus 6 digit.';
    return;
  }
  if (isOtpExpired.value) {
    errorMessage.value = 'Kode OTP sudah kadaluarsa. Silakan kirim ulang OTP.';
    return;
  }

  step.value = 'password';
  errorMessage.value = '';
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const handleResetPassword = async () => {
  errorMessage.value = '';
  successMessage.value = '';
  showBackToOtpHint.value = false;

  if (!password.value || password.value.length < 6) {
    errorMessage.value = 'Password baru minimal 6 karakter.';
    return;
  }
  if (password.value !== password_confirmation.value) {
    errorMessage.value = 'Konfirmasi password tidak cocok.';
    return;
  }

  loading.value = true;
  try {
    const res: any = await $api.post('/reset-password', {
      email: email.value,
      token: token.value.trim(),
      password: password.value,
      password_confirmation: password_confirmation.value,
    });
    successMessage.value = res.data?.message || 'Password berhasil direset.';
    step.value = 'done';
    errorMessage.value = '';
  } catch (error: any) {
    console.error('Reset password gagal:', error);
    const data = error?.response?.data;

    if (data?.errors) {
      const firstKey = Object.keys(data.errors)[0];
      errorMessage.value = data.errors[firstKey]?.[0] || data.message || 'Gagal mereset password.';
    } else {
      errorMessage.value = data?.message || 'Token/OTP tidak valid atau sudah kadaluarsa.';
    }

    // Kalau pesan menyinggung token/OTP tidak valid/kadaluarsa, kasih shortcut balik ke step OTP
    if (/token|otp|kadaluarsa/i.test(errorMessage.value)) {
      showBackToOtpHint.value = true;
    }
  } finally {
    loading.value = false;
  }
};

const backToOtp = () => {
  step.value = 'otp';
  errorMessage.value = '';
  successMessage.value = '';
  showBackToOtpHint.value = false;
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const backToOtpAndResend = () => {
  backToOtp();
  handleSendOtp();
};

const backToEmail = () => {
  step.value = 'email';
  errorMessage.value = '';
  successMessage.value = '';
  showBackToOtpHint.value = false;
  token.value = '';
  password.value = '';
  password_confirmation.value = '';

  if (cooldownTimer) { clearInterval(cooldownTimer); cooldownTimer = null; }
  if (expiryTimer) { clearInterval(expiryTimer); expiryTimer = null; }
  cooldown.value = 0;
  expirySecondsLeft.value = 0;
};
</script>

<style scoped>
.login-layout {
  display: flex; align-items: center; justify-content: center; min-height: 100vh;
  background: radial-gradient(circle at 20% -10%, #3a4b7c 0%, #1e294b 45%, #111827 100%);
  font-family: 'Inter', sans-serif; color: #f3f4f6; padding: 20px;
}
.login-card {
  background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 32px 28px;
  width: 100%; max-width: 440px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
}
.login-header { text-align: center; margin-bottom: 18px; }
.logo-badge {
  width: 48px; height: 48px; background: #38bdf8; color: #0f172a; font-weight: 800; font-size: 20px;
  display: flex; align-items: center; justify-content: center; border-radius: 12px; margin: 0 auto 12px auto;
  box-shadow: 0 8px 16px rgba(56, 189, 248, 0.3);
}
.login-header h2 { font-size: 20px; font-weight: 700; color: #fff; margin-bottom: 6px; }
.login-header p { font-size: 12.5px; color: #94a3b8; line-height: 1.5; }
.login-header p strong { color: #38bdf8; }

/* Steps */
.steps { display: flex; align-items: center; justify-content: center; gap: 6px; margin-bottom: 20px; flex-wrap: wrap; }
.step { display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; color: #64748b; }
.step-num { width: 22px; height: 22px; border-radius: 50%; border: 1.5px solid #334155; display: flex; align-items: center; justify-content: center; font-size: 11px; background: #0f172a; }
.step.active { color: #38bdf8; }
.step.active .step-num { border-color: #38bdf8; background: rgba(56,189,248,0.15); color: #38bdf8; }
.step.done { color: #34d399; }
.step.done .step-num { border-color: #34d399; background: rgba(52,211,153,0.15); color: #34d399; }
.step-line { width: 16px; height: 2px; background: #334155; border-radius: 2px; }
.step-line.done { background: #34d399; }

.login-form { display: flex; flex-direction: column; gap: 14px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: 12.5px; font-weight: 600; color: #cbd5e1; }
.field-hint { font-size: 11px; color: #64748b; }
.input-control {
  background: #0f172a; border: 1px solid #334155; border-radius: 10px; padding: 12px 14px;
  color: #fff; font-size: 14px; outline: none;
}
.input-control:focus { border-color: #38bdf8; box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15); }
.input-control:disabled { opacity: 0.55; cursor: not-allowed; }
.otp-input { letter-spacing: 0.18em; font-weight: 700; font-family: monospace; font-size: 16px; text-align: center; }

/* OTP info card */
.otp-info { display: flex; gap: 12px; background: rgba(56,189,248,0.08); border: 1px solid rgba(56,189,248,0.22); border-radius: 12px; padding: 12px 14px; }
.otp-info-icon { font-size: 22px; }
.otp-info-title { font-size: 13px; font-weight: 700; color: #38bdf8; }
.otp-info-sub { font-size: 12px; color: #94a3b8; margin-top: 2px; line-height: 1.4; }
.otp-info-sub strong { color: #e2e8f0; }

/* Expiry countdown box */
.expiry-box {
  background: rgba(52,211,153,0.1); border: 1px solid rgba(52,211,153,0.28);
  color: #34d399; font-size: 12.5px; font-weight: 600; padding: 10px 12px; border-radius: 10px; text-align: center;
}
.expiry-box strong { font-family: monospace; font-size: 13.5px; }
.expiry-box.expired {
  background: rgba(248,113,113,0.12); border-color: rgba(248,113,113,0.3); color: #f87171;
}

.success-alert {
  background: rgba(52, 211, 153, 0.13); border: 1px solid rgba(52, 211, 153, 0.32);
  color: #34d399; padding: 12px 14px; border-radius: 10px; font-size: 12.5px; line-height: 1.5;
}
.phone-masked { margin-top: 8px; font-size: 12px; color: #a7f3d0; }
.dev-otp-box { margin-top: 4px; background: #0f172a; border: 1px dashed #f59e0b; border-radius: 10px; padding: 10px 12px; }
.dev-label { font-size: 11px; color: #fbbf24; font-weight: 700; }
.dev-code { font-size: 20px; font-weight: 800; color: #fff; letter-spacing: 0.15em; margin: 6px 0; font-family: monospace; }
.btn-use-otp { background: #1e293b; border: 1px solid #334155; color: #38bdf8; padding: 6px 10px; border-radius: 8px; font-size: 11px; font-weight: 600; cursor: pointer; }
.alert-icon { font-size: 14px; }
.error-alert {
  background: rgba(248, 113, 113, 0.13); border: 1px solid rgba(248, 113, 113, 0.3);
  color: #f87171; padding: 10px 14px; border-radius: 10px; font-size: 12.5px; text-align: center;
}
.hint-inline { margin-top: 6px; }
.hint-inline .link-inline { color: #fca5a5; }
.btn-submit {
  background: #38bdf8; color: #0f172a; border: none; border-radius: 10px; padding: 12px;
  font-size: 14px; font-weight: 700; cursor: pointer; transition: background 0.2s ease; margin-top: 2px;
}
.btn-submit:hover { background: #0ea5e9; }
.btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }
.back-link { text-align: center; font-size: 12.5px; margin-top: 2px; color: #64748b; }
.back-link a { color: #94a3b8; text-decoration: none; }
.back-link a:hover { color: #e2e8f0; text-decoration: underline; }
.sep { margin: 0 6px; }
.link-inline { background: none; border: none; color: #38bdf8; font-size: 12.5px; font-weight: 600; cursor: pointer; padding: 0; text-decoration: underline; }
.link-inline:disabled { opacity: 0.5; cursor: not-allowed; text-decoration: none; }
.resend-row { display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 12.5px; color: #94a3b8; flex-wrap: wrap; text-align: center; }
.help-box { font-size: 11.5px; color: #64748b; background: rgba(15,23,42,0.5); border: 1px solid #334155; border-radius: 10px; padding: 10px 12px; line-height: 1.5; }
.help-box strong { color: #94a3b8; }
.help-box code { background: #0f172a; padding: 1px 5px; border-radius: 4px; font-size: 11px; color: #38bdf8; }
.help-box a { color: #38bdf8; text-decoration: none; }
.help-box a:hover { text-decoration: underline; }
</style>