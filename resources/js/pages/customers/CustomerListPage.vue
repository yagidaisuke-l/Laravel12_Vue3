<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/api'

interface Customer {
  id: number
  name: string | null
  phone: string
  status: 'temp' | 'full'
  memo: string | null
  last_visit: string | null
  visit_count: number
}

const customers  = ref<Customer[]>([])
const search     = ref('')
const loading    = ref(true)
const searchTimer = ref<ReturnType<typeof setTimeout> | null>(null)

async function fetchCustomers() {
  loading.value = true
  const params: Record<string, string> = {}
  if (search.value) {
    if (/\d/.test(search.value)) params.phone = search.value
    else params.name = search.value
  }
  const res = await api.get('/customers', { params })
  customers.value = res.data.data ?? res.data
  loading.value = false
}

function onSearch() {
  if (searchTimer.value) clearTimeout(searchTimer.value)
  searchTimer.value = setTimeout(fetchCustomers, 400)
}

onMounted(fetchCustomers)
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-bold text-gray-800">顧客管理</h2>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-4">
      <input
        v-model="search"
        @input="onSearch"
        type="text"
        placeholder="名前・電話番号で検索..."
        class="w-full sm:w-72 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
      />
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-x-auto">
      <div v-if="loading" class="px-5 py-10 text-center text-gray-400 text-sm">読み込み中...</div>
      <table v-else class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-gray-500 border-b bg-gray-50">
            <th class="px-5 py-3">顧客名</th>
            <th class="px-5 py-3">電話番号</th>
            <th class="px-5 py-3">来店回数</th>
            <th class="px-5 py-3">最終来店</th>
            <th class="px-5 py-3">種別</th>
            <th class="px-5 py-3">メモ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="customers.length === 0">
            <td colspan="6" class="px-5 py-8 text-center text-gray-400">顧客が見つかりません</td>
          </tr>
          <tr v-for="c in customers" :key="c.id" class="border-b last:border-0 hover:bg-gray-50">
            <td class="px-5 py-3">
              <span class="font-medium text-gray-800">{{ c.name ?? '（名前未登録）' }}</span>
            </td>
            <td class="px-5 py-3 text-gray-500">{{ c.phone }}</td>
            <td class="px-5 py-3 text-gray-700">{{ c.visit_count ?? 0 }}回</td>
            <td class="px-5 py-3 text-gray-500">{{ c.last_visit ?? '-' }}</td>
            <td class="px-5 py-3">
              <span :class="c.status === 'full' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500'"
                class="text-xs px-1.5 py-0.5 rounded font-medium">
                {{ c.status === 'full' ? '本登録' : '仮登録' }}
              </span>
            </td>
            <td class="px-5 py-3 text-gray-400 text-xs truncate max-w-40">{{ c.memo ?? '-' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
