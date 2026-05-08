<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const form = ref({
  date: '',
  time: '',
  duration: 60,
  customerId: '',
  castId: '',
  driverId: '',
  area: '',
  address: '',
  price: 0,
  note: '',
})

const casts = ['葵', '蘭', '桜', '涼', '優']
const drivers = ['山田', '佐藤', '中村', '渡辺']

function handleSubmit() {
  // TODO: API 連携
  router.push('/reservations')
}
</script>

<template>
  <div class="space-y-4 max-w-2xl">
    <div class="flex items-center gap-3">
      <button @click="router.back()" class="text-gray-400 hover:text-gray-600">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </button>
      <h2 class="text-lg font-bold text-gray-800">新規予約</h2>
    </div>

    <form @submit.prevent="handleSubmit" novalidate class="bg-white rounded-xl shadow-sm p-6 space-y-5">
      <!-- 日時 -->
      <div class="grid grid-cols-3 gap-4">
        <div class="col-span-1">
          <label class="block text-sm font-medium text-gray-700 mb-1">日付 <span class="text-red-500">*</span></label>
          <input v-model="form.date" type="date" required
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">時間 <span class="text-red-500">*</span></label>
          <input v-model="form.time" type="time" required
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">プレイ時間</label>
          <select v-model="form.duration"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option :value="60">60分</option>
            <option :value="90">90分</option>
            <option :value="120">120分</option>
            <option :value="150">150分</option>
            <option :value="180">180分</option>
          </select>
        </div>
      </div>

      <!-- 顧客 -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">顧客 <span class="text-red-500">*</span></label>
        <input v-model="form.customerId" type="text" placeholder="顧客名または会員番号で検索..." required
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </div>

      <!-- キャスト・ドライバー -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">キャスト</label>
          <select v-model="form.castId"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">未割当</option>
            <option v-for="c in casts" :key="c" :value="c">{{ c }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">ドライバー</label>
          <select v-model="form.driverId"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">未割当</option>
            <option v-for="d in drivers" :key="d" :value="d">{{ d }}</option>
          </select>
        </div>
      </div>

      <!-- エリア・住所 -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">エリア</label>
          <input v-model="form.area" type="text" placeholder="例: 渋谷区"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">住所・ホテル名</label>
          <input v-model="form.address" type="text" placeholder="例: ○○ホテル 503号室"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
      </div>

      <!-- 料金 -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">料金</label>
        <div class="relative">
          <span class="absolute left-3 top-2 text-sm text-gray-500">¥</span>
          <input v-model="form.price" type="number" min="0" step="1000"
            class="w-full border border-gray-300 rounded-lg pl-7 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
      </div>

      <!-- メモ -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">メモ</label>
        <textarea v-model="form.note" rows="3" placeholder="特記事項など..."
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
      </div>

      <!-- ボタン -->
      <div class="flex justify-end gap-3 pt-2">
        <button type="button" @click="router.back()"
          class="px-5 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
          キャンセル
        </button>
        <button type="submit"
          class="px-5 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
          予約を保存
        </button>
      </div>
    </form>
  </div>
</template>
