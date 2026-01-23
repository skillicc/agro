<template>
    <div>
        <div class="d-flex flex-wrap justify-space-between align-center mb-2 mb-sm-4 ga-2">
            <h1 class="text-h5 text-sm-h4">Purchases</h1>
            <v-btn color="primary" :to="{ name: 'purchase-create' }" :size="$vuetify.display.smAndDown ? 'small' : 'default'">
                <v-icon left>mdi-plus</v-icon>
                <span class="d-none d-sm-inline">New Purchase</span>
                <span class="d-sm-none">Add</span>
            </v-btn>
        </div>

        <!-- Filters -->
        <v-card class="mb-4">
            <v-card-text class="pa-2 pa-sm-4">
                <v-row dense>
                    <v-col cols="12" sm="4" md="3">
                        <v-autocomplete
                            v-model="filters.supplier_id"
                            :items="suppliers"
                            item-title="name"
                            item-value="id"
                            label="Supplier"
                            density="compact"
                            clearable
                            hide-details
                            @update:model-value="fetchPurchases"
                        ></v-autocomplete>
                    </v-col>
                    <v-col cols="6" sm="4" md="3">
                        <v-text-field
                            v-model="filters.start_date"
                            label="From Date"
                            type="date"
                            density="compact"
                            hide-details
                            clearable
                            @update:model-value="fetchPurchases"
                        ></v-text-field>
                    </v-col>
                    <v-col cols="6" sm="4" md="3">
                        <v-text-field
                            v-model="filters.end_date"
                            label="To Date"
                            type="date"
                            density="compact"
                            hide-details
                            clearable
                            @update:model-value="fetchPurchases"
                        ></v-text-field>
                    </v-col>
                    <v-col cols="6" sm="4" md="2">
                        <v-select
                            v-model="filters.sort_by"
                            :items="sortOptions"
                            item-title="label"
                            item-value="value"
                            label="Sort By"
                            density="compact"
                            hide-details
                            @update:model-value="fetchPurchases"
                        ></v-select>
                    </v-col>
                    <v-col cols="6" sm="4" md="2">
                        <v-btn-toggle v-model="filters.sort_order" mandatory density="compact" @update:model-value="fetchPurchases">
                            <v-btn value="desc" size="small">
                                <v-icon>mdi-sort-descending</v-icon>
                                DESC
                            </v-btn>
                            <v-btn value="asc" size="small">
                                <v-icon>mdi-sort-ascending</v-icon>
                                ASC
                            </v-btn>
                        </v-btn-toggle>
                    </v-col>
                </v-row>
                <v-row dense class="mt-2">
                    <v-col cols="12" class="d-flex align-center ga-2">
                        <v-btn color="primary" variant="tonal" size="small" @click="fetchPurchases">
                            <v-icon left>mdi-magnify</v-icon>
                            Search
                        </v-btn>
                        <v-btn variant="text" size="small" @click="clearFilters">
                            <v-icon left>mdi-filter-off</v-icon>
                            Clear
                        </v-btn>
                    </v-col>
                </v-row>
            </v-card-text>
        </v-card>

        <v-card>
            <v-card-text class="pa-2 pa-sm-4">
                <div class="table-responsive">
                <v-data-table
                    :headers="responsiveHeaders"
                    :items="purchases"
                    :loading="loading"
                    density="comfortable"
                    :items-per-page="10"
                    class="elevation-0">
                    <template v-slot:item.sl="{ index }">
                        {{ index + 1 }}
                    </template>
                    <template v-slot:item.total="{ item }">
                        ৳{{ formatNumber(item.total) }}
                    </template>
                    <template v-slot:item.due="{ item }">
                        <v-chip :color="item.due > 0 ? 'error' : 'success'" size="small">
                            ৳{{ formatNumber(item.due) }}
                        </v-chip>
                    </template>
                    <template v-slot:item.actions="{ item }">
                        <v-btn icon size="small" @click="viewPurchase(item)">
                            <v-icon>mdi-eye</v-icon>
                        </v-btn>
                        <v-btn icon size="small" color="primary" :to="{ name: 'purchase-edit', params: { id: item.id } }">
                            <v-icon>mdi-pencil</v-icon>
                        </v-btn>
                        <v-btn icon size="small" color="error" @click="confirmDelete(item)">
                            <v-icon>mdi-delete</v-icon>
                        </v-btn>
                    </template>
                </v-data-table>
                </div>
            </v-card-text>
        </v-card>

        <!-- View Dialog -->
        <v-dialog v-model="viewDialog" :max-width="$vuetify.display.smAndDown ? '95%' : '700'" :fullscreen="$vuetify.display.xs">
            <v-card v-if="selectedPurchase">
                <v-card-title>{{ selectedPurchase.reference_no }}</v-card-title>
                <v-card-text>
                    <v-row>
                        <v-col cols="6">
                            <strong>Reference No:</strong> {{ selectedPurchase.reference_no }}
                        </v-col>
                        <v-col cols="6">
                            <strong>Invoice No:</strong> {{ selectedPurchase.invoice_no || '-' }}
                        </v-col>
                        <v-col cols="6">
                            <strong>Date:</strong> {{ selectedPurchase.date }}
                        </v-col>
                        <v-col cols="6">
                            <strong>Supplier:</strong> {{ selectedPurchase.supplier?.name || 'N/A' }}
                        </v-col>
                        <v-col cols="6">
                            <strong>Project:</strong> {{ selectedPurchase.project?.name || '-' }}
                        </v-col>
                        <v-col cols="6">
                            <strong>Warehouse:</strong> {{ selectedPurchase.warehouse?.name || '-' }}
                        </v-col>
                        <v-col cols="6">
                            <strong>Created By:</strong> {{ selectedPurchase.creator?.name }}
                        </v-col>
                    </v-row>
                    <v-divider class="my-4"></v-divider>
                    <v-table density="compact">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Size</th>
                                <th>Qty</th>
                                <th>Unit (TP)</th>
                                <th>Unit (MRP)</th>
                                <th>Total (TP)</th>
                                <th>Total (MRP)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in selectedPurchase.items" :key="item.id">
                                <td>{{ item.product?.name }}</td>
                                <td>{{ item.size || '-' }}</td>
                                <td>{{ item.quantity }}</td>
                                <td>৳{{ formatNumber(item.unit_price) }}</td>
                                <td>৳{{ formatNumber(item.unit_mrp) }}</td>
                                <td class="text-primary">৳{{ formatNumber(item.total) }}</td>
                                <td class="text-success">৳{{ formatNumber(item.total_mrp) }}</td>
                            </tr>
                        </tbody>
                    </v-table>
                    <v-divider class="my-4"></v-divider>
                    <v-row>
                        <v-col cols="12" lg="6">
                            <div class="d-flex justify-space-between"><span>Subtotal (TP):</span> <span class="text-primary">৳{{ formatNumber(selectedPurchase.subtotal) }}</span></div>
                            <div class="d-flex justify-space-between"><span>Subtotal (MRP):</span> <span class="text-success">৳{{ formatNumber(calculateTotalMRP(selectedPurchase.items)) }}</span></div>
                            <div class="d-flex justify-space-between"><span>Discount:</span> <span>৳{{ formatNumber(selectedPurchase.discount) }}</span></div>
                        </v-col>
                        <v-col cols="12" lg="6">
                            <div class="d-flex justify-space-between text-h6"><span>Total (TP):</span> <span class="text-primary">৳{{ formatNumber(selectedPurchase.total) }}</span></div>
                            <div class="d-flex justify-space-between text-h6"><span>Total (MRP):</span> <span class="text-success">৳{{ formatNumber(calculateTotalMRP(selectedPurchase.items)) }}</span></div>
                            <v-divider class="my-2"></v-divider>
                            <div class="d-flex justify-space-between text-success"><span>Paid:</span> <span>৳{{ formatNumber(selectedPurchase.paid) }}</span></div>
                            <div class="d-flex justify-space-between text-error"><span>Due:</span> <span>৳{{ formatNumber(selectedPurchase.due) }}</span></div>
                        </v-col>
                    </v-row>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="viewDialog = false">Close</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Confirmation Dialog -->
        <v-dialog v-model="deleteDialog" :max-width="$vuetify.display.xs ? '95%' : '400'">
            <v-card>
                <v-card-title class="text-error">Delete Purchase</v-card-title>
                <v-card-text>
                    Are you sure you want to delete this purchase?
                    <div v-if="purchaseToDelete" class="mt-2">
                        <strong>{{ purchaseToDelete.reference_no }}</strong>
                    </div>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" @click="deletePurchase" :loading="deleting">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useDisplay } from 'vuetify'
import api from '../../services/api'

const display = useDisplay()
const purchases = ref([])
const suppliers = ref([])
const loading = ref(false)
const viewDialog = ref(false)
const selectedPurchase = ref(null)
const deleteDialog = ref(false)
const purchaseToDelete = ref(null)
const deleting = ref(false)

const filters = reactive({
    supplier_id: null,
    start_date: null,
    end_date: null,
    sort_by: 'date',
    sort_order: 'desc',
})

const sortOptions = [
    { label: 'Date', value: 'date' },
    { label: 'Reference', value: 'reference_no' },
    { label: 'Total', value: 'total' },
    { label: 'Due', value: 'due' },
    { label: 'Supplier', value: 'supplier' },
]

const headers = [
    { title: 'SL', key: 'sl', width: '50px' },
    { title: 'Reference', key: 'reference_no' },
    { title: 'Invoice', key: 'invoice_no' },
    { title: 'Date', key: 'date' },
    { title: 'Project', key: 'project.name' },
    { title: 'Warehouse', key: 'warehouse.name' },
    { title: 'Supplier', key: 'supplier.name' },
    { title: 'Total', key: 'total' },
    { title: 'Due', key: 'due' },
    { title: 'Actions', key: 'actions', sortable: false, width: '120px' },
]

// Hide some columns on smaller screens
const responsiveHeaders = computed(() => {
    if (display.xs.value) {
        return headers.filter(h => ['reference_no', 'total', 'actions'].includes(h.key))
    }
    if (display.smAndDown.value) {
        return headers.filter(h => !['warehouse.name', 'project.name', 'invoice_no'].includes(h.key))
    }
    if (display.md.value) {
        return headers.filter(h => h.key !== 'warehouse.name')
    }
    return headers
})

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD')

const calculateTotalMRP = (items) => {
    if (!items) return 0
    return items.reduce((sum, item) => sum + (parseFloat(item.total_mrp) || 0), 0)
}

const fetchSuppliers = async () => {
    try {
        const response = await api.get('/suppliers')
        suppliers.value = response.data
    } catch (error) {
        console.error('Error fetching suppliers:', error)
    }
}

const fetchPurchases = async () => {
    loading.value = true
    try {
        const params = new URLSearchParams()
        if (filters.supplier_id) params.append('supplier_id', filters.supplier_id)
        if (filters.start_date) params.append('start_date', filters.start_date)
        if (filters.end_date) params.append('end_date', filters.end_date)

        const query = params.toString() ? `?${params.toString()}` : ''
        const response = await api.get(`/purchases${query}`)
        let data = response.data

        // Apply sorting
        if (filters.sort_by) {
            data.sort((a, b) => {
                let valA, valB
                if (filters.sort_by === 'supplier') {
                    valA = a.supplier?.name || ''
                    valB = b.supplier?.name || ''
                } else if (filters.sort_by === 'total' || filters.sort_by === 'due') {
                    valA = parseFloat(a[filters.sort_by]) || 0
                    valB = parseFloat(b[filters.sort_by]) || 0
                } else {
                    valA = a[filters.sort_by] || ''
                    valB = b[filters.sort_by] || ''
                }

                if (filters.sort_order === 'asc') {
                    return valA > valB ? 1 : valA < valB ? -1 : 0
                } else {
                    return valA < valB ? 1 : valA > valB ? -1 : 0
                }
            })
        }

        purchases.value = data
    } catch (error) {
        console.error('Error:', error)
    }
    loading.value = false
}

const clearFilters = () => {
    filters.supplier_id = null
    filters.start_date = null
    filters.end_date = null
    filters.sort_by = 'date'
    filters.sort_order = 'desc'
    fetchPurchases()
}

const viewPurchase = async (purchase) => {
    try {
        const response = await api.get(`/purchases/${purchase.id}`)
        selectedPurchase.value = response.data
        viewDialog.value = true
    } catch (error) {
        console.error('Error:', error)
    }
}

const confirmDelete = (purchase) => {
    purchaseToDelete.value = purchase
    deleteDialog.value = true
}

const deletePurchase = async () => {
    deleting.value = true
    try {
        await api.delete(`/purchases/${purchaseToDelete.value.id}`)
        purchases.value = purchases.value.filter(p => p.id !== purchaseToDelete.value.id)
        deleteDialog.value = false
        purchaseToDelete.value = null
    } catch (error) {
        console.error('Error:', error)
        alert('Error deleting purchase')
    }
    deleting.value = false
}

onMounted(() => {
    fetchSuppliers()
    fetchPurchases()
})
</script>

<style scoped>
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
</style>
