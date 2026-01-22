<template>
    <v-app>
        <!-- App Bar -->
        <v-app-bar color="primary" :density="$vuetify.display.smAndDown ? 'compact' : 'default'">
            <v-app-bar-nav-icon @click="toggleDrawer"></v-app-bar-nav-icon>
            <v-toolbar-title class="text-body-1 text-sm-h6">Bangalio Agro</v-toolbar-title>
            <v-spacer></v-spacer>

            <!-- Cache Clear Button -->
            <v-btn icon @click="clearCache" :loading="clearingCache" :size="$vuetify.display.smAndDown ? 'small' : 'default'" class="mr-1">
                <v-icon>mdi-cached</v-icon>
                <v-tooltip activator="parent" location="bottom">Clear Cache & Refresh</v-tooltip>
            </v-btn>

            <!-- Theme Toggle -->
            <v-btn icon @click="toggleTheme" :size="$vuetify.display.smAndDown ? 'small' : 'default'" class="mr-1">
                <v-icon>{{ isDark ? 'mdi-weather-sunny' : 'mdi-weather-night' }}</v-icon>
                <v-tooltip activator="parent" location="bottom">{{ isDark ? 'Light Mode' : 'Dark Mode' }}</v-tooltip>
            </v-btn>

            <!-- Show user name on larger screens -->
            <span v-if="$vuetify.display.mdAndUp" class="text-body-2 mr-2">{{ authStore.user?.name }}</span>

            <v-menu>
                <template v-slot:activator="{ props }">
                    <v-btn icon v-bind="props" :size="$vuetify.display.smAndDown ? 'small' : 'default'">
                        <v-icon>mdi-account-circle</v-icon>
                    </v-btn>
                </template>
                <v-list density="compact">
                    <v-list-item>
                        <v-list-item-title>{{ authStore.user?.name }}</v-list-item-title>
                        <v-list-item-subtitle>{{ authStore.user?.role }}</v-list-item-subtitle>
                    </v-list-item>
                    <v-divider></v-divider>
                    <v-list-item :to="{ name: 'profile' }">
                        <template v-slot:prepend>
                            <v-icon>mdi-account</v-icon>
                        </template>
                        <v-list-item-title>Profile</v-list-item-title>
                    </v-list-item>
                    <v-list-item @click="logout">
                        <template v-slot:prepend>
                            <v-icon>mdi-logout</v-icon>
                        </template>
                        <v-list-item-title>Logout</v-list-item-title>
                    </v-list-item>
                </v-list>
            </v-menu>
        </v-app-bar>

        <!-- Navigation Drawer - Responsive width -->
        <v-navigation-drawer
            v-model="drawer"
            :temporary="$vuetify.display.smAndDown"
            :permanent="$vuetify.display.mdAndUp"
            :rail="$vuetify.display.mdAndUp && rail"
            :width="$vuetify.display.xs ? 260 : ($vuetify.display.md ? 260 : 280)"
        >
            <!-- Header when expanded -->
            <v-list-item
                v-if="!rail"
                prepend-avatar="https://ui-avatars.com/api/?name=Bangalio+Agro&background=4CAF50&color=fff"
                title="Bangalio Agro"
                subtitle="Inventory System"
                class="py-2"
            >
                <template v-slot:append v-if="$vuetify.display.mdAndUp">
                    <v-btn
                        icon="mdi-chevron-left"
                        variant="text"
                        size="small"
                        @click="rail = true"
                    ></v-btn>
                </template>
            </v-list-item>

            <!-- Header when collapsed (rail mode) - show expand button -->
            <div v-else class="d-flex justify-center py-2">
                <v-btn
                    icon="mdi-chevron-right"
                    variant="text"
                    size="small"
                    @click="rail = false"
                ></v-btn>
            </div>

            <v-divider></v-divider>

            <v-list density="compact" nav>
                <v-list-item
                    prepend-icon="mdi-view-dashboard"
                    title="Dashboard"
                    :to="{ name: 'dashboard' }"
                ></v-list-item>

                <v-list-item
                    prepend-icon="mdi-folder-multiple"
                    title="Projects"
                    :to="{ name: 'projects' }"
                ></v-list-item>

                <v-list-group value="Transactions" v-if="!rail">
                    <template v-slot:activator="{ props }">
                        <v-list-item
                            v-bind="props"
                            prepend-icon="mdi-swap-horizontal"
                            title="Transactions"
                        ></v-list-item>
                    </template>

                    <v-list-item
                        prepend-icon="mdi-cart-arrow-down"
                        title="Purchases"
                        :to="{ name: 'purchases' }"
                    ></v-list-item>
                    <v-list-item
                        prepend-icon="mdi-cart-arrow-up"
                        title="Sales"
                        :to="{ name: 'sales' }"
                    ></v-list-item>
                    <v-list-item
                        prepend-icon="mdi-cash-minus"
                        title="Expenses"
                        :to="{ name: 'expenses' }"
                    ></v-list-item>
                </v-list-group>

                <!-- Show individual items when rail is active -->
                <template v-if="rail">
                    <v-list-item
                        prepend-icon="mdi-cart-arrow-down"
                        title="Purchases"
                        :to="{ name: 'purchases' }"
                    ></v-list-item>
                    <v-list-item
                        prepend-icon="mdi-cart-arrow-up"
                        title="Sales"
                        :to="{ name: 'sales' }"
                    ></v-list-item>
                    <v-list-item
                        prepend-icon="mdi-cash-minus"
                        title="Expenses"
                        :to="{ name: 'expenses' }"
                    ></v-list-item>
                </template>

                <v-list-item
                    prepend-icon="mdi-package-variant"
                    title="Products"
                    :to="{ name: 'products' }"
                ></v-list-item>

                <v-list-item
                    prepend-icon="mdi-warehouse"
                    title="Warehouses"
                    :to="{ name: 'warehouses' }"
                ></v-list-item>

                <v-list-item
                    prepend-icon="mdi-truck-delivery"
                    title="Suppliers"
                    :to="{ name: 'suppliers' }"
                ></v-list-item>

                <v-list-item
                    prepend-icon="mdi-account-group"
                    title="Customers"
                    :to="{ name: 'customers' }"
                ></v-list-item>

                <v-list-item
                    prepend-icon="mdi-account-hard-hat"
                    title="Employees"
                    :to="{ name: 'employees' }"
                ></v-list-item>

                <v-list-item
                    prepend-icon="mdi-calendar-check"
                    title="Attendance"
                    :to="{ name: 'attendance' }"
                ></v-list-item>

                <v-list-item
                    prepend-icon="mdi-chart-bar"
                    title="Reports"
                    :to="{ name: 'reports' }"
                ></v-list-item>

                <v-list-item
                    prepend-icon="mdi-cash-multiple"
                    title="Invest, Loan & Liability"
                    :to="{ name: 'invest-loan-liability' }"
                ></v-list-item>

                <v-divider v-if="authStore.isAdmin"></v-divider>

                <v-list-item
                    v-if="authStore.isAdmin"
                    prepend-icon="mdi-account-cog"
                    title="User Management"
                    :to="{ name: 'users' }"
                ></v-list-item>
            </v-list>
        </v-navigation-drawer>

        <!-- Main Content -->
        <v-main>
            <v-container fluid class="pa-2 pa-sm-4 pa-md-6">
                <router-view />
            </v-container>
        </v-main>
    </v-app>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useDisplay, useTheme } from 'vuetify'
import { useAuthStore } from '../stores/auth'
import axios from 'axios'

const drawer = ref(false)
const rail = ref(false)
const clearingCache = ref(false)
const router = useRouter()
const authStore = useAuthStore()
const display = useDisplay()
const theme = useTheme()

// Theme toggle
const isDark = computed(() => theme.global.current.value.dark)

const toggleTheme = () => {
    const newTheme = isDark.value ? 'light' : 'dark'
    theme.global.name.value = newTheme
    localStorage.setItem('theme', newTheme)
}

// Clear cache and hard refresh
const clearCache = async () => {
    clearingCache.value = true
    try {
        await axios.post('/api/system/clear-cache')
        // Hard refresh (bypass browser cache)
        window.location.reload(true)
    } catch (error) {
        console.error('Error clearing cache:', error)
        alert('Error clearing cache: ' + (error.response?.data?.message || error.message))
        clearingCache.value = false
    }
}

// Auto-open drawer on medium and large screens
watch(() => display.mdAndUp.value, (isMediumOrLarge) => {
    if (isMediumOrLarge) {
        drawer.value = true
        // Start in rail mode on md screens for more content space
        rail.value = display.md.value
    }
}, { immediate: true })

// Collapse to rail when transitioning from lg to md
watch(() => display.md.value, (isMedium) => {
    if (isMedium && display.mdAndUp.value) {
        rail.value = true
    }
}, { immediate: false })

// Toggle drawer - handle rail mode properly
const toggleDrawer = () => {
    if (display.mdAndUp.value) {
        // On medium and large screens, toggle rail mode instead of drawer
        rail.value = !rail.value
    } else {
        // On small screens, toggle drawer
        drawer.value = !drawer.value
    }
}

const logout = async () => {
    await authStore.logout()
    router.push({ name: 'login' })
}
</script>
