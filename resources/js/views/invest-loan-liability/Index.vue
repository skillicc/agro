<template>
    <div>
        <div class="d-flex justify-space-between align-center mb-4">
            <h1 class="text-h4">Invest, Loan & Liability</h1>
            <v-btn color="primary" @click="openDialog()">
                <v-icon left>mdi-plus</v-icon>
                Add New
            </v-btn>
        </div>

        <!-- Summary Cards -->
        <v-row class="mb-4">
            <v-col cols="12" md="4" lg="2" v-for="card in summaryCards" :key="card.type">
                <v-card :color="card.color" variant="tonal">
                    <v-card-text class="text-center">
                        <v-icon :icon="card.icon" size="32" class="mb-2"></v-icon>
                        <div class="text-caption">{{ card.label }}</div>
                        <div class="text-h6 font-weight-bold">৳{{ formatNumber(summary[card.type] || 0) }}</div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <!-- Filter Tabs -->
        <v-card class="mb-4">
            <v-tabs v-model="activeTab" color="primary" show-arrows>
                <v-tab value="all">
                    <v-icon start>mdi-format-list-bulleted</v-icon>
                    All ({{ items.length }})
                </v-tab>
                <v-tab value="partner">
                    <v-icon start>mdi-handshake</v-icon>
                    Partner ({{ getTypeCount('partner') }})
                </v-tab>
                <v-tab value="shareholder">
                    <v-icon start>mdi-account-group</v-icon>
                    Shareholder ({{ getTypeCount('shareholder') }})
                </v-tab>
                <v-tab value="investment_day_term">
                    <v-icon start>mdi-calendar-clock</v-icon>
                    Day Term ({{ getTypeCount('investment_day_term') }})
                </v-tab>
                <v-tab value="loan">
                    <v-icon start>mdi-bank</v-icon>
                    Loan ({{ getTypeCount('loan') }})
                </v-tab>
                <v-tab value="account_payable">
                    <v-icon start>mdi-arrow-up-circle</v-icon>
                    Payable ({{ getTypeCount('account_payable') }})
                </v-tab>
                <v-tab value="account_receivable">
                    <v-icon start>mdi-arrow-down-circle</v-icon>
                    Receivable ({{ getTypeCount('account_receivable') }})
                </v-tab>
            </v-tabs>
        </v-card>

        <!-- Data Table -->
        <v-card>
            <v-card-text>
                <v-data-table :headers="headers" :items="filteredItems" :loading="loading">
                    <template v-slot:item.type="{ item }">
                        <v-chip :color="getTypeColor(item.type)" size="small">
                            {{ getTypeLabel(item.type) }}
                        </v-chip>
                    </template>
                    <template v-slot:item.amount="{ item }">
                        ৳{{ formatNumber(item.amount) }}
                    </template>
                    <template v-slot:item.date="{ item }">
                        {{ formatDate(item.date) }}
                    </template>
                    <template v-slot:item.due_date="{ item }">
                        {{ item.due_date ? formatDate(item.due_date) : '-' }}
                    </template>
                    <template v-slot:item.status="{ item }">
                        <v-chip :color="getStatusColor(item.status)" size="small">
                            {{ item.status }}
                        </v-chip>
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
        <v-dialog v-model="dialog" max-width="600">
            <v-card>
                <v-card-title>{{ editMode ? 'Edit Entry' : 'Add New Entry' }}</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="save">
                        <v-row>
                            <v-col cols="12" md="6">
                                <v-text-field v-model="form.name" label="Name / Title" required></v-text-field>
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-select
                                    v-model="form.type"
                                    :items="typeOptions"
                                    label="Type"
                                    required
                                ></v-select>
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-text-field v-model.number="form.amount" label="Amount" type="number" prefix="৳" required></v-text-field>
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-text-field v-model="form.date" label="Date" type="date" required></v-text-field>
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-text-field v-model="form.due_date" label="Due Date (Optional)" type="date"></v-text-field>
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-select
                                    v-model="form.status"
                                    :items="statusOptions"
                                    label="Status"
                                ></v-select>
                            </v-col>
                            <v-col cols="12">
                                <v-textarea v-model="form.description" label="Description (Optional)" rows="2"></v-textarea>
                            </v-col>
                        </v-row>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="dialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="save" :loading="saving">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Confirm Dialog -->
        <v-dialog v-model="deleteDialog" max-width="400">
            <v-card>
                <v-card-title>Confirm Delete</v-card-title>
                <v-card-text>Are you sure you want to delete "{{ selectedItem?.name }}"?</v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" @click="deleteItem" :loading="deleting">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import api from '../../services/api'

const items = ref([])
const summary = ref({})
const loading = ref(false)
const dialog = ref(false)
const deleteDialog = ref(false)
const editMode = ref(false)
const selectedItem = ref(null)
const saving = ref(false)
const deleting = ref(false)
const activeTab = ref('all')

const typeOptions = [
    { title: 'Partner', value: 'partner' },
    { title: 'Shareholder', value: 'shareholder' },
    { title: 'Investment Day Term', value: 'investment_day_term' },
    { title: 'Loan', value: 'loan' },
    { title: 'Account Payable (From Total)', value: 'account_payable' },
    { title: 'Account Receivable (From Total)', value: 'account_receivable' },
]

const statusOptions = [
    { title: 'Active', value: 'active' },
    { title: 'Completed', value: 'completed' },
    { title: 'Cancelled', value: 'cancelled' },
]

const summaryCards = [
    { type: 'partner', label: 'Partner', color: 'primary', icon: 'mdi-handshake' },
    { type: 'shareholder', label: 'Shareholder', color: 'info', icon: 'mdi-account-group' },
    { type: 'investment_day_term', label: 'Day Term', color: 'success', icon: 'mdi-calendar-clock' },
    { type: 'loan', label: 'Loan', color: 'warning', icon: 'mdi-bank' },
    { type: 'account_payable', label: 'Payable', color: 'error', icon: 'mdi-arrow-up-circle' },
    { type: 'account_receivable', label: 'Receivable', color: 'teal', icon: 'mdi-arrow-down-circle' },
]

const headers = [
    { title: 'Name', key: 'name' },
    { title: 'Type', key: 'type' },
    { title: 'Amount', key: 'amount' },
    { title: 'Date', key: 'date' },
    { title: 'Due Date', key: 'due_date' },
    { title: 'Status', key: 'status' },
    { title: 'Actions', key: 'actions', sortable: false },
]

const form = reactive({
    name: '',
    type: 'partner',
    amount: 0,
    date: new Date().toISOString().split('T')[0],
    due_date: '',
    description: '',
    status: 'active',
})

const filteredItems = computed(() => {
    if (activeTab.value === 'all') return items.value
    return items.value.filter(item => item.type === activeTab.value)
})

const getTypeCount = (type) => {
    return items.value.filter(item => item.type === type).length
}

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD')

const formatDate = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleDateString('en-GB')
}

const getTypeLabel = (type) => {
    const option = typeOptions.find(o => o.value === type)
    return option ? option.title : type
}

const getTypeColor = (type) => {
    const colors = {
        partner: 'primary',
        shareholder: 'info',
        investment_day_term: 'success',
        loan: 'warning',
        account_payable: 'error',
        account_receivable: 'teal',
    }
    return colors[type] || 'grey'
}

const getStatusColor = (status) => {
    const colors = {
        active: 'success',
        completed: 'info',
        cancelled: 'error',
    }
    return colors[status] || 'grey'
}

const fetchItems = async () => {
    loading.value = true
    try {
        const response = await api.get('/invest-loan-liabilities')
        items.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
    loading.value = false
}

const fetchSummary = async () => {
    try {
        const response = await api.get('/invest-loan-liabilities-summary')
        summary.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
}

const openDialog = (item = null) => {
    editMode.value = !!item
    selectedItem.value = item
    if (item) {
        Object.assign(form, {
            name: item.name,
            type: item.type,
            amount: item.amount,
            date: item.date ? item.date.split('T')[0] : '',
            due_date: item.due_date ? item.due_date.split('T')[0] : '',
            description: item.description || '',
            status: item.status,
        })
    } else {
        // Pre-select the type based on active tab
        const defaultType = activeTab.value !== 'all' ? activeTab.value : 'partner'
        Object.assign(form, {
            name: '',
            type: defaultType,
            amount: 0,
            date: new Date().toISOString().split('T')[0],
            due_date: '',
            description: '',
            status: 'active',
        })
    }
    dialog.value = true
}

const save = async () => {
    saving.value = true
    try {
        const payload = { ...form }
        if (!payload.due_date) delete payload.due_date

        if (editMode.value) {
            await api.put(`/invest-loan-liabilities/${selectedItem.value.id}`, payload)
        } else {
            await api.post('/invest-loan-liabilities', payload)
        }
        dialog.value = false
        fetchItems()
        fetchSummary()
    } catch (error) {
        console.error('Error:', error)
    }
    saving.value = false
}

const confirmDelete = (item) => {
    selectedItem.value = item
    deleteDialog.value = true
}

const deleteItem = async () => {
    deleting.value = true
    try {
        await api.delete(`/invest-loan-liabilities/${selectedItem.value.id}`)
        deleteDialog.value = false
        fetchItems()
        fetchSummary()
    } catch (error) {
        console.error('Error:', error)
    }
    deleting.value = false
}

onMounted(() => {
    fetchItems()
    fetchSummary()
})
</script>
