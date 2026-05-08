<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useOptionsStore } from '@/stores/options'

const store = useOptionsStore()
onMounted(() => store.fetchAll())

// ─── モーダル状態 ─────────────────────────────────────
type ModalMode = 'add' | 'edit'
const showModal = ref(false)
const modalMode = ref<ModalMode>('add')
const editingId = ref<number | null>(null)

const form = ref({ name: '', price: 0, description: '' })

function openAdd() {
  modalMode.value = 'add'
  form.value = { name: '', price: 0, description: '' }
  editingId.value = null
  showModal.value = true
}

function openEdit(id: number) {
  const o = store.options.find(o => o.id === id)
  if (!o) return
  modalMode.value = 'edit'
  editingId.value = id
  form.value = { name: o.name, price: o.price, description: o.description }
  showModal.value = true
}

const saving = ref(false)

async function saveModal() {
  if (!form.value.name) return
  saving.value = true
  try {
    if (modalMode.value === 'add') {
      await store.add(form.value.name, form.value.price, form.value.description)
    } else if (editingId.value !== null) {
      await store.update(editingId.value, form.value.name, form.value.price, form.value.description)
    }
    showModal.value = false
  } finally {
    saving.value = false
  }
}

async function removeOption(id: number) {
  if (confirm('このオプションを削除しますか？')) await store.remove(id)
}

const totalOptions = computed(() => store.options.length)
</script>

<template>
  <div class="space-y-4 max-w-2xl">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-lg font-bold text-gray-800">オプション管理</h2>
        <p class="text-xs text-gray-400 mt-0.5">全 {{ totalOptions }} 件</p>
      </div>
      <button @click="openAdd"
        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        + オプション追加
      </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-gray-500 border-b bg-gray-50">
            <th class="px-5 py-3">オプション名</th>
            <th class="px-5 py-3">追加料金</th>
            <th class="px-5 py-3">備考</th>
            <th class="px-5 py-3 w-24"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="o in store.options" :key="o.id" class="border-b last:border-0 hover:bg-gray-50">
            <td class="px-5 py-3 font-medium text-gray-800">{{ o.name }}</td>
            <td class="px-5 py-3 text-gray-700">
              <span v-if="o.price > 0">+¥{{ o.price.toLocaleString() }}</span>
              <span v-else class="text-gray-400">-</span>
            </td>
            <td class="px-5 py-3 text-gray-500 text-xs">{{ o.description || '-' }}</td>
            <td class="px-5 py-3 flex gap-3">
              <button @click="openEdit(o.id)"
                class="text-blue-600 hover:underline text-xs">編集</button>
              <button @click="removeOption(o.id)"
                class="text-red-400 hover:underline text-xs">削除</button>
            </td>
          </tr>
          <tr v-if="store.options.length === 0">
            <td colspan="4" class="px-5 py-8 text-center text-gray-400 text-sm">オプションが登録されていません</td>
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
          {{ modalMode === 'add' ? 'オプション追加' : 'オプション編集' }}
        </h3>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">オプション名 <span class="text-red-500">*</span></label>
          <input v-model="form.name" type="text" placeholder="例：AF"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">追加料金（円）</label>
          <div class="relative">
            <span class="absolute left-3 top-2 text-sm text-gray-400">¥</span>
            <input v-model="form.price" type="number" step="500" min="0"
              class="w-full border border-gray-300 rounded-lg pl-7 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">備考</label>
          <input v-model="form.description" type="text" placeholder="例：要事前確認"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
        <div class="flex gap-3 pt-1">
          <button @click="showModal = false"
            class="flex-1 border border-gray-300 text-sm py-2 rounded-lg hover:bg-gray-50 transition-colors">
            キャンセル
          </button>
          <button @click="saveModal" :disabled="!form.name || saving"
            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 rounded-lg transition-colors disabled:opacity-40">
            {{ saving ? '保存中...' : '保存' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
