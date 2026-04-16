import { ref, onMounted, onUnmounted } from 'vue'

export function usePullToRefresh(refreshFn, options = {}) {
  const {
    threshold = 60,
    resistance = 2.5,
    disabled = false,
    pullText = 'Pull to refresh',
    releasingText = 'Release to refresh',
    refreshingText = 'Refreshing...'
  } = options

  const isPulling = ref(false)
  const isRefreshing = ref(false)
  const pullDistance = ref(0)
  const pullTextDisplay = ref(pullText)

  let startY = 0
  let currentY = 0
  let element = null

  const onTouchStart = (e) => {
    if (disabled || isRefreshing.value) return
    if (e.touches.length !== 1) return
    
    const touch = e.touches[0]
    if (touch.clientY === 0) return
    
    startY = touch.clientY
    currentY = touch.clientY
    pullDistance.value = 0
    isPulling.value = false
  }

  const onTouchMove = (e) => {
    if (disabled || isRefreshing.value) return
    if (e.touches.length !== 1) return
    if (window.scrollY > 0) return
    
    const touch = e.touches[0]
    currentY = touch.clientY
    const diff = currentY - startY
    
    if (diff > 0) {
      e.preventDefault()
      
      const distance = diff / resistance
      pullDistance.value = Math.min(distance, threshold * 1.5)
      
      if (!isPulling.value && pullDistance.value > 10) {
        isPulling.value = true
        pullTextDisplay.value = releasingText
      } else if (isPulling.value && pullDistance.value < 10) {
        isPulling.value = false
        pullTextDisplay.value = pullText
      }
      
      if (isPulling.value && pullDistance.value < 10) {
        pullTextDisplay.value = pullText
      }
    }
  }

  const onTouchEnd = async () => {
    if (disabled || isRefreshing.value) return
    
    if (isPulling.value && pullDistance.value >= threshold) {
      isRefreshing.value = true
      pullTextDisplay.value = refreshingText
      
      try {
        await refreshFn()
      } catch (error) {
        console.error('Pull to refresh error:', error)
      } finally {
        isRefreshing.value = false
        pullDistance.value = 0
        isPulling.value = false
        pullTextDisplay.value = pullText
      }
    } else {
      pullDistance.value = 0
      isPulling.value = false
      pullTextDisplay.value = pullText
    }
  }

  const onMount = (el) => {
    element = el
    if (element) {
      element.addEventListener('touchstart', onTouchStart, { passive: true })
      element.addEventListener('touchmove', onTouchMove, { passive: false })
      element.addEventListener('touchend', onTouchEnd, { passive: true })
      element.addEventListener('touchcancel', onTouchEnd, { passive: true })
    }
  }

  const onUnmount = () => {
    if (element) {
      element.removeEventListener('touchstart', onTouchStart)
      element.removeEventListener('touchmove', onTouchMove)
      element.removeEventListener('touchend', onTouchEnd)
      element.removeEventListener('touchcancel', onTouchEnd)
    }
  }

  return {
    isPulling,
    isRefreshing,
    pullDistance,
    pullText: pullTextDisplay,
    onMount,
    onUnmount
  }
}