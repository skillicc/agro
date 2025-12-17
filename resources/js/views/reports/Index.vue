<template>
    <div>
        <h1 class="text-h4 mb-4">Reports</h1>

        <v-tabs v-model="tab" color="primary" class="mb-4">
            <v-tab value="monthly">Monthly Report</v-tab>
            <v-tab value="custom">Custom Report</v-tab>
            <v-tab value="profit-loss">Profit & Loss</v-tab>
        </v-tabs>

        <v-card>
            <v-card-text>
                <v-window v-model="tab">
                    <!-- Monthly Report -->
                    <v-window-item value="monthly">
                        <v-row class="mb-4">
                            <v-col cols="12" md="4">
                                <v-text-field v-model="monthlyFilters.month" label="Month (YYYY-MM)" placeholder="2025-01"></v-text-field>
                            </v-col>
                            <v-col cols="12" md="4">
                                <v-select v-model="monthlyFilters.project_id" :items="projects" item-title="name" item-value="id" label="Project" clearable></v-select>
                            </v-col>
                            <v-col cols="12" md="4">
                                <v-btn color="primary" @click="fetchMonthlyReport" :loading="loading" class="mt-2">
                                    Generate Report
                                </v-btn>
                            </v-col>
                        </v-row>

                        <v-row v-if="monthlyReport">
                            <v-col cols="12" md="3">
                                <v-card color="error" variant="tonal">
                                    <v-card-text class="text-center">
                                        <div class="text-h5">৳{{ formatNumber(monthlyReport.total_expenses) }}</div>
                                        <div>Total Expenses</div>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-card color="info" variant="tonal">
                                    <v-card-text class="text-center">
                                        <div class="text-h5">৳{{ formatNumber(monthlyReport.total_purchases) }}</div>
                                        <div>Total Purchases</div>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-card color="success" variant="tonal">
                                    <v-card-text class="text-center">
                                        <div class="text-h5">৳{{ formatNumber(monthlyReport.total_sales) }}</div>
                                        <div>Total Sales</div>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-card color="warning" variant="tonal">
                                    <v-card-text class="text-center">
                                        <div class="text-h5">৳{{ formatNumber(monthlyReport.total_salaries) }}</div>
                                        <div>Total Salaries</div>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                        </v-row>

                        <v-card v-if="monthlyReport" class="mt-4">
                            <v-card-title>Expense Breakdown</v-card-title>
                            <v-card-text>
                                <v-list>
                                    <v-list-item v-for="(expense, key) in monthlyReport.expenses" :key="key">
                                        <v-list-item-title>{{ expense.category }}</v-list-item-title>
                                        <template v-slot:append>
                                            <span class="font-weight-bold">৳{{ formatNumber(expense.total) }}</span>
                                            <span class="text-caption ml-2">({{ expense.count }} entries)</span>
                                        </template>
                                    </v-list-item>
                                </v-list>
                            </v-card-text>
                        </v-card>
                    </v-window-item>

                    <!-- Custom Report -->
                    <v-window-item value="custom">
                        <v-row class="mb-4">
                            <v-col cols="12" md="3">
                                <v-text-field v-model="customFilters.start_date" label="Start Date" type="date"></v-text-field>
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-text-field v-model="customFilters.end_date" label="End Date" type="date"></v-text-field>
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-select v-model="customFilters.project_id" :items="projects" item-title="name" item-value="id" label="Project" clearable></v-select>
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-btn color="primary" @click="fetchCustomReport" :loading="loading" class="mt-2">
                                    Generate Report
                                </v-btn>
                            </v-col>
                        </v-row>

                        <v-row v-if="customReport">
                            <v-col cols="12" md="2">
                                <v-card color="error" variant="tonal">
                                    <v-card-text class="text-center">
                                        <div class="text-h6">৳{{ formatNumber(customReport.summary?.total_expenses) }}</div>
                                        <div class="text-caption">Expenses</div>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-card color="info" variant="tonal">
                                    <v-card-text class="text-center">
                                        <div class="text-h6">৳{{ formatNumber(customReport.summary?.total_purchases) }}</div>
                                        <div class="text-caption">Purchases</div>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-card color="success" variant="tonal">
                                    <v-card-text class="text-center">
                                        <div class="text-h6">৳{{ formatNumber(customReport.summary?.total_sales) }}</div>
                                        <div class="text-caption">Sales</div>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-card color="warning" variant="tonal">
                                    <v-card-text class="text-center">
                                        <div class="text-h6">৳{{ formatNumber(customReport.summary?.total_damages) }}</div>
                                        <div class="text-caption">Damages</div>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                            <v-col cols="12" md="4">
                                <v-card :color="customReport.summary?.gross_profit >= 0 ? 'success' : 'error'" variant="tonal">
                                    <v-card-text class="text-center">
                                        <div class="text-h6">৳{{ formatNumber(customReport.summary?.gross_profit) }}</div>
                                        <div class="text-caption">Gross Profit (Sales - Purchases)</div>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                        </v-row>
                    </v-window-item>

                    <!-- Profit & Loss Report -->
                    <v-window-item value="profit-loss">
                        <v-row class="mb-4">
                            <v-col cols="12" md="3">
                                <v-text-field v-model="plFilters.start_date" label="Start Date" type="date"></v-text-field>
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-text-field v-model="plFilters.end_date" label="End Date" type="date"></v-text-field>
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-select v-model="plFilters.project_id" :items="projects" item-title="name" item-value="id" label="Project" clearable></v-select>
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-btn color="primary" @click="fetchProfitLoss" :loading="loading" class="mt-2">
                                    Generate P&L Report
                                </v-btn>
                            </v-col>
                        </v-row>

                        <div v-if="plReport">
                            <!-- Revenue Section -->
                            <v-card class="mb-4">
                                <v-card-title class="bg-success text-white">Revenue (Income)</v-card-title>
                                <v-card-text>
                                    <v-table density="compact">
                                        <tbody>
                                            <tr>
                                                <td class="font-weight-bold">Total Sales</td>
                                                <td class="text-right">৳{{ formatNumber(plReport.revenue.sales) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="font-weight-bold">Other Income</td>
                                                <td class="text-right">৳{{ formatNumber(plReport.revenue.other_income) }}</td>
                                            </tr>
                                            <tr class="bg-green-lighten-4">
                                                <td class="font-weight-bold text-h6">Total Revenue</td>
                                                <td class="text-right font-weight-bold text-h6">৳{{ formatNumber(plReport.revenue.total) }}</td>
                                            </tr>
                                        </tbody>
                                    </v-table>
                                </v-card-text>
                            </v-card>

                            <!-- Expenses Section -->
                            <v-card class="mb-4">
                                <v-card-title class="bg-error text-white">Expenses (Costs)</v-card-title>
                                <v-card-text>
                                    <v-table density="compact">
                                        <tbody>
                                            <tr>
                                                <td>Cost of Goods Sold (Purchases)</td>
                                                <td class="text-right">৳{{ formatNumber(plReport.expenses.purchases) }}</td>
                                            </tr>
                                            <tr>
                                                <td>Operating Expenses</td>
                                                <td class="text-right">৳{{ formatNumber(plReport.expenses.operating) }}</td>
                                            </tr>
                                            <tr>
                                                <td>Salaries & Wages</td>
                                                <td class="text-right">৳{{ formatNumber(plReport.expenses.salaries) }}</td>
                                            </tr>
                                            <tr>
                                                <td>Courier & Transportation</td>
                                                <td class="text-right">৳{{ formatNumber(plReport.expenses.courier_transport) }}</td>
                                            </tr>
                                            <tr>
                                                <td>Damage & Losses</td>
                                                <td class="text-right">৳{{ formatNumber(plReport.expenses.damages) }}</td>
                                            </tr>
                                            <tr class="bg-orange-lighten-4">
                                                <td class="font-weight-bold">Asset Depreciation</td>
                                                <td class="text-right font-weight-bold">৳{{ formatNumber(plReport.expenses.depreciation) }}</td>
                                            </tr>
                                            <tr class="bg-red-lighten-4">
                                                <td class="font-weight-bold text-h6">Total Expenses</td>
                                                <td class="text-right font-weight-bold text-h6">৳{{ formatNumber(plReport.expenses.total) }}</td>
                                            </tr>
                                        </tbody>
                                    </v-table>
                                </v-card-text>
                            </v-card>

                            <!-- Asset Value Reduction -->
                            <v-card class="mb-4" v-if="plReport.assets && plReport.assets.length > 0">
                                <v-card-title class="bg-warning">Asset Value Reduction (Depreciation)</v-card-title>
                                <v-card-text>
                                    <v-table density="compact">
                                        <thead>
                                            <tr>
                                                <th>Asset Name</th>
                                                <th class="text-right">Purchase Value</th>
                                                <th class="text-right">Depreciation Rate</th>
                                                <th class="text-right">Depreciation Amount</th>
                                                <th class="text-right">Current Value</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="asset in plReport.assets" :key="asset.id">
                                                <td>{{ asset.name }}</td>
                                                <td class="text-right">৳{{ formatNumber(asset.value) }}</td>
                                                <td class="text-right">{{ asset.depreciation_rate }}%</td>
                                                <td class="text-right text-warning">৳{{ formatNumber(asset.depreciation_amount) }}</td>
                                                <td class="text-right">৳{{ formatNumber(asset.current_value) }}</td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr class="bg-warning-lighten-4">
                                                <td colspan="3" class="font-weight-bold">Total Asset Depreciation</td>
                                                <td class="text-right font-weight-bold">৳{{ formatNumber(plReport.expenses.depreciation) }}</td>
                                                <td class="text-right font-weight-bold">৳{{ formatNumber(plReport.total_asset_current_value) }}</td>
                                            </tr>
                                        </tfoot>
                                    </v-table>
                                </v-card-text>
                            </v-card>

                            <!-- Profit/Loss Summary -->
                            <v-row>
                                <v-col cols="12" md="4">
                                    <v-card color="success" variant="tonal">
                                        <v-card-text class="text-center">
                                            <div class="text-h4">৳{{ formatNumber(plReport.gross_profit) }}</div>
                                            <div class="text-body-1">Gross Profit</div>
                                            <div class="text-caption">(Sales - Purchases)</div>
                                        </v-card-text>
                                    </v-card>
                                </v-col>
                                <v-col cols="12" md="4">
                                    <v-card color="info" variant="tonal">
                                        <v-card-text class="text-center">
                                            <div class="text-h4">৳{{ formatNumber(plReport.operating_profit) }}</div>
                                            <div class="text-body-1">Operating Profit</div>
                                            <div class="text-caption">(Gross Profit - Operating Expenses)</div>
                                        </v-card-text>
                                    </v-card>
                                </v-col>
                                <v-col cols="12" md="4">
                                    <v-card :color="plReport.net_profit >= 0 ? 'success' : 'error'">
                                        <v-card-text class="text-center text-white">
                                            <div class="text-h4">৳{{ formatNumber(plReport.net_profit) }}</div>
                                            <div class="text-body-1">{{ plReport.net_profit >= 0 ? 'Net Profit' : 'Net Loss' }}</div>
                                            <div class="text-caption">(Revenue - All Expenses - Depreciation)</div>
                                        </v-card-text>
                                    </v-card>
                                </v-col>
                            </v-row>

                            <!-- Expense Breakdown by Category -->
                            <v-card class="mt-4">
                                <v-card-title>Expense Breakdown by Category</v-card-title>
                                <v-card-text>
                                    <v-row>
                                        <v-col v-for="(expense, key) in plReport.expense_breakdown" :key="key" cols="12" md="4">
                                            <v-list-item>
                                                <v-list-item-title>{{ expense.category }}</v-list-item-title>
                                                <template v-slot:append>
                                                    <span class="font-weight-bold">৳{{ formatNumber(expense.total) }}</span>
                                                </template>
                                            </v-list-item>
                                        </v-col>
                                    </v-row>
                                </v-card-text>
                            </v-card>
                        </div>
                    </v-window-item>
                </v-window>
            </v-card-text>
        </v-card>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '../../services/api'

const tab = ref('monthly')
const loading = ref(false)
const projects = ref([])
const monthlyReport = ref(null)
const customReport = ref(null)
const plReport = ref(null)

const monthlyFilters = reactive({ month: new Date().toISOString().slice(0, 7), project_id: null })
const customFilters = reactive({ start_date: '', end_date: '', project_id: null })
const plFilters = reactive({ start_date: '', end_date: '', project_id: null })

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD')

const fetchProjects = async () => {
    try {
        const response = await api.get('/projects')
        projects.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
}

const fetchMonthlyReport = async () => {
    loading.value = true
    try {
        const params = new URLSearchParams()
        params.append('month', monthlyFilters.month)
        if (monthlyFilters.project_id) params.append('project_id', monthlyFilters.project_id)
        const response = await api.get(`/reports/monthly?${params}`)
        monthlyReport.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
    loading.value = false
}

const fetchCustomReport = async () => {
    loading.value = true
    try {
        const params = new URLSearchParams()
        params.append('start_date', customFilters.start_date)
        params.append('end_date', customFilters.end_date)
        if (customFilters.project_id) params.append('project_id', customFilters.project_id)
        const response = await api.get(`/reports/custom?${params}`)
        customReport.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
    loading.value = false
}

const fetchProfitLoss = async () => {
    loading.value = true
    try {
        const params = new URLSearchParams()
        if (plFilters.start_date) params.append('start_date', plFilters.start_date)
        if (plFilters.end_date) params.append('end_date', plFilters.end_date)
        if (plFilters.project_id) params.append('project_id', plFilters.project_id)
        const response = await api.get(`/reports/profit-loss?${params}`)
        plReport.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
    loading.value = false
}

onMounted(() => {
    fetchProjects()
})
</script>
