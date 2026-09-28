<template>
  <aside class="dc-sidebar">
    <div class="dc-brand">
      <div class="dc-logo-box">P</div>
      <div class="dc-brand-text">
        <h2>PARKIR</h2>
        <span>SYSTEM</span>
      </div>
    </div>

    <div class="dc-profile-card" v-if="user">
      <img v-if="user.foto_profil_url" :src="user.foto_profil_url" class="dc-profile-avatar" alt="foto profil" />
      <div v-else class="dc-profile-avatar dc-profile-avatar-fallback">{{ (user.name || "?").charAt(0).toUpperCase() }}</div>
      <div class="dc-profile-info">
        <strong class="dc-profile-name">{{ user.name }}</strong>
        <span class="dc-profile-email" :title="user.email">{{ user.email }}</span>
        <span class="dc-profile-role">{{ user.role === "super_admin" ? "Super Admin" : "Petugas" }}</span>
      </div>
    </div>
    <div class="dc-profile-card dc-profile-card-empty" v-else>
      <div class="dc-profile-avatar dc-profile-avatar-fallback">?</div>
      <div class="dc-profile-info">
        <strong class="dc-profile-name">Tamu</strong>
        <span class="dc-profile-email">Belum login</span>
      </div>
    </div>

    <div class="dc-menu-section">
      <span class="dc-menu-label">MENU SUPER ADMIN</span>
      <nav class="dc-nav-list">
        <NuxtLink to="/super-admin/beranda" class="dc-nav-item" active-class="is-active">
          <span class="dc-icon">🏠</span>
          <span>Beranda</span>
        </NuxtLink>
        <NuxtLink to="/super-admin/laporan" class="dc-nav-item" active-class="is-active">
          <span class="dc-icon">📊</span>
          <span>Laporan</span>
        </NuxtLink>
        <NuxtLink to="/super-admin/transaksi" class="dc-nav-item" active-class="is-active">
          <span class="dc-icon">🧾</span>
          <span>Kelola Data</span>
        </NuxtLink>
        <NuxtLink to="/super-admin/petugas" class="dc-nav-item" active-class="is-active">
          <span class="dc-icon">👤</span>
          <span>Kelola Petugas</span>
        </NuxtLink>
      </nav>
    </div>

    <div class="dc-sidebar-footer">
      <NuxtLink to="/super-admin/profil" class="dc-nav-item dc-btn-edit-akun" active-class="is-active">
        <span class="dc-icon">👤</span>
        <span>Edit Akun</span>
      </NuxtLink>
      <button class="dc-nav-item dc-btn-logout" @click="handleLogout">
        <span class="dc-icon">🚪</span>
        <span>Logout</span>
      </button>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from "vue";
const router = useRouter();
const { $api } = useNuxtApp();
interface SidebarUser { name: string; email: string; role: string; foto_profil_url: string | null; foto_profil?: string | null; no_tlp?: string | null; }
const user = ref<SidebarUser | null>(null);
const loadUserFromStorage = () => {
  if (typeof window === "undefined") return;
  try {
    const raw = localStorage.getItem("user");
    if (raw) {
      const parsed = JSON.parse(raw);
      user.value = { name: parsed.name || parsed.nama || "Super Admin", email: parsed.email || "", role: parsed.role || localStorage.getItem("role") || "super_admin", foto_profil_url: parsed.foto_profil_url || parsed.foto_profil || null, foto_profil: parsed.foto_profil || null };
    } else { const role = localStorage.getItem("role"); if (role) user.value = { name: role === "super_admin" ? "Super Admin" : "Petugas", email: "", role, foto_profil_url: null }; }
  } catch {}
};
const fetchMe = async () => {
  if (typeof window === "undefined") return;
  const token = localStorage.getItem("token");
  if (!token) return;
  try { const res:any = await $api.get("/me"); const u = res.data?.data || res.data; if(u){ user.value = { name: u.name, email: u.email, role: u.role, foto_profil_url: u.foto_profil_url || null, foto_profil: u.foto_profil || null, no_tlp: u.no_tlp || null }; localStorage.setItem("user", JSON.stringify(u)); if(u.role) localStorage.setItem("role", u.role); } } catch {}
};
const onStorage = (e: StorageEvent) => { if (e.key === "user" || e.key === "role" || e.key === "token") loadUserFromStorage(); };
onMounted(() => { loadUserFromStorage(); fetchMe(); window.addEventListener("storage", onStorage); });
onBeforeUnmount(() => { if(typeof window !== "undefined") window.removeEventListener("storage", onStorage); });
const handleLogout = () => { if(typeof window !== "undefined"){ localStorage.removeItem("token"); localStorage.removeItem("user"); localStorage.removeItem("role"); router.push("/login"); } };
</script>

<style scoped>
.dc-sidebar{width:260px;min-height:100vh;background:#111827;border-right:1px solid #1f2937;padding:24px 16px;display:flex;flex-direction:column;box-sizing:border-box}
.dc-brand{display:flex;align-items:center;gap:12px;padding:0 8px 24px 8px;border-bottom:1px solid #1f2937;margin-bottom:16px}
.dc-logo-box{width:42px;height:42px;background:linear-gradient(135deg,#3b82f6,#1d4ed8);color:#fff;font-weight:700;font-size:20px;display:flex;align-items:center;justify-content:center;border-radius:12px;box-shadow:0 0 15px rgba(59,130,246,.4)}
.dc-brand-text h2{font-size:16px;font-weight:800;color:#fff;margin:0;letter-spacing:.05em}
.dc-brand-text span{font-size:10px;font-weight:600;color:#9ca3af;letter-spacing:.1em}
.dc-profile-card{display:flex;align-items:center;gap:12px;background:#1e293b;border:1px solid #334155;border-radius:14px;padding:12px 14px;margin-bottom:18px}
.dc-profile-card-empty{opacity:.85}
.dc-profile-avatar{width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid #334155;flex-shrink:0}
.dc-profile-avatar-fallback{display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#3b82f6,#1d4ed8);color:#fff;font-weight:800;font-size:16px;border:none}
.dc-profile-info{min-width:0;flex:1}
.dc-profile-name{display:block;font-size:13px;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.dc-profile-email{display:block;font-size:11px;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.dc-profile-role{display:inline-block;margin-top:4px;font-size:10px;font-weight:700;letter-spacing:.04em;color:#38bdf8;background:rgba(56,189,248,.12);border:1px solid rgba(56,189,248,.22);padding:2px 7px;border-radius:999px;text-transform:capitalize}
.dc-menu-section{display:flex;flex-direction:column;gap:8px;flex:1}
.dc-menu-label{font-size:11px;font-weight:700;color:#6b7280;letter-spacing:.08em;padding:0 12px;margin-bottom:4px}
.dc-nav-list{display:flex;flex-direction:column;gap:4px}
.dc-nav-item{display:flex;align-items:center;gap:14px;padding:12px 14px;border-radius:12px;color:#9ca3af;text-decoration:none;font-size:14px;font-weight:500;transition:all .15s;background:transparent;border:none;width:100%;cursor:pointer;text-align:left}
.dc-nav-item:hover{background:rgba(255,255,255,.03);color:#e5e7eb}
.dc-nav-item.is-active{background:#2563eb;color:#fff;font-weight:600;box-shadow:0 4px 14px rgba(37,99,235,.35)}
.dc-sidebar-footer{padding-top:14px;border-top:1px solid #1f2937;margin-top:auto;display:flex;flex-direction:column;gap:6px}
.dc-btn-edit-akun{color:#cbd5e1;border:1px solid #334155;background:rgba(30,41,59,.6)}
.dc-btn-edit-akun:hover{background:#1e293b;color:#fff;border-color:#475569}
.dc-btn-edit-akun.is-active{background:#2563eb;border-color:#2563eb;color:#fff}
.dc-btn-logout{color:#f87171}
.dc-btn-logout:hover{background:rgba(248,113,113,.1);color:#fca5a5}
.dc-icon{font-size:16px;width:20px;text-align:center}
</style>
