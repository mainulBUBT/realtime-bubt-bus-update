import { Capacitor } from '@capacitor/core'
import { StatusBar, Style } from '@capacitor/status-bar'

const THEME_META_SELECTOR = 'meta[name="theme-color"]'

function normalizeHexColor(color, fallback = '#059669') {
  if (typeof color !== 'string') return fallback

  const normalized = color.trim()

  if (/^#[0-9a-f]{6}$/i.test(normalized)) {
    return normalized
  }

  if (/^#[0-9a-f]{3}$/i.test(normalized)) {
    const [, r, g, b] = normalized
    return `#${r}${r}${g}${g}${b}${b}`
  }

  return fallback
}

function shouldUseLightStatusBarText(color) {
  const normalized = normalizeHexColor(color).slice(1)
  const red = parseInt(normalized.slice(0, 2), 16)
  const green = parseInt(normalized.slice(2, 4), 16)
  const blue = parseInt(normalized.slice(4, 6), 16)
  const luminance = (0.299 * red) + (0.587 * green) + (0.114 * blue)

  return luminance < 160
}

export function setThemeColorMeta(color) {
  if (typeof document === 'undefined') return

  const normalized = normalizeHexColor(color)
  let meta = document.querySelector(THEME_META_SELECTOR)

  if (!meta) {
    meta = document.createElement('meta')
    meta.setAttribute('name', 'theme-color')
    document.head.appendChild(meta)
  }

  meta.setAttribute('content', normalized)
}

export async function applySystemBarTheme(color) {
  const normalized = normalizeHexColor(color)

  setThemeColorMeta(normalized)

  if (!Capacitor.isNativePlatform()) return

  try {
    await StatusBar.setOverlaysWebView({ overlay: false })
    await StatusBar.setBackgroundColor({ color: normalized })
    await StatusBar.setStyle({
      style: shouldUseLightStatusBarText(normalized) ? Style.Light : Style.Dark
    })
  } catch (error) {
    if (import.meta.env.DEV) {
      console.warn('Failed to apply system bar theme:', error)
    }
  }
}
