<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useShiftsStore, type Shift, type ShiftStatus, ABSENT_REASONS } from '@/stores/shifts'
import { useCastsStore } from '@/stores/casts'
import { useRanksStore } from '@/stores/ranks'

// ─── 日付ユーティリティ（スクリプト上部で定義） ────────────
function fmtDate(d: Date): string { return d.toISOString().slice(0, 10) }
function addDays(base: string, n: number): string {
  const d = new Date(base); d.setDate(d.getDate() + n); return fmtDate(d)
}
function addMonths(base: string, n: number): string {
  const d = new Date(base); d.setMonth(d.getMonth() + n); d.setDate(d.getDate() - 1); return fmtDate(d)
}
// 曜日（0=日,1=月,...,6=土）
function getDow(dateStr: string): number { return new Date(dateStr).getDay() }

const shiftsStore = useShiftsStore()
const castsStore  = useCastsStore()
const ranksStore  = useRanksStore()

// ─── ビュー切替 ──────────────────────────────────────────
type ViewMode = 'today' | 'week' | 'month'
const viewMode = ref<ViewMode>('week')

// ─── 日付ナビゲーション ──────────────────────────────────
const baseDate = ref(new Date())

function fmt(d: Date): string { return d.toISOString().slice(0, 10) }
function todayStr(): string   { return fmt(new Date()) }

function navigate(dir: number) {
  const d = new Date(baseDate.value)
  if (viewMode.value === 'today') d.setDate(d.getDate() + dir)
  if (viewMode.value === 'week')  d.setDate(d.getDate() + dir * 7)
  if (viewMode.value === 'month') d.setMonth(d.getMonth() + dir)
  baseDate.value = d
}
function goToday() { baseDate.value = new Date() }

async function refreshRange() {
  if (viewMode.value === 'today') {
    await shiftsStore.fetchDate(fmt(baseDate.value))
  } else if (viewMode.value === 'week') {
    await shiftsStore.fetchRange(fmt(weekDays.value[0]), fmt(weekDays.value[6]))
  } else {
    const y = baseDate.value.getFullYear()
    const m = String(baseDate.value.getMonth() + 1).padStart(2, '0')
    const last = new Date(y, baseDate.value.getMonth() + 1, 0).getDate()
    await shiftsStore.fetchRange(`${y}-${m}-01`, `${y}-${m}-${String(last).padStart(2, '0')}`)
  }
}

onMounted(() => {
  castsStore.fetchAll()
  ranksStore.fetchAll()
  refreshRange()
})

watch([baseDate, viewMode], refreshRange)

// ─── ヘッダー表示 ────────────────────────────────────────
const headerLabel = computed(() => {
  const d = baseDate.value
  if (viewMode.value === 'today') {
    return d.toLocaleDateString('ja-JP', { year: 'numeric', month: 'long', day: 'numeric', weekday: 'short' })
  }
  if (viewMode.value === 'week') {
    const days = weekDays.value
    return `${days[0].toLocaleDateString('ja-JP', { month: 'long', day: 'numeric' })} 〜 ${days[6].toLocaleDateString('ja-JP', { month: 'long', day: 'numeric' })}`
  }
  return d.toLocaleDateString('ja-JP', { year: 'numeric', month: 'long' })
})

// ─── キャスト一覧（ランク順） ────────────────────────────
const sortedCasts = computed(() =>
  [...castsStore.casts].sort((a, b) => {
    const ra = ranksStore.getById(a.rankId)?.order ?? 0
    const rb = ranksStore.getById(b.rankId)?.order ?? 0
    return rb - ra
  })
)

// ─── 今日ビュー ──────────────────────────────────────────
const todayShifts = computed(() => {
  const date = fmt(baseDate.value)
  return shiftsStore.getByDate(date)
    .map(s => ({
      ...s,
      cast: castsStore.casts.find(c => c.id === s.castId),
      rank: ranksStore.getById(castsStore.casts.find(c => c.id === s.castId)?.rankId ?? null),
    }))
    .sort((a, b) => (b.rank?.order ?? 0) - (a.rank?.order ?? 0))
})

// ─── 週ビュー ────────────────────────────────────────────
const weekDays = computed(() => {
  const d = new Date(baseDate.value)
  const dow = (d.getDay() + 6) % 7 // 月曜起点
  d.setDate(d.getDate() - dow)
  return Array.from({ length: 7 }, (_, i) => {
    const day = new Date(d); day.setDate(d.getDate() + i); return day
  })
})

const DOW_JA = ['月', '火', '水', '木', '金', '土', '日']

function weekShift(castId: number, date: Date) {
  return shiftsStore.getByCastAndDate(castId, fmt(date))
}

// ─── 月ビュー ────────────────────────────────────────────
const calendarDays = computed(() => {
  const y = baseDate.value.getFullYear()
  const m = baseDate.value.getMonth()
  const firstDow = (new Date(y, m, 1).getDay() + 6) % 7
  const daysInMonth = new Date(y, m + 1, 0).getDate()
  const cells: (number | null)[] = [
    ...Array(firstDow).fill(null),
    ...Array.from({ length: daysInMonth }, (_, i) => i + 1),
  ]
  // 6行になるよう末尾を埋める
  while (cells.length % 7 !== 0) cells.push(null)
  return cells
})

function calDate(day: number | null): string {
  if (!day) return ''
  const y = baseDate.value.getFullYear()
  const m = String(baseDate.value.getMonth() + 1).padStart(2, '0')
  return `${y}-${m}-${String(day).padStart(2, '0')}`
}

function calShifts(day: number | null) {
  if (!day) return []
  return shiftsStore.getByDate(calDate(day))
    .map(s => ({
      ...s,
      cast: castsStore.casts.find(c => c.id === s.castId),
      rank: ranksStore.getById(castsStore.casts.find(c => c.id === s.castId)?.rankId ?? null),
    }))
}

// ─── モーダル ────────────────────────────────────────────
const showModal = ref(false)
const editingShiftId = ref<number | null>(null)
const modalForm = ref({ castId: 0, date: '', startTime: '', endTime: '', note: '' })

function openAdd(date = '', castId = 0) {
  editingShiftId.value = null
  modalForm.value = { castId: castId || sortedCasts.value[0]?.id || 0, date: date || todayStr(), startTime: '18:00', endTime: '24:00', note: '' }
  showModal.value = true
}

function openEdit(shift: Shift) {
  editingShiftId.value = shift.id
  modalForm.value = { castId: shift.castId, date: shift.date, startTime: shift.startTime, endTime: shift.endTime, note: shift.note }
  showModal.value = true
}

async function saveModal() {
  if (!modalForm.value.castId || !modalForm.value.date || !modalForm.value.startTime || !modalForm.value.endTime) return
  if (editingShiftId.value !== null) {
    await shiftsStore.update(editingShiftId.value, { ...modalForm.value })
  } else {
    const existing = shiftsStore.getByCastAndDate(modalForm.value.castId, modalForm.value.date)
    if (existing) {
      if (!confirm('この日のシフトは既に登録されています。上書きしますか？')) return
      await shiftsStore.update(existing.id, { ...modalForm.value })
    } else {
      await shiftsStore.add({ ...modalForm.value })
    }
  }
  showModal.value = false
}

async function deleteShift() {
  if (editingShiftId.value !== null && confirm('このシフトを削除しますか？')) {
    await shiftsStore.remove(editingShiftId.value)
    showModal.value = false
  }
}

// ─── 欠勤処理モーダル ─────────────────────────────────────
const showAbsentModal  = ref(false)
const absentShiftId    = ref<number | null>(null)
const absentReason     = ref<ShiftStatus>('体調不良')
const absentNote       = ref('')
const absentFromHere   = ref(false)  // 以降のシフトも欠勤にする

const absentTargetShift = computed(() =>
  shiftsStore.shifts.find(s => s.id === absentShiftId.value)
)
const absentTargetCast = computed(() =>
  castsStore.casts.find(c => c.id === absentTargetShift.value?.castId)
)

function openAbsentModal(shiftId: number) {
  absentShiftId.value = shiftId
  const s = shiftsStore.shifts.find(s => s.id === shiftId)
  absentReason.value  = (s?.status !== '出勤予定' ? s?.status : '体調不良') ?? '体調不良'
  absentNote.value    = s?.absentNote ?? ''
  absentFromHere.value = false
  showAbsentModal.value = true
}

async function saveAbsent() {
  const s = shiftsStore.shifts.find(s => s.id === absentShiftId.value)
  if (!s) return
  if (absentFromHere.value) {
    await shiftsStore.markAbsentFromDate(s.castId, s.date, absentReason.value, absentNote.value)
  } else {
    await shiftsStore.markAbsent(s.id, absentReason.value, absentNote.value)
  }
  showAbsentModal.value = false
}

// 以降の欠勤件数（プレビュー用）
const absentFromHereCount = computed(() => {
  const s = shiftsStore.shifts.find(s => s.id === absentShiftId.value)
  if (!s) return 0
  return shiftsStore.shifts.filter(
    sh => sh.castId === s.castId && sh.date >= s.date && sh.status === '出勤予定'
  ).length
})

async function undoAbsent(shiftId: number) {
  await shiftsStore.cancelAbsent(shiftId)
}

async function undoAbsentFromDate(shiftId: number) {
  const s = shiftsStore.shifts.find(s => s.id === shiftId)
  if (!s) return
  if (confirm(`${absentTargetCast.value?.name} の ${s.date} 以降の欠勤をすべて出勤予定に戻しますか？`)) {
    await shiftsStore.cancelAbsentFromDate(s.castId, s.date)
  }
}

const ABSENT_COLOR: Record<string, string> = {
  '体調不良': 'bg-orange-100 text-orange-700 border-orange-200',
  '無断欠勤': 'bg-red-100 text-red-700 border-red-200',
  'その他欠勤': 'bg-gray-100 text-gray-600 border-gray-200',
}

// ─── 一括登録モーダル ─────────────────────────────────────
const showBulkModal = ref(false)
const DOW_LABELS = ['日', '月', '火', '水', '木', '金', '土']

const bulkForm = ref({
  castId: 0,
  startDate: todayStr(),
  period: '1week' as '1week' | '1month' | 'custom',
  endDate: addDays(todayStr(), 6),
  dows: [1, 2, 3, 4, 5] as number[],  // 月〜金をデフォルト選択
  startTime: '18:00',
  endTime: '24:00',
  overwrite: false,
  note: '',
})

function openBulkModal() {
  bulkForm.value = {
    castId: sortedCasts.value[0]?.id ?? 0,
    startDate: todayStr(),
    period: '1week',
    endDate: addDays(todayStr(), 6),
    dows: [1, 2, 3, 4, 5],
    startTime: '18:00',
    endTime: '24:00',
    overwrite: false,
    note: '',
  }
  showBulkModal.value = true
}

function onPeriodChange() {
  if (bulkForm.value.period === '1week') {
    bulkForm.value.endDate = addDays(bulkForm.value.startDate, 6)
  } else if (bulkForm.value.period === '1month') {
    bulkForm.value.endDate = addMonths(bulkForm.value.startDate, 1)
  }
}

function onStartDateChange() {
  if (bulkForm.value.period !== 'custom') onPeriodChange()
}

function toggleDow(dow: number) {
  const idx = bulkForm.value.dows.indexOf(dow)
  if (idx === -1) bulkForm.value.dows.push(dow)
  else bulkForm.value.dows.splice(idx, 1)
}

// 登録予定日一覧を生成
const bulkPreviewDates = computed(() => {
  const { startDate, endDate, dows } = bulkForm.value
  if (!startDate || !endDate || endDate < startDate) return []
  const result: string[] = []
  let cur = startDate
  while (cur <= endDate) {
    if (dows.includes(getDow(cur))) result.push(cur)
    cur = addDays(cur, 1)
  }
  return result
})

// 既存シフトと重複する日数
const bulkConflictCount = computed(() =>
  bulkPreviewDates.value.filter(d =>
    shiftsStore.getByCastAndDate(bulkForm.value.castId, d)
  ).length
)

async function saveBulk() {
  const { castId, startDate, endDate, dows, startTime, endTime, overwrite, note } = bulkForm.value
  if (!castId || !startTime || !endTime) return
  const registered = await shiftsStore.bulkAdd({ castId, startDate, endDate, dows, startTime, endTime, overwrite, note })
  showBulkModal.value = false
  alert(`${registered}件登録しました。`)
  await refreshRange()
}

// ─── ユーティリティ ───────────────────────────────────────
function isToday(dateStr: string) { return dateStr === todayStr() }

function rankBadgeClass(castId: number) {
  const cast = castsStore.casts.find(c => c.id === castId)
  return ranksStore.getById(cast?.rankId ?? null)?.color ?? 'bg-gray-400'
}
</script>

<template>
  <div class="space-y-4">
    <!-- ヘッダー -->
    <div class="flex items-center justify-between flex-wrap gap-3">
      <h2 class="text-lg font-bold text-gray-800">シフト管理</h2>
      <div class="flex items-center gap-2">
        <!-- ビュー切替 -->
        <div class="flex rounded-lg border border-gray-300 overflow-hidden text-sm">
          <button v-for="v in ([['today','今日'],['week','週'],['month','月']] as const)" :key="v[0]"
            @click="viewMode = v[0]"
            :class="viewMode === v[0] ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
            class="px-3 py-1.5 transition-colors font-medium">
            {{ v[1] }}
          </button>
        </div>
        <button @click="openBulkModal()"
          class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-1.5 rounded-lg transition-colors">
          一括登録
        </button>
        <button @click="openAdd()"
          class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-1.5 rounded-lg transition-colors">
          + 1件追加
        </button>
      </div>
    </div>

    <!-- ナビゲーション -->
    <div class="flex items-center gap-3">
      <button @click="navigate(-1)"
        class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">
        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
      </button>
      <span class="text-sm font-semibold text-gray-700 min-w-48 text-center">{{ headerLabel }}</span>
      <button @click="navigate(1)"
        class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">
        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
      </button>
      <button @click="goToday"
        class="text-xs text-blue-600 hover:underline ml-1">今日</button>
    </div>

    <!-- ══ 今日ビュー ══ -->
    <div v-if="viewMode === 'today'" class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div class="px-5 py-3 border-b bg-gray-50 flex items-center justify-between">
        <span class="text-sm font-semibold text-gray-700">
          出勤キャスト（{{ todayShifts.length }}名）
        </span>
        <button @click="openAdd(fmt(baseDate))"
          class="text-xs text-blue-600 hover:underline">+ 追加</button>
      </div>
      <div v-if="todayShifts.length === 0" class="px-5 py-10 text-center text-gray-400 text-sm">
        この日のシフトはありません
      </div>
      <div v-else class="divide-y">
        <div v-for="s in todayShifts" :key="s.id"
          class="flex items-center gap-3 px-5 py-4 hover:bg-gray-50 transition-colors"
          :class="s.status !== '出勤予定' ? 'opacity-60 bg-red-50' : ''">
          <!-- アバター -->
          <div class="relative shrink-0">
            <div class="w-10 h-10 bg-pink-100 rounded-full flex items-center justify-center font-bold text-pink-500"
              :class="s.status !== '出勤予定' ? 'grayscale' : ''">
              {{ s.cast?.name[0] }}
            </div>
            <span v-if="s.rank" :class="s.rank.color"
              class="absolute -bottom-1 -right-1 text-white text-xs font-bold px-1.5 py-0.5 rounded-full leading-none shadow">
              {{ s.rank.name }}
            </span>
          </div>
          <!-- 情報 -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <p class="font-semibold text-gray-800">{{ s.cast?.name }}</p>
              <!-- 欠勤バッジ -->
              <span v-if="s.status !== '出勤予定'"
                :class="ABSENT_COLOR[s.status]"
                class="text-xs font-bold px-2 py-0.5 rounded-full border">
                {{ s.status }}
              </span>
            </div>
            <p class="text-sm text-gray-500">{{ s.startTime }} 〜 {{ s.endTime }}</p>
            <p v-if="s.absentNote" class="text-xs text-red-400 mt-0.5">{{ s.absentNote }}</p>
            <p v-else-if="s.note" class="text-xs text-gray-400 mt-0.5">{{ s.note }}</p>
          </div>
          <!-- 出勤予定のアクション -->
          <template v-if="s.status === '出勤予定'">
            <button @click="openAbsentModal(s.id)"
              class="text-xs text-red-500 border border-red-200 hover:bg-red-50 px-2.5 py-1 rounded-lg transition-colors shrink-0">
              欠勤処理
            </button>
            <button @click="openEdit(s)" class="text-xs text-blue-600 hover:underline shrink-0">編集</button>
          </template>
          <!-- 欠勤時のアクション -->
          <template v-else>
            <button @click="undoAbsent(s.id)"
              class="text-xs text-gray-500 border border-gray-300 hover:bg-gray-100 px-2.5 py-1 rounded-lg transition-colors shrink-0">
              出勤に戻す
            </button>
            <button @click="openAbsentModal(s.id)" class="text-xs text-orange-500 hover:underline shrink-0">変更</button>
          </template>
        </div>
      </div>
    </div>

    <!-- ══ 週ビュー ══ -->
    <div v-if="viewMode === 'week'" class="bg-white rounded-xl shadow-sm overflow-x-auto">
      <table class="w-full text-xs min-w-[640px]">
        <thead>
          <tr class="border-b bg-gray-50">
            <th class="px-3 py-3 text-left text-gray-500 font-medium w-24 sticky left-0 bg-gray-50">キャスト</th>
            <th v-for="(day, i) in weekDays" :key="i"
              class="px-2 py-3 text-center font-medium min-w-20"
              :class="isToday(fmt(day)) ? 'bg-blue-50 text-blue-700' : 'text-gray-500'">
              <div>{{ DOW_JA[i] }}</div>
              <div :class="isToday(fmt(day)) ? 'bg-blue-600 text-white w-6 h-6 rounded-full mx-auto flex items-center justify-center' : ''">
                {{ day.getDate() }}
              </div>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="cast in sortedCasts" :key="cast.id" class="border-b last:border-0 hover:bg-gray-50/50">
            <!-- キャスト名 -->
            <td class="px-3 py-2 sticky left-0 bg-white">
              <div class="flex items-center gap-2">
                <span :class="rankBadgeClass(cast.id)"
                  class="w-2 h-2 rounded-full shrink-0"></span>
                <span class="font-medium text-gray-800 truncate">{{ cast.name }}</span>
              </div>
            </td>
            <!-- 各日のシフト -->
            <td v-for="(day, i) in weekDays" :key="i"
              class="px-1 py-1.5 text-center"
              :class="isToday(fmt(day)) ? 'bg-blue-50' : ''">
              <template v-if="weekShift(cast.id, day)">
                <div class="relative group">
                  <button
                    @click="openEdit(weekShift(cast.id, day)!)"
                    :class="weekShift(cast.id, day)!.status !== '出勤予定'
                      ? 'bg-red-100 text-red-700 hover:bg-red-200'
                      : 'bg-green-100 text-green-800 hover:bg-green-200'"
                    class="w-full rounded px-1 py-1 leading-tight transition-colors">
                    <div class="font-semibold">{{ weekShift(cast.id, day)!.startTime }}</div>
                    <div class="text-xs opacity-70">{{ weekShift(cast.id, day)!.endTime }}</div>
                    <div v-if="weekShift(cast.id, day)!.status !== '出勤予定'"
                      class="text-xs font-bold mt-0.5">
                      {{ weekShift(cast.id, day)!.status }}
                    </div>
                  </button>
                  <!-- 欠勤ボタン（ホバー時） -->
                  <button v-if="weekShift(cast.id, day)!.status === '出勤予定'"
                    @click.stop="openAbsentModal(weekShift(cast.id, day)!.id)"
                    class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-red-500 text-white rounded-full text-xs hidden group-hover:flex items-center justify-center shadow">
                    ✕
                  </button>
                  <button v-else
                    @click.stop="undoAbsent(weekShift(cast.id, day)!.id)"
                    class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-green-500 text-white rounded-full text-xs hidden group-hover:flex items-center justify-center shadow">
                    ↩
                  </button>
                </div>
              </template>
              <template v-else>
                <button @click="openAdd(fmt(day), cast.id)"
                  class="w-full h-10 rounded border border-dashed border-gray-200 hover:border-blue-400 hover:bg-blue-50 text-gray-300 hover:text-blue-400 transition-colors text-lg leading-none">
                  +
                </button>
              </template>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ══ 月ビュー ══ -->
    <div v-if="viewMode === 'month'" class="bg-white rounded-xl shadow-sm overflow-hidden">
      <!-- 曜日ヘッダー -->
      <div class="grid grid-cols-7 border-b">
        <div v-for="(d, i) in ['月','火','水','木','金','土','日']" :key="i"
          class="py-2 text-center text-xs font-medium"
          :class="i === 5 ? 'text-blue-500' : i === 6 ? 'text-red-500' : 'text-gray-500'">
          {{ d }}
        </div>
      </div>
      <!-- カレンダーグリッド -->
      <div class="grid grid-cols-7">
        <div v-for="(day, i) in calendarDays" :key="i"
          class="min-h-20 border-b border-r p-1.5 last-of-type:border-r-0"
          :class="[
            !day ? 'bg-gray-50' : 'hover:bg-gray-50',
            day && isToday(calDate(day)) ? 'bg-blue-50' : '',
          ]">
          <template v-if="day">
            <div class="flex items-center justify-between mb-1">
              <span
                :class="isToday(calDate(day))
                  ? 'bg-blue-600 text-white w-5 h-5 rounded-full flex items-center justify-center font-bold'
                  : 'text-gray-600'"
                class="text-xs">
                {{ day }}
              </span>
              <button @click="openAdd(calDate(day))"
                class="text-gray-300 hover:text-blue-500 text-sm leading-none transition-colors">+</button>
            </div>
            <!-- シフトバッジ -->
            <div class="space-y-0.5">
              <div v-for="s in calShifts(day)" :key="s.id"
                @click="s.status === '出勤予定' ? openEdit(s) : openAbsentModal(s.id)"
                class="flex items-center gap-1 cursor-pointer hover:opacity-80 transition-opacity">
                <span :class="s.status !== '出勤予定' ? 'bg-red-400' : rankBadgeClass(s.castId)"
                  class="w-1.5 h-1.5 rounded-full shrink-0"></span>
                <span class="text-xs truncate leading-tight"
                  :class="s.status !== '出勤予定' ? 'text-red-500 line-through' : 'text-gray-700'">
                  {{ s.cast?.name }}
                  <span v-if="s.status !== '出勤予定'" class="no-underline not-italic text-red-400">（{{ s.status }}）</span>
                  <span v-else>{{ s.startTime }}〜</span>
                </span>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>
  </div>

  <!-- ══ 欠勤処理モーダル ══ -->
  <Teleport to="body">
    <div v-if="showAbsentModal" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="absolute inset-0 bg-black/40" @click="showAbsentModal = false"></div>
      <div class="relative bg-white rounded-xl shadow-xl w-full max-w-sm mx-4 p-6 space-y-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
          </div>
          <div>
            <h3 class="font-bold text-gray-800">欠勤処理</h3>
            <p class="text-sm text-gray-500">{{ absentTargetCast?.name }} / {{ absentTargetShift?.date }}</p>
          </div>
        </div>

        <!-- 理由選択 -->
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-2">欠勤理由 <span class="text-red-500">*</span></label>
          <div class="space-y-2">
            <label v-for="reason in ABSENT_REASONS" :key="reason"
              class="flex items-center gap-3 px-3 py-2.5 rounded-lg border-2 cursor-pointer transition-all"
              :class="absentReason === reason
                ? reason === '無断欠勤' ? 'border-red-400 bg-red-50'
                : reason === '体調不良' ? 'border-orange-400 bg-orange-50'
                : 'border-gray-400 bg-gray-50'
                : 'border-gray-200 hover:bg-gray-50'"
            >
              <input type="radio" :value="reason" v-model="absentReason" class="hidden" />
              <span class="text-lg">
                {{ reason === '体調不良' ? '🤒' : reason === '無断欠勤' ? '🚨' : '📋' }}
              </span>
              <div>
                <p class="text-sm font-semibold text-gray-800">{{ reason }}</p>
                <p class="text-xs text-gray-400">
                  {{ reason === '体調不良' ? '病気・怪我などによる欠勤'
                   : reason === '無断欠勤'  ? '連絡なし・無断欠勤'
                   : 'その他の理由による欠勤' }}
                </p>
              </div>
              <div v-if="absentReason === reason"
                class="ml-auto w-5 h-5 rounded-full flex items-center justify-center shrink-0"
                :class="reason === '無断欠勤' ? 'bg-red-500' : reason === '体調不良' ? 'bg-orange-500' : 'bg-gray-500'">
                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                </svg>
              </div>
            </label>
          </div>
        </div>

        <!-- メモ -->
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">メモ（任意）</label>
          <input v-model="absentNote" type="text"
            :placeholder="absentReason === '無断欠勤' ? '例：連絡試みるも不通' : '例：発熱のため'"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-400" />
        </div>

        <!-- 以降も欠勤オプション -->
        <label
          class="flex items-start gap-3 rounded-xl border-2 px-4 py-3 cursor-pointer transition-all"
          :class="absentFromHere
            ? 'border-red-400 bg-red-50'
            : 'border-gray-200 hover:bg-gray-50'"
        >
          <input type="checkbox" v-model="absentFromHere" class="accent-red-500 w-4 h-4 mt-0.5 shrink-0" />
          <div>
            <p class="text-sm font-semibold text-gray-800">以降のシフトもすべて欠勤にする</p>
            <p class="text-xs text-gray-400 mt-0.5">
              {{ absentTargetShift?.date }} 以降の登録済みシフト
              <span class="font-bold text-red-500">{{ absentFromHereCount }}件</span>
              をまとめて欠勤にします
            </p>
          </div>
        </label>

        <div class="flex gap-3 pt-1">
          <button @click="showAbsentModal = false"
            class="flex-1 border border-gray-300 text-sm py-2.5 rounded-lg hover:bg-gray-50 transition-colors">
            キャンセル
          </button>
          <button @click="saveAbsent"
            class="flex-1 text-white text-sm font-medium py-2.5 rounded-lg transition-colors"
            :class="absentReason === '無断欠勤' ? 'bg-red-600 hover:bg-red-700' : 'bg-orange-500 hover:bg-orange-600'">
            {{ absentFromHere ? `${absentFromHereCount}件を欠勤登録` : '欠勤として登録' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- ══ 一括登録モーダル ══ -->
  <Teleport to="body">
    <div v-if="showBulkModal" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="absolute inset-0 bg-black/40" @click="showBulkModal = false"></div>
      <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 space-y-4 max-h-[90vh] overflow-y-auto">
        <h3 class="font-bold text-gray-800">シフト一括登録</h3>

        <!-- キャスト -->
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">キャスト <span class="text-red-500">*</span></label>
          <select v-model="bulkForm.castId"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option v-for="c in sortedCasts" :key="c.id" :value="c.id">
              {{ c.name }}（{{ ranksStore.getById(c.rankId)?.name ?? '未設定' }}）
            </option>
          </select>
        </div>

        <!-- 開始日 -->
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">開始日 <span class="text-red-500">*</span></label>
          <input v-model="bulkForm.startDate" type="date" @change="onStartDateChange"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>

        <!-- 期間 -->
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-2">期間</label>
          <div class="flex gap-2 flex-wrap">
            <button v-for="opt in ([['1week','1週間'],['1month','1ヶ月'],['custom','カスタム']] as const)"
              :key="opt[0]"
              @click="bulkForm.period = opt[0]; onPeriodChange()"
              :class="bulkForm.period === opt[0]
                ? 'bg-blue-600 text-white border-blue-600'
                : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'"
              class="flex-1 text-sm font-medium py-2 rounded-lg border transition-colors">
              {{ opt[1] }}
            </button>
          </div>
          <!-- カスタム終了日 -->
          <div v-if="bulkForm.period === 'custom'" class="mt-2">
            <label class="block text-xs font-medium text-gray-600 mb-1">終了日</label>
            <input v-model="bulkForm.endDate" type="date" :min="bulkForm.startDate"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <p v-else class="text-xs text-gray-400 mt-1">
            {{ bulkForm.startDate }} 〜 {{ bulkForm.endDate }}
          </p>
        </div>

        <!-- 曜日 -->
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-2">出勤曜日</label>
          <div class="flex gap-1.5">
            <button v-for="(label, dow) in DOW_LABELS" :key="dow"
              @click="toggleDow(dow)"
              :class="[
                bulkForm.dows.includes(dow)
                  ? dow === 0 ? 'bg-red-500 text-white border-red-500'
                  : dow === 6 ? 'bg-blue-500 text-white border-blue-500'
                  : 'bg-gray-700 text-white border-gray-700'
                  : 'bg-white text-gray-400 border-gray-300 hover:bg-gray-50'
              ]"
              class="flex-1 py-2 text-xs font-bold rounded-lg border transition-colors">
              {{ label }}
            </button>
          </div>
        </div>

        <!-- 時間 -->
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">出勤時間 <span class="text-red-500">*</span></label>
            <input v-model="bulkForm.startTime" type="time"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">退勤時間 <span class="text-red-500">*</span></label>
            <input v-model="bulkForm.endTime" type="time"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
        </div>

        <!-- メモ -->
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">メモ</label>
          <input v-model="bulkForm.note" type="text" placeholder="特記事項など"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>

        <!-- 登録プレビュー -->
        <div class="bg-gray-50 rounded-lg p-3 space-y-1.5">
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600 font-medium">登録予定日数</span>
            <span class="font-bold text-blue-700">{{ bulkPreviewDates.length }} 日間</span>
          </div>
          <div v-if="bulkConflictCount > 0" class="flex items-center justify-between text-sm">
            <span class="text-amber-600">既存シフトと重複</span>
            <span class="font-bold text-amber-600">{{ bulkConflictCount }} 件</span>
          </div>
          <!-- 日付リスト（最大10件表示） -->
          <div class="flex flex-wrap gap-1 mt-1">
            <span v-for="d in bulkPreviewDates.slice(0, 10)" :key="d"
              class="text-xs bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded">
              {{ d.slice(5) }}
            </span>
            <span v-if="bulkPreviewDates.length > 10"
              class="text-xs text-gray-400 px-1.5 py-0.5">
              ...他 {{ bulkPreviewDates.length - 10 }} 日
            </span>
            <span v-if="bulkPreviewDates.length === 0" class="text-xs text-gray-400">
              対象日なし
            </span>
          </div>
        </div>

        <!-- 上書き設定 -->
        <label v-if="bulkConflictCount > 0"
          class="flex items-center gap-2 cursor-pointer bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
          <input type="checkbox" v-model="bulkForm.overwrite" class="accent-amber-500 w-4 h-4" />
          <span class="text-sm text-amber-700">既存シフトを上書きする</span>
        </label>

        <!-- ボタン -->
        <div class="flex gap-3 pt-1">
          <button @click="showBulkModal = false"
            class="flex-1 border border-gray-300 text-sm py-2.5 rounded-lg hover:bg-gray-50 transition-colors">
            キャンセル
          </button>
          <button @click="saveBulk"
            :disabled="bulkPreviewDates.length === 0 || !bulkForm.startTime || !bulkForm.endTime"
            class="flex-1 bg-green-600 hover:bg-green-700 text-white text-sm font-medium py-2.5 rounded-lg transition-colors disabled:opacity-40">
            {{ bulkPreviewDates.length }}日分を登録
          </button>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- ══ シフト追加/編集モーダル ══ -->
  <Teleport to="body">
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="absolute inset-0 bg-black/40" @click="showModal = false"></div>
      <div class="relative bg-white rounded-xl shadow-xl w-full max-w-sm mx-4 p-6 space-y-4">
        <h3 class="font-bold text-gray-800">
          {{ editingShiftId ? 'シフト編集' : 'シフト追加' }}
        </h3>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">キャスト <span class="text-red-500">*</span></label>
          <select v-model="modalForm.castId"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option v-for="c in sortedCasts" :key="c.id" :value="c.id">
              {{ c.name }}（{{ ranksStore.getById(c.rankId)?.name ?? '未設定' }}）
            </option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">日付 <span class="text-red-500">*</span></label>
          <input v-model="modalForm.date" type="date"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">出勤時間 <span class="text-red-500">*</span></label>
            <input v-model="modalForm.startTime" type="time"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">退勤時間 <span class="text-red-500">*</span></label>
            <input v-model="modalForm.endTime" type="time"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
        </div>
        <p class="text-xs text-gray-400 -mt-2">※ 深夜は 25:00〜29:59 の形式で入力</p>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">メモ</label>
          <input v-model="modalForm.note" type="text" placeholder="特記事項など"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>

        <div class="flex gap-3 pt-1">
          <button v-if="editingShiftId" @click="deleteShift"
            class="px-3 py-2 text-sm text-red-500 border border-red-200 rounded-lg hover:bg-red-50 transition-colors">
            削除
          </button>
          <button @click="showModal = false"
            class="flex-1 border border-gray-300 text-sm py-2 rounded-lg hover:bg-gray-50 transition-colors">
            キャンセル
          </button>
          <button @click="saveModal"
            :disabled="!modalForm.castId || !modalForm.date || !modalForm.startTime || !modalForm.endTime"
            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 rounded-lg transition-colors disabled:opacity-40">
            保存
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
