import { ref } from 'vue'
import { useRouter } from 'vue-router'

const transitionName = ref('page-fade')

export function useNavigationDirection() {
  const router = useRouter()

  function updateTransition(to, from) {
    if (!from) {
      transitionName.value = 'page-fade'
      return
    }

    const toMeta = to.meta || {}
    const fromMeta = from.meta || {}
    const hasTabOrder = Number.isFinite(toMeta.tabIndex) && Number.isFinite(fromMeta.tabIndex)

    // Ordered tab ↔ tab: directional slide + fade
    if (toMeta.transition === 'tab' && fromMeta.transition === 'tab' && hasTabOrder) {
      if (toMeta.tabIndex === fromMeta.tabIndex) {
        transitionName.value = 'page-fade'
        return
      }

      transitionName.value = toMeta.tabIndex > fromMeta.tabIndex ? 'slide-forward' : 'slide-backward'
      return
    }

    // Tab ↔ Tab without ordering: cross-fade
    if (toMeta.transition === 'tab' && fromMeta.transition === 'tab') {
      transitionName.value = 'page-fade'
      return
    }

    // Push ↔ Push: slide based on depth
    if (toMeta.transition === 'push' && fromMeta.transition === 'push') {
      const toDepth = toMeta.depth || 0
      const fromDepth = fromMeta.depth || 0

      transitionName.value = toDepth >= fromDepth ? 'slide-forward' : 'slide-backward'
      return
    }

    // Tab → Push or Push → Tab: slide
    if (toMeta.transition === 'push') {
      transitionName.value = 'slide-forward'
      return
    }

    if (fromMeta.transition === 'push') {
      transitionName.value = 'slide-backward'
      return
    }

    transitionName.value = 'page-fade'
  }

  router.afterEach((to, from) => {
    updateTransition(to, from)
  })

  return { transitionName }
}
