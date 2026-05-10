<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useCodewordsStore, type Codeword } from '@/stores/codewords'

const store = useCodewordsStore()

onMounted(() => store.fetchAll())

// ─── モーダル ─────────────────────────────────────────
type ModalMode = 'add' | 'edit'
const showModal = ref(false)
const modalMode = ref<ModalMode>('add')
const editingId = ref<number | null>(null)

const emptyForm = (): Omit<Codeword, 'id'> => ({
  siteName: '',
  word: '',
  discountType: 'fixed',
  discountValue: 1000,
  description: '',
  isActive: true,
})
const form = ref(emptyForm())

function openAdd() {
  modalMode.value = 'add'
  editingId.value = null
  form.value = emptyForm()
  showModal.value = true
}

function openEdit(c: Codeword) {
  modalMode.value = 'edit'
  editingId.value = c.id
  form.value = { siteName: c.siteName, word: c.word, discountType: c.discountType, discountValue: c.discountValue, description: c.description, isActive: c.isActive }
  showModal.value = true
}

async function saveModal() {
  if (!form.value.siteName || !form.value.word) return
  if (modalMode.value === 'add') {
    await store.add(form.value)
  } else if (editingId.value !== null) {
    await store.update(editingId.value, form.value)
  }
  showModal.value = false
}

function discountLabel(c: Codeword) {
  return c.discountType === 'fixed'
    ? `¥${c.discountValue.toLocaleString()} 引き`
    : `${c.discountValue}% 引き`
}
</script>

<template>
  <div class="space-y-4 max-w-2xl">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-lg font-bold text-gray-800">合言葉管理</h2>
        <p class="text-xs text-gray-400 mt-0.5">提携サイトの合言葉と割引を登録します</p>
      </div>
      <button @click="openAdd"
        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        + 合言葉追加
      </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-gray-500 border-b bg-gray-50">
            <th class="px-5 py-3">提携サイト</th>
            <th class="px-5 py-3">合言葉</th>
            <th class="px-5 py-3">割引</th>
            <th class="px-5 py-3">備考</th>
            <th class="px-5 py-3 text-center">有効</th>
            <th class="px-5 py-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in store.codewords" :key="c.id"
            class="border-b last:border-0 hover:bg-gray-50"
            :class="!c.isActive ? 'opacity-50' : ''">
            <td class="px-5 py-3 font-medium text-gray-800">{{ c.siteName }}</td>
            <td class="px-5 py-3">
              <span class="bg-amber-50 border border-amber-200 text-amber-800 text-xs font-mono px-2 py-1 rounded">
                {{ c.word }}
              </span>
            </td>
            <td class="px-5 py-3">
              <span class="bg-green-100 text-green-700 text-xs font-bold px-2 py-1 rounded-full">
                {{ discountLabel(c) }}
              </span>
            </td>
            <td class="px-5 py-3 text-gray-400 text-xs">{{ c.description || '-' }}</td>
            <td class="px-5 py-3 text-center">
              <button @click="store.toggleActive(c.id)"
                :class="c.isActive ? 'bg-green-500' : 'bg-gray-300'"
                class="relative inline-flex h-5 w-9 rounded-full transition-colors focus:outline-none">
                <span :class="c.isActive ? 'translate-x-4' : 'translate-x-0.5'"
                  class="inline-block h-4 w-4 mt-0.5 rounded-full bg-white shadow transition-transform"></span>
              </button>
            </td>
            <td class="px-5 py-3 flex gap-3">
              <button @click="openEdit(c)" class="text-blue-600 hover:underline text-xs">編集</button>
              <button @click="store.remove(c.id)" class="text-red-400 hover:underline text-xs">削除</button>
            </td>
          </tr>
          <tr v-if="store.codewords.length === 0">
            <td colspan="6" class="px-5 py-8 text-center text-gray-400 text-sm">合言葉が登録されていません</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- モーダル -->
  <Teleport to="body">
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="absolute inset-0 bg-black/40" @click="showModal = false"></div>
      <div class="relative bg-white rounded-xl shadow-xl w-full max-w-sm mx-4 p-6 space-y-4">
        <h3 class="font-bold text-gray-800">
          {{ modalMode === 'add' ? '合言葉追加' : '合言葉編集' }}
        </h3>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">提携サイト名 <span class="text-red-500">*</span></label>
          <input v-model="form.siteName" type="text" placeholder="例：デリヘルジャパン"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">合言葉 <span class="text-red-500">*</span></label>
          <input v-model="form.word" type="text" placeholder="例：○○見た"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-2">割引タイプ</label>
          <div class="flex gap-3">
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.discountType" type="radio" value="fixed" class="accent-blue-600" />
              <span class="text-sm">金額割引</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.discountType" type="radio" value="percent" class="accent-blue-600" />
              <span class="text-sm">割合割引</span>
            </label>
          </div>
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">
            割引{{ form.discountType === 'fixed' ? '額（円）' : '率（%）' }}
          </label>
          <div class="relative">
            <span class="absolute left-3 top-2 text-sm text-gray-400">
              {{ form.discountType === 'fixed' ? '¥' : '%' }}
            </span>
            <input v-model="form.discountValue" type="number" :step="form.discountType === 'fixed' ? 500 : 1" min="0"
              class="w-full border border-gray-300 rounded-lg pl-7 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">備考</label>
          <input v-model="form.description" type="text" placeholder="例：初回限定"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>

        <label class="flex items-center gap-2 cursor-pointer">
          <input v-model="form.isActive" type="checkbox" class="accent-blue-600 w-4 h-4" />
          <span class="text-sm text-gray-700">有効にする</span>
        </label>

        <div class="flex gap-3 pt-1">
          <button @click="showModal = false"
            class="flex-1 border border-gray-300 text-sm py-2 rounded-lg hover:bg-gray-50 transition-colors">
            キャンセル
          </button>
          <button @click="saveModal" :disabled="!form.siteName || !form.word"
            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 rounded-lg transition-colors disabled:opacity-40">
            保存
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
