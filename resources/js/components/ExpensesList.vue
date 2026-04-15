<template>
    <div>
        <div class="d-flex flex-wrap justify-space-between align-center mb-4 ga-2">
            <h3 class="text-h6">Expenses</h3>
            <div class="d-flex flex-wrap align-center ga-2">
                <v-select
                    v-if="!props.warehouseId"
                    v-model="selectedLandId"
                    :items="projectLands"
                    item-title="name"
                    item-value="id"
                    label="Filter by Land"
                    clearable
                    density="compact"
                    hide-details
                    variant="outlined"
                    style="min-width: 220px;"
                    :disabled="!projectLands.length"
                    @update:model-value="fetchExpenses"
                ></v-select>
                <v-btn color="primary" size="small" @click="openDialog()">
                    <v-icon left>mdi-plus</v-icon>
                    Add Expense
                </v-btn>
            </div>
        </div>

        <v-row dense class="mb-3">
            <v-col cols="12" sm="6" md="3">
                <v-card variant="tonal" color="primary">
                    <v-card-text>
                        <div class="text-caption text-medium-emphasis">Expense Count</div>
                        <div class="text-h6 font-weight-bold">{{ expenseSummary.count }}</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" sm="6" md="3">
                <v-card variant="tonal" color="warning">
                    <v-card-text>
                        <div class="text-caption text-medium-emphasis">Total Amount</div>
                        <div class="text-h6 font-weight-bold">৳{{ formatNumber(expenseSummary.amount) }}</div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <v-data-table :headers="headers" :items="expenses" :loading="loading" density="compact">
            <template v-slot:item.category="{ item }">
                {{ item.category?.name }}
            </template>
            <template v-slot:item.land="{ item }">
                {{ item.land?.name || '-' }}
            </template>
            <template v-slot:item.amount="{ item }">
                ৳{{ Number(item.amount).toLocaleString() }}
            </template>
            <template v-slot:item.actions="{ item }">
                <v-btn icon size="x-small" color="info" @click="openHistory(item)" title="View history">
                    <v-icon>mdi-history</v-icon>
                </v-btn>
                <v-btn icon size="x-small" @click="openDialog(item)">
                    <v-icon>mdi-pencil</v-icon>
                </v-btn>
                <v-btn icon size="x-small" color="error" @click="confirmDelete(item)">
                    <v-icon>mdi-delete</v-icon>
                </v-btn>
            </template>
        </v-data-table>

        <!-- Add/Edit Dialog -->
        <v-dialog v-model="dialog" :max-width="$vuetify.display.xs ? '100%' : '500'" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title>{{ editMode ? 'Edit Expense' : 'Add Expense' }}</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="saveExpense">
                        <v-select
                            v-if="!props.warehouseId"
                            v-model="form.land_id"
                            :items="projectLands"
                            item-title="name"
                            item-value="id"
                            label="Land / Plot"
                            clearable
                            :disabled="!projectLands.length"
                        ></v-select>
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
                            v-model="form.name"
                            label="Name"
                        ></v-text-field>
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

        <v-dialog v-model="historyDialog" :max-width="$vuetify.display.xs ? '100%' : '800'" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title>Expense History</v-card-title>
                <v-card-text>
                    <v-row dense class="mb-3">
                        <v-col cols="12" md="6">
                            <strong>Added By:</strong> {{ historyExpense?.creator?.name || '-' }}
                        </v-col>
                        <v-col cols="12" md="6">
                            <strong>Last Edited By:</strong> {{ historyExpense?.editor?.name || historyExpense?.creator?.name || '-' }}
                        </v-col>
                        <v-col cols="12" md="6">
                            <strong>Created At:</strong> {{ formatDateTime(historyExpense?.created_at) }}
                        </v-col>
                        <v-col cols="12" md="6">
                            <strong>Updated At:</strong> {{ formatDateTime(historyExpense?.updated_at) }}
                        </v-col>
                    </v-row>

                    <v-table density="compact">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>By</th>
                                <th>Time</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="historyLoading">
                                <td colspan="4" class="text-medium-emphasis">Loading history...</td>
                            </tr>
                            <template v-else>
                                <tr v-if="!historyItems.length">
                                    <td colspan="4" class="text-medium-emphasis">No detailed edit history found for this expense yet.</td>
                                </tr>
                                <tr v-for="entry in historyItems" v-else :key="entry.id">
                                    <td>
                                        <v-chip size="x-small" :color="historyActionColor(entry.action)">{{ entry.action }}</v-chip>
                                    </td>
                                    <td>{{ entry.user?.name || '-' }}</td>
                                    <td>{{ formatDateTime(entry.created_at) }}</td>
                                    <td>
                                        <div v-for="line in historyChangeLines(entry)" :key="line" class="text-caption">
                                            {{ line }}
                                        </div>
                                    </td>
                                </tr>
                            </template>
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
import { computed, ref, reactive, onMounted } from 'vue'
import api from '../services/api'

const props = defineProps({
    projectId: { type: [Number, String], default: null },
    warehouseId: { type: [Number, String], default: null }
})

const expenses = ref([])
const categories = ref([])
const projectLands = ref([])
const loading = ref(false)
const dialog = ref(false)
const deleteDialog = ref(false)
const historyDialog = ref(false)
const historyLoading = ref(false)
const editMode = ref(false)
const selectedExpense = ref(null)
const historyExpense = ref(null)
const historyItems = ref([])
const saving = ref(false)
const deleting = ref(false)
const selectedLandId = ref(null)
const categoryDialog = ref(false)
const newCategoryName = ref('')
const savingCategory = ref(false)

const headers = [
    { title: 'Date', key: 'date' },
    { title: 'Name', key: 'name' },
    { title: 'Bill No.', key: 'bill_no' },
    { title: 'Land', key: 'land' },
    { title: 'Category', key: 'category' },
    { title: 'Amount', key: 'amount' },
    { title: 'Description', key: 'description' },
    { title: 'Actions', key: 'actions', sortable: false },
]

const form = reactive({
    land_id: null,
    expense_category_id: null,
    name: '',
    bill_no: '',
    amount: '',
    date: new Date().toISOString().split('T')[0],
    description: ''
})

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD')

const expenseSummary = computed(() => {
    return expenses.value.reduce((summary, expense) => {
        summary.count += 1
        summary.amount += Number(expense.amount || 0)
        return summary
    }, {
        count: 0,
        amount: 0,
    })
})

const fetchExpenses = async () => {
    loading.value = true
    try {
        const params = props.warehouseId
            ? { warehouse_id: props.warehouseId }
            : { project_id: props.projectId }

        if (selectedLandId.value) {
            params.land_id = selectedLandId.value
        }

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

const formatDateTime = (value) => {
    if (!value) return '-'
    return new Date(value).toLocaleString('en-BD')
}

const historyActionColor = (action) => ({
    created: 'success',
    updated: 'info',
    deleted: 'error',
}[action] || 'grey')

const historyChangeLines = (entry) => {
    const changes = Object.values(entry?.changes || {})

    if (!changes.length) {
        return [entry?.action || 'No changes']
    }

    return changes.map((change) => {
        const oldValue = change.old ?? '-'
        const newValue = change.new ?? '-'

        if (entry.action === 'created') {
            return `${change.label}: ${newValue}`
        }

        if (entry.action === 'deleted') {
            return `${change.label}: ${oldValue}`
        }

        return `${change.label}: ${oldValue} → ${newValue}`
    })
}

const fetchProjectLands = async () => {
    if (!props.projectId || props.warehouseId) {
        projectLands.value = []
        return
    }

    try {
        const response = await api.get(`/lands?project_id=${props.projectId}`)
        projectLands.value = response.data
    } catch (error) {
        console.error('Error loading lands:', error)
    }
}

const openDialog = (expense = null) => {
    editMode.value = !!expense
    selectedExpense.value = expense
    if (expense) {
        Object.assign(form, {
            land_id: expense.land_id || null,
            expense_category_id: expense.expense_category_id,
            name: expense.name || '',
            bill_no: expense.bill_no || '',
            amount: expense.amount,
            date: expense.date,
            description: expense.description || ''
        })
    } else {
        Object.assign(form, {
            land_id: selectedLandId.value || null,
            expense_category_id: null,
            name: '',
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

const openHistory = async (expense) => {
    historyLoading.value = true
    historyExpense.value = null
    historyItems.value = []

    try {
        const [expenseResponse, historyResponse] = await Promise.all([
            api.get(`/expenses/${expense.id}`),
            api.get(`/expenses/${expense.id}/history`),
        ])

        historyExpense.value = expenseResponse.data
        historyItems.value = Array.isArray(historyResponse.data)
            ? historyResponse.data
            : Array.isArray(historyResponse.data?.data)
                ? historyResponse.data.data
                : []
        historyDialog.value = true
    } catch (error) {
        console.error('Error:', error)
    }

    historyLoading.value = false
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
    fetchProjectLands()
})
</script>
