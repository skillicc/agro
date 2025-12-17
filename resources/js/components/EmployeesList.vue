<template>
    <div>
        <div class="d-flex justify-space-between align-center mb-4">
            <h3 class="text-h6">Employees & Salaries</h3>
            <div>
                <v-btn color="primary" size="small" class="mr-2" @click="openEmployeeDialog()">
                    <v-icon left>mdi-plus</v-icon>
                    Add Employee
                </v-btn>
                <v-btn color="success" size="small" @click="openSalaryDialog()">
                    <v-icon left>mdi-cash</v-icon>
                    Pay Salary
                </v-btn>
            </div>
        </div>

        <v-data-table :headers="headers" :items="employees" :loading="loading" density="compact">
            <template v-slot:item.salary="{ item }">
                ৳{{ Number(item.salary).toLocaleString() }}
            </template>
            <template v-slot:item.is_active="{ item }">
                <v-chip :color="item.is_active ? 'success' : 'grey'" size="small">
                    {{ item.is_active ? 'Active' : 'Inactive' }}
                </v-chip>
            </template>
            <template v-slot:item.actions="{ item }">
                <v-btn icon size="x-small" @click="viewSalaries(item)">
                    <v-icon>mdi-history</v-icon>
                </v-btn>
                <v-btn icon size="x-small" @click="openEmployeeDialog(item)">
                    <v-icon>mdi-pencil</v-icon>
                </v-btn>
                <v-btn icon size="x-small" color="error" @click="confirmDelete(item)">
                    <v-icon>mdi-delete</v-icon>
                </v-btn>
            </template>
        </v-data-table>

        <!-- Add/Edit Employee Dialog -->
        <v-dialog v-model="employeeDialog" max-width="500">
            <v-card>
                <v-card-title>{{ editMode ? 'Edit Employee' : 'Add Employee' }}</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="saveEmployee">
                        <v-text-field v-model="employeeForm.name" label="Name" required></v-text-field>
                        <v-text-field v-model="employeeForm.phone" label="Phone"></v-text-field>
                        <v-text-field v-model="employeeForm.position" label="Position"></v-text-field>
                        <v-text-field v-model="employeeForm.salary" label="Monthly Salary" type="number" prefix="৳" required></v-text-field>
                        <v-text-field v-model="employeeForm.join_date" label="Join Date" type="date"></v-text-field>
                        <v-switch v-model="employeeForm.is_active" label="Active" color="success"></v-switch>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="employeeDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveEmployee" :loading="saving">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Pay Salary Dialog -->
        <v-dialog v-model="salaryDialog" max-width="500">
            <v-card>
                <v-card-title>Pay Salary</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="paySalary">
                        <v-select
                            v-model="salaryForm.employee_id"
                            :items="employees"
                            item-title="name"
                            item-value="id"
                            label="Employee"
                            required
                        ></v-select>
                        <v-text-field v-model="salaryForm.month" label="Month" type="month" required></v-text-field>
                        <v-text-field v-model="salaryForm.amount" label="Amount" type="number" prefix="৳" required></v-text-field>
                        <v-text-field v-model="salaryForm.payment_date" label="Payment Date" type="date" required></v-text-field>
                        <v-textarea v-model="salaryForm.note" label="Note" rows="2"></v-textarea>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="salaryDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="paySalary" :loading="paying">Pay</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Salary History Dialog -->
        <v-dialog v-model="historyDialog" max-width="600">
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
                                <td>৳{{ Number(salary.amount).toLocaleString() }}</td>
                                <td>{{ salary.payment_date }}</td>
                                <td>{{ salary.note }}</td>
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

        <!-- Delete Confirm -->
        <v-dialog v-model="deleteDialog" max-width="400">
            <v-card>
                <v-card-title>Confirm Delete</v-card-title>
                <v-card-text>Are you sure you want to delete "{{ selectedEmployee?.name }}"?</v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" @click="deleteEmployee" :loading="deleting">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '../services/api'

const props = defineProps({
    projectId: { type: [Number, String], required: true }
})

const employees = ref([])
const salaryHistory = ref([])
const loading = ref(false)
const employeeDialog = ref(false)
const salaryDialog = ref(false)
const historyDialog = ref(false)
const deleteDialog = ref(false)
const editMode = ref(false)
const selectedEmployee = ref(null)
const saving = ref(false)
const paying = ref(false)
const deleting = ref(false)

const headers = [
    { title: 'Name', key: 'name' },
    { title: 'Phone', key: 'phone' },
    { title: 'Position', key: 'position' },
    { title: 'Salary', key: 'salary' },
    { title: 'Status', key: 'is_active' },
    { title: 'Actions', key: 'actions', sortable: false },
]

const employeeForm = reactive({
    name: '',
    phone: '',
    position: '',
    salary: '',
    join_date: '',
    is_active: true
})

const salaryForm = reactive({
    employee_id: null,
    month: new Date().toISOString().slice(0, 7),
    amount: '',
    payment_date: new Date().toISOString().split('T')[0],
    note: ''
})

const fetchEmployees = async () => {
    loading.value = true
    try {
        const response = await api.get(`/employees?project_id=${props.projectId}`)
        employees.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
    loading.value = false
}

const openEmployeeDialog = (employee = null) => {
    editMode.value = !!employee
    selectedEmployee.value = employee
    if (employee) {
        Object.assign(employeeForm, {
            name: employee.name,
            phone: employee.phone || '',
            position: employee.position || '',
            salary: employee.salary,
            join_date: employee.join_date || '',
            is_active: employee.is_active
        })
    } else {
        Object.assign(employeeForm, {
            name: '',
            phone: '',
            position: '',
            salary: '',
            join_date: '',
            is_active: true
        })
    }
    employeeDialog.value = true
}

const saveEmployee = async () => {
    saving.value = true
    try {
        const data = { ...employeeForm, project_id: props.projectId }
        if (editMode.value) {
            await api.put(`/employees/${selectedEmployee.value.id}`, data)
        } else {
            await api.post('/employees', data)
        }
        employeeDialog.value = false
        fetchEmployees()
    } catch (error) {
        console.error('Error:', error)
    }
    saving.value = false
}

const openSalaryDialog = () => {
    Object.assign(salaryForm, {
        employee_id: null,
        month: new Date().toISOString().slice(0, 7),
        amount: '',
        payment_date: new Date().toISOString().split('T')[0],
        note: ''
    })
    salaryDialog.value = true
}

const paySalary = async () => {
    paying.value = true
    try {
        await api.post('/salaries', { ...salaryForm, project_id: props.projectId })
        salaryDialog.value = false
    } catch (error) {
        console.error('Error:', error)
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

const confirmDelete = (employee) => {
    selectedEmployee.value = employee
    deleteDialog.value = true
}

const deleteEmployee = async () => {
    deleting.value = true
    try {
        await api.delete(`/employees/${selectedEmployee.value.id}`)
        deleteDialog.value = false
        fetchEmployees()
    } catch (error) {
        console.error('Error:', error)
    }
    deleting.value = false
}

onMounted(() => {
    fetchEmployees()
})
</script>
