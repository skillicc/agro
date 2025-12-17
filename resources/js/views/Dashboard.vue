<template>
    <div>
        <h1 class="text-h4 text-sm-h3 mb-4">Dashboard</h1>

        <v-row>
            <!-- Summary Cards -->
            <v-col cols="12" sm="6" md="6" lg="3" xl="3">
                <v-card color="primary" variant="tonal" class="h-100">
                    <v-card-text class="pa-3 pa-sm-4">
                        <div class="d-flex align-center">
                            <v-icon :size="$vuetify.display.smAndDown ? 36 : 48" class="mr-3 mr-sm-4">mdi-folder-multiple</v-icon>
                            <div>
                                <div class="text-h5 text-sm-h4">{{ dashboard.total_projects }}</div>
                                <div class="text-body-2 text-sm-subtitle-1">Projects</div>
                            </div>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>

            <v-col cols="12" sm="6" md="6" lg="3" xl="3">
                <v-card color="success" variant="tonal" class="h-100">
                    <v-card-text class="pa-3 pa-sm-4">
                        <div class="d-flex align-center">
                            <v-icon :size="$vuetify.display.smAndDown ? 36 : 48" class="mr-3 mr-sm-4">mdi-cart-arrow-up</v-icon>
                            <div>
                                <div class="text-h6 text-sm-h5 text-md-h4">৳{{ formatNumber(dashboard.monthly_sales) }}</div>
                                <div class="text-body-2 text-sm-subtitle-1">Monthly Sales</div>
                            </div>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>

            <v-col cols="12" sm="6" md="6" lg="3" xl="3">
                <v-card color="info" variant="tonal" class="h-100">
                    <v-card-text class="pa-3 pa-sm-4">
                        <div class="d-flex align-center">
                            <v-icon :size="$vuetify.display.smAndDown ? 36 : 48" class="mr-3 mr-sm-4">mdi-cart-arrow-down</v-icon>
                            <div>
                                <div class="text-h6 text-sm-h5 text-md-h4">৳{{ formatNumber(dashboard.monthly_purchases) }}</div>
                                <div class="text-body-2 text-sm-subtitle-1">Monthly Purchases</div>
                            </div>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>

            <v-col cols="12" sm="6" md="6" lg="3" xl="3">
                <v-card color="warning" variant="tonal" class="h-100">
                    <v-card-text class="pa-3 pa-sm-4">
                        <div class="d-flex align-center">
                            <v-icon :size="$vuetify.display.smAndDown ? 36 : 48" class="mr-3 mr-sm-4">mdi-cash-minus</v-icon>
                            <div>
                                <div class="text-h6 text-sm-h5 text-md-h4">৳{{ formatNumber(dashboard.monthly_expenses) }}</div>
                                <div class="text-body-2 text-sm-subtitle-1">Monthly Expenses</div>
                            </div>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <v-row class="mt-2 mt-sm-4">
            <!-- Due Summary -->
            <v-col cols="12" md="6" lg="6" xl="6">
                <v-card class="h-100">
                    <v-card-title class="text-subtitle-1 text-sm-h6">
                        <v-icon class="mr-2" size="small">mdi-alert-circle</v-icon>
                        Due Summary
                    </v-card-title>
                    <v-card-text class="pa-2 pa-sm-4">
                        <v-list density="compact">
                            <v-list-item>
                                <template v-slot:prepend>
                                    <v-icon color="error" :size="$vuetify.display.xs ? 'small' : 'default'">mdi-truck-delivery</v-icon>
                                </template>
                                <v-list-item-title class="text-body-2 text-sm-body-1">Supplier Due</v-list-item-title>
                                <template v-slot:append>
                                    <span class="text-error font-weight-bold text-body-2 text-sm-body-1">৳{{ formatNumber(dashboard.total_supplier_due) }}</span>
                                </template>
                            </v-list-item>
                            <v-list-item>
                                <template v-slot:prepend>
                                    <v-icon color="warning" :size="$vuetify.display.xs ? 'small' : 'default'">mdi-account-group</v-icon>
                                </template>
                                <v-list-item-title class="text-body-2 text-sm-body-1">Customer Due</v-list-item-title>
                                <template v-slot:append>
                                    <span class="text-warning font-weight-bold text-body-2 text-sm-body-1">৳{{ formatNumber(dashboard.total_customer_due) }}</span>
                                </template>
                            </v-list-item>
                            <v-list-item>
                                <template v-slot:prepend>
                                    <v-icon color="info" :size="$vuetify.display.xs ? 'small' : 'default'">mdi-package-variant-closed-remove</v-icon>
                                </template>
                                <v-list-item-title class="text-body-2 text-sm-body-1">Low Stock Products</v-list-item-title>
                                <template v-slot:append>
                                    <v-chip color="info" size="small">{{ dashboard.low_stock_products }}</v-chip>
                                </template>
                            </v-list-item>
                        </v-list>
                    </v-card-text>
                </v-card>
            </v-col>

            <!-- Quick Stats -->
            <v-col cols="12" md="6" lg="6" xl="6">
                <v-card class="h-100">
                    <v-card-title class="text-subtitle-1 text-sm-h6">
                        <v-icon class="mr-2" size="small">mdi-chart-box</v-icon>
                        Quick Stats
                    </v-card-title>
                    <v-card-text class="pa-2 pa-sm-4">
                        <v-list density="compact">
                            <v-list-item>
                                <template v-slot:prepend>
                                    <v-icon color="primary" :size="$vuetify.display.xs ? 'small' : 'default'">mdi-truck-delivery</v-icon>
                                </template>
                                <v-list-item-title class="text-body-2 text-sm-body-1">Total Suppliers</v-list-item-title>
                                <template v-slot:append>
                                    <span class="font-weight-bold">{{ dashboard.total_suppliers }}</span>
                                </template>
                            </v-list-item>
                            <v-list-item>
                                <template v-slot:prepend>
                                    <v-icon color="success" :size="$vuetify.display.xs ? 'small' : 'default'">mdi-account-group</v-icon>
                                </template>
                                <v-list-item-title class="text-body-2 text-sm-body-1">Total Customers</v-list-item-title>
                                <template v-slot:append>
                                    <span class="font-weight-bold">{{ dashboard.total_customers }}</span>
                                </template>
                            </v-list-item>
                            <v-list-item>
                                <template v-slot:prepend>
                                    <v-icon color="secondary" :size="$vuetify.display.xs ? 'small' : 'default'">mdi-package-variant</v-icon>
                                </template>
                                <v-list-item-title class="text-body-2 text-sm-body-1">Total Products</v-list-item-title>
                                <template v-slot:append>
                                    <span class="font-weight-bold">{{ dashboard.total_products }}</span>
                                </template>
                            </v-list-item>
                        </v-list>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <v-row class="mt-2 mt-sm-4">
            <!-- Recent Sales -->
            <v-col cols="12" md="6" lg="6" xl="6">
                <v-card class="h-100">
                    <v-card-title class="text-subtitle-1 text-sm-h6">
                        <v-icon class="mr-2" size="small">mdi-cart-arrow-up</v-icon>
                        Recent Sales
                    </v-card-title>
                    <v-card-text class="pa-2 pa-sm-4">
                        <v-list v-if="dashboard.recent_sales?.length" density="compact">
                            <v-list-item v-for="sale in dashboard.recent_sales" :key="sale.id" class="px-1 px-sm-2">
                                <v-list-item-title class="text-body-2 text-sm-body-1">{{ sale.invoice_no }}</v-list-item-title>
                                <v-list-item-subtitle class="text-caption text-sm-body-2">
                                    {{ sale.customer?.name || 'Walk-in' }} - {{ sale.project?.name }}
                                </v-list-item-subtitle>
                                <template v-slot:append>
                                    <span class="text-success text-body-2 text-sm-body-1">৳{{ formatNumber(sale.total) }}</span>
                                </template>
                            </v-list-item>
                        </v-list>
                        <div v-else class="text-center text-grey py-4">No recent sales</div>
                    </v-card-text>
                </v-card>
            </v-col>

            <!-- Recent Purchases -->
            <v-col cols="12" md="6" lg="6" xl="6">
                <v-card class="h-100">
                    <v-card-title class="text-subtitle-1 text-sm-h6">
                        <v-icon class="mr-2" size="small">mdi-cart-arrow-down</v-icon>
                        Recent Purchases
                    </v-card-title>
                    <v-card-text class="pa-2 pa-sm-4">
                        <v-list v-if="dashboard.recent_purchases?.length" density="compact">
                            <v-list-item v-for="purchase in dashboard.recent_purchases" :key="purchase.id" class="px-1 px-sm-2">
                                <v-list-item-title class="text-body-2 text-sm-body-1">{{ purchase.invoice_no }}</v-list-item-title>
                                <v-list-item-subtitle class="text-caption text-sm-body-2">
                                    {{ purchase.supplier?.name || 'Unknown' }} - {{ purchase.project?.name }}
                                </v-list-item-subtitle>
                                <template v-slot:append>
                                    <span class="text-info text-body-2 text-sm-body-1">৳{{ formatNumber(purchase.total) }}</span>
                                </template>
                            </v-list-item>
                        </v-list>
                        <div v-else class="text-center text-grey py-4">No recent purchases</div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../services/api'

const dashboard = ref({
    total_projects: 0,
    total_suppliers: 0,
    total_customers: 0,
    total_products: 0,
    low_stock_products: 0,
    monthly_sales: 0,
    monthly_purchases: 0,
    monthly_expenses: 0,
    total_supplier_due: 0,
    total_customer_due: 0,
    recent_sales: [],
    recent_purchases: [],
})

const loading = ref(false)

const formatNumber = (num) => {
    return Number(num || 0).toLocaleString('en-BD')
}

const fetchDashboard = async () => {
    loading.value = true
    try {
        const response = await api.get('/dashboard')
        dashboard.value = response.data
    } catch (error) {
        console.error('Error fetching dashboard:', error)
    }
    loading.value = false
}

onMounted(() => {
    fetchDashboard()
})
</script>

<style scoped>
.h-100 {
    height: 100%;
}
</style>
