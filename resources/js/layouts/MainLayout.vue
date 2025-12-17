<template>
    <v-app>
        <!-- App Bar -->
        <v-app-bar color="primary" :density="$vuetify.display.smAndDown ? 'compact' : 'default'">
            <v-app-bar-nav-icon @click="toggleDrawer"></v-app-bar-nav-icon>
            <v-toolbar-title class="text-body-1 text-sm-h6">Bangalio Agro</v-toolbar-title>
            <v-spacer></v-spacer>

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
            :temporary="$vuetify.display.mdAndDown"
            :permanent="$vuetify.display.lgAndUp"
            :rail="$vuetify.display.lgAndUp && rail"
            :width="$vuetify.display.xs ? 280 : 300"
        >
            <!-- Header when expanded -->
            <v-list-item
                v-if="!rail"
                prepend-avatar="https://ui-avatars.com/api/?name=Bangalio+Agro&background=4CAF50&color=fff"
                title="Bangalio Agro"
                subtitle="Inventory System"
                class="py-2"
            >
                <template v-slot:append v-if="$vuetify.display.lgAndUp">
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
import { ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useDisplay } from 'vuetify'
import { useAuthStore } from '../stores/auth'

const drawer = ref(false)
const rail = ref(false)
const router = useRouter()
const authStore = useAuthStore()
const display = useDisplay()

// Auto-open drawer on large screens
watch(() => display.lgAndUp.value, (isLarge) => {
    if (isLarge) {
        drawer.value = true
        rail.value = false // Reset rail when screen size changes
    }
}, { immediate: true })

// Toggle drawer - handle rail mode properly
const toggleDrawer = () => {
    if (display.lgAndUp.value) {
        // On large screens, toggle rail mode instead of drawer
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
