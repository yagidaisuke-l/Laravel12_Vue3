<script setup lang="ts">
const stats = [
  { label: '本日の予約', value: 12, sub: '未対応 3件', color: 'bg-blue-500' },
  { label: '稼働中キャスト', value: 4, sub: '待機中 6名', color: 'bg-green-500' },
  { label: '稼働中ドライバー', value: 3, sub: '待機中 2名', color: 'bg-orange-500' },
  { label: '本日の売上', value: '¥128,000', sub: '前日比 +12%', color: 'bg-purple-500' },
]

const recentReservations = [
  { id: 1, time: '14:00', customer: '田中 太郎', cast: '葵', driver: '山田', area: '渋谷区', status: '稼働中' },
  { id: 2, time: '15:00', customer: '鈴木 一郎', cast: '蘭', driver: '佐藤', area: '新宿区', status: '出発待ち' },
  { id: 3, time: '16:00', customer: '高橋 健', cast: '桜', driver: '未割当', area: '品川区', status: '受付済' },
  { id: 4, time: '17:00', customer: '伊藤 誠', cast: '未割当', driver: '未割当', area: '渋谷区', status: '受付済' },
]

const statusColor: Record<string, string> = {
  '稼働中': 'bg-green-100 text-green-700',
  '出発待ち': 'bg-yellow-100 text-yellow-700',
  '受付済': 'bg-blue-100 text-blue-700',
  '完了': 'bg-gray-100 text-gray-500',
}
</script>

<template>
  <div class="space-y-6">
    <h2 class="text-lg font-bold text-gray-800">ダッシュボード</h2>

    <!-- 統計カード -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="s in stats" :key="s.label" class="bg-white rounded-xl shadow-sm p-5">
        <div class="flex items-center gap-3">
          <div :class="s.color" class="w-2 h-10 rounded-full shrink-0"></div>
          <div>
            <p class="text-xs text-gray-500">{{ s.label }}</p>
            <p class="text-2xl font-bold text-gray-800">{{ s.value }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ s.sub }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- 直近の予約 -->
    <div class="bg-white rounded-xl shadow-sm">
      <div class="flex items-center justify-between px-5 py-4 border-b">
        <h3 class="font-semibold text-gray-700">本日の予約</h3>
        <RouterLink to="/reservations" class="text-xs text-blue-600 hover:underline">すべて見る</RouterLink>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-xs text-gray-500 border-b">
              <th class="px-5 py-3">時間</th>
              <th class="px-5 py-3">顧客</th>
              <th class="px-5 py-3">キャスト</th>
              <th class="px-5 py-3">ドライバー</th>
              <th class="px-5 py-3">エリア</th>
              <th class="px-5 py-3">ステータス</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in recentReservations" :key="r.id" class="border-b last:border-0 hover:bg-gray-50">
              <td class="px-5 py-3 font-medium">{{ r.time }}</td>
              <td class="px-5 py-3">{{ r.customer }}</td>
              <td class="px-5 py-3" :class="r.cast === '未割当' ? 'text-red-400' : ''">{{ r.cast }}</td>
              <td class="px-5 py-3" :class="r.driver === '未割当' ? 'text-red-400' : ''">{{ r.driver }}</td>
              <td class="px-5 py-3 text-gray-500">{{ r.area }}</td>
              <td class="px-5 py-3">
                <span :class="statusColor[r.status]" class="px-2 py-0.5 rounded-full text-xs font-medium">
                  {{ r.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
