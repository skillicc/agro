<template>
    <div v-if="project">
        <div class="d-flex justify-space-between align-center mb-4">
            <div>
                <v-btn icon variant="text" @click="$router.back()">
                    <v-icon>mdi-arrow-left</v-icon>
                </v-btn>
                <span class="text-h4 ml-2">{{ project.name }}</span>
                <v-chip class="ml-2" :color="getProjectColor(project.type)">
                    {{ project.type }}
                </v-chip>
            </div>
        </div>

        <!-- Summary Cards -->
        <v-row>
            <v-col cols="12" sm="6" md="3">
                <v-card color="error" variant="tonal">
                    <v-card-text class="text-center">
                        <div class="text-h5">৳{{ formatNumber(summary.total_expenses) }}</div>
                        <div>Total Expenses</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" sm="6" md="3">
                <v-card color="info" variant="tonal">
                    <v-card-text class="text-center">
                        <div class="text-h5">৳{{ formatNumber(summary.total_purchases) }}</div>
                        <div>Total Purchases</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" sm="6" md="3">
                <v-card color="success" variant="tonal">
                    <v-card-text class="text-center">
                        <div class="text-h5">৳{{ formatNumber(summary.total_sales) }}</div>
                        <div>Total Sales</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" sm="6" md="3">
                <v-card color="warning" variant="tonal">
                    <v-card-text class="text-center">
                        <div class="text-h5">৳{{ formatNumber(summary.total_salaries) }}</div>
                        <div>Total Salaries</div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <!-- Tabs for different sections -->
        <v-card class="mt-4">
            <v-tabs v-model="tab" color="primary">
                <v-tab value="expenses">Expenses</v-tab>
                <v-tab value="purchases">Purchases</v-tab>
                <v-tab value="sales">Sales</v-tab>
                <v-tab value="assets">Assets</v-tab>
                <v-tab value="employees">Employees</v-tab>
                <v-tab value="attendance" v-if="isAdministration">Attendance</v-tab>
                <v-tab value="damages" v-if="project.type === 'nursery' || project.type === 'shop'">Damages</v-tab>
                <v-tab value="productions" v-if="project.type === 'nursery'">Productions</v-tab>
            </v-tabs>

            <v-card-text>
                <v-window v-model="tab">
                    <v-window-item value="expenses">
                        <ExpensesList :projectId="project.id" />
                    </v-window-item>
                    <v-window-item value="purchases">
                        <PurchasesList :projectId="project.id" />
                    </v-window-item>
                    <v-window-item value="sales">
                        <SalesList :projectId="project.id" />
                    </v-window-item>
                    <v-window-item value="assets">
                        <AssetsList :projectId="project.id" />
                    </v-window-item>
                    <v-window-item value="employees">
                        <EmployeesList :projectId="project.id" />
                    </v-window-item>
                    <v-window-item value="attendance" v-if="isAdministration">
                        <AdminAttendance :projectId="project.id" />
                    </v-window-item>
                    <v-window-item value="damages">
                        <DamagesList :projectId="project.id" />
                    </v-window-item>
                    <v-window-item value="productions">
                        <ProductionsList :projectId="project.id" />
                    </v-window-item>
                </v-window>
            </v-card-text>
        </v-card>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../../services/api'
import ExpensesList from '../../components/ExpensesList.vue'
import PurchasesList from '../../components/PurchasesList.vue'
import SalesList from '../../components/SalesList.vue'
import AssetsList from '../../components/AssetsList.vue'
import EmployeesList from '../../components/EmployeesList.vue'
import DamagesList from '../../components/DamagesList.vue'
import ProductionsList from '../../components/ProductionsList.vue'
import AdminAttendance from '../../components/AdminAttendance.vue'

const route = useRoute()
const project = ref(null)
const summary = ref({})
const tab = ref('expenses')

// Check if this is the Administration project
const isAdministration = computed(() => {
    return project.value?.name === 'Administration'
})

const getProjectColor = (type) => {
    const colors = { field: 'green', nursery: 'teal', shop: 'blue' }
    return colors[type] || 'grey'
}

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD')

const fetchProject = async () => {
    try {
        const response = await api.get(`/projects/${route.params.id}`)
        project.value = response.data
    } catch (error) {
        console.error('Error fetching project:', error)
    }
}

const fetchSummary = async () => {
    try {
        const response = await api.get(`/projects/${route.params.id}/summary`)
        summary.value = response.data
    } catch (error) {
        console.error('Error fetching summary:', error)
    }
}

onMounted(() => {
    fetchProject()
    fetchSummary()
})
</script>
