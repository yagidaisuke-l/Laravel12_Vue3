<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useCastsStore } from '@/stores/casts'
import { useOptionsStore } from '@/stores/options'
import { useRanksStore } from '@/stores/ranks'

const castsStore   = useCastsStore()
const optionsStore = useOptionsStore()
const ranksStore   = useRanksStore()

onMounted(() => {
  castsStore.fetchAll()
  optionsStore.fetchAll()
  ranksStore.fetchAll()
})

const statusColor: Record<string, string> = {
  '稼働中': 'bg-green-100 text-green-700',
  '待機中': 'bg-blue-100 text-blue-700',
  '休み':   'bg-gray-100 text-gray-500',
}

// ─── ランク順ソート（高ランク→低ランク、ランク未設定は末尾） ──
const sortedCasts = computed(() =>
  [...castsStore.casts].sort((a, b) => {
    const ra = ranksStore.getById(a.rankId)?.order ?? 0
    const rb = ranksStore.getById(b.rankId)?.order ?? 0
    return rb - ra
  })
)

// ─── NGオプション編集モーダル ─────────────────────────────
const showNgModal    = ref(false)
const editingCastId  = ref<number | null>(null)
const tempNgIds      = ref<number[]>([])

const editingCast = computed(() => castsStore.casts.find(c => c.id === editingCastId.value))

function openNgModal(castId: number) {
  editingCastId.value = castId
  tempNgIds.value = [...(castsStore.casts.find(c => c.id === castId)?.ngOptionIds ?? [])]
  showNgModal.value = true
}
function toggleNgOption(id: number) {
  const idx = tempNgIds.value.indexOf(id)
  if (idx === -1) tempNgIds.value.push(id)
  else tempNgIds.value.splice(idx, 1)
}
async function saveNgOptions() {
  if (editingCastId.value !== null) await castsStore.updateNgOptions(editingCastId.value, tempNgIds.value)
  showNgModal.value = false
}
function getOptionName(id: number) {
  return optionsStore.options.find(o => o.id === id)?.name ?? `#${id}`
}

// ─── ランク編集モーダル ────────────────────────────────────
const showRankModal  = ref(false)
const rankCastId     = ref<number | null>(null)
const tempRankId     = ref<number | null>(null)

const rankCast = computed(() => castsStore.casts.find(c => c.id === rankCastId.value))

function openRankModal(castId: number) {
  rankCastId.value = castId
  tempRankId.value = castsStore.casts.find(c => c.id === castId)?.rankId ?? null
  showRankModal.value = true
}
async function saveRank() {
  if (rankCastId.value !== null) await castsStore.updateRank(rankCastId.value, tempRankId.value)
  showRankModal.value = false
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-bold text-gray-800">キャスト管理</h2>
      <button class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        + キャスト追加
      </button>
    </div>

    <!-- ランク凡例 -->
    <div class="bg-white rounded-xl shadow-sm px-5 py-3 flex flex-wrap gap-3">
      <span class="text-xs font-medium text-gray-500 self-center">ランク：</span>
      <div v-for="r in ranksStore.sorted()" :key="r.id" class="flex items-center gap-1.5">
        <span :class="r.color" class="inline-block w-2.5 h-2.5 rounded-full"></span>
        <span class="text-xs text-gray-700 font-medium">{{ r.name }}</span>
        <span class="text-xs text-gray-400">
          {{ r.designationFee > 0 ? `+¥${r.designationFee.toLocaleString()}` : '無料' }}
        </span>
      </div>
    </div>

    <!-- キャストカード（ランク順） -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="c in sortedCasts" :key="c.id"
        class="bg-white rounded-xl shadow-sm p-5 space-y-3">

        <!-- ヘッダー -->
        <div class="flex items-center gap-3">
          <div class="relative shrink-0">
            <div class="w-12 h-12 bg-pink-100 rounded-full flex items-center justify-center text-lg font-bold text-pink-500">
              {{ c.name[0] }}
            </div>
            <!-- ランクバッジ -->
            <span v-if="ranksStore.getById(c.rankId)"
              :class="ranksStore.getById(c.rankId)!.color"
              class="absolute -bottom-1 -right-1 text-white text-xs font-bold px-1.5 py-0.5 rounded-full leading-none shadow">
              {{ ranksStore.getById(c.rankId)!.name }}
            </span>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-1">
              <p class="font-semibold text-gray-800">{{ c.name }}</p>
              <span :class="statusColor[c.status]" class="text-xs px-2 py-0.5 rounded-full font-medium shrink-0">
                {{ c.status }}
              </span>
            </div>
            <p class="text-xs text-gray-400 mt-0.5">{{ c.age }}歳 ／ 本日 {{ c.todayCount }}件</p>
            <!-- 指名料 -->
            <p class="text-xs mt-0.5">
              <span v-if="ranksStore.getById(c.rankId) && ranksStore.getById(c.rankId)!.designationFee > 0"
                class="text-amber-600 font-medium">
                指名料 +¥{{ ranksStore.getById(c.rankId)!.designationFee.toLocaleString() }}
              </span>
              <span v-else class="text-gray-400">指名料なし</span>
            </p>
          </div>
        </div>

        <!-- ランク設定 -->
        <div class="flex items-center justify-between border-t pt-3">
          <div class="flex items-center gap-2">
            <span v-if="ranksStore.getById(c.rankId)"
              :class="ranksStore.getById(c.rankId)!.color"
              class="w-3 h-3 rounded-full shrink-0"></span>
            <span class="text-sm text-gray-600">
              {{ ranksStore.getById(c.rankId)?.name ?? '未設定' }}
            </span>
          </div>
          <button @click="openRankModal(c.id)"
            class="text-xs text-blue-600 hover:underline">ランク変更</button>
        </div>

        <!-- NGオプション -->
        <div class="border-t pt-3">
          <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-medium text-gray-500">対応不可オプション</p>
            <button @click="openNgModal(c.id)" class="text-xs text-blue-600 hover:underline">設定</button>
          </div>
          <div class="flex flex-wrap gap-1 min-h-5">
            <span v-if="c.ngOptionIds.length === 0" class="text-xs text-gray-300">なし</span>
            <span v-for="id in c.ngOptionIds" :key="id"
              class="text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded font-medium">
              {{ getOptionName(id) }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ランク変更モーダル -->
  <Teleport to="body">
    <div v-if="showRankModal" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="absolute inset-0 bg-black/40" @click="showRankModal = false"></div>
      <div class="relative bg-white rounded-xl shadow-xl w-full max-w-xs mx-4 p-6 space-y-4">
        <h3 class="font-bold text-gray-800">{{ rankCast?.name }} のランク設定</h3>
        <div class="space-y-2">
          <label
            v-for="r in ranksStore.sorted()" :key="r.id"
            class="flex items-center gap-3 px-3 py-3 rounded-lg border-2 cursor-pointer transition-all"
            :class="tempRankId === r.id ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:bg-gray-50'"
          >
            <input type="radio" :value="r.id" v-model="tempRankId" class="hidden" />
            <span :class="r.color" class="w-3 h-3 rounded-full shrink-0"></span>
            <div class="flex-1">
              <p class="text-sm font-bold text-gray-800">{{ r.name }}</p>
              <p class="text-xs text-gray-400">
                指名料：{{ r.designationFee > 0 ? `+¥${r.designationFee.toLocaleString()}` : '無料' }}
              </p>
            </div>
            <div v-if="tempRankId === r.id"
              class="w-5 h-5 bg-blue-500 rounded-full flex items-center justify-center shrink-0">
              <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
              </svg>
            </div>
          </label>
          <label
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg border-2 cursor-pointer transition-all"
            :class="tempRankId === null ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:bg-gray-50'"
          >
            <input type="radio" :value="null" v-model="tempRankId" class="hidden" />
            <span class="w-3 h-3 rounded-full bg-gray-300 shrink-0"></span>
            <p class="text-sm text-gray-500 flex-1">ランクなし</p>
          </label>
        </div>
        <div class="flex gap-3 pt-1">
          <button @click="showRankModal = false"
            class="flex-1 border border-gray-300 text-sm py-2 rounded-lg hover:bg-gray-50 transition-colors">
            キャンセル
          </button>
          <button @click="saveRank"
            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 rounded-lg transition-colors">
            保存
          </button>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- NGオプション編集モーダル -->
  <Teleport to="body">
    <div v-if="showNgModal" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="absolute inset-0 bg-black/40" @click="showNgModal = false"></div>
      <div class="relative bg-white rounded-xl shadow-xl w-full max-w-sm mx-4 p-6 space-y-4">
        <h3 class="font-bold text-gray-800">{{ editingCast?.name }} の対応不可オプション</h3>
        <p class="text-xs text-gray-500">チェックしたオプションは受付時にグレーアウトされます</p>
        <div class="space-y-2 max-h-64 overflow-y-auto">
          <label v-for="o in optionsStore.options" :key="o.id"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg border cursor-pointer transition-colors"
            :class="tempNgIds.includes(o.id) ? 'border-red-300 bg-red-50' : 'border-gray-200 hover:bg-gray-50'"
          >
            <input type="checkbox" :checked="tempNgIds.includes(o.id)" @change="toggleNgOption(o.id)"
              class="accent-red-500 w-4 h-4" />
            <div class="flex-1">
              <p class="text-sm font-medium text-gray-800">{{ o.name }}</p>
              <p v-if="o.description" class="text-xs text-gray-400">{{ o.description }}</p>
            </div>
            <span class="text-xs text-gray-400">+¥{{ o.price.toLocaleString() }}</span>
          </label>
        </div>
        <div class="flex gap-3 pt-1">
          <button @click="showNgModal = false"
            class="flex-1 border border-gray-300 text-sm py-2 rounded-lg hover:bg-gray-50 transition-colors">
            キャンセル
          </button>
          <button @click="saveNgOptions"
            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 rounded-lg transition-colors">
            保存
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
