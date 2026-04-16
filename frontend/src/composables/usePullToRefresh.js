import { ref } from 'vue'

export function usePullToRefresh(refreshFn, options = {}) {
  const {
    threshold = 60,
    disabled = false
  } = options

  const isPulling = ref(false)
  const isRefreshing = ref(false)
  const pullDistance = ref(0)
  const canRelease = ref(false)

  let startY = 0
  let element = null
  let savedScrollTop = 0
  let scrollTimeout = null

  const onTouchStart = (e) => {
    if (disabled || isRefreshing.value) return
    if (e.touches.length !== 1) return
    
    const touch = e.touches[0]
    startY = touch.clientY
    savedScrollTop = 0
    pullDistance.value = 0
    isPulling.value = false
    canRelease.value = false
  }

  const onTouchMove = (e) => {
    if (disabled || isRefreshing.value) return
    if (e.touches.length !== 1) return
    
    const touch = e.touches[0]
    const diff = touch.clientY - startY
    
    if (diff > 0 && !isPulling.value) {
      if (savedScrollTop === 0) {
        e.preventDefault()
        
        pullDistance.value = diff
        isPulling.value = true
        
        if (pullDistance.value >= threshold) {
          canRelease.value = true
        } else {
          canRelease.value = false
        }
      }
    }
  }

  const onTouchEnd = async () => {
    if (!isPulling.value) return
    
    if (canRelease.value) {
      isRefreshing.value = true
      pullDistance.value = threshold
      
      try {
        await refreshFn()
      } catch (error) {
        console.error('Pull to refresh error:', error)
      } finally {
        isRefreshing.value = false
        pullDistance.value = 0
        isPulling.value = false
        canRelease.value = false
        
        if (scrollTimeout) clearTimeout(scrollTimeout)
        scrollTimeout = setTimeout(() => {
          if (element) element.scrollTop = 0
        }, 50)
      }
    } else {
      pullDistance.value = 0
      isPulling.value = false
      canRelease.value = false
    }
  }

  const onScroll = (e) => {
    if (!isPulling.value) {
      savedScrollTop = e.target.scrollTop
    }
  }

  const onMount = (el) => {
    element = el
    if (element) {
      element.addEventListener('touchstart', onTouchStart, { passive: true })
      element.addEventListener('touchmove', onTouchMove, { passive: false })
      element.addEventListener('touchend', onTouchEnd, { passive: true })
      element.addEventListener('touchcancel', onTouchEnd, { passive: true })
      element.addEventListener('scroll', onScroll, { passive: true })
    }
  }

  const onUnmount = () => {
    if (element) {
      element.removeEventListener('touchstart', onTouchStart)
      element.removeEventListener('touchmove', onTouchMove)
      element.removeEventListener('touchend', onTouchEnd)
      element.removeEventListener('touchcancel', onTouchEnd)
      element.removeEventListener('scroll', onScroll)
    }
    if (scrollTimeout) clearTimeout(scrollTimeout)
  }

  return {
    isPulling,
    isRefreshing,
    pullDistance,
    canRelease,
    onMount,
    onUnmount
  }
}