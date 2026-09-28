<template>
  <aside class="dc-sidebar">
    <!-- Logo & Brand -->
    <div class="dc-brand">
      <div class="dc-logo-box">P</div>
      <div class="dc-brand-text">
        <h2>PARKIR</h2>
        <span>SYSTEM</span>
      </div>
    </div>

    <!-- Profil Petugas (di atas menu) -->
    <div class="dc-profile-card" v-if="user">
      <img v-if="user.foto_profil_url" :src="user.foto_profil_url" class="dc-profile-avatar" alt="foto profil" />
      <div v-else class="dc-profile-avatar dc-profile-avatar-fallback">{{ (user.name || '?').charAt(0).toUpperCase() }}</div>
      <div class="dc-profile-info">
        <strong class="dc-profile-name">{{ user.name }}</strong>
        <span class="dc-profile-email" :title="user.email">{{ user.email }}</span>
        <span class="dc-profile-role">{{ user.role === 'super_admin' ? 'Super Admin' : 'Petugas' }}</span>
      </div>
    </div>
    <div class="dc-profile-card dc-profile-card-empty" v-else>
      <div class="dc-profile-avatar dc-profile-avatar-fallback">?</div>
      <div class="dc-profile-info">
        <strong class="dc-profile-name">Tamu</strong>
        <span class="dc-profile-email">Belum login</span>
      </div>
    </div>

    <!-- Menu Section -->
    <div class="dc-menu-section">
      <span class="dc-menu-label">MENU PETUGAS</span>

      <nav class="dc-nav-list">
        <NuxtLink to="/" class="dc-nav-item" active-class="is-active" exact-active-class="is-active" @click="catatAktivitas('Beranda')">
          <span class="dc-icon">🏠</span>
          <span>Beranda</span>
        </NuxtLink>

        <NuxtLink to="/petugas/scan-keluar" class="dc-nav-item" active-class="is-active" @click="catatAktivitas('Scan Keluar')">
          <span class="dc-icon">📤</span>
          <span>Scan Keluar</span>
        </NuxtLink>

        <NuxtLink to="/petugas/tiket" class="dc-nav-item" active-class="is-active" @click="catatAktivitas('Tiket Parkir')">
          <span class="dc-icon">🎟️</span>
          <span>Tiket Parkir</span>
        </NuxtLink>

        <NuxtLink to="/petugas/member" class="dc-nav-item" active-class="is-active" @click="catatAktivitas('Member')">
          <span class="dc-icon">👥</span>
          <span>Member</span>
        </NuxtLink>
      </nav>
    </div>

    <!-- Footer: Edit Akun di atas Logout -->
    <div class="dc-sidebar-footer">
      <NuxtLink to="/petugas/profil" class="dc-nav-item dc-btn-edit-akun" active-class="is-active" @click="catatAktivitas('Edit Akun')">
        <span class="dc-icon">👤</span>
        <span>Edit Akun</span>
      </NuxtLink>
      <button @click="handleLogout" class="dc-nav-item dc-btn-logout">
        <span class="dc-icon">🚪</span>
        <span>Logout</span>
      </button>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';

const router = useRouter();
const { $api } = useNuxtApp();

interface SidebarUser {
  name: string;
  email: string;
  role: string;
  foto_profil_url: string | null;
  foto_profil?: string | null;
  no_tlp?: string | null;
}

const user = ref<SidebarUser | null>(null);

const loadUserFromStorage = () => {
  if (typeof window === 'undefined') return;
  try {
    const raw = localStorage.getItem('user');
    if (raw) {
      const parsed = JSON.parse(raw);
      user.value = {
        name: parsed.name || parsed.nama || 'Petugas',
        email: parsed.email || '',
        role: parsed.role || localStorage.getItem('role') || 'petugas',
        foto_profil_url: parsed.foto_profil_url || parsed.foto_profil || null,
        foto_profil: parsed.foto_profil || null,
      };
    } else {
      // fallback dari role/token saja
      const role = localStorage.getItem('role');
      if (role) {
        user.value = { name: role === 'super_admin' ? 'Super Admin' : 'Petugas', email: '', role, foto_profil_url: null };
      }
    }
  } catch {}
};

const fetchMe = async () => {
  if (typeof window === 'undefined') return;
  const token = localStorage.getItem('token');
  if (!token) return;
  try {
    const res: any = await $api.get('/me');
    const u = res.data?.data || res.data;
    if (u) {
      user.value = {
        name: u.name,
        email: u.email,
        role: u.role,
        foto_profil_url: u.foto_profil_url || null,
        foto_profil: u.foto_profil || null,
        no_tlp: u.no_tlp || null,
      };
      localStorage.setItem('user', JSON.stringify(u));
      if (u.role) localStorage.setItem('role', u.role);
    }
  } catch {
    // token mungkin expired — biarkan data dari storage
  }
};

const onStorage = (e: StorageEvent) => {
  if (e.key === 'user' || e.key === 'role' || e.key === 'token') loadUserFromStorage();
};

onMounted(() => {
  loadUserFromStorage();
  fetchMe();
  window.addEventListener('storage', onStorage);
});

onBeforeUnmount(() => {
  if (typeof window !== 'undefined') window.removeEventListener('storage', onStorage);
});

const catatAktivitas = (namaMenu: string) => {
  if (typeof window !== 'undefined') {
    const riwayatLama = JSON.parse(localStorage.getItem('riwayat_aktivitas') || '[]');
    const aktivitasBaru = {
      plat_nomor: `Akses: ${namaMenu}`,
      waktu: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
      status: 'Menu'
    };
    const updateRiwayat = [aktivitasBaru, ...riwayatLama].slice(0, 5);
    localStorage.setItem('riwayat_aktivitas', JSON.stringify(updateRiwayat));
  }
};

const handleLogout = () => {
  if (typeof window !== 'undefined') {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    localStorage.removeItem('role');
    router.push('/login');
  }
};
</script>

<style scoped>
.dc-sidebar {
  width: 260px;
  min-height: 100vh;
  background: #111827;
  border-right: 1px solid #1f2937;
  padding: 24px 16px;
  display: flex;
  flex-direction: column;
  box-sizing: border-box;
}

.dc-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 0 8px 24px 8px;
  border-bottom: 1px solid #1f2937;
  margin-bottom: 16px;
}

.dc-logo-box {
  width: 42px;
  height: 42px;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: #ffffff;
  font-weight: 700;
  font-size: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  box-shadow: 0 0 15px rgba(59, 130, 246, 0.4);
}

.dc-brand-text h2 {
  font-size: 16px;
  font-weight: 800;
  color: #ffffff;
  margin: 0;
  letter-spacing: 0.05em;
}

.dc-brand-text span {
  font-size: 10px;
  font-weight: 600;
  color: #9ca3af;
  letter-spacing: 0.1em;
}

/* Profil di atas menu */
.dc-profile-card {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 14px;
  padding: 12px 14px;
  margin-bottom: 18px;
}
.dc-profile-card-empty {
  opacity: 0.85;
}
.dc-profile-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #334155;
  flex-shrink: 0;
}
.dc-profile-avatar-fallback {
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: #fff;
  font-weight: 800;
  font-size: 16px;
  border: none;
}
.dc-profile-info {
  min-width: 0;
  flex: 1;
}
.dc-profile-name {
  display: block;
  font-size: 13px;
  font-weight: 700;
  color: #ffffff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.dc-profile-email {
  display: block;
  font-size: 11px;
  color: #94a3b8;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.dc-profile-role {
  display: inline-block;
  margin-top: 4px;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.04em;
  color: #38bdf8;
  background: rgba(56, 189, 248, 0.12);
  border: 1px solid rgba(56, 189, 248, 0.22);
  padding: 2px 7px;
  border-radius: 999px;
  text-transform: capitalize;
}

.dc-menu-section {
  display: flex;
  flex-direction: column;
  gap: 8px;
  flex: 1;
}

.dc-menu-label {
  font-size: 11px;
  font-weight: 700;
  color: #6b7280;
  letter-spacing: 0.08em;
  padding: 0 12px;
  margin-bottom: 4px;
}

.dc-nav-list {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.dc-nav-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px 14px;
  border-radius: 12px;
  color: #9ca3af;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.15s ease;
  background: transparent;
  border: none;
  width: 100%;
  cursor: pointer;
  text-align: left;
}

.dc-nav-item:hover {
  background: rgba(255, 255, 255, 0.03);
  color: #e5e7eb;
}

.dc-nav-item.is-active {
  background: #2563eb;
  color: #ffffff;
  font-weight: 600;
  box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
}

.dc-sidebar-footer {
  padding-top: 14px;
  border-top: 1px solid #1f2937;
  margin-top: auto;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.dc-btn-edit-akun {
  color: #cbd5e1;
  border: 1px solid #334155;
  background: rgba(30, 41, 59, 0.6);
}
.dc-btn-edit-akun:hover {
  background: #1e293b;
  color: #ffffff;
  border-color: #475569;
}
.dc-btn-edit-akun.is-active {
  background: #2563eb;
  border-color: #2563eb;
  color: #ffffff;
}

.dc-btn-logout {
  color: #f87171;
}

.dc-btn-logout:hover {
  background: rgba(248, 113, 113, 0.1);
  color: #fca5a5;
}

.dc-icon {
  font-size: 16px;
  width: 20px;
  text-align: center;
}
</style>