<template>
    <v-card class="elevation-12 mx-auto" :max-width="$vuetify.display.xs ? '100%' : 450">
        <v-toolbar color="primary" dark flat :density="$vuetify.display.xs ? 'compact' : 'default'">
            <v-toolbar-title class="text-body-1 text-sm-h6">
                <v-icon class="mr-2" :size="$vuetify.display.xs ? 'small' : 'default'">mdi-leaf</v-icon>
                Bangalio Agro
            </v-toolbar-title>
        </v-toolbar>
        <v-card-text class="pa-4 pa-sm-6">
            <div class="text-center mb-4">
                <h2 class="text-h6 text-sm-h5 mb-1">Welcome Back</h2>
                <p class="text-body-2 text-grey">Sign in to continue</p>
            </div>
            <v-form @submit.prevent="handleLogin">
                <v-text-field
                    v-model="form.email"
                    label="Email"
                    prepend-inner-icon="mdi-email"
                    type="email"
                    required
                    :error-messages="errors.email"
                    variant="outlined"
                    density="comfortable"
                    class="mb-2"
                ></v-text-field>

                <v-text-field
                    v-model="form.password"
                    label="Password"
                    prepend-inner-icon="mdi-lock"
                    :type="showPassword ? 'text' : 'password'"
                    :append-inner-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                    @click:append-inner="showPassword = !showPassword"
                    required
                    :error-messages="errors.password"
                    variant="outlined"
                    density="comfortable"
                    class="mb-2"
                ></v-text-field>

                <v-alert v-if="errorMessage" type="error" class="mb-4" density="compact">
                    {{ errorMessage }}
                </v-alert>

                <v-btn
                    type="submit"
                    color="primary"
                    block
                    :loading="loading"
                    :disabled="loading"
                    :size="$vuetify.display.xs ? 'default' : 'large'"
                    class="mt-2"
                >
                    <v-icon left>mdi-login</v-icon>
                    Login
                </v-btn>
            </v-form>

        </v-card-text>
    </v-card>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
    email: '',
    password: '',
})

const errors = reactive({
    email: '',
    password: '',
})

const showPassword = ref(false)
const loading = ref(false)
const errorMessage = ref('')

const handleLogin = async () => {
    loading.value = true
    errorMessage.value = ''
    errors.email = ''
    errors.password = ''

    const result = await authStore.login(form.email, form.password)

    if (result.success) {
        router.push({ name: 'dashboard' })
    } else {
        errorMessage.value = result.message
    }

    loading.value = false
}
</script>
