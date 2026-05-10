<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/api'
import { useDriversStore } from '@/stores/drivers'

const driversStore = useDriversStore()

const dispatches = ref<any[]>([])
const loading    = ref(true)
const today      = new Date().toISOString().slice(0, 10)

async function fetchAll() {
  loading.value = true
  const [dRes] = await Promise.all([
    api.get('/dispatches', { params: { date: today } }),
    driversStore.fetchAll(),
  ])
  dispatches.value = dRes.data
  loading.value = false
}

onMounted(fetchAll)

const statusColor: Record<string, string> = {
  '未配車': 'bg-red-100 text-red-600 border-red-200',
  '配車済': 'bg-yellow-100 text-yellow-700 border-yellow-200',
  '完了':   'bg-gray-100 text-gray-500 border-gray-200',
}

const driverStatusColor: Record<string, string> = {
  '待機中': 'bg-green-100 text-green-700',
  '稼働中': 'bg-gray-100 text-gray-500',
  '休み':   'bg-gray-100 text-gray-400',
}

// ─── 配車アサインモーダル ─────────────────────────────────
const showAssignModal   = ref(false)
const assigningDispatch = ref<any>(null)
const selectedDriverId  = ref<number | null>(null)
const assigning         = ref(false)

function openAssign(dispatch: any) {
  assigningDispatch.value = dispatch
  selectedDriverId.value  = dispatch.driver_id ?? null
  showAssignModal.value   = true
}

async function doAssign() {
  if (!assigningDispatch.value || !selectedDriverId.value) return
  assigning.value = true
  try {
    const res = await api.put(`/dispatches/${assigningDispatch.value.id}`, {
      driver_id: selectedDriverId.value,
      status:    '配車済',
    })
    const idx = dispatches.value.findIndex(d => d.id === assigningDispatch.value.id)
    if (idx !== -1) dispatches.value[idx] = res.data
    await driversStore.fetchAll()
    showAssignModal.value = false
  } finally {
    assigning.value = false
  }
}

async function complete(dispatch: any) {
  const res = await api.put(`/dispatches/${dispatch.id}`, { status: '完了' })
  const idx = dispatches.value.findIndex(d => d.id === dispatch.id)
  if (idx !== -1) dispatches.value[idx] = res.data
  await driversStore.fetchAll()
}

function scheduledTime(d: any): string {
  return d.scheduled_at ? d.scheduled_at.slice(11, 16) : '-'
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-bold text-gray-800">配車管理</h2>
      <span class="text-sm text-gray-500">{{ new Date().toLocaleDateString('ja-JP') }}</span>
    </div>

    <!-- ドライバー稼働状況 -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div v-for="d in driversStore.drivers" :key="d.id"
        class="bg-white rounded-xl shadow-sm p-4 text-center">
        <div class="w-10 h-10 bg-gray-200 rounded-full mx-auto mb-2 flex items-center justify-center text-sm font-bold text-gray-600">
          {{ d.name[0] }}
        </div>
        <p class="text-sm font-medium text-gray-700">{{ d.name }}</p>
        <span :class="driverStatusColor[d.status]"
          class="text-xs px-2 py-0.5 rounded-full mt-1 inline-block">
          {{ d.status }}
        </span>
        <p v-if="d.returnAt" class="text-xs text-gray-400 mt-0.5">〜{{ d.returnAt }}頃</p>
      </div>
    </div>

    <!-- 配車リスト -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div class="px-5 py-4 border-b">
        <h3 class="font-semibold text-gray-700">本日の配車一覧</h3>
      </div>

      <div v-if="loading" class="px-5 py-10 text-center text-gray-400 text-sm">読み込み中...</div>
      <div v-else-if="dispatches.length === 0" class="px-5 py-10 text-center text-gray-400 text-sm">本日の配車データはありません</div>
      <div v-else class="divide-y">
        <div v-for="d in dispatches" :key="d.id"
          :class="['px-5 py-4 flex items-center gap-4 hover:bg-gray-50', d.status === '配車済' ? 'border-l-4 border-yellow-400' : 'border-l-4 border-transparent']">
          <div class="text-center w-12 shrink-0">
            <p class="text-base font-bold text-gray-800">{{ scheduledTime(d) }}</p>
            <p class="text-xs text-gray-400">{{ d.type }}</p>
          </div>
          <div class="flex-1 grid grid-cols-2 sm:grid-cols-4 gap-2 text-sm">
            <div>
              <p class="text-xs text-gray-400">キャスト</p>
              <p class="font-medium text-gray-700">{{ d.reservation?.cast?.name ?? '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400">ドライバー</p>
              <p :class="d.driver ? 'font-medium text-gray-700' : 'text-red-400'">{{ d.driver?.name ?? '未割当' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400">エリア</p>
              <p class="text-gray-600">{{ d.reservation?.area ?? '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400">場所</p>
              <p class="text-gray-600 truncate">{{ d.reservation?.address ?? '-' }}</p>
            </div>
          </div>
          <div class="flex items-center gap-2 shrink-0">
            <span :class="statusColor[d.status]" class="px-2.5 py-1 rounded-full text-xs font-medium border">
              {{ d.status }}
            </span>
            <button v-if="d.status === '未配車'"
              @click="openAssign(d)"
              class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-2.5 py-1 rounded-lg transition-colors">
              配車する
            </button>
            <button v-if="d.status === '配車済'"
              @click="complete(d)"
              class="text-xs bg-gray-600 hover:bg-gray-700 text-white px-2.5 py-1 rounded-lg transition-colors">
              完了
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ドライバーアサインモーダル -->
  <Teleport to="body">
    <div v-if="showAssignModal" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="absolute inset-0 bg-black/40" @click="showAssignModal = false"></div>
      <div class="relative bg-white rounded-xl shadow-xl w-full max-w-sm mx-4 p-6 space-y-4">
        <h3 class="font-bold text-gray-800">ドライバーを配車する</h3>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-2">ドライバー選択</label>
          <div class="space-y-2">
            <label v-for="d in driversStore.drivers.filter(d => d.status === '待機中')" :key="d.id"
              class="flex items-center gap-3 px-3 py-2.5 rounded-lg border-2 cursor-pointer transition-all"
              :class="selectedDriverId === d.id ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:bg-gray-50'">
              <input type="radio" :value="d.id" v-model="selectedDriverId" class="hidden" />
              <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center text-sm font-bold text-gray-600 shrink-0">
                {{ d.name[0] }}
              </div>
              <div class="flex-1">
                <p class="text-sm font-semibold text-gray-800">{{ d.name }}</p>
                <p class="text-xs text-gray-400">{{ d.car }}</p>
              </div>
            </label>
            <p v-if="driversStore.drivers.filter(d => d.status === '待機中').length === 0"
              class="text-sm text-gray-400 text-center py-2">待機中のドライバーがいません</p>
          </div>
        </div>
        <div class="flex gap-3 pt-1">
          <button @click="showAssignModal = false"
            class="flex-1 border border-gray-300 text-sm py-2 rounded-lg hover:bg-gray-50">
            キャンセル
          </button>
          <button @click="doAssign" :disabled="!selectedDriverId || assigning"
            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 rounded-lg disabled:opacity-40">
            {{ assigning ? '配車中...' : '配車する' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
