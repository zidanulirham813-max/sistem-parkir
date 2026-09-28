import axios from 'axios'

export default defineNuxtPlugin(() => {
  const api = axios.create({
    baseURL: 'http://localhost:8000/api',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  })

  // Kirim token otomatis jika ada di localStorage (setelah login)
  api.interceptors.request.use((config) => {
    if (typeof window !== 'undefined') {
      const token = localStorage.getItem('token')
      if (token) {
        config.headers.Authorization = `Bearer ${token}`
      }
    }
    return config
  })

  // Jika backend balas 401/403 → paksa logout, tapi jangan loop di halaman public
  api.interceptors.response.use(
    (res) => res,
    (error) => {
      const status = error?.response?.status
      const path = typeof window !== 'undefined' ? window.location.pathname : ''
      const isPublic = ['/login', '/forgot-password', '/reset-password'].some(p => path === p || path.startsWith(p + '/')) || path.startsWith('/users')

      if ((status === 401 || status === 403) && !isPublic && typeof window !== 'undefined') {
        const msg: string = error?.response?.data?.message || ''
        // Hanya auto-redirect untuk case auth/role, bukan error bisnis biasa
        const isAuthError = /unauthorized|forbidden|tidak diizinkan|token/i.test(msg) || status === 401
        if (isAuthError) {
          localStorage.removeItem('token')
          localStorage.removeItem('user')
          localStorage.removeItem('role')
          // pakai hard redirect biar middleware auth.global ke-trigger
          window.location.href = '/login'
        }
      }
      return Promise.reject(error)
    }
  )

  return {
    provide: {
      api: api,
    },
  }
})
