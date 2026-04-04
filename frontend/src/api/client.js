export const getApiBaseUrl = () => import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
export const getAuthToken = () => localStorage.getItem('auth_token')

export const buildApiHeaders = (token = getAuthToken()) => {
  const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'Cache-Control': 'no-cache, no-store, must-revalidate',
    'Pragma': 'no-cache',
    'Expires': '0'
  }

  if (token) {
    headers.Authorization = `Bearer ${token}`
  }

  return headers
}

export const resolveApiUrl = (path = '') => {
  const base = getApiBaseUrl().replace(/\/+$/, '')
  const suffix = path.startsWith('/') ? path : `/${path}`
  return `${base}${suffix}`
}

export const dispatchUnauthorized = () => {
  localStorage.removeItem('auth_token')
  localStorage.removeItem('user')
  window.dispatchEvent(new CustomEvent('auth:unauthorized'))
}

function createHttpError(status, data, response = null) {
  const error = new Error(data?.message || `HTTP ${status}`)
  error.response = {
    status,
    data
  }
  error.status = status
  error.data = data
  error.fetchResponse = response
  return error
}

function buildUrl(path = '', params = {}, { cacheBust = false } = {}) {
  const url = new URL(resolveApiUrl(path), window.location.origin)
  const searchParams = new URLSearchParams(url.search)

  Object.entries(params || {}).forEach(([key, value]) => {
    if (value == null) return

    if (Array.isArray(value)) {
      value.forEach((item) => {
        if (item != null) {
          searchParams.append(key, String(item))
        }
      })
      return
    }

    searchParams.set(key, String(value))
  })

  if (cacheBust) {
    searchParams.set('_t', String(Date.now()))
  }

  url.search = searchParams.toString()
  return url.toString()
}

async function parseResponseBody(response) {
  if (response.status === 204) {
    return null
  }

  const contentType = response.headers.get('content-type') || ''

  if (contentType.includes('application/json')) {
    return response.json()
  }

  const text = await response.text()

  if (!text) {
    return null
  }

  try {
    return JSON.parse(text)
  } catch {
    return text
  }
}

async function request(method, path, config = {}) {
  const token = getAuthToken()
  const headers = {
    ...buildApiHeaders(token),
    ...(config.headers || {})
  }

  const isGet = method === 'GET'
  const url = buildUrl(path, config.params, { cacheBust: isGet })

  const fetchOptions = {
    method,
    headers
  }

  if (config.signal) {
    fetchOptions.signal = config.signal
  }

  if (!isGet && config.data !== undefined) {
    fetchOptions.body = typeof config.data === 'string'
      ? config.data
      : JSON.stringify(config.data)
  }

  const response = await fetch(url, fetchOptions)
  const data = await parseResponseBody(response)

  if (!response.ok) {
    if (response.status === 401) {
      dispatchUnauthorized()
    }

    throw createHttpError(response.status, data, response)
  }

  return {
    data,
    status: response.status,
    headers: response.headers,
    ok: response.ok
  }
}

const api = {
  get(path, config = {}) {
    return request('GET', path, config)
  },

  delete(path, config = {}) {
    return request('DELETE', path, config)
  },

  post(path, data, config = {}) {
    return request('POST', path, { ...config, data })
  },

  put(path, data, config = {}) {
    return request('PUT', path, { ...config, data })
  },

  patch(path, data, config = {}) {
    return request('PATCH', path, { ...config, data })
  }
}

export default api
