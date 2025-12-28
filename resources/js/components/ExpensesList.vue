<template>
    <div>
        <div class="d-flex justify-space-between align-center mb-4">
            <h3 class="text-h6">Expenses</h3>
            <v-btn color="primary" size="small" @click="openDialog()">
                <v-icon left>mdi-plus</v-icon>
                Add Expense
            </v-btn>
        </div>

        <v-data-table :headers="headers" :items="expenses" :loading="loading" density="compact">
            <template v-slot:item.category="{ item }">
                {{ item.category?.name }}
            </template>
            <template v-slot:item.amount="{ item }">
                ৳{{ Number(item.amount).toLocaleString() }}
            </template>
            <template v-slot:item.actions="{ item }">
                <v-btn icon size="x-small" @click="openDialog(item)">
                    <v-icon>mdi-pencil</v-icon>
                </v-btn>
                <v-btn icon size="x-small" color="error" @click="confirmDelete(item)">
                    <v-icon>mdi-delete</v-icon>
                </v-btn>
            </template>
        </v-data-table>

        <!-- Add/Edit Dialog -->
        <v-dialog v-model="dialog" max-width="500">
            <v-card>
                <v-card-title>{{ editMode ? 'Edit Expense' : 'Add Expense' }}</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="saveExpense">
                        <div class="d-flex ga-2 align-center">
                            <v-select
                                v-model="form.expense_category_id"
                                :items="categories"
                                item-title="name"
                                item-value="id"
                                label="Category"
                                required
                                class="flex-grow-1"
                            ></v-select>
                            <v-btn
                                icon
                                size="small"
                                color="primary"
                                variant="tonal"
                                @click="openCategoryDialog"
                                title="Add New Category"
                            >
                                <v-icon>mdi-plus</v-icon>
                            </v-btn>
                        </div>
                        <v-text-field
                            v-model="form.bill_no"
                            label="Bill No."
                        ></v-text-field>
                        <v-text-field
                            v-model="form.amount"
                            label="Amount"
                            type="number"
                            prefix="৳"
                            required
                        ></v-text-field>
                        <v-text-field
                            v-model="form.date"
                            label="Date"
                            type="date"
                            required
                        ></v-text-field>
                        <v-textarea
                            v-model="form.description"
                            label="Description"
                            rows="2"
                        ></v-textarea>
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

        <!-- Add Category Dialog -->
        <v-dialog v-model="categoryDialog" max-width="400">
            <v-card>
                <v-card-title>Add New Category</v-card-title>
                <v-card-text>
                    <v-text-field
                        v-model="newCategoryName"
                        label="Category Name"
                        required
                        autofocus
                        @keyup.enter="saveCategory"
                    ></v-text-field>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="categoryDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveCategory" :loading="savingCategory">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '../services/api'

const props = defineProps({
    projectId: { type: [Number, String], default: null },
    warehouseId: { type: [Number, String], default: null }
})

const expenses = ref([])
const categories = ref([])
const loading = ref(false)
const dialog = ref(false)
const deleteDialog = ref(false)
const editMode = ref(false)
const selectedExpense = ref(null)
const saving = ref(false)
const deleting = ref(false)
const categoryDialog = ref(false)
const newCategoryName = ref('')
const savingCategory = ref(false)

const headers = [
    { title: 'Date', key: 'date' },
    { title: 'Bill No.', key: 'bill_no' },
    { title: 'Category', key: 'category' },
    { title: 'Amount', key: 'amount' },
    { title: 'Description', key: 'description' },
    { title: 'Actions', key: 'actions', sortable: false },
]

const form = reactive({
    expense_category_id: null,
    bill_no: '',
    amount: '',
    date: new Date().toISOString().split('T')[0],
    description: ''
})

const fetchExpenses = async () => {
    loading.value = true
    try {
        const params = props.warehouseId
            ? { warehouse_id: props.warehouseId }
            : { project_id: props.projectId }
        const response = await api.get('/expenses', { params })
        expenses.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
    loading.value = false
}

const fetchCategories = async () => {
    try {
        const response = await api.get('/expense-categories')
        categories.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
}

const openDialog = (expense = null) => {
    editMode.value = !!expense
    selectedExpense.value = expense
    if (expense) {
        Object.assign(form, {
            expense_category_id: expense.expense_category_id,
            bill_no: expense.bill_no || '',
            amount: expense.amount,
            date: expense.date,
            description: expense.description || ''
        })
    } else {
        Object.assign(form, {
            expense_category_id: null,
            bill_no: '',
            amount: '',
            date: new Date().toISOString().split('T')[0],
            description: ''
        })
    }
    dialog.value = true
}

const saveExpense = async () => {
    saving.value = true
    try {
        const data = { ...form }
        if (props.warehouseId) {
            data.warehouse_id = props.warehouseId
        } else {
            data.project_id = props.projectId
        }
        if (editMode.value) {
            await api.put(`/expenses/${selectedExpense.value.id}`, data)
        } else {
            await api.post('/expenses', data)
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

const openCategoryDialog = () => {
    newCategoryName.value = ''
    categoryDialog.value = true
}

const saveCategory = async () => {
    if (!newCategoryName.value.trim()) return

    savingCategory.value = true
    try {
        const response = await api.post('/expense-categories', { name: newCategoryName.value.trim() })
        categoryDialog.value = false
        await fetchCategories()
        // Auto-select the newly created category
        form.expense_category_id = response.data.id
    } catch (error) {
        console.error('Error:', error)
    }
    savingCategory.value = false
}

onMounted(() => {
    fetchExpenses()
    fetchCategories()
})
</script>
