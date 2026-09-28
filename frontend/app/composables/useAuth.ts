// composables/useAuth.ts — helper terpusat untuk cek role dari localStorage + /me
export const useAuth = () => {
  const getToken = (): string | null => {
    if (typeof window === 'undefined') return null
    return localStorage.getItem('token')
  }

  const getRole = (): string | null => {
    if (typeof window === 'undefined') return null
    // 1) coba dari user object (paling akurat, diisi saat login & fetch /me)
    try {
      const raw = localStorage.getItem('user')
      if (raw) {
        const u = JSON.parse(raw)
        if (u?.role) return String(u.role).toLowerCase()
      }
    } catch {}
    // 2) fallback ke key role terpisah
    const r = localStorage.getItem('role')
    return r ? String(r).toLowerCase() : null
  }

  const getUser = (): any | null => {
    if (typeof window === 'undefined') return null
    try {
      const raw = localStorage.getItem('user')
      return raw ? JSON.parse(raw) : null
    } catch { return null }
  }

  const isLoggedIn = (): boolean => !!getToken()

  const isSuperAdmin = (): boolean => {
    const r = getRole()
    return r === 'super_admin' || r === 'admin'
  }

  const isPetugas = (): boolean => getRole() === 'petugas'

  const logout = () => {
    if (typeof window === 'undefined') return
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    localStorage.removeItem('role')
  }

  return { getToken, getRole, getUser, isLoggedIn, isSuperAdmin, isPetugas, logout }
}
