// middleware/auth.global.ts — cek role di sisi frontend (Nuxt global middleware)
// Petugas tidak boleh buka /super-admin/* → redirect ke /. Belum login → ke /login.
// Super admin bebas ke semua halaman (termasuk halaman petugas jika perlu, atau dibatasi ke super-admin saja).
// Halaman public: /login, /forgot-password, /reset-password, /users/**

export default defineNuxtRouteMiddleware((to) => {
  const publicPrefixes = ['/login', '/forgot-password', '/reset-password']
  const publicPath = (p: string) => publicPrefixes.some(x => p === x || p.startsWith(x + '/')) || p.startsWith('/users')

  const path = to.path

  // Halaman public → bebas
  if (publicPath(path)) return

  // SSR: belum ada localStorage → skip cek, biar hydration di client yang handle
  if (typeof window === 'undefined') return

  const token = localStorage.getItem('token')
  if (!token) {
    return navigateTo('/login')
  }

  // Ambil role se-akurat mungkin
  let role: string | null = null
  try {
    const raw = localStorage.getItem('user')
    if (raw) {
      const u = JSON.parse(raw)
      if (u?.role) role = String(u.role).toLowerCase()
    }
  } catch {}
  if (!role) {
    const r = localStorage.getItem('role')
    if (r) role = String(r).toLowerCase()
  }

  const isSuperAdmin = role === 'super_admin' || role === 'admin'
  const isPetugas = role === 'petugas'

  // Jika role tidak dikenali → paksa login ulang
  if (!role) {
    return navigateTo('/login')
  }

  // Petugas dilarang masuk area super-admin
  if (path.startsWith('/super-admin') && !isSuperAdmin) {
    // Ganti dengan halaman yang aman untuk petugas
    return navigateTo('/')
  }

  // Opsional: super admin tidak perlu dibatasi dari halaman petugas,
  // tapi jika mau ketat: uncomment blok di bawah agar super_admin hanya boleh di /super-admin/*
  // if (isSuperAdmin && (path === '/' || path.startsWith('/petugas'))) {
  //   return navigateTo('/super-admin/beranda')
  // }

  // Petugas sudah pasti boleh di / dan /petugas/*, tidak ada rule tambahan
  // unknown role handled above
  void (isPetugas || isSuperAdmin)
})
