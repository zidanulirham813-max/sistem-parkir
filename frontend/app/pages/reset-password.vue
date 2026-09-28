<template>
  <div class="login-layout">
    <div class="login-card">
      <div class="login-header">
        <div class="logo-badge">P</div>
        <h2>Reset Password</h2>
        <p>Masukkan email, token, dan password baru</p>
      </div>

      <form @submit.prevent="handleReset" class="login-form">
        <div class="form-group">
          <label>Email</label>
          <input v-model="form.email" type="email" placeholder="Masukkan email" required class="input-control" />
        </div>
        <div class="form-group">
          <label>Token</label>
          <input v-model="form.token" type="text" placeholder="Token dari email / respon forgot-password" required class="input-control" />
        </div>
        <div class="form-group">
          <label>Password Baru</label>
          <input v-model="form.password" type="password" placeholder="Minimal 6 karakter" required class="input-control" />
        </div>
        <div class="form-group">
          <label>Konfirmasi Password</label>
          <input v-model="form.password_confirmation" type="password" placeholder="Ulangi password" required class="input-control" />
        </div>

        <div v-if="successMessage" class="success-alert">{{ successMessage }}</div>
        <div v-if="errorMessage" class="error-alert">{{ errorMessage }}</div>

        <button type="submit" class="btn-submit" :disabled="loading">
          {{ loading ? 'Memproses...' : 'Reset Password' }}
        </button>
        <div class="back-link">
          <NuxtLink to="/login">Kembali ke Login</NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue';
const { $api } = useNuxtApp();
const form = reactive({ email: '', token: '', password: '', password_confirmation: '' });
const loading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

// Auto-fill dari query ?token= & ?email= jika dibuka dari link email
const route = useRoute();
onMounted(() => {
  if (route.query.token) form.token = String(route.query.token);
  if (route.query.email) form.email = String(route.query.email);
});

const handleReset = async () => {
  loading.value = true;
  errorMessage.value = '';
  successMessage.value = '';
  try {
    const res: any = await $api.post('/reset-password', form);
    successMessage.value = res.data?.message || 'Password berhasil direset. Silakan login.';
  } catch (e: any) {
    errorMessage.value = e?.response?.data?.message || 'Gagal mereset password.';
  } finally { loading.value = false; }
};
</script>

<style scoped>
.login-layout { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: radial-gradient(circle at 20% -10%, #3a4b7c 0%, #1e294b 45%, #111827 100%); font-family: 'Inter', sans-serif; color: #f3f4f6; padding: 20px; }
.login-card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 36px 32px; width: 100%; max-width: 400px; box-shadow: 0 20px 40px rgba(0,0,0,0.4); }
.login-header { text-align: center; margin-bottom: 28px; }
.logo-badge { width: 48px; height: 48px; background: #38bdf8; color: #0f172a; font-weight: 800; font-size: 20px; display: flex; align-items: center; justify-content: center; border-radius: 12px; margin: 0 auto 14px auto; box-shadow: 0 8px 16px rgba(56,189,248,0.3); }
.login-header h2 { font-size: 20px; font-weight: 700; color: #fff; margin-bottom: 6px; }
.login-header p { font-size: 13px; color: #94a3b8; }
.login-form { display: flex; flex-direction: column; gap: 18px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: 12.5px; font-weight: 600; color: #cbd5e1; }
.input-control { background: #0f172a; border: 1px solid #334155; border-radius: 10px; padding: 12px 14px; color: #fff; font-size: 14px; outline: none; }
.input-control:focus { border-color: #38bdf8; box-shadow: 0 0 0 3px rgba(56,189,248,0.15); }
.success-alert { background: rgba(52,211,153,0.15); border: 1px solid rgba(52,211,153,0.3); color: #34d399; padding: 10px 14px; border-radius: 8px; font-size: 12.5px; text-align: center; }
.error-alert { background: rgba(248,113,113,0.15); border: 1px solid rgba(248,113,113,0.3); color: #f87171; padding: 10px 14px; border-radius: 8px; font-size: 12.5px; text-align: center; }
.btn-submit { background: #38bdf8; color: #0f172a; border: none; border-radius: 10px; padding: 12px; font-size: 14px; font-weight: 700; cursor: pointer; }
.btn-submit:hover { background: #0ea5e9; }
.back-link { text-align: center; font-size: 13px; margin-top: 10px; }
.back-link a { color: #94a3b8; text-decoration: none; }
.back-link a:hover { color: #e2e8f0; text-decoration: underline; }
</style>
