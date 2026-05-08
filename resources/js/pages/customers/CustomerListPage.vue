<script setup lang="ts">
import { ref, computed } from 'vue'

const customers = ref([
  { id: 1, name: '田中 太郎', phone: '090-1234-5678', visitCount: 12, lastVisit: '2026-05-05', totalSpend: 216000, note: '' },
  { id: 2, name: '鈴木 一郎', phone: '090-2345-6789', visitCount: 5, lastVisit: '2026-05-03', totalSpend: 90000, note: 'VIP' },
  { id: 3, name: '高橋 健', phone: '090-3456-7890', visitCount: 2, lastVisit: '2026-04-28', totalSpend: 36000, note: '' },
  { id: 4, name: '伊藤 誠', phone: '090-4567-8901', visitCount: 8, lastVisit: '2026-05-01', totalSpend: 144000, note: '' },
  { id: 5, name: '渡辺 浩', phone: '090-5678-9012', visitCount: 20, lastVisit: '2026-05-04', totalSpend: 360000, note: 'VIP' },
])

const search = ref('')
const filtered = computed(() =>
  search.value
    ? customers.value.filter(c => c.name.includes(search.value) || c.phone.includes(search.value))
    : customers.value
)
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-bold text-gray-800">顧客管理</h2>
      <button class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        + 顧客追加
      </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-4">
      <input
        v-model="search"
        type="text"
        placeholder="名前・電話番号で検索..."
        class="w-full sm:w-72 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
      />
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-gray-500 border-b bg-gray-50">
            <th class="px-5 py-3">顧客名</th>
            <th class="px-5 py-3">電話番号</th>
            <th class="px-5 py-3">来店回数</th>
            <th class="px-5 py-3">最終来店</th>
            <th class="px-5 py-3">累計利用額</th>
            <th class="px-5 py-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in filtered" :key="c.id" class="border-b last:border-0 hover:bg-gray-50">
            <td class="px-5 py-3">
              <div class="flex items-center gap-2">
                <span class="font-medium text-gray-800">{{ c.name }}</span>
                <span v-if="c.note === 'VIP'" class="text-xs bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded font-medium">VIP</span>
              </div>
            </td>
            <td class="px-5 py-3 text-gray-500">{{ c.phone }}</td>
            <td class="px-5 py-3 text-gray-700">{{ c.visitCount }}回</td>
            <td class="px-5 py-3 text-gray-500">{{ c.lastVisit }}</td>
            <td class="px-5 py-3 font-medium text-gray-700">¥{{ c.totalSpend.toLocaleString() }}</td>
            <td class="px-5 py-3">
              <button class="text-blue-600 hover:underline text-xs">詳細</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
