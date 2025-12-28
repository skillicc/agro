<template>
    <div>
        <div class="d-flex justify-space-between align-center mb-4">
            <h1 class="text-h4">Expenses</h1>
            <v-btn color="primary" @click="openDialog()">
                <v-icon left>mdi-plus</v-icon>
                Add Expense
            </v-btn>
        </div>

        <!-- Filters -->
        <v-card class="mb-4">
            <v-card-text>
                <v-row>
                    <v-col cols="12" sm="6" lg="3">
                        <v-select v-model="filters.project_id" :items="projects" item-title="name" item-value="id" label="Project" clearable @update:model-value="fetchExpenses"></v-select>
                    </v-col>
                    <v-col cols="12" sm="6" lg="3">
                        <v-text-field v-model="filters.start_date" label="Start Date" type="date" @update:model-value="fetchExpenses"></v-text-field>
                    </v-col>
                    <v-col cols="12" sm="6" lg="3">
                        <v-text-field v-model="filters.end_date" label="End Date" type="date" @update:model-value="fetchExpenses"></v-text-field>
                    </v-col>
                </v-row>
            </v-card-text>
        </v-card>

        <v-card>
            <v-card-text>
                <v-data-table :headers="headers" :items="expenses" :loading="loading">
                    <template v-slot:item.sl="{ index }">
                        {{ index + 1 }}
                    </template>
                    <template v-slot:item.amount="{ item }">
                        ৳{{ formatNumber(item.amount) }}
                    </template>
                    <template v-slot:item.actions="{ item }">
                        <v-btn icon size="small" @click="openDialog(item)">
                            <v-icon>mdi-pencil</v-icon>
                        </v-btn>
                        <v-btn icon size="small" color="error" @click="confirmDelete(item)">
                            <v-icon>mdi-delete</v-icon>
                        </v-btn>
                    </template>
                </v-data-table>
            </v-card-text>
        </v-card>

        <!-- Add/Edit Dialog -->
        <v-dialog v-model="dialog" max-width="500">
            <v-card>
                <v-card-title>{{ editMode ? 'Edit Expense' : 'Add Expense' }}</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="saveExpense">
                        <v-select v-model="form.project_id" :items="projects" item-title="name" item-value="id" label="Project" required @update:model-value="loadCategories"></v-select>
                        <v-select v-model="form.expense_category_id" :items="categories" item-title="name" item-value="id" label="Category" required></v-select>
                        <v-text-field v-model="form.bill_no" label="Bill No."></v-text-field>
                        <v-text-field v-model.number="form.amount" label="Amount" type="number" required></v-text-field>
                        <v-text-field v-model="form.date" label="Date" type="date" required></v-text-field>
                        <v-textarea v-model="form.description" label="Description" rows="2"></v-textarea>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="dialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveExpense" :loading="saving">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Confirm -->
        <v-dialog v-model="deleteDialog" max-width="400">
            <v-card>
                <v-card-title>Confirm Delete</v-card-title>
                <v-card-text>Are you sure you want to delete this expense?</v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" @click="deleteExpense" :loading="deleting">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '../../services/api'

const expenses = ref([])
const projects = ref([])
const categories = ref([])
const loading = ref(false)
const dialog = ref(false)
const deleteDialog = ref(false)
const editMode = ref(false)
const selectedExpense = ref(null)
const saving = ref(false)
const deleting = ref(false)

const filters = reactive({ project_id: null, start_date: '', end_date: '' })
const form = reactive({ project_id: null, expense_category_id: null, bill_no: '', amount: 0, date: new Date().toISOString().split('T')[0], description: '' })

const headers = [
    { title: 'SL', key: 'sl', width: '60px' },
    { title: 'Date', key: 'date' },
    { title: 'Bill No.', key: 'bill_no' },
    { title: 'Project', key: 'project.name' },
    { title: 'Category', key: 'category.name' },
    { title: 'Amount', key: 'amount' },
    { title: 'Created By', key: 'creator.name' },
    { title: 'Actions', key: 'actions', sortable: false },
]

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD')

const fetchExpenses = async () => {
    loading.value = true
    try {
        const params = new URLSearchParams()
        if (filters.project_id) params.append('project_id', filters.project_id)
        if (filters.start_date) params.append('start_date', filters.start_date)
        if (filters.end_date) params.append('end_date', filters.end_date)
        const response = await api.get(`/expenses?${params}`)
        expenses.value = response.data
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

const loadCategories = async () => {
    if (!form.project_id) return
    const project = projects.value.find(p => p.id === form.project_id)
    try {
        const response = await api.get(`/expense-categories?project_type=${project?.type || 'all'}`)
        categories.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
}

const openDialog = (expense = null) => {
    editMode.value = !!expense
    selectedExpense.value = expense
    if (expense) {
        Object.assign(form, expense)
        loadCategories()
    } else {
        Object.assign(form, { project_id: null, expense_category_id: null, bill_no: '', amount: 0, date: new Date().toISOString().split('T')[0], description: '' })
    }
    dialog.value = true
}

const saveExpense = async () => {
    saving.value = true
    try {
        if (editMode.value) {
            await api.put(`/expenses/${selectedExpense.value.id}`, form)
        } else {
            await api.post('/expenses', form)
        }
        dialog.value = false
        fetchExpenses()
    } catch (error) {
        console.error('Error:', error)
    }
    saving.value = false
}

const confirmDelete = (expense) => {
    selectedExpense.value = expense
    deleteDialog.value = true
}

const deleteExpense = async () => {
    deleting.value = true
    try {
        await api.delete(`/expenses/${selectedExpense.value.id}`)
        deleteDialog.value = false
        fetchExpenses()
    } catch (error) {
        console.error('Error:', error)
    }
    deleting.value = false
}

onMounted(() => {
    fetchProjects()
    fetchExpenses()
})
</script>
