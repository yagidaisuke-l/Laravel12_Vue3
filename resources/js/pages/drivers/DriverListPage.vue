<script setup lang="ts">
import { ref } from 'vue'
import { useDriversStore, type Driver, type DriverStatus } from '@/stores/drivers'

const store = useDriversStore()

const showModal  = ref(false)
const editingId  = ref<number | null>(null)
const modalForm  = ref({ name: '', status: '待機中' as DriverStatus, car: '', phone: '', todayCount: 0, returnAt: '' })

function openAdd() {
  editingId.value = null
  modalForm.value = { name: '', status: '待機中', car: '', phone: '', todayCount: 0, returnAt: '' }
  showModal.value = true
}

function openEdit(d: Driver) {
  editingId.value = d.id
  modalForm.value = { name: d.name, status: d.status, car: d.car, phone: d.phone, todayCount: d.todayCount, returnAt: d.returnAt }
  showModal.value = true
}

function saveModal() {
  if (!modalForm.value.name) return
  if (editingId.value !== null) {
    store.update(editingId.value, { ...modalForm.value })
  } else {
    store.add({ ...modalForm.value })
  }
  showModal.value = false
}

function deleteDriver() {
  if (editingId.value !== null && confirm('このドライバーを削除しますか？')) {
    store.remove(editingId.value)
    showModal.value = false
  }
}

const statusColor: Record<string, string> = {
  '稼働中': 'bg-green-100 text-green-700',
  '待機中': 'bg-blue-100 text-blue-700',
  '休み':   'bg-gray-100 text-gray-500',
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-bold text-gray-800">ドライバー管理</h2>
      <button @click="openAdd"
        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        + ドライバー追加
      </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-gray-500 border-b bg-gray-50">
            <th class="px-5 py-3">名前</th>
            <th class="px-5 py-3">ステータス</th>
            <th class="px-5 py-3">担当車両</th>
            <th class="px-5 py-3">戻り予定</th>
            <th class="px-5 py-3">本日件数</th>
            <th class="px-5 py-3">電話番号</th>
            <th class="px-5 py-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="d in store.drivers" :key="d.id" class="border-b last:border-0 hover:bg-gray-50">
            <td class="px-5 py-3">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-xs font-bold text-blue-600">
                  {{ d.name[0] }}
                </div>
                <span class="font-medium text-gray-800">{{ d.name }}</span>
              </div>
            </td>
            <td class="px-5 py-3">
              <span :class="statusColor[d.status]" class="text-xs px-2 py-0.5 rounded-full font-medium">
                {{ d.status }}
              </span>
            </td>
            <td class="px-5 py-3 text-gray-500 text-xs">{{ d.car }}</td>
            <td class="px-5 py-3 text-gray-600 text-sm">
              <span v-if="d.status === '稼働中' && d.returnAt" class="font-mono">{{ d.returnAt }}</span>
              <span v-else class="text-gray-300">—</span>
            </td>
            <td class="px-5 py-3 text-gray-700">{{ d.todayCount }}件</td>
            <td class="px-5 py-3 text-gray-500">{{ d.phone }}</td>
            <td class="px-5 py-3">
              <button @click="openEdit(d)" class="text-blue-600 hover:underline text-xs">編集</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- モーダル -->
  <div v-if="showModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
      <h3 class="font-bold text-gray-800">{{ editingId ? 'ドライバー編集' : 'ドライバー追加' }}</h3>

      <div class="space-y-3">
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">名前 <span class="text-red-500">*</span></label>
          <input v-model="modalForm.name" type="text" placeholder="例：山田 一郎"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">ステータス</label>
          <select v-model="modalForm.status"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option>待機中</option>
            <option>稼働中</option>
            <option>休み</option>
          </select>
        </div>
        <div v-if="modalForm.status === '稼働中'">
          <label class="block text-xs font-medium text-gray-600 mb-1">戻り予定時刻</label>
          <input v-model="modalForm.returnAt" type="time"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">担当車両</label>
          <input v-model="modalForm.car" type="text" placeholder="例：トヨタ アルファード / 品川 330 な 1234"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">電話番号</label>
          <input v-model="modalForm.phone" type="tel" placeholder="090-xxxx-xxxx"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
      </div>

      <div class="flex gap-3 pt-2">
        <button v-if="editingId" @click="deleteDriver"
          class="text-red-500 hover:text-red-700 text-sm px-3 py-2 rounded-lg border border-red-200 hover:bg-red-50 transition-colors">
          削除
        </button>
        <button @click="showModal = false"
          class="flex-1 border border-gray-300 text-sm py-2 rounded-lg hover:bg-gray-50 transition-colors">
          キャンセル
        </button>
        <button @click="saveModal" :disabled="!modalForm.name"
          class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 rounded-lg transition-colors disabled:opacity-40">
          {{ editingId ? '更新' : '追加' }}
        </button>
      </div>
    </div>
  </div>
</template>
