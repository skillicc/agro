import { defineStore } from 'pinia'
import api from '../services/api'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('user')) || null,
        token: localStorage.getItem('token') || null,
        loading: false,
    }),

    getters: {
        isAuthenticated: (state) => !!state.token,
        isAdmin: (state) => state.user?.role === 'admin',
        userProjects: (state) => state.user?.projects || [],
    },

    actions: {
        async login(email, password) {
            this.loading = true
            try {
                const response = await api.post('/login', { email, password })
                this.token = response.data.token
                this.user = response.data.user
                localStorage.setItem('token', this.token)
                localStorage.setItem('user', JSON.stringify(this.user))
                return { success: true }
            } catch (error) {
                return {
                    success: false,
                    message: error.response?.data?.message || 'Login failed'
                }
            } finally {
                this.loading = false
            }
        },

        async logout() {
            try {
                await api.post('/logout')
            } catch (error) {
                console.error('Logout error:', error)
            } finally {
                this.token = null
                this.user = null
                localStorage.removeItem('token')
                localStorage.removeItem('user')
            }
        },

        async checkAuth() {
            if (!this.token) return false
            try {
                const response = await api.get('/user')
                this.user = response.data
                localStorage.setItem('user', JSON.stringify(this.user))
                return true
            } catch (error) {
                this.logout()
                return false
            }
        },

        async updateProfile(data) {
            const response = await api.put('/profile', data)
            this.user = { ...this.user, ...response.data }
            localStorage.setItem('user', JSON.stringify(this.user))
            return response.data
        },

        async changePassword(data) {
            return await api.put('/change-password', data)
        },

        hasProjectAccess(projectId) {
            if (this.isAdmin) return true
            return this.userProjects.some(p => p.id === projectId)
        },
    },
})
