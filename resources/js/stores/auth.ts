import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useAuthStore = defineStore('auth', () => {
  const isLoggedIn = ref(!!localStorage.getItem('auth_token'))

  function login(token: string) {
    localStorage.setItem('auth_token', token)
    isLoggedIn.value = true
  }

  function logout() {
    localStorage.removeItem('auth_token')
    isLoggedIn.value = false
  }

  return { isLoggedIn, login, logout }
})
