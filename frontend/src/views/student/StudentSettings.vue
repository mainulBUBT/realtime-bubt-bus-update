<script setup>
import { computed, ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api/client'
import { useAuthStore } from '@/stores/useAuthStore'

const router = useRouter()
const authStore = useAuthStore()

const loading = ref(true)
const name = ref('')
const phone = ref('')

const currentPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')

const savingProfile = ref(false)
const savingPassword = ref(false)
const profileMessage = ref('')
const passwordMessage = ref('')
const profileError = ref('')
const passwordError = ref('')

const isVerified = computed(() => !!authStore.user?.email_verified_at)
const userInitial = computed(() => authStore.user?.name?.charAt(0)?.toUpperCase() || 'S')

onMounted(async () => {
  try {
    await authStore.fetchMe()
  } catch {}
  name.value = authStore.user?.name || ''
  phone.value = authStore.user?.phone || ''
  loading.value = false
})

async function saveProfile() {
  profileMessage.value = ''
  profileError.value = ''
  savingProfile.value = true
  try {
    await api.patch('/auth/profile', { name: name.value, phone: phone.value })
    await authStore.fetchMe()
    profileMessage.value = 'Profile updated successfully.'
  } catch (err) {
    profileError.value = err?.response?.data?.message || err?.message || 'Failed to update profile'
  } finally {
    savingProfile.value = false
  }
}

async function changePassword() {
  passwordMessage.value = ''
  passwordError.value = ''
  savingPassword.value = true
  try {
    await api.patch('/auth/password', {
      current_password: currentPassword.value,
      password: newPassword.value,
      password_confirmation: confirmPassword.value
    })
    currentPassword.value = ''
    newPassword.value = ''
    confirmPassword.value = ''
    passwordMessage.value = 'Password updated successfully.'
  } catch (err) {
    passwordError.value = err?.response?.data?.message || err?.message || 'Failed to update password'
  } finally {
    savingPassword.value = false
  }
}
</script>

<template>
  <div class="sub-page">
    <div class="sub-page-header">
      <div class="header-left">
        <button class="back-btn" @click="router.back()">
          <i class="bi bi-arrow-left"></i>
        </button>
        <div>
          <h1 class="header-title">Settings</h1>
          <span class="header-subtitle">Manage your account</span>
        </div>
      </div>
    </div>

    <template v-if="loading">
      <!-- Skeleton -->
      <div class="content-card">
        <div class="skel-row">
          <div class="skel-circle"></div>
          <div class="skel-lines">
            <div class="skel-line" style="width:60%;height:16px"></div>
            <div class="skel-line" style="width:80%;height:12px;margin-top:8px"></div>
          </div>
        </div>
      </div>
      <div class="content-card">
        <div class="skel-line" style="width:40%;height:14px;margin-bottom:16px"></div>
        <div class="skel-line" style="width:100%;height:40px;margin-bottom:12px"></div>
        <div class="skel-line" style="width:100%;height:40px;margin-bottom:12px"></div>
        <div class="skel-line" style="width:100%;height:44px"></div>
      </div>
      <div class="content-card">
        <div class="skel-line" style="width:50%;height:14px;margin-bottom:16px"></div>
        <div class="skel-line" style="width:100%;height:40px;margin-bottom:12px"></div>
        <div class="skel-line" style="width:100%;height:40px;margin-bottom:12px"></div>
        <div class="skel-line" style="width:100%;height:40px;margin-bottom:12px"></div>
        <div class="skel-line" style="width:100%;height:44px"></div>
      </div>
    </template>

    <template v-else>
      <!-- Profile Card -->
      <div class="content-card">
        <div class="profile-row">
          <div class="profile-avatar">{{ userInitial }}</div>
          <div class="profile-info">
            <h3 class="profile-name">{{ authStore.user?.name || 'Student' }}</h3>
            <span class="profile-email">
              {{ authStore.user?.email || '—' }}
              <span v-if="isVerified" class="verified-badge"><i class="bi bi-patch-check-fill"></i></span>
            </span>
          </div>
        </div>
      </div>

      <!-- Edit Profile -->
      <div class="content-card">
        <h4 class="card-title"><i class="bi bi-person-fill"></i> Edit Profile</h4>

        <div class="field-group">
          <label class="field-label">Name</label>
          <input v-model="name" type="text" class="field-input" placeholder="Your name">
        </div>

        <div class="field-group">
          <label class="field-label">Phone</label>
          <input v-model="phone" type="text" class="field-input" placeholder="Phone number">
        </div>

        <div v-if="profileError" class="msg-error">{{ profileError }}</div>
        <div v-if="profileMessage" class="msg-success">{{ profileMessage }}</div>

        <button class="btn-primary" :disabled="savingProfile" @click="saveProfile">
          <span v-if="savingProfile" class="loading-spinner-sm"></span>
          {{ savingProfile ? 'Saving...' : 'Save Profile' }}
        </button>
      </div>

      <!-- Change Password -->
      <div class="content-card">
        <h4 class="card-title"><i class="bi bi-shield-lock-fill"></i> Change Password</h4>

        <div class="field-group">
          <label class="field-label">Current Password</label>
          <input v-model="currentPassword" type="password" class="field-input" placeholder="Current password">
        </div>

        <div class="field-group">
          <label class="field-label">New Password</label>
          <input v-model="newPassword" type="password" class="field-input" placeholder="New password">
        </div>

        <div class="field-group">
          <label class="field-label">Confirm Password</label>
          <input v-model="confirmPassword" type="password" class="field-input" placeholder="Confirm new password">
        </div>

        <div v-if="passwordError" class="msg-error">{{ passwordError }}</div>
        <div v-if="passwordMessage" class="msg-success">{{ passwordMessage }}</div>

        <button class="btn-secondary" :disabled="savingPassword" @click="changePassword">
          <span v-if="savingPassword" class="loading-spinner-sm"></span>
          {{ savingPassword ? 'Updating...' : 'Update Password' }}
        </button>
      </div>
    </template>
  </div>
</template>

<style scoped>
.sub-page { padding: 0 0 100px 0; }

.sub-page-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px 20px; background: white; border-bottom: 1px solid #f0f0f0;
  position: sticky; top: var(--mobile-header-height, 60px); z-index: 10;
}
.header-left { display: flex; align-items: center; gap: 12px; }

.back-btn {
  width: 36px; height: 36px; border-radius: 10px; border: none;
  background: #f3f4f6; display: flex; align-items: center; justify-content: center;
  font-size: 16px; color: #374151; cursor: pointer; transition: all 0.2s;
}
.back-btn:active { transform: scale(0.95); background: #e5e7eb; }

.header-title { font-size: 18px; font-weight: 700; color: #111827; margin: 0; line-height: 1.2; }
.header-subtitle { font-size: 12px; color: #9ca3af; }

.content-card { margin: 12px 16px; padding: 16px; background: white; border-radius: 14px; border: 1px solid #f0f0f0; }
.card-title { font-size: 14px; font-weight: 600; color: #374151; margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px; }
.card-title i { color: var(--primary); }

/* Skeleton */
.skel-row { display: flex; align-items: center; gap: 14px; }
.skel-circle { width: 48px; height: 48px; border-radius: 14px; flex-shrink: 0; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
.skel-lines { flex: 1; }
.skel-line { border-radius: 6px; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
@keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

.profile-row { display: flex; align-items: center; gap: 14px; }
.profile-avatar {
  width: 48px; height: 48px; border-radius: 14px;
  background: var(--primary); color: white; font-size: 20px; font-weight: 700;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.profile-info { flex: 1; min-width: 0; }
.profile-name { font-size: 16px; font-weight: 600; color: #111827; margin: 0; }
.profile-email { font-size: 13px; color: #6b7280; display: flex; align-items: center; gap: 4px; }
.verified-badge { color: var(--primary); font-size: 14px; }

.field-group { margin-bottom: 14px; }
.field-label { display: block; font-size: 12px; font-weight: 600; color: #6b7280; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
.field-input {
  width: 100%; padding: 10px 14px; border: 1.5px solid #e5e7eb;
  border-radius: 10px; font-size: 14px; color: #111827; background: #fafafa;
  outline: none; transition: all 0.2s; box-sizing: border-box;
}
.field-input:focus { border-color: var(--primary); background: white; box-shadow: 0 0 0 3px var(--primary-50); }

.msg-error { font-size: 13px; color: #DC2626; margin-bottom: 12px; padding: 8px 12px; background: #FEF2F2; border-radius: 8px; }
.msg-success { font-size: 13px; color: #16A34A; margin-bottom: 12px; padding: 8px 12px; background: #F0FDF4; border-radius: 8px; }

.btn-primary, .btn-secondary {
  width: 100%; padding: 12px; border: none; border-radius: 12px;
  font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s;
  display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-primary { background: var(--primary); color: white; }
.btn-primary:active { transform: scale(0.98); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-secondary { background: #f3f4f6; color: #374151; }
.btn-secondary:active { transform: scale(0.98); background: #e5e7eb; }
.btn-secondary:disabled { opacity: 0.6; cursor: not-allowed; }

.loading-spinner-sm {
  display: inline-block; width: 14px; height: 14px;
  border: 2px solid rgba(255,255,255,0.3); border-top-color: white;
  border-radius: 50%; animation: spin 0.6s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
