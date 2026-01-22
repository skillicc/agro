<template>
    <div>
        <div class="d-flex flex-column flex-sm-row justify-space-between align-start align-sm-center mb-4 ga-2">
            <h1 class="text-h5 text-sm-h4">Attendance</h1>
            <div class="d-flex ga-2">
                <v-btn color="info" :to="{ name: 'attendance-details' }" :size="$vuetify.display.xs ? 'small' : 'default'">
                    <v-icon left>mdi-chart-bar</v-icon>
                    <span class="d-none d-sm-inline ms-1">Details</span>
                </v-btn>
                <v-btn color="success" @click="markAllPresent" :loading="marking" :size="$vuetify.display.xs ? 'small' : 'default'">
                    <v-icon left>mdi-check-all</v-icon>
                    <span class="d-none d-sm-inline">All Present</span>
                </v-btn>
                <v-btn color="error" @click="confirmCancelAll" :size="$vuetify.display.xs ? 'small' : 'default'">
                    <v-icon left>mdi-close-circle</v-icon>
                    <span class="d-none d-sm-inline">Cancel All</span>
                </v-btn>
            </div>
        </div>

        <!-- Date Picker, Filter & Summary -->
        <v-row class="mb-4">
            <v-col cols="6" sm="4" md="2">
                <v-text-field
                    v-model="selectedDate"
                    label="Date"
                    type="date"
                    density="comfortable"
                    hide-details
                    @change="fetchAttendances"
                ></v-text-field>
            </v-col>
            <v-col cols="6" sm="4" md="2">
                <v-select
                    v-model="filterType"
                    :items="employeeTypes"
                    item-title="label"
                    item-value="value"
                    label="Employee Type"
                    clearable
                    density="comfortable"
                    hide-details
                    @update:model-value="fetchAttendances"
                ></v-select>
            </v-col>
            <v-col cols="3" sm="2" md="1">
                <v-card color="primary" variant="tonal">
                    <v-card-text class="pa-2 text-center">
                        <div class="text-h6">{{ summary?.total ?? 0 }}</div>
                        <div class="text-caption">Total</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="3" sm="2" md="1">
                <v-card color="success" variant="tonal">
                    <v-card-text class="pa-2 text-center">
                        <div class="text-h6">{{ summary?.present ?? 0 }}</div>
                        <div class="text-caption">Present</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="3" sm="2" md="1">
                <v-card color="error" variant="tonal">
                    <v-card-text class="pa-2 text-center">
                        <div class="text-h6">{{ summary?.absent ?? 0 }}</div>
                        <div class="text-caption">Absent</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="3" sm="2" md="1">
                <v-card color="purple" variant="tonal">
                    <v-card-text class="pa-2 text-center">
                        <div class="text-h6">{{ summary?.leave ?? 0 }}</div>
                        <div class="text-caption">Leave</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="3" sm="2" md="1">
                <v-card color="warning" variant="tonal">
                    <v-card-text class="pa-2 text-center">
                        <div class="text-h6">{{ summary?.sick_leave ?? 0 }}</div>
                        <div class="text-caption">Sick</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="3" sm="2" md="1">
                <v-card color="blue" variant="tonal">
                    <v-card-text class="pa-2 text-center">
                        <div class="text-h6">{{ summary?.regular_count ?? 0 }}</div>
                        <div class="text-caption">Regular</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" sm="4" md="2">
                <v-card color="orange" variant="tonal">
                    <v-card-text class="pa-2 text-center">
                        <div class="text-h6">{{ summary?.contractual_count ?? 0 }}</div>
                        <div class="text-caption">Contractual</div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <!-- Attendance Table -->
        <v-card>
            <v-data-table
                :headers="headers"
                :items="attendances"
                :loading="loading"
                density="compact"
                :items-per-page="50"
            >
                <template v-slot:item.sl="{ index }">
                    {{ index + 1 }}
                </template>
                <template v-slot:item.employee.name="{ item }">
                    <div>
                        <strong>{{ item.employee?.name }}</strong>
                        <div class="text-caption text-grey">{{ item.employee?.position }}</div>
                    </div>
                </template>
                <template v-slot:item.employee_type="{ item }">
                    <v-chip
                        :color="item.employee?.employee_type === 'regular' ? 'blue' : 'orange'"
                        size="x-small"
                    >
                        {{ item.employee?.employee_type === 'regular' ? 'R' : 'C' }}
                    </v-chip>
                </template>
                <template v-slot:item.employee.project="{ item }">
                    {{ item.employee?.project?.name || '-' }}
                </template>
                <template v-slot:item.status="{ item }">
                    <v-chip
                        :color="getStatusColor(item.status)"
                        size="small"
                        @click="toggleAttendance(item)"
                        style="cursor: pointer;"
                        :title="item.note || ''"
                    >
                        {{ getStatusLabel(item.status) }}
                    </v-chip>
                    <v-icon v-if="item.note" size="x-small" class="ml-1" color="grey" :title="item.note">mdi-note-text</v-icon>
                </template>
                <template v-slot:item.actions="{ item }">
                    <v-btn
                        icon
                        size="x-small"
                        color="info"
                        @click="openStatusDialog(item)"
                        title="Change Status"
                    >
                        <v-icon>mdi-pencil</v-icon>
                    </v-btn>
                </template>
            </v-data-table>
        </v-card>

        <!-- Cancel All Confirm Dialog -->
        <v-dialog v-model="cancelAllDialog" max-width="400">
            <v-card>
                <v-card-title>Confirm Cancel All</v-card-title>
                <v-card-text>
                    Are you sure you want to mark all employees as <strong>Absent</strong> for {{ selectedDate }}?
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="cancelAllDialog = false">No</v-btn>
                    <v-btn color="error" @click="cancelAll" :loading="cancelling">Yes, Cancel All</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Status Update Dialog -->
        <v-dialog v-model="statusDialog" max-width="450">
            <v-card>
                <v-card-title>Update Attendance</v-card-title>
                <v-card-text>
                    <div class="mb-3">
                        <strong>{{ selectedAttendance?.employee?.name }}</strong>
                        <div class="text-caption text-grey">{{ selectedDate }}</div>
                    </div>
                    <v-select
                        v-model="statusForm.status"
                        :items="statusOptions"
                        item-title="label"
                        item-value="value"
                        label="Status"
                        density="comfortable"
                    ></v-select>
                    <v-textarea
                        v-model="statusForm.note"
                        label="Note (optional)"
                        rows="2"
                        density="comfortable"
                        placeholder="Enter reason for absence/leave..."
                    ></v-textarea>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="statusDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="updateStatus" :loading="updatingStatus">Update</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'

const attendances = ref([])
const loading = ref(false)
const marking = ref(false)
const cancelling = ref(false)
const cancelAllDialog = ref(false)
const selectedDate = ref(new Date().toISOString().split('T')[0])

// Status dialog
const statusDialog = ref(false)
const selectedAttendance = ref(null)
const updatingStatus = ref(false)
const statusForm = ref({
    status: 'present',
    note: '',
})

const statusOptions = [
    { value: 'present', label: 'Present' },
    { value: 'absent', label: 'Absent' },
    { value: 'leave', label: 'Leave' },
    { value: 'sick_leave', label: 'Sick Leave' },
]

const summary = ref({
    total: 0,
    present: 0,
    absent: 0,
    leave: 0,
    sick_leave: 0,
})

const employeeTypes = [
    { value: 'regular', label: 'Regular' },
    { value: 'contractual', label: 'Contractual' },
]

const filterType = ref(null)

const headers = [
    { title: 'SL', key: 'sl', sortable: false, width: '50px' },
    { title: 'Employee', key: 'employee.name' },
    { title: 'Type', key: 'employee_type', width: '60px' },
    { title: 'Project', key: 'employee.project' },
    { title: 'Status', key: 'status', align: 'center' },
    { title: 'Action', key: 'actions', sortable: false, align: 'center', width: '80px' },
]

const fetchAttendances = async () => {
    loading.value = true
    try {
        const params = { date: selectedDate.value }
        if (filterType.value) {
            params.employee_type = filterType.value
        }
        const response = await api.get('/attendances', { params })
        attendances.value = response.data.attendances || []
        summary.value = response.data.summary || { total: 0, present: 0, absent: 0, leave: 0, sick_leave: 0, regular_count: 0, contractual_count: 0 }
    } catch (error) {
        console.error('Error:', error)
        attendances.value = []
        summary.value = { total: 0, present: 0, absent: 0, leave: 0, sick_leave: 0, regular_count: 0, contractual_count: 0 }
    }
    loading.value = false
}

const toggleAttendance = async (attendance) => {
    try {
        let response
        if (!attendance.id) {
            // Create new attendance for unmarked employee
            response = await api.post('/attendances', {
                employee_id: attendance.employee_id,
                date: selectedDate.value,
                status: 'present'
            })
        } else {
            // Toggle existing attendance
            response = await api.post(`/attendances/${attendance.id}/toggle`)
        }
        const index = attendances.value.findIndex(a => a.employee_id === attendance.employee_id)
        if (index !== -1) {
            attendances.value[index] = {
                ...response.data,
                employee_id: response.data.employee_id || attendance.employee_id
            }
        }
        updateSummary()
    } catch (error) {
        console.error('Error:', error)
    }
}

const openStatusDialog = (attendance) => {
    selectedAttendance.value = attendance
    statusForm.value.status = attendance.status || 'present'
    statusForm.value.note = attendance.note || ''
    statusDialog.value = true
}

const updateStatus = async () => {
    if (!selectedAttendance.value) return
    updatingStatus.value = true
    try {
        let response
        if (!selectedAttendance.value.id) {
            // Create new attendance for unmarked employee
            response = await api.post('/attendances', {
                employee_id: selectedAttendance.value.employee_id,
                date: selectedDate.value,
                status: statusForm.value.status
            })
        } else {
            // Update existing attendance
            response = await api.put(`/attendances/${selectedAttendance.value.id}/status`, statusForm.value)
        }
        const index = attendances.value.findIndex(a => a.employee_id === selectedAttendance.value.employee_id)
        if (index !== -1) {
            attendances.value[index] = {
                ...response.data,
                employee_id: response.data.employee_id || selectedAttendance.value.employee_id
            }
        }
        updateSummary()
        statusDialog.value = false
    } catch (error) {
        console.error('Error:', error)
    }
    updatingStatus.value = false
}

const getStatusColor = (status) => {
    if (!status) return 'grey'
    const colors = {
        present: 'success',
        absent: 'error',
        leave: 'purple',
        sick_leave: 'warning',
    }
    return colors[status] || 'grey'
}

const getStatusLabel = (status) => {
    if (!status) return 'Not Marked'
    const labels = {
        present: 'Present',
        absent: 'Absent',
        leave: 'Leave',
        sick_leave: 'Sick Leave',
    }
    return labels[status] || status
}

const confirmCancelAll = () => {
    cancelAllDialog.value = true
}

const cancelAll = async () => {
    cancelling.value = true
    try {
        await api.post('/attendances/cancel-all', { date: selectedDate.value })
        cancelAllDialog.value = false
        fetchAttendances()
    } catch (error) {
        console.error('Error:', error)
    }
    cancelling.value = false
}

const markAllPresent = async () => {
    marking.value = true
    try {
        await api.post('/attendances/mark-all-present', { date: selectedDate.value })
        fetchAttendances()
    } catch (error) {
        console.error('Error:', error)
    }
    marking.value = false
}

const updateSummary = () => {
    summary.value.total = attendances.value.length
    summary.value.present = attendances.value.filter(a => a.status === 'present').length
    summary.value.absent = attendances.value.filter(a => a.status === 'absent').length
    summary.value.leave = attendances.value.filter(a => a.status === 'leave').length
    summary.value.sick_leave = attendances.value.filter(a => a.status === 'sick_leave').length
    summary.value.not_marked = attendances.value.filter(a => !a.status).length
}

onMounted(() => {
    fetchAttendances()
})
</script>
