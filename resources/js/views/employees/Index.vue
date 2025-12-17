<template>
    <div>
        <div class="d-flex justify-space-between align-center mb-4">
            <h1 class="text-h4">Employees</h1>
            <v-btn color="primary" @click="openDialog()">
                <v-icon left>mdi-plus</v-icon>
                Add Employee
            </v-btn>
        </div>

        <v-card>
            <v-card-text>
                <v-data-table :headers="headers" :items="employees" :loading="loading">
                    <template v-slot:item.salary_amount="{ item }">
                        ৳{{ formatNumber(item.salary_amount) }}
                    </template>
                    <template v-slot:item.is_active="{ item }">
                        <v-chip :color="item.is_active ? 'success' : 'error'" size="small">
                            {{ item.is_active ? 'Active' : 'Inactive' }}
                        </v-chip>
                    </template>
                    <template v-slot:item.actions="{ item }">
                        <v-btn icon size="small" color="success" @click="openSalaryDialog(item)">
                            <v-icon>mdi-cash</v-icon>
                        </v-btn>
                        <v-btn icon size="small" color="warning" @click="openAdvanceDialog(item)">
                            <v-icon>mdi-cash-minus</v-icon>
                        </v-btn>
                        <v-btn icon size="small" @click="openDialog(item)">
                            <v-icon>mdi-pencil</v-icon>
                        </v-btn>
                    </template>
                </v-data-table>
            </v-card-text>
        </v-card>

        <!-- Add/Edit Dialog -->
        <v-dialog v-model="dialog" max-width="500">
            <v-card>
                <v-card-title>{{ editMode ? 'Edit Employee' : 'Add Employee' }}</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="saveEmployee">
                        <v-select v-model="form.project_id" :items="projects" item-title="name" item-value="id" label="Project" required></v-select>
                        <v-text-field v-model="form.name" label="Name" required></v-text-field>
                        <v-text-field v-model="form.phone" label="Phone"></v-text-field>
                        <v-text-field v-model="form.position" label="Position"></v-text-field>
                        <v-text-field v-model.number="form.salary_amount" label="Salary Amount" type="number" required></v-text-field>
                        <v-text-field v-model="form.joining_date" label="Joining Date" type="date"></v-text-field>
                        <v-switch v-model="form.is_active" label="Active" color="success"></v-switch>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="dialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveEmployee" :loading="saving">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Salary Dialog -->
        <v-dialog v-model="salaryDialog" max-width="400">
            <v-card>
                <v-card-title>Pay Salary - {{ selectedEmployee?.name }}</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="paySalary">
                        <v-text-field v-model.number="salaryForm.amount" label="Amount" type="number" required></v-text-field>
                        <v-text-field v-model="salaryForm.month" label="Month (YYYY-MM)" placeholder="2025-01" required></v-text-field>
                        <v-text-field v-model="salaryForm.payment_date" label="Payment Date" type="date" required></v-text-field>
                        <v-textarea v-model="salaryForm.note" label="Note" rows="2"></v-textarea>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="salaryDialog = false">Cancel</v-btn>
                    <v-btn color="success" @click="paySalary" :loading="payingSalary">Pay</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Advance Dialog -->
        <v-dialog v-model="advanceDialog" max-width="400">
            <v-card>
                <v-card-title>Give Advance - {{ selectedEmployee?.name }}</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="giveAdvance">
                        <v-text-field v-model.number="advanceForm.amount" label="Amount" type="number" required></v-text-field>
                        <v-text-field v-model="advanceForm.date" label="Date" type="date" required></v-text-field>
                        <v-textarea v-model="advanceForm.reason" label="Reason" rows="2"></v-textarea>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="advanceDialog = false">Cancel</v-btn>
                    <v-btn color="warning" @click="giveAdvance" :loading="givingAdvance">Give</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '../../services/api'

const employees = ref([])
const projects = ref([])
const loading = ref(false)
const dialog = ref(false)
const salaryDialog = ref(false)
const advanceDialog = ref(false)
const editMode = ref(false)
const selectedEmployee = ref(null)
const saving = ref(false)
const payingSalary = ref(false)
const givingAdvance = ref(false)

const headers = [
    { title: 'Name', key: 'name' },
    { title: 'Project', key: 'project.name' },
    { title: 'Position', key: 'position' },
    { title: 'Phone', key: 'phone' },
    { title: 'Salary', key: 'salary_amount' },
    { title: 'Status', key: 'is_active' },
    { title: 'Actions', key: 'actions', sortable: false },
]

const form = reactive({ project_id: null, name: '', phone: '', position: '', salary_amount: 0, joining_date: '', is_active: true })
const salaryForm = reactive({ amount: 0, month: '', payment_date: new Date().toISOString().split('T')[0], note: '' })
const advanceForm = reactive({ amount: 0, date: new Date().toISOString().split('T')[0], reason: '' })

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD')

const fetchEmployees = async () => {
    loading.value = true
    try {
        const response = await api.get('/employees')
        employees.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
    loading.value = false
}

const fetchProjects = async () => {
    try {
        const response = await api.get('/projects')
        projects.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
}

const openDialog = (employee = null) => {
    editMode.value = !!employee
    selectedEmployee.value = employee
    if (employee) {
        Object.assign(form, employee)
    } else {
        Object.assign(form, { project_id: null, name: '', phone: '', position: '', salary_amount: 0, joining_date: '', is_active: true })
    }
    dialog.value = true
}

const saveEmployee = async () => {
    saving.value = true
    try {
        if (editMode.value) {
            await api.put(`/employees/${selectedEmployee.value.id}`, form)
        } else {
            await api.post('/employees', form)
        }
        dialog.value = false
        fetchEmployees()
    } catch (error) {
        console.error('Error:', error)
    }
    saving.value = false
}

const openSalaryDialog = (employee) => {
    selectedEmployee.value = employee
    salaryForm.amount = employee.salary_amount
    salaryForm.month = new Date().toISOString().slice(0, 7)
    salaryDialog.value = true
}

const paySalary = async () => {
    payingSalary.value = true
    try {
        await api.post(`/employees/${selectedEmployee.value.id}/salary`, salaryForm)
        salaryDialog.value = false
    } catch (error) {
        console.error('Error:', error)
    }
    payingSalary.value = false
}

const openAdvanceDialog = (employee) => {
    selectedEmployee.value = employee
    advanceForm.amount = 0
    advanceDialog.value = true
}

const giveAdvance = async () => {
    givingAdvance.value = true
    try {
        await api.post(`/employees/${selectedEmployee.value.id}/advance`, advanceForm)
        advanceDialog.value = false
    } catch (error) {
        console.error('Error:', error)
    }
    givingAdvance.value = false
}

onMounted(() => {
    fetchEmployees()
    fetchProjects()
})
</script>
