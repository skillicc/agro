<template>
    <div>
        <h1 class="text-h4 text-sm-h3 mb-4">Dashboard</h1>

        <!-- Main Summary Cards -->
        <v-row>
            <v-col cols="6" sm="6" md="4" lg="2">
                <v-card color="primary" variant="tonal" class="h-100">
                    <v-card-text class="pa-2 pa-sm-3 text-center">
                        <v-icon size="32" class="mb-1">mdi-folder-multiple</v-icon>
                        <div class="text-h6">{{ dashboard.total_projects }}</div>
                        <div class="text-caption">Projects</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" sm="6" md="4" lg="2">
                <v-card color="success" variant="tonal" class="h-100">
                    <v-card-text class="pa-2 pa-sm-3 text-center">
                        <v-icon size="32" class="mb-1">mdi-cart-arrow-up</v-icon>
                        <div class="text-h6">৳{{ formatNumber(dashboard.monthly_sales) }}</div>
                        <div class="text-caption">Monthly Sales</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" sm="6" md="4" lg="2">
                <v-card color="info" variant="tonal" class="h-100">
                    <v-card-text class="pa-2 pa-sm-3 text-center">
                        <v-icon size="32" class="mb-1">mdi-cart-arrow-down</v-icon>
                        <div class="text-h6">৳{{ formatNumber(dashboard.monthly_purchases) }}</div>
                        <div class="text-caption">Monthly Purchases</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" sm="6" md="4" lg="2">
                <v-card color="warning" variant="tonal" class="h-100">
                    <v-card-text class="pa-2 pa-sm-3 text-center">
                        <v-icon size="32" class="mb-1">mdi-cash-minus</v-icon>
                        <div class="text-h6">৳{{ formatNumber(dashboard.monthly_expenses) }}</div>
                        <div class="text-caption">Monthly Expenses</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" sm="6" md="4" lg="2">
                <v-card color="purple" variant="tonal" class="h-100">
                    <v-card-text class="pa-2 pa-sm-3 text-center">
                        <v-icon size="32" class="mb-1">mdi-cash</v-icon>
                        <div class="text-h6">৳{{ formatNumber(dashboard.monthly_salaries) }}</div>
                        <div class="text-caption">Monthly Salaries</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" sm="6" md="4" lg="2">
                <v-card :color="dashboard.monthly_profit >= 0 ? 'green' : 'red'" variant="tonal" class="h-100">
                    <v-card-text class="pa-2 pa-sm-3 text-center">
                        <v-icon size="32" class="mb-1">{{ dashboard.monthly_profit >= 0 ? 'mdi-trending-up' : 'mdi-trending-down' }}</v-icon>
                        <div class="text-h6">৳{{ formatNumber(Math.abs(dashboard.monthly_profit)) }}</div>
                        <div class="text-caption">{{ dashboard.monthly_profit >= 0 ? 'Monthly Profit' : 'Monthly Loss' }}</div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <!-- Secondary Stats Row -->
        <v-row class="mt-2">
            <v-col cols="6" sm="4" md="3" lg="2">
                <v-card variant="outlined" class="h-100">
                    <v-card-text class="pa-2 pa-sm-3 text-center">
                        <v-icon size="28" color="primary" class="mb-1">mdi-account-hard-hat</v-icon>
                        <div class="text-subtitle-1 font-weight-bold">{{ dashboard.active_employees }}/{{ dashboard.total_employees }}</div>
                        <div class="text-caption">Employees</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" sm="4" md="3" lg="2">
                <v-card variant="outlined" class="h-100">
                    <v-card-text class="pa-2 pa-sm-3 text-center">
                        <v-icon size="28" color="success" class="mb-1">mdi-package-variant</v-icon>
                        <div class="text-subtitle-1 font-weight-bold">৳{{ formatNumber(dashboard.total_stock_value) }}</div>
                        <div class="text-caption">Stock Value</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" sm="4" md="3" lg="2">
                <v-card variant="outlined" class="h-100">
                    <v-card-text class="pa-2 pa-sm-3 text-center">
                        <v-icon size="28" color="info" class="mb-1">mdi-cash-plus</v-icon>
                        <div class="text-subtitle-1 font-weight-bold">৳{{ formatNumber(dashboard.total_investment) }}</div>
                        <div class="text-caption">Total Investment</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" sm="4" md="3" lg="2">
                <v-card variant="outlined" class="h-100">
                    <v-card-text class="pa-2 pa-sm-3 text-center">
                        <v-icon size="28" color="warning" class="mb-1">mdi-bank</v-icon>
                        <div class="text-subtitle-1 font-weight-bold">৳{{ formatNumber(dashboard.total_loan) }}</div>
                        <div class="text-caption">Total Loan</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" sm="4" md="3" lg="2">
                <v-card variant="outlined" class="h-100">
                    <v-card-text class="pa-2 pa-sm-3 text-center">
                        <v-icon size="28" color="error" class="mb-1">mdi-cash-fast</v-icon>
                        <div class="text-subtitle-1 font-weight-bold">৳{{ formatNumber(dashboard.monthly_advances) }}</div>
                        <div class="text-caption">Monthly Advances</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" sm="4" md="3" lg="2">
                <v-card variant="outlined" class="h-100">
                    <v-card-text class="pa-2 pa-sm-3 text-center">
                        <v-icon size="28" color="secondary" class="mb-1">mdi-package-variant-closed</v-icon>
                        <div class="text-subtitle-1 font-weight-bold">{{ dashboard.total_products }}</div>
                        <div class="text-caption">Products</div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <v-row class="mt-2 mt-sm-4">
            <!-- Due Summary & Quick Stats -->
            <v-col cols="12" md="6" lg="4">
                <v-card class="h-100">
                    <v-card-title class="text-subtitle-1 text-sm-h6 pb-0">
                        <v-icon class="mr-2" size="small">mdi-alert-circle</v-icon>
                        Due & Alerts
                    </v-card-title>
                    <v-card-text class="pa-2 pa-sm-4">
                        <v-list density="compact">
                            <v-list-item>
                                <template v-slot:prepend>
                                    <v-icon color="error" size="small">mdi-truck-delivery</v-icon>
                                </template>
                                <v-list-item-title class="text-body-2">Supplier Due</v-list-item-title>
                                <template v-slot:append>
                                    <span class="text-error font-weight-bold text-body-2">৳{{ formatNumber(dashboard.total_supplier_due) }}</span>
                                </template>
                            </v-list-item>
                            <v-list-item>
                                <template v-slot:prepend>
                                    <v-icon color="warning" size="small">mdi-account-group</v-icon>
                                </template>
                                <v-list-item-title class="text-body-2">Customer Due</v-list-item-title>
                                <template v-slot:append>
                                    <span class="text-warning font-weight-bold text-body-2">৳{{ formatNumber(dashboard.total_customer_due) }}</span>
                                </template>
                            </v-list-item>
                            <v-list-item>
                                <template v-slot:prepend>
                                    <v-icon color="info" size="small">mdi-package-variant-closed-remove</v-icon>
                                </template>
                                <v-list-item-title class="text-body-2">Low Stock Products</v-list-item-title>
                                <template v-slot:append>
                                    <v-chip color="info" size="x-small">{{ dashboard.low_stock_products }}</v-chip>
                                </template>
                            </v-list-item>
                            <v-divider class="my-2"></v-divider>
                            <v-list-item>
                                <template v-slot:prepend>
                                    <v-icon color="primary" size="small">mdi-truck-delivery</v-icon>
                                </template>
                                <v-list-item-title class="text-body-2">Total Suppliers</v-list-item-title>
                                <template v-slot:append>
                                    <span class="font-weight-bold text-body-2">{{ dashboard.total_suppliers }}</span>
                                </template>
                            </v-list-item>
                            <v-list-item>
                                <template v-slot:prepend>
                                    <v-icon color="success" size="small">mdi-account-group</v-icon>
                                </template>
                                <v-list-item-title class="text-body-2">Total Customers</v-list-item-title>
                                <template v-slot:append>
                                    <span class="font-weight-bold text-body-2">{{ dashboard.total_customers }}</span>
                                </template>
                            </v-list-item>
                        </v-list>
                    </v-card-text>
                </v-card>
            </v-col>

            <!-- Top Selling Products -->
            <v-col cols="12" md="6" lg="4">
                <v-card class="h-100">
                    <v-card-title class="text-subtitle-1 text-sm-h6 pb-0">
                        <v-icon class="mr-2" size="small">mdi-trophy</v-icon>
                        Top Selling Products (This Month)
                    </v-card-title>
                    <v-card-text class="pa-2 pa-sm-4">
                        <v-list v-if="dashboard.top_products?.length" density="compact">
                            <v-list-item v-for="(product, index) in dashboard.top_products" :key="index" class="px-1">
                                <template v-slot:prepend>
                                    <v-avatar size="24" :color="['gold', 'silver', '#CD7F32', 'grey', 'grey'][index]" class="mr-2">
                                        <span class="text-caption text-white">{{ index + 1 }}</span>
                                    </v-avatar>
                                </template>
                                <v-list-item-title class="text-body-2">{{ product.name }}</v-list-item-title>
                                <template v-slot:append>
                                    <div class="text-right">
                                        <div class="text-body-2 font-weight-bold">{{ product.total_qty }} pcs</div>
                                        <div class="text-caption text-grey">৳{{ formatNumber(product.total_amount) }}</div>
                                    </div>
                                </template>
                            </v-list-item>
                        </v-list>
                        <div v-else class="text-center text-grey py-4">No sales this month</div>
                    </v-card-text>
                </v-card>
            </v-col>

            <!-- Recent Expenses -->
            <v-col cols="12" md="6" lg="4">
                <v-card class="h-100">
                    <v-card-title class="text-subtitle-1 text-sm-h6 pb-0">
                        <v-icon class="mr-2" size="small">mdi-cash-minus</v-icon>
                        Recent Expenses
                    </v-card-title>
                    <v-card-text class="pa-2 pa-sm-4">
                        <v-list v-if="dashboard.recent_expenses?.length" density="compact">
                            <v-list-item v-for="expense in dashboard.recent_expenses" :key="expense.id" class="px-1">
                                <v-list-item-title class="text-body-2">{{ expense.description || expense.category?.name }}</v-list-item-title>
                                <v-list-item-subtitle class="text-caption">
                                    {{ expense.project?.name }} - {{ expense.date }}
                                </v-list-item-subtitle>
                                <template v-slot:append>
                                    <span class="text-warning text-body-2 font-weight-bold">৳{{ formatNumber(expense.amount) }}</span>
                                </template>
                            </v-list-item>
                        </v-list>
                        <div v-else class="text-center text-grey py-4">No recent expenses</div>
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
    monthly_salaries: 0,
    monthly_advances: 0,
    monthly_profit: 0,
    total_employees: 0,
    active_employees: 0,
    total_stock_value: 0,
    total_investment: 0,
    total_loan: 0,
    total_supplier_due: 0,
    total_customer_due: 0,
    recent_sales: [],
    recent_purchases: [],
    recent_expenses: [],
    top_products: [],
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
