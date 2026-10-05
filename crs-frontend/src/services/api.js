import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api',
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('crs_token') 
    || localStorage.getItem('token') 
    || localStorage.getItem('auth_token')
    || localStorage.getItem('accessToken')
  if (token && token !== 'demo-token') {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
}, (error) => Promise.reject(error))

api.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status
    const isAuthEndpoint = error.config?.url?.includes('/auth/login') || error.config?.url?.includes('/auth/register')
    
    // Lỗi 401 (Unauthorized - Hết hạn token hoặc không hợp lệ)
    if (status === 401 && !isAuthEndpoint) {
      console.warn('⚠️ Token hết hạn hoặc không có quyền truy cập:', error.config?.url)
      localStorage.removeItem('crs_token')
      localStorage.removeItem('crs_user')
      localStorage.removeItem('crs_role')
      window.dispatchEvent(new CustomEvent('crs:unauthorized'))
    }
    // Lỗi 403 (Forbidden - Cấm truy cập / Chưa xác minh Email)
    if (status === 403) window.dispatchEvent(new CustomEvent('crs:forbidden'))
    // Lỗi 503 (Service Unavailable - Dịch vụ tạm thời offline)
    if (status === 503) window.dispatchEvent(new CustomEvent('crs:offline'))
    return Promise.reject(error)
  }
)

export default api
