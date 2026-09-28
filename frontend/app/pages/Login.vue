<template>
  <div class="login-layout">
    <div class="login-card">
      <div class="login-header">
        <div class="logo-badge">P</div>
        <h2>PARKIR SYSTEM</h2>
        <p>Silakan masuk menggunakan akun Anda</p>
      </div>

      <form @submit.prevent="handleLogin" class="login-form">
        <div class="form-group">
          <label>Username / Email</label>
          <input
            v-model="form.username"
            type="text"
            placeholder="Masukkan username"
            required
            class="input-control"
          />
        </div>

        <div class="form-group">
          <label>Password</label>
          <input
            v-model="form.password"
            type="password"
            placeholder="Masukkan password"
            required
            class="input-control"
          />
        </div>

        <div class="forgot-password-link">
          <NuxtLink to="/forgot-password">Lupa Password?</NuxtLink>
        </div>

        <div v-if="errorMessage" class="error-alert">
          {{ errorMessage }}
        </div>

        <button type="submit" class="btn-submit" :disabled="loading">
          {{ loading ? 'Memproses...' : 'Masuk' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue';

const { $api } = useNuxtApp();
const router = useRouter();

const form = reactive({
  username: '',
  password: ''
});

const loading = ref(false);
const errorMessage = ref('');

const handleLogin = async () => {
  loading.value = true;
  errorMessage.value = '';

  try {
    const res: any = await $api.post('/login', form);

    const token = res.data?.access_token || res.data?.token;
    const user = res.data?.user || null;
    const userRole = user?.role || res.data?.role || 'petugas';

    if (token) {
      localStorage.setItem('token', token);
      localStorage.setItem('role', userRole);
    }
    if (user) {
      // simpan untuk halaman profil petugas & header
      localStorage.setItem('user', JSON.stringify(user));
    }

    if (userRole === 'super_admin' || userRole === 'admin') {
      router.push('/super-admin/beranda');
    } else {
      router.push('/'); // Beranda petugas
    }
  } catch (error: any) {
    console.error('Login gagal:', error);
    errorMessage.value = error?.response?.data?.message || 'Username atau password salah.';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.login-layout {
  display: flex; align-items: center; justify-content: center; min-height: 100vh;
  background: radial-gradient(circle at 20% -10%, #3a4b7c 0%, #1e294b 45%, #111827 100%);
  font-family: 'Inter', sans-serif; color: #f3f4f6; padding: 20px;
}
.login-card {
  background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 36px 32px;
  width: 100%; max-width: 400px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
}
.login-header { text-align: center; margin-bottom: 28px; }
.logo-badge {
  width: 48px; height: 48px; background: #38bdf8; color: #0f172a; font-weight: 800; font-size: 20px;
  display: flex; align-items: center; justify-content: center; border-radius: 12px; margin: 0 auto 14px auto;
  box-shadow: 0 8px 16px rgba(56, 189, 248, 0.3);
}
.login-header h2 { font-size: 20px; font-weight: 700; color: #fff; margin-bottom: 6px; }
.login-header p { font-size: 13px; color: #94a3b8; }
.login-form { display: flex; flex-direction: column; gap: 18px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: 12.5px; font-weight: 600; color: #cbd5e1; }
.input-control {
  background: #0f172a; border: 1px solid #334155; border-radius: 10px; padding: 12px 14px;
  color: #fff; font-size: 14px; outline: none;
}
.input-control:focus { border-color: #38bdf8; box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15); }
.forgot-password-link {
  text-align: right;
  font-size: 12.5px;
}
.forgot-password-link a {
  color: #38bdf8;
  text-decoration: none;
  font-weight: 600;
}
.forgot-password-link a:hover {
  text-decoration: underline;
}
.error-alert {
  background: rgba(248, 113, 113, 0.15); border: 1px solid rgba(248, 113, 113, 0.3);
  color: #f87171; padding: 10px 14px; border-radius: 8px; font-size: 12.5px; text-align: center;
}
.btn-submit {
  background: #38bdf8; color: #0f172a; border: none; border-radius: 10px; padding: 12px;
  font-size: 14px; font-weight: 700; cursor: pointer; transition: background 0.2s ease; margin-top: 6px;
}
.btn-submit:hover { background: #0ea5e9; }
</style>
