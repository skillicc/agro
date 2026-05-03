<template>
    <div>
        <div class="d-flex flex-wrap justify-space-between align-center mb-4 ga-2">
            <div>
                <h1 class="text-h5 text-sm-h4">Simple Salary Sheet</h1>
                <div class="text-body-2 text-medium-emphasis">
                    {{ employee?.name || 'Employee' }}
                </div>
            </div>
            <div class="d-flex ga-2">
                <v-btn color="primary" variant="outlined" @click="saveAllocations">Save Allocation</v-btn>
                <v-btn variant="outlined" @click="resetAllocations">Reset Allocation</v-btn>
                <v-btn color="primary" @click="goBack">Back</v-btn>
            </div>
        </div>

        <v-row class="mb-4">
            <v-col cols="12" sm="4">
                <v-card color="info" variant="tonal">
                    <v-card-text class="text-center">
                        <div class="text-h6">৳{{ formatNumber(totalGiven) }}</div>
                        <div class="text-caption">Total Given (Incl. Advance)</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" sm="4">
                <v-card color="success" variant="tonal">
                    <v-card-text class="text-center">
                        <div class="text-h6">৳{{ formatNumber(totalAllocated) }}</div>
                        <div class="text-caption">Allocated By You</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" sm="4">
                <v-card :color="remainingAmount >= 0 ? 'warning' : 'error'" variant="tonal">
                    <v-card-text class="text-center">
                        <div class="text-h6">৳{{ formatNumber(remainingAmount) }}</div>
                        <div class="text-caption">Remaining Usable Amount</div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <v-alert type="info" variant="tonal" class="mb-4" density="comfortable">
            মাসভিত্তিক কত পেইড দেখাবেন সেটা আপনি নিজে নির্ধারণ করতে পারবেন। কোনো মাসের Due Salary পূর্ণ allocate করলে মাসটি Paid দেখাবে।
        </v-alert>

        <v-card>
            <v-card-text>
                <v-table density="compact">
                    <thead>
                        <tr>
                            <th>Month/Year</th>
                            <th>Due Salary</th>
                            <th>Your Paid Allocation</th>
                            <th>Status</th>
                            <th>Remaining Due</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rows" :key="row.month">
                            <td>{{ formatMonthLong(row.month) }}</td>
                            <td>৳{{ formatNumber(row.dueSalary) }}</td>
                            <td style="min-width: 170px;">
                                <v-text-field
                                    :model-value="allocations[row.month] ?? 0"
                                    type="number"
                                    min="0"
                                    step="1"
                                    density="compact"
                                    hide-details
                                    @update:model-value="(value) => setAllocation(row.month, value)"
                                ></v-text-field>
                            </td>
                            <td>
                                <v-chip :color="isMonthPaid(row.month, row.dueSalary) ? 'success' : 'warning'" size="small">
                                    {{ isMonthPaid(row.month, row.dueSalary) ? 'Paid' : 'Due' }}
                                </v-chip>
                            </td>
                            <td>
                                ৳{{ formatNumber(monthRemainingDue(row.month, row.dueSalary)) }}
                            </td>
                        </tr>
                        <tr v-if="rows.length === 0">
                            <td colspan="5" class="text-center text-grey">No months available</td>
                        </tr>
                    </tbody>
                </v-table>
            </v-card-text>
        </v-card>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../services/api'

const route = useRoute()
const router = useRouter()

const employee = ref(null)
const rows = ref([])
const allocations = ref({})
const salaryHistory = ref([])
const advanceHistory = ref([])
const salaryExpectationsByMonth = ref({})
const lastSavedAt = ref('')

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD')

const toMonth = (value) => {
    if (!value) return null
    const normalized = String(value)
    if (/^\d{4}-\d{2}$/.test(normalized)) return normalized
    if (/^\d{4}-\d{2}-\d{2}/.test(normalized)) return normalized.slice(0, 7)
    return null
}

const formatMonthValue = (dateObj) => {
    const year = dateObj.getFullYear()
    const month = String(dateObj.getMonth() + 1).padStart(2, '0')
    return `${year}-${month}`
}

const buildMonthRange = (startMonth, endMonth) => {
    if (!startMonth || !endMonth || startMonth > endMonth) return []

    const [startYear, startMonthNum] = startMonth.split('-').map(Number)
    const [endYear, endMonthNum] = endMonth.split('-').map(Number)
    const cursor = new Date(startYear, startMonthNum - 1, 1)
    const end = new Date(endYear, endMonthNum - 1, 1)
    const months = []

    while (cursor <= end) {
        months.push(formatMonthValue(cursor))
        cursor.setMonth(cursor.getMonth() + 1)
    }

    return months
}

const formatMonthLong = (monthStr) => {
    const [year, month] = monthStr.split('-')
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
    return `${monthNames[parseInt(month, 10) - 1]} ${year}`
}

const getStorageKey = () => {
    const employeeId = route.params.id
    return `simple_salary_sheet_allocations_${employeeId}`
}

const totalGiven = computed(() => {
    const salaryTotal = salaryHistory.value.reduce((sum, item) => sum + Number(item.amount || 0), 0)
    const advanceTotal = advanceHistory.value.reduce((sum, item) => sum + Number(item.amount || 0), 0)
    return salaryTotal + advanceTotal
})

const totalAllocated = computed(() => {
    return Object.values(allocations.value).reduce((sum, amount) => sum + Number(amount || 0), 0)
})

const remainingAmount = computed(() => Number(totalGiven.value || 0) - Number(totalAllocated.value || 0))

const isMonthPaid = (month, dueSalary) => {
    return Number(allocations.value[month] || 0) >= Number(dueSalary || 0)
}

const monthRemainingDue = (month, dueSalary) => {
    return Math.max(0, Number(dueSalary || 0) - Number(allocations.value[month] || 0))
}

const setAllocation = (month, rawValue) => {
    const requested = Math.max(0, Number(rawValue || 0))
    const current = Number(allocations.value[month] || 0)
    const maxAllowed = current + Number(remainingAmount.value || 0)
    allocations.value[month] = Math.max(0, Math.min(requested, maxAllowed))
}

const resetAllocations = () => {
    allocations.value = rows.value.reduce((acc, row) => {
        acc[row.month] = 0
        return acc
    }, {})
}

const saveAllocations = () => {
    try {
        const payload = {
            allocations: allocations.value,
            savedAt: new Date().toISOString(),
        }
        localStorage.setItem(getStorageKey(), JSON.stringify(payload))
        lastSavedAt.value = payload.savedAt
        alert('Allocation saved successfully!')
    } catch (error) {
        console.error('Error saving allocation:', error)
        alert('Failed to save allocation')
    }
}

const loadSavedAllocations = () => {
    try {
        const raw = localStorage.getItem(getStorageKey())
        if (!raw) return

        const parsed = JSON.parse(raw)
        const saved = parsed?.allocations || {}

        rows.value.forEach((row) => {
            allocations.value[row.month] = Number(saved[row.month] || 0)
        })

        lastSavedAt.value = parsed?.savedAt || ''
    } catch (error) {
        console.error('Error loading saved allocation:', error)
    }
}

const goBack = () => {
    router.push({ name: 'employees' })
}

const loadData = async () => {
    const employeeId = route.params.id
    if (!employeeId) return

    const [employeesRes, salariesRes, advancesRes] = await Promise.all([
        api.get('/employees'),
        api.get(`/employees/${employeeId}/salaries`),
        api.get(`/employees/${employeeId}/advances`),
    ])

    employee.value = (employeesRes.data || []).find((item) => String(item.id) === String(employeeId)) || null
    salaryHistory.value = salariesRes.data || []
    advanceHistory.value = advancesRes.data || []

    const salaryMonths = salaryHistory.value.map((item) => toMonth(item.month)).filter(Boolean)
    const salaryPayMonths = salaryHistory.value.map((item) => toMonth(item.payment_date)).filter(Boolean)
    const advanceMonths = advanceHistory.value.map((item) => toMonth(item.date)).filter(Boolean)
    const knownMonths = [...new Set([...salaryMonths, ...salaryPayMonths, ...advanceMonths])].sort((a, b) => a.localeCompare(b))

    const joiningMonth = toMonth(employee.value?.joining_date)
    const now = new Date()
    const previousMonth = formatMonthValue(new Date(now.getFullYear(), now.getMonth() - 1, 1))

    const startMonth = [joiningMonth, knownMonths[0], previousMonth].filter(Boolean).sort((a, b) => a.localeCompare(b))[0]
    const endMonth = [knownMonths[knownMonths.length - 1], previousMonth].filter(Boolean).sort((a, b) => b.localeCompare(a))[0]
    const months = buildMonthRange(startMonth, endMonth)

    if (months.length === 0) {
        rows.value = []
        allocations.value = {}
        return
    }

    const responses = await Promise.all(
        months.map((month) => api.get(`/employees/${employeeId}/calculate-salary`, { params: { month } }))
    )

    salaryExpectationsByMonth.value = responses.reduce((acc, response) => {
        acc[response.data.month] = response.data
        return acc
    }, {})

    rows.value = months.map((month) => {
        const details = salaryExpectationsByMonth.value[month] || {}
        const dueSalary = Number(details.calculated_salary ?? employee.value?.salary_amount ?? 0)
        return { month, dueSalary }
    })

    allocations.value = rows.value.reduce((acc, row) => {
        acc[row.month] = 0
        return acc
    }, {})

    loadSavedAllocations()
}

onMounted(async () => {
    try {
        await loadData()
    } catch (error) {
        console.error('Error loading simple salary sheet:', error)
    }
})
</script>