<template>
    <div>
        <div class="d-flex justify-space-between align-center mb-4">
            <h1 class="text-h4">My Profile</h1>
        </div>

        <v-row>
            <!-- Profile Info Card -->
            <v-col cols="12" md="4">
                <v-card>
                    <v-card-text class="text-center">
                        <v-avatar size="120" color="primary" class="mb-4">
                            <span class="text-h3 text-white">{{ userInitials }}</span>
                        </v-avatar>
                        <h2 class="text-h5 mb-1">{{ user?.name }}</h2>
                        <p class="text-grey mb-2">{{ user?.email }}</p>
                        <v-chip :color="user?.role === 'admin' ? 'success' : 'info'" size="small">
                            {{ user?.role === 'admin' ? 'Administrator' : 'User' }}
                        </v-chip>
                        <v-divider class="my-4"></v-divider>
                        <div class="text-left">
                            <div class="d-flex justify-space-between mb-2">
                                <span class="text-grey">Status:</span>
                                <v-chip :color="user?.is_active ? 'success' : 'error'" size="x-small">
                                    {{ user?.is_active ? 'Active' : 'Inactive' }}
                                </v-chip>
                            </div>
                            <div class="d-flex justify-space-between mb-2">
                                <span class="text-grey">Member Since:</span>
                                <span>{{ formatDate(user?.created_at) }}</span>
                            </div>
                            <div class="d-flex justify-space-between">
                                <span class="text-grey">Projects:</span>
                                <span>{{ user?.projects?.length || 0 }}</span>
                            </div>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>

            <!-- Update Profile Card -->
            <v-col cols="12" md="8">
                <v-card>
                    <v-tabs v-model="tab" color="primary">
                        <v-tab value="profile">Update Profile</v-tab>
                        <v-tab value="password">Change Password</v-tab>
                    </v-tabs>

                    <v-card-text>
                        <v-window v-model="tab">
                            <!-- Update Profile Tab -->
                            <v-window-item value="profile">
                                <v-form @submit.prevent="updateProfile">
                                    <v-text-field
                                        v-model="profileForm.name"
                                        label="Full Name"
                                        prepend-inner-icon="mdi-account"
                                        variant="outlined"
                                        class="mb-3"
                                        :error-messages="errors.name"
                                    ></v-text-field>

                                    <v-text-field
                                        v-model="profileForm.email"
                                        label="Email Address"
                                        type="email"
                                        prepend-inner-icon="mdi-email"
                                        variant="outlined"
                                        class="mb-3"
                                        :error-messages="errors.email"
                                    ></v-text-field>

                                    <v-text-field
                                        v-model="profileForm.phone"
                                        label="Phone Number"
                                        prepend-inner-icon="mdi-phone"
                                        variant="outlined"
                                        class="mb-3"
                                    ></v-text-field>

                                    <v-btn
                                        color="primary"
                                        type="submit"
                                        :loading="savingProfile"
                                        block
                                    >
                                        <v-icon left>mdi-content-save</v-icon>
                                        Update Profile
                                    </v-btn>
                                </v-form>
                            </v-window-item>

                            <!-- Change Password Tab -->
                            <v-window-item value="password">
                                <v-form @submit.prevent="changePassword">
                                    <v-text-field
                                        v-model="passwordForm.current_password"
                                        label="Current Password"
                                        :type="showCurrentPassword ? 'text' : 'password'"
                                        prepend-inner-icon="mdi-lock"
                                        :append-inner-icon="showCurrentPassword ? 'mdi-eye-off' : 'mdi-eye'"
                                        @click:append-inner="showCurrentPassword = !showCurrentPassword"
                                        variant="outlined"
                                        class="mb-3"
                                        :error-messages="errors.current_password"
                                    ></v-text-field>

                                    <v-text-field
                                        v-model="passwordForm.password"
                                        label="New Password"
                                        :type="showNewPassword ? 'text' : 'password'"
                                        prepend-inner-icon="mdi-lock-plus"
                                        :append-inner-icon="showNewPassword ? 'mdi-eye-off' : 'mdi-eye'"
                                        @click:append-inner="showNewPassword = !showNewPassword"
                                        variant="outlined"
                                        class="mb-3"
                                        :error-messages="errors.password"
                                        hint="Minimum 8 characters"
                                    ></v-text-field>

                                    <v-text-field
                                        v-model="passwordForm.password_confirmation"
                                        label="Confirm New Password"
                                        :type="showConfirmPassword ? 'text' : 'password'"
                                        prepend-inner-icon="mdi-lock-check"
                                        :append-inner-icon="showConfirmPassword ? 'mdi-eye-off' : 'mdi-eye'"
                                        @click:append-inner="showConfirmPassword = !showConfirmPassword"
                                        variant="outlined"
                                        class="mb-3"
                                    ></v-text-field>

                                    <v-btn
                                        color="warning"
                                        type="submit"
                                        :loading="changingPassword"
                                        block
                                    >
                                        <v-icon left>mdi-lock-reset</v-icon>
                                        Change Password
                                    </v-btn>
                                </v-form>
                            </v-window-item>
                        </v-window>
                    </v-card-text>
                </v-card>

                <!-- Activity / Projects Card -->
                <v-card class="mt-4" v-if="user?.projects?.length">
                    <v-card-title>
                        <v-icon class="mr-2">mdi-folder-multiple</v-icon>
                        Assigned Projects
                    </v-card-title>
                    <v-card-text>
                        <v-chip
                            v-for="project in user.projects"
                            :key="project.id"
                            class="ma-1"
                            color="primary"
                            variant="outlined"
                        >
                            {{ project.name }}
                        </v-chip>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <!-- Success Snackbar -->
        <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="3000">
            {{ snackbarMessage }}
        </v-snackbar>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import api from '../../services/api'

const authStore = useAuthStore()
const user = computed(() => authStore.user)

const tab = ref('profile')
const savingProfile = ref(false)
const changingPassword = ref(false)
const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)
const snackbar = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref('success')
const errors = ref({})

const profileForm = reactive({
    name: '',
    email: '',
    phone: ''
})

const passwordForm = reactive({
    current_password: '',
    password: '',
    password_confirmation: ''
})

const userInitials = computed(() => {
    if (!user.value?.name) return '?'
    return user.value.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
})

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('en-BD', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

const showSnackbar = (message, color = 'success') => {
    snackbarMessage.value = message
    snackbarColor.value = color
    snackbar.value = true
}

const updateProfile = async () => {
    savingProfile.value = true
    errors.value = {}

    try {
        const response = await api.put('/profile', profileForm)
        authStore.setUser(response.data)
        showSnackbar('Profile updated successfully!')
    } catch (error) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors
        }
        showSnackbar('Error updating profile', 'error')
    }

    savingProfile.value = false
}

const changePassword = async () => {
    changingPassword.value = true
    errors.value = {}

    try {
        await api.put('/change-password', passwordForm)
        showSnackbar('Password changed successfully!')
        // Reset password form
        passwordForm.current_password = ''
        passwordForm.password = ''
        passwordForm.password_confirmation = ''
    } catch (error) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors
        } else if (error.response?.data?.message) {
            showSnackbar(error.response.data.message, 'error')
        } else {
            showSnackbar('Error changing password', 'error')
        }
    }

    changingPassword.value = false
}

onMounted(() => {
    if (user.value) {
        profileForm.name = user.value.name || ''
        profileForm.email = user.value.email || ''
        profileForm.phone = user.value.phone || ''
    }
})
</script>
