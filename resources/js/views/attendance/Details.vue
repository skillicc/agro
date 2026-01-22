<template>
    <div>
        <div class="d-flex flex-column flex-sm-row justify-space-between align-start align-sm-center mb-4 ga-2">
            <h1 class="text-h5 text-sm-h4">Attendance Details</h1>
            <v-btn color="primary" :to="{ name: 'attendance' }" :size="$vuetify.display.xs ? 'small' : 'default'">
                <v-icon left>mdi-arrow-left</v-icon>
                <span class="d-none d-sm-inline ms-1">Back to Attendance</span>
            </v-btn>
        </div>

        <!-- Month/Year Selector -->
        <v-row class="mb-4">
            <v-col cols="6" sm="3" md="2">
                <v-select
                    v-model="selectedMonth"
                    :items="months"
                    item-title="name"
                    item-value="value"
                    label="Month"
                    density="comfortable"
                    @update:model-value="fetchAllData"
                ></v-select>
            </v-col>
            <v-col cols="6" sm="3" md="2">
                <v-select
                    v-model="selectedYear"
                    :items="years"
                    label="Year"
                    density="comfortable"
                    @update:model-value="fetchAllData"
                ></v-select>
            </v-col>
        </v-row>

        <!-- Summary Cards -->
        <v-row class="mb-4">
            <v-col cols="6" sm="3">
                <v-card color="primary" variant="tonal">
                    <v-card-text class="pa-3 text-center">
                        <div class="text-h5">{{ summaryStats.totalDays }}</div>
                        <div class="text-caption">Working Days</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" sm="3">
                <v-card color="info" variant="tonal">
                    <v-card-text class="pa-3 text-center">
                        <div class="text-h5">{{ summaryStats.totalEmployees }}</div>
                        <div class="text-caption">Employees</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" sm="3">
                <v-card color="success" variant="tonal">
                    <v-card-text class="pa-3 text-center">
                        <div class="text-h5">{{ summaryStats.avgPresent }}%</div>
                        <div class="text-caption">Avg Present</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" sm="3">
                <v-card color="error" variant="tonal">
                    <v-card-text class="pa-3 text-center">
                        <div class="text-h5">{{ summaryStats.avgAbsent }}%</div>
                        <div class="text-caption">Avg Absent</div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <v-row>
            <!-- Pie Chart - Present vs Absent Overview -->
            <v-col cols="12" md="4">
                <v-card :loading="loading">
                    <v-card-title class="text-body-1 font-weight-bold">
                        Overall Attendance
                    </v-card-title>
                    <v-card-text>
                        <div v-if="pieChartData" style="height: 280px;">
                            <Doughnut :data="pieChartData" :options="pieChartOptions" />
                        </div>
                        <div v-else class="d-flex align-center justify-center" style="height: 280px;">
                            <span class="text-grey">No data available</span>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>

            <!-- Daily Trend Bar Chart -->
            <v-col cols="12" md="8">
                <v-card :loading="loading">
                    <v-card-title class="text-body-1 font-weight-bold">
                        Daily Attendance Trend - {{ monthName }} {{ selectedYear }}
                    </v-card-title>
                    <v-card-text>
                        <div v-if="dailyChartData" style="height: 280px;">
                            <Bar :data="dailyChartData" :options="barChartOptions" />
                        </div>
                        <div v-else class="d-flex align-center justify-center" style="height: 280px;">
                            <span class="text-grey">No data available</span>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <!-- Employee Attendance Chart -->
        <v-row class="mt-4">
            <v-col cols="12">
                <v-card :loading="loading">
                    <v-card-title class="text-body-1 font-weight-bold">
                        Employee Attendance Summary - {{ monthName }} {{ selectedYear }}
                    </v-card-title>
                    <v-card-text>
                        <div v-if="employeeChartData" :style="{ height: employeeChartHeight + 'px' }">
                            <Bar :data="employeeChartData" :options="employeeChartOptions" />
                        </div>
                        <div v-else class="d-flex align-center justify-center" style="height: 300px;">
                            <span class="text-grey">No data available</span>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <!-- Employee Details Table -->
        <v-row class="mt-4">
            <v-col cols="12">
                <v-card>
                    <v-card-title class="text-body-1 font-weight-bold">
                        Employee Attendance Details
                    </v-card-title>
                    <v-data-table
                        :headers="tableHeaders"
                        :items="employeeReport"
                        :loading="loading"
                        density="compact"
                        :items-per-page="25"
                    >
                        <template v-slot:item.sl="{ index }">
                            {{ index + 1 }}
                        </template>
                        <template v-slot:item.employee.name="{ item }">
                            <div>
                                <strong>{{ item.employee?.name }}</strong>
                                <div class="text-caption text-grey">{{ item.employee?.designation }}</div>
                            </div>
                        </template>
                        <template v-slot:item.employee.project="{ item }">
                            {{ item.employee?.project?.name || '-' }}
                        </template>
                        <template v-slot:item.present_days="{ item }">
                            <v-chip color="success" size="small">{{ item.present_days }}</v-chip>
                        </template>
                        <template v-slot:item.absent_days="{ item }">
                            <v-chip
                                color="error"
                                size="small"
                                :style="item.absent_days > 0 ? 'cursor: help' : ''"
                                :title="item.absent_dates && item.absent_dates.length > 0 ? 'Absent: ' + item.absent_dates.join(', ') : ''"
                            >
                                {{ item.absent_days }}
                            </v-chip>
                        </template>
                        <template v-slot:item.leave_days="{ item }">
                            <v-chip color="warning" size="small">{{ item.leave_days || 0 }}</v-chip>
                        </template>
                        <template v-slot:item.sick_leave_days="{ item }">
                            <v-chip color="purple" size="small">{{ item.sick_leave_days || 0 }}</v-chip>
                        </template>
                        <template v-slot:item.attendance_rate="{ item }">
                            <v-chip
                                :color="getAttendanceColor(item.attendance_rate)"
                                size="small"
                            >
                                {{ item.attendance_rate }}%
                            </v-chip>
                        </template>
                    </v-data-table>
                </v-card>
            </v-col>
        </v-row>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Bar, Doughnut } from 'vue-chartjs'
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
    ArcElement
} from 'chart.js'
import api from '../../services/api'

// Register Chart.js components
ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
    ArcElement
)

const loading = ref(false)
const dailyData = ref([])
const employeeReport = ref([])

const currentDate = new Date()
const selectedMonth = ref(currentDate.getMonth() + 1)
const selectedYear = ref(currentDate.getFullYear())

const months = [
    { name: 'January', value: 1 },
    { name: 'February', value: 2 },
    { name: 'March', value: 3 },
    { name: 'April', value: 4 },
    { name: 'May', value: 5 },
    { name: 'June', value: 6 },
    { name: 'July', value: 7 },
    { name: 'August', value: 8 },
    { name: 'September', value: 9 },
    { name: 'October', value: 10 },
    { name: 'November', value: 11 },
    { name: 'December', value: 12 },
]

const years = computed(() => {
    const currentYear = new Date().getFullYear()
    return [currentYear - 1, currentYear, currentYear + 1]
})

const monthName = computed(() => {
    return months.find(m => m.value === selectedMonth.value)?.name || ''
})

const tableHeaders = [
    { title: 'SL', key: 'sl', sortable: false, width: '60px' },
    { title: 'Employee', key: 'employee.name' },
    { title: 'Project', key: 'employee.project' },
    { title: 'Total Days', key: 'total_days', align: 'center' },
    { title: 'Present', key: 'present_days', align: 'center' },
    { title: 'Absent', key: 'absent_days', align: 'center' },
    { title: 'Leave', key: 'leave_days', align: 'center' },
    { title: 'Sick Leave', key: 'sick_leave_days', align: 'center' },
    { title: 'Rate', key: 'attendance_rate', align: 'center' },
]

// Summary Statistics
const summaryStats = computed(() => {
    const totalDays = dailyData.value.length
    const totalEmployees = employeeReport.value.length

    let totalPresent = 0
    let totalRecords = 0

    dailyData.value.forEach(day => {
        totalPresent += day.present
        totalRecords += day.total
    })

    const avgPresent = totalRecords > 0 ? Math.round((totalPresent / totalRecords) * 100) : 0
    const avgAbsent = 100 - avgPresent

    return {
        totalDays,
        totalEmployees,
        avgPresent,
        avgAbsent
    }
})

// Pie Chart Data
const pieChartData = computed(() => {
    let totalPresent = 0
    let totalAbsent = 0
    let totalLeave = 0
    let totalSickLeave = 0

    dailyData.value.forEach(day => {
        totalPresent += day.present
        totalAbsent += day.absent
        totalLeave += day.leave || 0
        totalSickLeave += day.sick_leave || 0
    })

    if (totalPresent === 0 && totalAbsent === 0 && totalLeave === 0 && totalSickLeave === 0) return null

    return {
        labels: ['Present', 'Absent', 'Leave', 'Sick Leave'],
        datasets: [{
            data: [totalPresent, totalAbsent, totalLeave, totalSickLeave],
            backgroundColor: ['#4CAF50', '#F44336', '#FF9800', '#9C27B0'],
            borderWidth: 0
        }]
    }
})

const pieChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom'
        }
    }
}

// Daily Bar Chart Data
const dailyChartData = computed(() => {
    if (dailyData.value.length === 0) return null

    return {
        labels: dailyData.value.map(d => d.day),
        datasets: [
            {
                label: 'Present',
                data: dailyData.value.map(d => d.present),
                backgroundColor: '#4CAF50',
                borderRadius: 4
            },
            {
                label: 'Absent',
                data: dailyData.value.map(d => d.absent),
                backgroundColor: '#F44336',
                borderRadius: 4
            },
            {
                label: 'Leave',
                data: dailyData.value.map(d => d.leave || 0),
                backgroundColor: '#FF9800',
                borderRadius: 4
            },
            {
                label: 'Sick Leave',
                data: dailyData.value.map(d => d.sick_leave || 0),
                backgroundColor: '#9C27B0',
                borderRadius: 4
            }
        ]
    }
})

const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        x: {
            stacked: true,
            title: {
                display: true,
                text: 'Day of Month'
            }
        },
        y: {
            stacked: true,
            beginAtZero: true,
            title: {
                display: true,
                text: 'Employees'
            }
        }
    },
    plugins: {
        legend: {
            position: 'top'
        }
    }
}

// Employee Bar Chart
const employeeChartData = computed(() => {
    if (employeeReport.value.length === 0) return null

    return {
        labels: employeeReport.value.map(e => e.employee?.name || 'Unknown'),
        datasets: [
            {
                label: 'Present Days',
                data: employeeReport.value.map(e => e.present_days),
                backgroundColor: '#4CAF50',
                borderRadius: 4
            },
            {
                label: 'Absent Days',
                data: employeeReport.value.map(e => e.absent_days),
                backgroundColor: '#F44336',
                borderRadius: 4
            },
            {
                label: 'Leave Days',
                data: employeeReport.value.map(e => e.leave_days || 0),
                backgroundColor: '#FF9800',
                borderRadius: 4
            },
            {
                label: 'Sick Leave Days',
                data: employeeReport.value.map(e => e.sick_leave_days || 0),
                backgroundColor: '#9C27B0',
                borderRadius: 4
            }
        ]
    }
})

const employeeChartHeight = computed(() => {
    return Math.max(300, employeeReport.value.length * 35)
})

const employeeChartOptions = computed(() => ({
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        x: {
            stacked: true,
            beginAtZero: true,
            title: {
                display: true,
                text: 'Days'
            }
        },
        y: {
            stacked: true
        }
    },
    plugins: {
        legend: {
            position: 'top'
        }
    }
}))

const getAttendanceColor = (rate) => {
    if (rate >= 90) return 'success'
    if (rate >= 75) return 'warning'
    return 'error'
}

const fetchDailyData = async () => {
    try {
        const response = await api.get('/attendances/daily-chart', {
            params: { month: selectedMonth.value, year: selectedYear.value }
        })
        dailyData.value = response.data.daily_data || []
    } catch (error) {
        console.error('Error fetching daily data:', error)
        dailyData.value = []
    }
}

const fetchEmployeeReport = async () => {
    try {
        const response = await api.get('/attendances/monthly-report', {
            params: { month: selectedMonth.value, year: selectedYear.value }
        })
        const report = response.data.report || []
        // Add attendance rate calculation
        employeeReport.value = report.map(item => ({
            ...item,
            attendance_rate: item.total_days > 0
                ? Math.round((item.present_days / item.total_days) * 100)
                : 0
        }))
    } catch (error) {
        console.error('Error fetching employee report:', error)
        employeeReport.value = []
    }
}

const fetchAllData = async () => {
    loading.value = true
    await Promise.all([
        fetchDailyData(),
        fetchEmployeeReport()
    ])
    loading.value = false
}

onMounted(() => {
    fetchAllData()
})
</script>
