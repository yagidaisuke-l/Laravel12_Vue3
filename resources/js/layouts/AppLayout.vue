<script setup lang="ts">
import { ref } from 'vue'
import { RouterView, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/api'

const router = useRouter()
const auth = useAuthStore()
const sidebarOpen = ref(true)

// ─── お問い合わせモーダル ──────────────────────────────
const showContact  = ref(false)
const contactSent  = ref(false)
const contactSending = ref(false)
const contactError = ref('')
const contactForm  = ref({ name: '', email: '', category: 'システムの不具合', body: '' })

const categories = ['システムの不具合', '機能の要望', '操作方法について', 'その他']

function openContact() {
  contactForm.value = { name: '', email: '', category: 'システムの不具合', body: '' }
  contactSent.value  = false
  contactError.value = ''
  showContact.value  = true
}

async function sendContact() {
  contactSending.value = true
  contactError.value   = ''
  try {
    await api.post('/contact', contactForm.value)
    contactSent.value = true
  } catch (e: any) {
    contactError.value = e.response?.data?.message ?? '送信に失敗しました'
  } finally {
    contactSending.value = false
  }
}

const nav = [
  { name: 'ダッシュボード', to: '/', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
  { name: '受付', to: '/reception', icon: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z' },
  { name: '予約管理', to: '/reservations', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
  { name: '配車管理', to: '/dispatch', icon: 'M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z' },
  { name: 'キャスト管理', to: '/casts', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
  { name: 'シフト管理', to: '/shifts', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
  { name: 'ドライバー管理', to: '/drivers', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' },
  { name: '顧客管理', to: '/customers', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
  { name: 'オプション管理', to: '/options', icon: 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4' },
  { name: '合言葉管理', to: '/codewords', icon: 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z' },
  { name: 'ランク管理', to: '/ranks', icon: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z' },
]

function doLogout() {
  auth.logout()
  router.push('/login')
}
</script>

<template>
  <div class="flex h-screen bg-gray-100">
    <!-- サイドバー -->
    <aside
      :class="sidebarOpen ? 'w-56' : 'w-16'"
      class="flex flex-col bg-gray-900 text-white transition-all duration-200 shrink-0"
    >
      <div class="flex items-center justify-between h-14 px-4 border-b border-gray-700">
        <span v-if="sidebarOpen" class="text-sm font-bold truncate">予約・配車管理</span>
        <button @click="sidebarOpen = !sidebarOpen" class="text-gray-400 hover:text-white ml-auto">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
      </div>
      <nav class="flex-1 py-4 space-y-1 overflow-y-auto">
        <RouterLink
          v-for="item in nav"
          :key="item.to"
          :to="item.to"
          class="flex items-center px-4 py-2.5 text-sm text-gray-300 hover:bg-gray-700 hover:text-white transition-colors"
          activeClass="bg-gray-700 text-white"
          exactActiveClass="bg-blue-600 text-white"
        >
          <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
          </svg>
          <span v-if="sidebarOpen" class="ml-3 truncate">{{ item.name }}</span>
        </RouterLink>
      </nav>
      <div class="border-t border-gray-700 p-4 space-y-2">
        <button
          @click="openContact"
          class="flex items-center w-full text-sm text-gray-400 hover:text-white transition-colors"
        >
          <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
          <span v-if="sidebarOpen" class="ml-3">お問い合わせ</span>
        </button>
        <button
          @click="doLogout"
          class="flex items-center w-full text-sm text-gray-400 hover:text-white transition-colors"
        >
          <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          <span v-if="sidebarOpen" class="ml-3">ログアウト</span>
        </button>
      </div>
    </aside>

    <!-- メインコンテンツ -->
    <div class="flex flex-col flex-1 overflow-hidden">
      <header class="h-14 bg-white border-b flex items-center px-6 shrink-0">
        <h1 class="text-base font-semibold text-gray-700">
          <RouterView name="title" />
        </h1>
      </header>
      <main class="flex-1 overflow-y-auto p-6">
        <RouterView />
      </main>
    </div>
  </div>

  <!-- お問い合わせモーダル -->
  <Teleport to="body">
    <div
      v-if="showContact"
      class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
      <!-- backdrop -->
      <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showContact = false" />

      <!-- card -->
      <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden">
        <!-- header -->
        <div class="bg-gradient-to-r from-blue-800 to-blue-500 px-8 py-6 text-white">
          <h2 class="text-lg font-bold">お問い合わせ</h2>
          <p class="text-sm text-blue-100 mt-1">ご不明な点やご要望をお気軽にどうぞ</p>
        </div>

        <!-- 送信完了 -->
        <div v-if="contactSent" class="px-8 py-10 text-center">
          <svg class="w-14 h-14 mx-auto text-green-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p class="text-gray-700 font-semibold text-base">送信しました</p>
          <p class="text-sm text-gray-500 mt-1">内容を確認次第、ご連絡いたします。</p>
          <button
            @click="showContact = false"
            class="mt-6 px-6 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition-colors"
          >閉じる</button>
        </div>

        <!-- フォーム -->
        <form v-else @submit.prevent="sendContact" class="px-8 py-6 space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">お名前 <span class="text-red-500">*</span></label>
              <input
                v-model="contactForm.name"
                type="text"
                required
                placeholder="山田 太郎"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">メールアドレス <span class="text-red-500">*</span></label>
              <input
                v-model="contactForm.email"
                type="email"
                required
                placeholder="you@example.com"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">カテゴリ <span class="text-red-500">*</span></label>
            <select
              v-model="contactForm.category"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
            >
              <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">お問い合わせ内容 <span class="text-red-500">*</span></label>
            <textarea
              v-model="contactForm.body"
              required
              rows="5"
              placeholder="詳しい内容をご記入ください"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
            />
          </div>

          <p v-if="contactError" class="text-red-500 text-sm">{{ contactError }}</p>

          <div class="flex justify-end gap-3 pt-2">
            <button
              type="button"
              @click="showContact = false"
              class="px-5 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
            >キャンセル</button>
            <button
              type="submit"
              :disabled="contactSending"
              class="px-5 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
            >
              <svg v-if="contactSending" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
              </svg>
              {{ contactSending ? '送信中...' : '送信する' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>
