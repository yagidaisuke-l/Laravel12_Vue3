<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useOptionsStore } from '@/stores/options'
import { useCastsStore } from '@/stores/casts'
import { useCodewordsStore } from '@/stores/codewords'
import { useAreasStore } from '@/stores/areas'
import { useRanksStore } from '@/stores/ranks'
import { useShiftsStore } from '@/stores/shifts'
import { useDriversStore } from '@/stores/drivers'
import api from '@/api'

const optionsStore   = useOptionsStore()
const castsStore     = useCastsStore()
const codewordsStore = useCodewordsStore()
const areasStore     = useAreasStore()
const ranksStore     = useRanksStore()
const shiftsStore    = useShiftsStore()
const driversStore   = useDriversStore()

// ページ読み込み時にマスタデータを取得
onMounted(() => {
  optionsStore.fetchAll()
  castsStore.fetchAll()
  codewordsStore.fetchAll()
  areasStore.fetchAll()
  ranksStore.fetchAll()
})

// ─── 配車状況 ──────────────────────────────────────────────
const dispatchPanelOpen = ref(true)

// HH:MM の時刻文字列から現在時刻までの残り分数を返す
function minutesUntil(timeStr: string): number {
  if (!timeStr) return 0
  const now = new Date()
  const [h, m] = timeStr.split(':').map(Number)
  const target = new Date(now)
  target.setHours(h, m, 0, 0)
  if (target <= now) target.setDate(target.getDate() + 1)
  return Math.round((target.getTime() - now.getTime()) / 60000)
}

function returnLabel(returnAt: string): string {
  const min = minutesUntil(returnAt)
  if (min <= 5)  return 'もうすぐ戻り'
  if (min < 60)  return `約${min}分後に空き`
  const h = Math.floor(min / 60)
  const r = min % 60
  return r > 0 ? `約${h}時間${r}分後に空き` : `約${h}時間後に空き`
}

// 最短空き時間（待機中がいれば0、いなければ最小の残り分数）
const earliestAvailableMin = computed(() => {
  if (driversStore.standbyCount > 0) return 0
  const mins = driversStore.busyDrivers.map(d => minutesUntil(d.returnAt))
  return mins.length ? Math.min(...mins) : null
})

// ─── 型定義 ────────────────────────────────────────────
type CustomerStatus    = 'temp' | 'full'
type ReservationStatus = '仮予約' | '確定'
type Step = 'search' | 'customer' | 'casting' | 'options' | 'datetime' | 'confirm'

interface CastHistory { castName: string; count: number }
interface Customer {
  id: number; phone: string; name: string; status: CustomerStatus
  memo: string; ngCasts: string[]; visitCount: number; lastVisit: string
  castHistory: CastHistory[]
}

function mapCustomer(c: any): Customer {
  return {
    id:          c.id,
    phone:       c.phone,
    name:        c.name ?? '',
    status:      c.status,
    memo:        c.memo ?? '',
    ngCasts:     (c.ng_casts ?? []).map((nc: any) => nc.name),
    visitCount:  c.visit_count ?? 0,
    lastVisit:   c.last_visit ?? '',
    castHistory: c.cast_history ?? [],
  }
}

// ─── 合言葉 ──────────────────────────────────────────────
const activeCodewords   = computed(() => codewordsStore.codewords.filter(c => c.isActive))
const codewordPanelOpen = ref(true)
const selectedCodewordId = ref<number | null>(null)
const selectedCodeword   = computed(() =>
  activeCodewords.value.find(c => c.id === selectedCodewordId.value) ?? null
)

function selectCodeword(id: number) {
  selectedCodewordId.value = selectedCodewordId.value === id ? null : id
}

function cwDiscountLabel(c: { discountType: 'fixed' | 'percent'; discountValue: number }) {
  return c.discountType === 'fixed'
    ? `¥${c.discountValue.toLocaleString()} 引き`
    : `${c.discountValue}% 引き`
}

// ─── フロー状態 ──────────────────────────────────────────
const step = ref<Step>('search')
const STEPS: Step[] = ['search', 'customer', 'casting', 'options', 'datetime', 'confirm']
const stepIndex = (s: Step) => STEPS.indexOf(s)
const currentStepIndex = computed(() => stepIndex(step.value))

// ─── ① 電話番号検索 ──────────────────────────────────────
const phoneInput      = ref('')
const foundCustomer   = ref<Customer | null>(null)
const isNewCustomer   = ref(false)
const searching       = ref(false)
const phoneNormalized = computed(() => phoneInput.value.replace(/[-\s]/g, ''))

async function searchCustomer() {
  if (!phoneNormalized.value) return
  searching.value = true
  try {
    const res = await api.get('/customers', { params: { phone: phoneNormalized.value } })
    const data = res.data.data ?? res.data
    if (data.length > 0) {
      const detail = await api.get(`/customers/${data[0].id}`)
      foundCustomer.value = mapCustomer(detail.data)
      isNewCustomer.value = false
    } else {
      foundCustomer.value = null
      isNewCustomer.value = true
    }
    step.value = 'customer'
  } finally {
    searching.value = false
  }
}
function onPhoneKeydown(e: KeyboardEvent) { if (e.key === 'Enter') searchCustomer() }

// ─── ② 仮登録 ────────────────────────────────────────────
const newCustomerName = ref('')

// ─── ③ キャスト選択 ──────────────────────────────────────
const selectedCastId   = ref<number | null>(null)
const selectedCast     = computed(() => castsStore.casts.find(c => c.id === selectedCastId.value) ?? null)
const selectedCastName = computed(() => selectedCast.value?.name ?? '')
const selectedCastRank = computed(() => ranksStore.getById(selectedCast.value?.rankId ?? null))
const designationFee   = computed(() => selectedCastRank.value?.designationFee ?? 0)

// キャスト選択時の日付（デフォルト: 今日）
const castFilterDate = ref(new Date().toISOString().slice(0, 10))
const showOffShiftCasts = ref(false)

const sortedCasts = computed(() => {
  const history  = foundCustomer.value?.castHistory ?? []
  const ngNames  = foundCustomer.value?.ngCasts ?? []
  const onShiftIds = shiftsStore.getCastIdsByDate(castFilterDate.value)

  return castsStore.casts
    .map(c => {
      const hist    = history.find(h => h.castName === c.name)
      const isNg    = ngNames.includes(c.name)
      const rank    = ranksStore.getById(c.rankId)
      const shift   = shiftsStore.getByCastAndDate(c.id, castFilterDate.value)
      const onShift = onShiftIds.includes(c.id)
      const isAbsent = shift ? shift.status !== '出勤予定' : false
      return { ...c, count: hist?.count ?? 0, isNg, rank, shift, onShift, isAbsent }
    })
    .filter(c => showOffShiftCasts.value || c.onShift || c.isAbsent)
    .sort((a, b) => {
      if (a.isNg !== b.isNg) return a.isNg ? 1 : -1
      const ra = a.rank?.order ?? 0
      const rb = b.rank?.order ?? 0
      if (ra !== rb) return rb - ra
      return b.count - a.count
    })
})

// ─── ④ オプション選択 ────────────────────────────────────
const selectedOptionIds = ref<number[]>([])

const availableOptions = computed(() => {
  const cast  = castsStore.casts.find(c => c.id === selectedCastId.value)
  const ngIds = cast?.ngOptionIds ?? []
  return optionsStore.options.map(o => ({ ...o, isNg: ngIds.includes(o.id) }))
})

function toggleOption(id: number) {
  const o = availableOptions.value.find(o => o.id === id)
  if (o?.isNg) return
  const idx = selectedOptionIds.value.indexOf(id)
  if (idx === -1) selectedOptionIds.value.push(id)
  else selectedOptionIds.value.splice(idx, 1)
}

const selectedOptions    = computed(() => optionsStore.options.filter(o => selectedOptionIds.value.includes(o.id)))
const optionsTotalPrice  = computed(() => selectedOptions.value.reduce((s, o) => s + o.price, 0))

// ─── ⑤ 日時・コース・料金 ────────────────────────────────
function nowTime(): string {
  const d = new Date()
  return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`
}

const form = ref({
  date: new Date().toISOString().slice(0, 10),
  time: nowTime(),
  duration: 60,
  area: '',
  address: '',
  basePrice: 12000,
  transportFee: 0,
  transportFeeAuto: true,   // エリア連動フラグ
  discountAmount: 0,
  reservationStatus: '確定' as ReservationStatus,
  note: '',
})

// エリア変更 → 交通費自動セット
watch(() => form.value.area, (area) => {
  if (!form.value.transportFeeAuto) return
  const fee = areasStore.getFee(area)
  form.value.transportFee = fee ?? 0
})

// 合言葉選択 → 割引額自動セット
watch(selectedCodeword, (cw) => {
  if (!cw) { form.value.discountAmount = 0; return }
  if (cw.discountType === 'fixed') {
    form.value.discountAmount = cw.discountValue
  } else {
    form.value.discountAmount = Math.floor(form.value.basePrice * cw.discountValue / 100)
  }
})

// 合計金額
const totalPrice = computed(() =>
  Math.max(0,
    form.value.basePrice
    + designationFee.value
    + optionsTotalPrice.value
    + form.value.transportFee
    - form.value.discountAmount
  )
)

// ─── リセット ─────────────────────────────────────────────
function resetAll() {
  phoneInput.value = ''
  foundCustomer.value = null
  isNewCustomer.value = false
  newCustomerName.value = ''
  selectedCastId.value = null
  selectedOptionIds.value = []
  selectedCodewordId.value = null
  form.value = { date: new Date().toISOString().slice(0, 10), time: nowTime(), duration: 60, area: '', address: '', basePrice: 12000, transportFee: 0, transportFeeAuto: true, discountAmount: 0, reservationStatus: '確定', note: '' }
  step.value = 'search'
}

const submitting = ref(false)
const submitError = ref('')

async function submitReservation() {
  submitting.value = true
  submitError.value = ''
  try {
    // 新規顧客は先に仮登録
    let customerId = foundCustomer.value?.id
    if (isNewCustomer.value) {
      const res = await api.post('/customers', {
        phone:  phoneNormalized.value,
        name:   newCustomerName.value || null,
        status: 'temp',
      })
      customerId = res.data.id
    }

    await api.post('/reservations', {
      customer_id:          customerId,
      cast_id:              selectedCastId.value,
      codeword_id:          selectedCodewordId.value ?? null,
      date:                 form.value.date,
      time:                 form.value.time,
      duration:             form.value.duration,
      area:                 form.value.area,
      address:              form.value.address,
      base_price:           form.value.basePrice,
      designation_fee:      designationFee.value,
      options_total_price:  optionsTotalPrice.value,
      transport_fee:        form.value.transportFee,
      discount_amount:      form.value.discountAmount,
      total_price:          totalPrice.value,
      reservation_status:   form.value.reservationStatus,
      note:                 form.value.note,
      option_ids:           selectedOptionIds.value,
    })

    alert('予約を登録しました')
    resetAll()
  } catch (e: any) {
    submitError.value = e.response?.data?.message ?? '登録に失敗しました'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="max-w-2xl mx-auto space-y-4">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-bold text-gray-800">受付</h2>
      <button v-if="step !== 'search'" @click="resetAll"
        class="text-xs text-gray-400 hover:text-gray-600 underline">リセット</button>
    </div>

    <!-- ══ 合言葉パネル（常時表示） ══ -->
    <div class="bg-amber-50 border border-amber-200 rounded-xl overflow-hidden">
      <button @click="codewordPanelOpen = !codewordPanelOpen"
        class="w-full flex items-center justify-between px-4 py-2.5 hover:bg-amber-100 transition-colors">
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
          </svg>
          <span class="text-sm font-semibold text-amber-800">提携サイト 合言葉一覧</span>
          <span class="text-xs bg-amber-200 text-amber-700 px-1.5 py-0.5 rounded-full font-medium">{{ activeCodewords.length }}件有効</span>
          <span v-if="selectedCodeword"
            class="text-xs bg-green-200 text-green-800 px-2 py-0.5 rounded-full font-bold">
            ✓ 「{{ selectedCodeword.word }}」適用中
          </span>
        </div>
        <svg :class="codewordPanelOpen ? 'rotate-180' : ''"
          class="w-4 h-4 text-amber-500 transition-transform shrink-0"
          fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </button>

      <div v-if="codewordPanelOpen" class="border-t border-amber-200 px-4 py-3">
        <p class="text-xs text-amber-600 mb-2">クリックで選択 → 料金に割引が反映されます</p>
        <div v-if="activeCodewords.length === 0" class="text-xs text-amber-500 text-center py-2">
          有効な合言葉がありません
        </div>
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-2">
          <button
            v-for="c in activeCodewords" :key="c.id"
            @click="selectCodeword(c.id)"
            :class="[
              'flex items-center justify-between rounded-lg px-3 py-2 gap-3 border-2 text-left transition-all',
              selectedCodewordId === c.id
                ? 'border-green-400 bg-green-50 shadow-sm'
                : 'border-amber-200 bg-white hover:border-amber-400',
            ]"
          >
            <div class="min-w-0">
              <p class="text-xs text-amber-600 font-medium truncate">{{ c.siteName }}</p>
              <p class="text-sm font-mono font-bold text-amber-900">「{{ c.word }}」</p>
              <p v-if="c.description" class="text-xs text-gray-400">{{ c.description }}</p>
            </div>
            <div class="shrink-0 text-right">
              <span class="block bg-green-100 text-green-700 text-xs font-bold px-2 py-1 rounded-full whitespace-nowrap">
                {{ cwDiscountLabel(c) }}
              </span>
              <span v-if="selectedCodewordId === c.id" class="text-xs text-green-600 font-medium mt-0.5 block">✓ 選択中</span>
            </div>
          </button>
        </div>
        <p class="text-xs text-amber-400 mt-2 text-right">
          <RouterLink to="/codewords" class="hover:underline">合言葉を管理 →</RouterLink>
        </p>
      </div>
    </div>

    <!-- ══ 配車状況パネル（常時表示） ══ -->
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
      <button @click="dispatchPanelOpen = !dispatchPanelOpen"
        class="w-full flex items-center justify-between px-4 py-2.5 hover:bg-gray-50 transition-colors">
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 5H4m0 0l4 4m-4-4l4-4" />
          </svg>
          <span class="text-sm font-semibold text-gray-700">配車状況</span>

          <!-- 待機中バッジ -->
          <span v-if="driversStore.standbyCount > 0"
            class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-bold">
            今すぐ配車可 {{ driversStore.standbyCount }}名
          </span>
          <span v-else-if="earliestAvailableMin !== null"
            class="text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full font-bold">
            最短 約{{ earliestAvailableMin }}分後
          </span>
          <span v-else class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">稼働中なし</span>

          <!-- 稼働中バッジ -->
          <span v-if="driversStore.busyCount > 0"
            class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full">
            稼働中 {{ driversStore.busyCount }}名
          </span>
        </div>
        <svg :class="dispatchPanelOpen ? 'rotate-180' : ''"
          class="w-4 h-4 text-gray-400 transition-transform shrink-0"
          fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </button>

      <div v-if="dispatchPanelOpen" class="border-t border-gray-100 px-4 py-3 space-y-2">

        <!-- 待機中 -->
        <div v-if="driversStore.standbyDrivers.length > 0">
          <p class="text-xs font-semibold text-green-600 mb-1.5">今すぐ配車できます</p>
          <div class="space-y-1.5">
            <div v-for="d in driversStore.standbyDrivers" :key="d.id"
              class="flex items-center justify-between bg-green-50 border border-green-200 rounded-lg px-3 py-2">
              <div class="flex items-center gap-2 min-w-0">
                <div class="w-7 h-7 bg-green-500 rounded-full flex items-center justify-center shrink-0">
                  <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
                <div class="min-w-0">
                  <p class="text-sm font-bold text-gray-800">{{ d.name }}</p>
                  <p class="text-xs text-gray-500 truncate">{{ d.car }}</p>
                </div>
              </div>
              <div class="text-right shrink-0 ml-3">
                <span class="text-xs font-bold text-green-700 bg-green-100 px-2 py-0.5 rounded-full">待機中</span>
                <p class="text-xs text-gray-400 mt-0.5">本日 {{ d.todayCount }}件</p>
              </div>
            </div>
          </div>
        </div>

        <!-- 稼働中 -->
        <div v-if="driversStore.busyDrivers.length > 0">
          <p class="text-xs font-semibold text-orange-600 mb-1.5 mt-2">稼働中（戻り予定）</p>
          <div class="space-y-1.5">
            <div v-for="d in driversStore.busyDrivers" :key="d.id"
              class="flex items-center justify-between bg-orange-50 border border-orange-200 rounded-lg px-3 py-2">
              <div class="flex items-center gap-2 min-w-0">
                <div class="w-7 h-7 bg-orange-400 rounded-full flex items-center justify-center shrink-0">
                  <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div class="min-w-0">
                  <p class="text-sm font-bold text-gray-800">{{ d.name }}</p>
                  <p class="text-xs text-gray-500 truncate">{{ d.car }}</p>
                </div>
              </div>
              <div class="text-right shrink-0 ml-3">
                <span class="text-xs font-bold text-orange-700">{{ returnLabel(d.returnAt) }}</span>
                <p class="text-xs text-gray-400 mt-0.5">{{ d.returnAt }} 戻り予定</p>
              </div>
            </div>
          </div>
        </div>

        <!-- 全員休みのケース -->
        <div v-if="driversStore.standbyDrivers.length === 0 && driversStore.busyDrivers.length === 0"
          class="text-sm text-gray-400 text-center py-3">
          稼働中のドライバーがいません
        </div>

        <p class="text-xs text-gray-400 text-right pt-1">
          <RouterLink to="/drivers" class="hover:underline">ドライバー管理 →</RouterLink>
        </p>
      </div>
    </div>

    <!-- ステッパー -->
    <div class="flex items-center text-xs overflow-x-auto pb-1">
      <template v-for="(label, i) in ['電話番号', '顧客確認', 'キャスト', 'オプション', '日時', '確定']" :key="i">
        <div class="flex items-center gap-1 shrink-0">
          <div :class="[
            'w-6 h-6 rounded-full flex items-center justify-center font-bold shrink-0',
            currentStepIndex > i ? 'bg-blue-600 text-white' :
            currentStepIndex === i ? 'bg-blue-600 text-white ring-2 ring-blue-200' :
            'bg-gray-200 text-gray-400'
          ]">
            <svg v-if="currentStepIndex > i" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
            </svg>
            <span v-else>{{ i + 1 }}</span>
          </div>
          <span :class="currentStepIndex === i ? 'text-blue-600 font-medium' : 'text-gray-400'">{{ label }}</span>
        </div>
        <div v-if="i < 5" class="flex-1 h-px bg-gray-200 mx-2 min-w-3"></div>
      </template>
    </div>

    <!-- ① 電話番号 -->
    <div class="bg-white rounded-xl shadow-sm p-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">電話番号</label>
      <div class="flex gap-2">
        <input
          v-model="phoneInput" type="tel" placeholder="090-xxxx-xxxx" autofocus
          @keydown="onPhoneKeydown" :disabled="step !== 'search'"
          class="flex-1 border border-gray-300 rounded-lg px-4 py-2.5 text-base font-mono focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-50 disabled:text-gray-500"
        />
        <button v-if="step === 'search'" @click="searchCustomer" :disabled="!phoneNormalized || searching"
          class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors disabled:opacity-40">
          {{ searching ? '検索中...' : '検索' }}
        </button>
        <button v-else @click="() => { step = 'search'; foundCustomer = null }"
          class="text-sm text-blue-600 hover:underline px-2 shrink-0">変更</button>
      </div>
    </div>

    <!-- ② 顧客確認 / 仮登録 -->
    <template v-if="currentStepIndex >= 1">
      <div v-if="foundCustomer" class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="flex items-center gap-3 px-5 py-4 border-b bg-green-50">
          <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center shrink-0">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-bold text-green-800">既存顧客</p>
            <p class="text-xs text-green-600">来店 {{ foundCustomer.visitCount }}回 ／ 最終来店 {{ foundCustomer.lastVisit }}</p>
          </div>
          <span :class="foundCustomer.status === 'full' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700'"
            class="text-xs px-2 py-0.5 rounded-full font-medium shrink-0">
            {{ foundCustomer.status === 'full' ? '本登録' : '仮登録' }}
          </span>
        </div>
        <div class="px-5 py-4 space-y-2">
          <p class="text-lg font-bold text-gray-800">{{ foundCustomer.name || '（名前未登録）' }}</p>
          <div v-if="foundCustomer.memo" class="text-sm bg-yellow-50 border border-yellow-200 rounded-lg px-3 py-2">
            <span class="font-medium text-yellow-700">メモ：</span>{{ foundCustomer.memo }}
          </div>
          <div v-if="foundCustomer.ngCasts.length" class="flex items-center gap-2 flex-wrap">
            <span class="text-red-500 font-medium text-xs">キャストNG：</span>
            <span v-for="ng in foundCustomer.ngCasts" :key="ng"
              class="bg-red-100 text-red-600 px-2 py-0.5 rounded text-xs font-medium">{{ ng }}</span>
          </div>
          <div v-if="foundCustomer.castHistory.length" class="flex items-center gap-2 flex-wrap">
            <span class="text-gray-500 text-xs">指名履歴：</span>
            <span v-for="h in foundCustomer.castHistory" :key="h.castName"
              class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded text-xs">{{ h.castName }}（{{ h.count }}回）</span>
          </div>
        </div>
        <div class="px-5 pb-4">
          <button @click="step = 'casting'"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2.5 rounded-lg transition-colors">
            このお客様で進む →
          </button>
        </div>
      </div>

      <div v-else class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="flex items-center gap-3 px-5 py-4 border-b bg-orange-50">
          <div class="w-8 h-8 bg-orange-400 rounded-full flex items-center justify-center shrink-0">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </div>
          <div>
            <p class="text-sm font-bold text-orange-800">新規顧客</p>
            <p class="text-xs text-orange-600">{{ phoneInput }} は未登録です</p>
          </div>
        </div>
        <div class="px-5 py-4 space-y-3">
          <input v-model="newCustomerName" type="text" placeholder="お名前（任意）"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          <button @click="step = 'casting'"
            class="w-full bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium py-2.5 rounded-lg transition-colors">
            仮登録して進む →
          </button>
        </div>
      </div>
    </template>

    <!-- ③ キャスト選択 -->
    <div v-if="currentStepIndex >= 2" class="bg-white rounded-xl shadow-sm p-5">
      <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
        <h3 class="font-semibold text-gray-700">キャスト選択</h3>
        <div class="flex items-center gap-2">
          <input v-model="castFilterDate" type="date"
            class="border border-gray-300 rounded-lg px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500" />
          <label class="flex items-center gap-1 text-xs text-gray-500 cursor-pointer select-none">
            <input type="checkbox" v-model="showOffShiftCasts" class="accent-blue-600 w-3.5 h-3.5" />
            シフト外も表示
          </label>
        </div>
      </div>
      <p v-if="sortedCasts.length === 0" class="text-sm text-gray-400 text-center py-4">
        この日のシフトがありません。
        <label class="text-blue-500 underline cursor-pointer" @click="showOffShiftCasts = true">全員表示</label>
      </p>
      <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
        <button v-for="c in sortedCasts" :key="c.id"
          :disabled="c.isNg || c.isAbsent"
          @click="selectedCastId = c.id"
          :class="[
            'relative rounded-xl border-2 px-3 py-3 text-left transition-all',
            c.isNg || c.isAbsent ? 'opacity-50 cursor-not-allowed border-gray-200 bg-gray-50' :
            selectedCastId === c.id ? 'border-blue-500 bg-blue-50' :
            'border-gray-200 hover:border-blue-300 hover:bg-gray-50',
          ]"
        >
          <div class="flex items-center justify-between mb-1">
            <div class="flex items-center gap-1.5 min-w-0">
              <span class="font-bold text-gray-800">{{ c.name }}</span>
              <span v-if="c.rank" :class="c.rank.color"
                class="text-white text-xs font-bold px-1.5 py-0.5 rounded-full leading-none shrink-0">
                {{ c.rank.name }}
              </span>
            </div>
            <span :class="c.status === '待機中' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
              class="text-xs px-1.5 py-0.5 rounded-full shrink-0 ml-1">{{ c.status }}</span>
          </div>
          <div class="flex gap-1 flex-wrap mt-0.5">
            <span v-if="c.shift" class="text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded font-medium">
              {{ c.shift.startTime }}〜{{ c.shift.endTime }}
            </span>
            <span v-if="c.rank && c.rank.designationFee > 0"
              class="text-xs text-amber-600 font-medium">
              +¥{{ c.rank.designationFee.toLocaleString() }}
            </span>
            <span v-if="c.count >= 5" class="text-xs bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded font-medium">指名 {{ c.count }}回</span>
            <span v-else-if="c.count > 0" class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded">指名 {{ c.count }}回</span>
            <span v-if="c.isNg" class="text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded font-medium">NG</span>
            <span v-if="c.isAbsent"
              :class="c.shift?.status === '無断欠勤' ? 'bg-red-100 text-red-700' : 'bg-orange-100 text-orange-700'"
              class="text-xs px-1.5 py-0.5 rounded font-bold">
              {{ c.shift?.status }}
            </span>
          </div>
          <div v-if="selectedCastId === c.id"
            class="absolute top-2 right-2 w-4 h-4 bg-blue-500 rounded-full flex items-center justify-center">
            <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
            </svg>
          </div>
        </button>
      </div>
      <button @click="step = 'options'" :disabled="!selectedCastId"
        class="mt-4 w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2.5 rounded-lg transition-colors disabled:opacity-40">
        {{ selectedCastName ? selectedCastName + ' で次へ →' : 'キャストを選択してください' }}
      </button>
    </div>

    <!-- ④ オプション選択 -->
    <div v-if="currentStepIndex >= 3" class="bg-white rounded-xl shadow-sm p-5">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold text-gray-700">オプション選択</h3>
        <span class="text-xs text-gray-400">{{ selectedCastName }} のNG はグレー</span>
      </div>
      <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
        <button v-for="o in availableOptions" :key="o.id" :disabled="o.isNg" @click="toggleOption(o.id)"
          :class="[
            'relative rounded-xl border-2 px-3 py-3 text-left transition-all',
            o.isNg ? 'opacity-40 cursor-not-allowed border-gray-200 bg-gray-50' :
            selectedOptionIds.includes(o.id) ? 'border-blue-500 bg-blue-50' :
            'border-gray-200 hover:border-blue-300 hover:bg-gray-50',
          ]"
        >
          <p class="font-medium text-gray-800 text-sm">{{ o.name }}</p>
          <p class="text-xs mt-0.5" :class="o.isNg ? 'text-gray-400' : 'text-blue-600 font-medium'">
            +¥{{ o.price.toLocaleString() }}
          </p>
          <span v-if="o.isNg" class="absolute top-1.5 right-1.5 text-xs bg-red-100 text-red-500 px-1 py-0.5 rounded font-medium">NG</span>
          <div v-if="selectedOptionIds.includes(o.id)"
            class="absolute top-2 right-2 w-4 h-4 bg-blue-500 rounded-full flex items-center justify-center">
            <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
            </svg>
          </div>
        </button>
      </div>
      <div class="mt-3 flex items-center justify-between text-sm">
        <span v-if="selectedOptions.length > 0" class="text-gray-600">
          {{ selectedOptions.map(o => o.name).join('・') }}
          <span class="text-blue-600 font-medium ml-1">+¥{{ optionsTotalPrice.toLocaleString() }}</span>
        </span>
        <span v-else class="text-gray-400">オプションなし</span>
        <button @click="step = 'datetime'"
          class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
          次へ →
        </button>
      </div>
    </div>

    <!-- ⑤ 日時・コース・料金 -->
    <div v-if="currentStepIndex >= 4" class="bg-white rounded-xl shadow-sm p-5">
      <h3 class="font-semibold text-gray-700 mb-4">日時・コース・料金</h3>
      <div class="space-y-4">
        <!-- 日時 -->
        <div class="grid grid-cols-3 gap-3">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">日付</label>
            <input v-model="form.date" type="date"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">時間</label>
            <input v-model="form.time" type="time"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">コース</label>
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

        <!-- エリア・場所 -->
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">エリア</label>
            <input v-model="form.area" type="text" list="area-list" placeholder="例：渋谷区"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            <datalist id="area-list">
              <option v-for="a in areasStore.areas" :key="a.id" :value="a.area" />
            </datalist>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">場所</label>
            <input v-model="form.address" type="text" placeholder="例：○○ホテル 305号"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
        </div>

        <!-- 料金内訳 -->
        <div class="bg-gray-50 rounded-xl p-4 space-y-2">
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">料金内訳</p>

          <!-- 基本料金 -->
          <div class="flex items-center gap-3">
            <label class="text-sm text-gray-600 w-24 shrink-0">基本料金</label>
            <div class="relative flex-1">
              <span class="absolute left-3 top-2 text-sm text-gray-400">¥</span>
              <input v-model="form.basePrice" type="number" step="1000"
                class="w-full border border-gray-300 rounded-lg pl-7 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" />
            </div>
          </div>

          <!-- 指名料（読み取り専用） -->
          <div v-if="designationFee > 0" class="flex items-center gap-3">
            <span class="text-sm text-gray-600 w-24 shrink-0">指名料</span>
            <span class="text-sm text-amber-600 font-medium">+¥{{ designationFee.toLocaleString() }}</span>
            <span class="text-xs text-gray-400">{{ selectedCastName }}（{{ selectedCastRank?.name }}）</span>
          </div>

          <!-- オプション料金（読み取り専用） -->
          <div v-if="optionsTotalPrice > 0" class="flex items-center gap-3">
            <span class="text-sm text-gray-600 w-24 shrink-0">オプション</span>
            <span class="text-sm text-blue-600 font-medium">+¥{{ optionsTotalPrice.toLocaleString() }}</span>
            <span class="text-xs text-gray-400">{{ selectedOptions.map(o => o.name).join('・') }}</span>
          </div>

          <!-- 交通費 -->
          <div class="flex items-center gap-3">
            <label class="text-sm text-gray-600 w-24 shrink-0">
              交通費
              <span v-if="form.transportFeeAuto && form.area"
                class="block text-xs text-green-600 font-normal">自動設定</span>
            </label>
            <div class="relative flex-1">
              <span class="absolute left-3 top-2 text-sm text-gray-400">¥</span>
              <input v-model="form.transportFee" type="number" step="500"
                @input="form.transportFeeAuto = false"
                class="w-full border border-gray-300 rounded-lg pl-7 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" />
            </div>
            <button v-if="!form.transportFeeAuto && form.area"
              @click="() => { form.transportFeeAuto = true; const f = areasStore.getFee(form.area); form.transportFee = f ?? 0 }"
              class="text-xs text-blue-500 hover:underline shrink-0">自動に戻す</button>
          </div>

          <!-- 割引 -->
          <div class="flex items-center gap-3">
            <label class="text-sm text-gray-600 w-24 shrink-0">
              割引
              <span v-if="selectedCodeword"
                class="block text-xs text-amber-600 font-normal truncate max-w-20">{{ selectedCodeword.siteName }}</span>
            </label>
            <div class="relative flex-1">
              <span class="absolute left-3 top-2 text-sm text-gray-400">-¥</span>
              <input v-model="form.discountAmount" type="number" step="500"
                class="w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                :class="form.discountAmount > 0 ? 'border-amber-300 bg-amber-50' : ''" />
            </div>
          </div>

          <!-- 合計 -->
          <div class="border-t border-gray-200 pt-3 flex items-center justify-between">
            <span class="text-sm font-bold text-gray-700">合計</span>
            <span class="text-2xl font-bold text-gray-900">¥{{ totalPrice.toLocaleString() }}</span>
          </div>
        </div>

        <!-- ステータス・メモ -->
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">ステータス</label>
            <select v-model="form.reservationStatus"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option>仮予約</option>
              <option>確定</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">メモ</label>
            <input v-model="form.note" type="text" placeholder="備考など..."
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
        </div>

        <button @click="step = 'confirm'" :disabled="!form.time"
          class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2.5 rounded-lg transition-colors disabled:opacity-40">
          内容を確認する →
        </button>
      </div>
    </div>

    <!-- ⑥ 確認・確定 -->
    <div v-if="step === 'confirm'" class="bg-white rounded-xl shadow-sm p-5 space-y-4">
      <h3 class="font-semibold text-gray-700">予約内容の確認</h3>
      <div class="bg-gray-50 rounded-lg p-4 space-y-2 text-sm">
        <div class="grid grid-cols-2 gap-y-2.5">
          <span class="text-gray-400">顧客</span>
          <span class="font-medium">{{ foundCustomer?.name || newCustomerName || '新規（名前未登録）' }}</span>
          <span class="text-gray-400">電話番号</span>
          <span class="font-mono">{{ phoneInput }}</span>
          <span class="text-gray-400">キャスト</span>
          <span class="font-medium text-blue-700">{{ selectedCastName }}</span>
          <span class="text-gray-400">オプション</span>
          <span>
            <span v-if="selectedOptions.length === 0" class="text-gray-400">なし</span>
            <span v-else>
              <span v-for="o in selectedOptions" :key="o.id"
                class="inline-block bg-blue-100 text-blue-700 text-xs px-1.5 py-0.5 rounded font-medium mr-1">{{ o.name }}</span>
            </span>
          </span>
          <span class="text-gray-400">日時</span>
          <span>{{ form.date }} {{ form.time }}</span>
          <span class="text-gray-400">コース</span>
          <span>{{ form.duration }}分</span>
          <span class="text-gray-400">場所</span>
          <span>{{ form.area }} {{ form.address }}</span>

          <!-- 料金内訳 -->
          <span class="text-gray-400">基本料金</span>
          <span>¥{{ form.basePrice.toLocaleString() }}</span>
          <template v-if="designationFee > 0">
            <span class="text-gray-400">指名料</span>
            <span class="text-amber-600 font-medium">
              +¥{{ designationFee.toLocaleString() }}
              <span class="text-xs text-gray-400 ml-1">（{{ selectedCastRank?.name }}）</span>
            </span>
          </template>
          <template v-if="optionsTotalPrice > 0">
            <span class="text-gray-400">オプション</span>
            <span class="text-blue-600">+¥{{ optionsTotalPrice.toLocaleString() }}</span>
          </template>
          <template v-if="form.transportFee > 0">
            <span class="text-gray-400">交通費</span>
            <span>+¥{{ form.transportFee.toLocaleString() }}</span>
          </template>
          <template v-if="form.discountAmount > 0">
            <span class="text-gray-400">割引</span>
            <span class="text-green-600 font-medium">
              -¥{{ form.discountAmount.toLocaleString() }}
              <span v-if="selectedCodeword" class="text-xs text-gray-400 ml-1">（{{ selectedCodeword.siteName }}）</span>
            </span>
          </template>

          <span class="text-gray-700 font-bold border-t pt-2">合計</span>
          <span class="text-xl font-bold text-gray-900 border-t pt-2">¥{{ totalPrice.toLocaleString() }}</span>

          <span class="text-gray-400">ステータス</span>
          <span>
            <span :class="form.reservationStatus === '確定' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700'"
              class="px-2 py-0.5 rounded-full text-xs font-medium">{{ form.reservationStatus }}</span>
          </span>
        </div>
      </div>
      <div class="flex gap-3">
        <button @click="step = 'datetime'"
          class="flex-1 border border-gray-300 text-sm py-2.5 rounded-lg hover:bg-gray-50 transition-colors">
          修正する
        </button>
        <p v-if="submitError" class="text-red-500 text-sm text-center">{{ submitError }}</p>
        <button @click="submitReservation" :disabled="submitting"
          class="flex-1 bg-green-600 hover:bg-green-700 text-white text-sm font-medium py-2.5 rounded-lg transition-colors disabled:opacity-50">
          {{ submitting ? '登録中...' : '予約を確定する' }}
        </button>
      </div>
    </div>
  </div>
</template>
