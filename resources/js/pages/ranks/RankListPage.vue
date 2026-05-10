<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRanksStore, type Rank } from '@/stores/ranks'

const store = useRanksStore()

onMounted(() => store.fetchAll())

const COLORS = [
  { label: 'グレー',   value: 'bg-gray-400' },
  { label: 'ブルー',   value: 'bg-blue-400' },
  { label: 'パープル', value: 'bg-purple-500' },
  { label: 'アンバー', value: 'bg-amber-500' },
  { label: 'ローズ',   value: 'bg-rose-500' },
  { label: 'グリーン', value: 'bg-green-500' },
  { label: 'シアン',   value: 'bg-cyan-500' },
]

type ModalMode = 'add' | 'edit'
const showModal  = ref(false)
const modalMode  = ref<ModalMode>('add')
const editingId  = ref<number | null>(null)

const emptyForm = () => ({ name: '', designationFee: 0, color: 'bg-gray-400' })
const form = ref(emptyForm())

function openAdd() {
  modalMode.value = 'add'; editingId.value = null; form.value = emptyForm(); showModal.value = true
}
function openEdit(r: Rank) {
  modalMode.value = 'edit'; editingId.value = r.id
  form.value = { name: r.name, designationFee: r.designationFee, color: r.color }
  showModal.value = true
}
async function save() {
  if (!form.value.name) return
  if (modalMode.value === 'add') await store.add(form.value.name, form.value.designationFee, form.value.color)
  else if (editingId.value !== null) await store.update(editingId.value, form.value.name, form.value.designationFee, form.value.color)
  showModal.value = false
}
</script>

<template>
  <div class="space-y-4 max-w-lg">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-lg font-bold text-gray-800">ランク管理</h2>
        <p class="text-xs text-gray-400 mt-0.5">ランクごとの指名料を設定します</p>
      </div>
      <button @click="openAdd"
        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        + ランク追加
      </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-gray-500 border-b bg-gray-50">
            <th class="px-5 py-3">ランク</th>
            <th class="px-5 py-3">指名料（追加）</th>
            <th class="px-5 py-3 w-24"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in store.sorted()" :key="r.id" class="border-b last:border-0 hover:bg-gray-50">
            <td class="px-5 py-3">
              <div class="flex items-center gap-2">
                <span :class="r.color" class="w-3 h-3 rounded-full shrink-0"></span>
                <span :class="[r.color, 'text-white text-xs font-bold px-2 py-0.5 rounded-full']">{{ r.name }}</span>
              </div>
            </td>
            <td class="px-5 py-3 font-medium text-gray-700">
              {{ r.designationFee > 0 ? `+¥${r.designationFee.toLocaleString()}` : '無料' }}
            </td>
            <td class="px-5 py-3 flex gap-3">
              <button @click="openEdit(r)" class="text-blue-600 hover:underline text-xs">編集</button>
              <button @click="store.remove(r.id)" class="text-red-400 hover:underline text-xs">削除</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <Teleport to="body">
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="absolute inset-0 bg-black/40" @click="showModal = false"></div>
      <div class="relative bg-white rounded-xl shadow-xl w-full max-w-sm mx-4 p-6 space-y-4">
        <h3 class="font-bold text-gray-800">{{ modalMode === 'add' ? 'ランク追加' : 'ランク編集' }}</h3>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">ランク名 <span class="text-red-500">*</span></label>
          <input v-model="form.name" type="text" placeholder="例：Sランク"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">指名料（追加料金）</label>
          <div class="relative">
            <span class="absolute left-3 top-2 text-sm text-gray-400">+¥</span>
            <input v-model="form.designationFee" type="number" step="500" min="0"
              class="w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-2">カラー</label>
          <div class="flex gap-2 flex-wrap">
            <button v-for="c in COLORS" :key="c.value" @click="form.color = c.value"
              :class="[c.value, form.color === c.value ? 'ring-2 ring-offset-2 ring-blue-500' : '']"
              class="w-8 h-8 rounded-full transition-all" :title="c.label"></button>
          </div>
          <div class="flex items-center gap-2 mt-2">
            <span :class="form.color" class="w-3 h-3 rounded-full"></span>
            <span :class="[form.color, 'text-white text-xs font-bold px-2 py-0.5 rounded-full']">{{ form.name || 'プレビュー' }}</span>
          </div>
        </div>

        <div class="flex gap-3 pt-1">
          <button @click="showModal = false"
            class="flex-1 border border-gray-300 text-sm py-2 rounded-lg hover:bg-gray-50 transition-colors">
            キャンセル
          </button>
          <button @click="save" :disabled="!form.name"
            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 rounded-lg transition-colors disabled:opacity-40">
            保存
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
