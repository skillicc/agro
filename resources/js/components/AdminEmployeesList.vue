<template>
    <div>
        <div class="d-flex justify-space-between align-center mb-4">
            <h3 class="text-h6">Administration Employees</h3>
        </div>

        <v-data-table
            :headers="headers"
            :items="employees"
            :loading="loading"
            density="compact"
            :items-per-page="10"
        >
            <template v-slot:item.sl="{ index }">
                {{ index + 1 }}
            </template>
            <template v-slot:item.name="{ item }">
                <div>
                    <strong>{{ item.name }}</strong>
                    <div class="text-caption text-grey">{{ item.position }}</div>
                </div>
            </template>
            <template v-slot:item.salary="{ item }">
                <span class="font-weight-medium">৳{{ formatNumber(item.salary) }}</span>
            </template>
            <template v-slot:item.el_balance="{ item }">
                <v-chip :color="item.el_balance > 0 ? 'success' : 'grey'" size="small">
                    {{ Number(item.el_balance || 0).toFixed(1) }}
                </v-chip>
            </template>
            <template v-slot:item.absent_count="{ item }">
                <v-chip :color="item.absent_count > 0 ? 'error' : 'success'" size="small">
                    {{ item.absent_count || 0 }}
                </v-chip>
            </template>
            <template v-slot:item.advance_balance="{ item }">
                <v-chip :color="item.advance_balance > 0 ? 'warning' : 'grey'" size="small">
                    ৳{{ formatNumber(item.advance_balance || 0) }}
                </v-chip>
            </template>
            <template v-slot:item.paid_this_month="{ item }">
                <v-chip :color="item.paid_this_month > 0 ? 'info' : 'grey'" size="small">
                    ৳{{ formatNumber(item.paid_this_month || 0) }}
                </v-chip>
            </template>
            <template v-slot:item.due="{ item }">
                <v-chip :color="item.due > 0 ? 'error' : 'success'" size="small">
                    ৳{{ formatNumber(item.due || 0) }}
                </v-chip>
            </template>
            <template v-slot:item.is_active="{ item }">
                <v-chip :color="item.is_active ? 'success' : 'grey'" size="x-small">
                    {{ item.is_active ? 'Active' : 'Inactive' }}
                </v-chip>
            </template>
        </v-data-table>

        <!-- Summary Cards -->
        <v-row class="mt-4">
            <v-col cols="6" sm="4" md="2">
                <v-card color="primary" variant="tonal">
                    <v-card-text class="pa-2 text-center">
                        <div class="text-h6">৳{{ formatNumber(totalSalary) }}</div>
                        <div class="text-caption">Total Salary</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" sm="4" md="2">
                <v-card color="success" variant="tonal">
                    <v-card-text class="pa-2 text-center">
                        <div class="text-h6">{{ totalEL.toFixed(1) }}</div>
                        <div class="text-caption">Total EL</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" sm="4" md="2">
                <v-card color="warning" variant="tonal">
                    <v-card-text class="pa-2 text-center">
                        <div class="text-h6">৳{{ formatNumber(totalAdvance) }}</div>
                        <div class="text-caption">Total Advance</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" sm="4" md="2">
                <v-card color="info" variant="tonal">
                    <v-card-text class="pa-2 text-center">
                        <div class="text-h6">৳{{ formatNumber(totalPaid) }}</div>
                        <div class="text-caption">Paid (This Month)</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" sm="4" md="2">
                <v-card color="error" variant="tonal">
                    <v-card-text class="pa-2 text-center">
                        <div class="text-h6">৳{{ formatNumber(totalDue) }}</div>
                        <div class="text-caption">Total Due</div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../services/api'

const props = defineProps({
    projectId: { type: [Number, String], required: true }
})

const employees = ref([])
const loading = ref(false)

const headers = [
    { title: 'SL', key: 'sl', width: '50px', sortable: false },
    { title: 'Name', key: 'name' },
    { title: 'Salary', key: 'salary', align: 'end' },
    { title: 'EL', key: 'el_balance', align: 'center' },
    { title: 'Absent', key: 'absent_count', align: 'center' },
    { title: 'Advance', key: 'advance_balance', align: 'end' },
    { title: 'Paid', key: 'paid_this_month', align: 'end' },
    { title: 'Due', key: 'due', align: 'end' },
    { title: 'Status', key: 'is_active', align: 'center' },
]

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD')

// Computed totals
const totalSalary = computed(() => employees.value.reduce((sum, e) => sum + Number(e.salary || 0), 0))
const totalEL = computed(() => employees.value.reduce((sum, e) => sum + Number(e.el_balance || 0), 0))
const totalAdvance = computed(() => employees.value.reduce((sum, e) => sum + Number(e.advance_balance || 0), 0))
const totalPaid = computed(() => employees.value.reduce((sum, e) => sum + Number(e.paid_this_month || 0), 0))
const totalDue = computed(() => employees.value.reduce((sum, e) => sum + Number(e.due || 0), 0))

const fetchEmployees = async () => {
    loading.value = true
    try {
        const response = await api.get('/employees/admin')
        employees.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
    loading.value = false
}

onMounted(() => {
    fetchEmployees()
})
</script>
