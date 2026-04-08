export function showToast(message, options = {}) {
  if (!message) return

  const {
    type = 'warning',
    duration = 3000
  } = options

  window.dispatchEvent(new CustomEvent('app:toast', {
    detail: {
      message,
      type,
      duration
    }
  }))
}
