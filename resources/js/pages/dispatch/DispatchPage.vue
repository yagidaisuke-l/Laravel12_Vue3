<script setup lang="ts">
import { ref } from 'vue'

const dispatches = ref([
  { id: 1, time: '14:00', cast: '葵', driver: '山田', area: '渋谷区', address: '○○ホテル 305号', status: '稼働中', departedAt: '13:45', arrivedAt: '14:05' },
  { id: 2, time: '15:00', cast: '蘭', driver: '佐藤', area: '新宿区', address: '△△マンション 802', status: '出発待ち', departedAt: null, arrivedAt: null },
  { id: 3, time: '16:00', cast: '桜', driver: null, area: '品川区', address: '□□ホテル 211', status: '未割当', departedAt: null, arrivedAt: null },
  { id: 4, time: '18:00', cast: '涼', driver: '中村', area: '港区', address: '◇◇ホテル 1002', status: '待機中', departedAt: null, arrivedAt: null },
])

const statusColor: Record<string, string> = {
  '稼働中': 'bg-green-100 text-green-700 border-green-200',
  '出発待ち': 'bg-yellow-100 text-yellow-700 border-yellow-200',
  '未割当': 'bg-red-100 text-red-600 border-red-200',
  '待機中': 'bg-blue-100 text-blue-700 border-blue-200',
  '完了': 'bg-gray-100 text-gray-500 border-gray-200',
}

function updateStatus(id: number, status: string) {
  const d = dispatches.value.find(d => d.id === id)
  if (d) d.status = status
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-bold text-gray-800">配車管理</h2>
      <span class="text-sm text-gray-500">{{ new Date().toLocaleDateString('ja-JP') }}</span>
    </div>

    <!-- ドライバー空き状況 -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div v-for="d in ['山田', '佐藤', '中村', '渡辺']" :key="d"
        class="bg-white rounded-xl shadow-sm p-4 text-center">
        <div class="w-10 h-10 bg-gray-200 rounded-full mx-auto mb-2 flex items-center justify-center text-sm font-bold text-gray-600">
          {{ d[0] }}
        </div>
        <p class="text-sm font-medium text-gray-700">{{ d }}</p>
        <span :class="d === '中村' ? 'bg-gray-100 text-gray-500' : 'bg-green-100 text-green-700'"
          class="text-xs px-2 py-0.5 rounded-full mt-1 inline-block">
          {{ d === '中村' ? '稼働中' : '待機中' }}
        </span>
      </div>
    </div>

    <!-- 配車リスト -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div class="px-5 py-4 border-b">
        <h3 class="font-semibold text-gray-700">本日の配車一覧</h3>
      </div>
      <div class="divide-y">
        <div v-for="d in dispatches" :key="d.id"
          :class="['px-5 py-4 flex items-center gap-4 hover:bg-gray-50', d.status === '稼働中' ? 'border-l-4 border-green-400' : 'border-l-4 border-transparent']">
          <div class="text-center w-12 shrink-0">
            <p class="text-base font-bold text-gray-800">{{ d.time }}</p>
          </div>
          <div class="flex-1 grid grid-cols-2 sm:grid-cols-4 gap-2 text-sm">
            <div>
              <p class="text-xs text-gray-400">キャスト</p>
              <p class="font-medium text-gray-700">{{ d.cast }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400">ドライバー</p>
              <p :class="d.driver ? 'font-medium text-gray-700' : 'text-red-400'">{{ d.driver ?? '未割当' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400">エリア</p>
              <p class="text-gray-600">{{ d.area }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400">場所</p>
              <p class="text-gray-600 truncate">{{ d.address }}</p>
            </div>
          </div>
          <div class="flex items-center gap-2 shrink-0">
            <span :class="statusColor[d.status]" class="px-2.5 py-1 rounded-full text-xs font-medium border">
              {{ d.status }}
            </span>
            <div class="flex gap-1">
              <button v-if="d.status === '出発待ち'"
                @click="updateStatus(d.id, '稼働中')"
                class="text-xs bg-green-600 hover:bg-green-700 text-white px-2.5 py-1 rounded-lg transition-colors">
                出発
              </button>
              <button v-if="d.status === '稼働中'"
                @click="updateStatus(d.id, '完了')"
                class="text-xs bg-gray-600 hover:bg-gray-700 text-white px-2.5 py-1 rounded-lg transition-colors">
                完了
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
