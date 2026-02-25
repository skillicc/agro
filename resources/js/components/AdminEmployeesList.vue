<template>
    <div>
        <div class="d-flex justify-space-between align-center mb-4">
            <h3 class="text-h6">Administration Employees</h3>
            <v-btn color="success" size="small" @click="openSalaryDialog()">
                <v-icon left>mdi-cash</v-icon>
                Pay Salary
            </v-btn>
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
            <template v-slot:item.actions="{ item }">
                <v-btn icon size="x-small" color="info" title="Salary History" @click="viewSalaries(item)">
                    <v-icon>mdi-history</v-icon>
                </v-btn>
                <v-btn icon size="x-small" color="success" title="Pay Salary" @click="openSalaryDialog(item)">
                    <v-icon>mdi-cash</v-icon>
                </v-btn>
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

        <!-- Pay Salary Dialog -->
        <v-dialog v-model="salaryDialog" :max-width="$vuetify.display.xs ? '100%' : '500'" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title>Pay Salary</v-card-title>
                <v-card-text>
                    <v-select
                        v-model="salaryForm.employee_id"
                        :items="employees"
                        item-title="name"
                        item-value="id"
                        label="Employee"
                        required
                        @update:model-value="onEmployeeSelect"
                    ></v-select>
                    <v-text-field v-model="salaryForm.month" label="Month" type="month" required></v-text-field>
                    <v-text-field
                        v-model="salaryForm.amount"
                        label="Amount"
                        type="number"
                        prefix="৳"
                        required
                        :hint="selectedEmployeeDue > 0 ? `Due: ৳${formatNumber(selectedEmployeeDue)}` : ''"
                        persistent-hint
                    ></v-text-field>
                    <v-text-field v-model="salaryForm.payment_date" label="Payment Date" type="date" required></v-text-field>
                    <v-textarea v-model="salaryForm.note" label="Note" rows="2"></v-textarea>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="salaryDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="paySalary" :loading="paying">Pay</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Salary History Dialog -->
        <v-dialog v-model="historyDialog" :max-width="$vuetify.display.xs ? '100%' : '600'" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title>Salary History - {{ selectedEmployee?.name }}</v-card-title>
                <v-card-text>
                    <v-table density="compact">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Amount</th>
                                <th>Payment Date</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="salary in salaryHistory" :key="salary.id">
                                <td>{{ salary.month }}</td>
                                <td>৳{{ formatNumber(salary.amount) }}</td>
                                <td>{{ salary.payment_date }}</td>
                                <td>{{ salary.note }}</td>
                            </tr>
                            <tr v-if="salaryHistory.length === 0">
                                <td colspan="4" class="text-center text-grey pa-4">No salary records found</td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="historyDialog = false">Close</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../services/api'

const props = defineProps({
    projectId: { type: [Number, String], required: true }
})

const employees = ref([])
const salaryHistory = ref([])
const loading = ref(false)
const salaryDialog = ref(false)
const historyDialog = ref(false)
const selectedEmployee = ref(null)
const paying = ref(false)

const salaryForm = ref({
    employee_id: null,
    month: new Date().toISOString().slice(0, 7),
    amount: '',
    payment_date: new Date().toISOString().split('T')[0],
    note: ''
})

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
    { title: 'Actions', key: 'actions', sortable: false, align: 'center', width: '80px' },
]

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD')

const totalSalary = computed(() => employees.value.reduce((sum, e) => sum + Number(e.salary || 0), 0))
const totalEL = computed(() => employees.value.reduce((sum, e) => sum + Number(e.el_balance || 0), 0))
const totalAdvance = computed(() => employees.value.reduce((sum, e) => sum + Number(e.advance_balance || 0), 0))
const totalPaid = computed(() => employees.value.reduce((sum, e) => sum + Number(e.paid_this_month || 0), 0))
const totalDue = computed(() => employees.value.reduce((sum, e) => sum + Number(e.due || 0), 0))

const selectedEmployeeDue = computed(() => {
    if (!salaryForm.value.employee_id) return 0
    const emp = employees.value.find(e => e.id === salaryForm.value.employee_id)
    return emp ? Number(emp.due || 0) : 0
})

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

const openSalaryDialog = (employee = null) => {
    salaryForm.value = {
        employee_id: employee ? employee.id : null,
        month: new Date().toISOString().slice(0, 7),
        amount: employee ? (employee.due > 0 ? employee.due : employee.salary) : '',
        payment_date: new Date().toISOString().split('T')[0],
        note: ''
    }
    salaryDialog.value = true
}

const onEmployeeSelect = (employeeId) => {
    const emp = employees.value.find(e => e.id === employeeId)
    if (emp) {
        salaryForm.value.amount = emp.due > 0 ? emp.due : emp.salary
    }
}

const paySalary = async () => {
    paying.value = true
    try {
        await api.post('/salaries', { ...salaryForm.value, project_id: props.projectId })
        salaryDialog.value = false
        fetchEmployees()
    } catch (error) {
        console.error('Error:', error)
        alert('Error paying salary')
    }
    paying.value = false
}

const viewSalaries = async (employee) => {
    selectedEmployee.value = employee
    try {
        const response = await api.get(`/employees/${employee.id}/salaries`)
        salaryHistory.value = response.data
        historyDialog.value = true
    } catch (error) {
        console.error('Error:', error)
    }
}

onMounted(() => {
    fetchEmployees()
})
</script>
