<template>
    <div>
        <div class="d-flex flex-wrap justify-space-between align-center mb-2 mb-sm-4 ga-2">
            <h1 class="text-h5 text-sm-h4">Sales</h1>
            <v-btn color="primary" :to="{ name: 'sale-create' }" :size="$vuetify.display.smAndDown ? 'small' : 'default'">
                <v-icon left>mdi-plus</v-icon>
                <span class="d-none d-sm-inline">New Sale</span>
                <span class="d-sm-none">Add</span>
            </v-btn>
        </div>

        <v-card>
            <v-card-text class="pa-2 pa-sm-4">
                <v-row dense class="mb-3" align="center">
                    <v-col cols="12" sm="6" md="3">
                        <v-text-field
                            v-model="filterStartDate"
                            label="From Date"
                            type="date"
                            clearable
                            hide-details
                            density="compact"
                        ></v-text-field>
                    </v-col>
                    <v-col cols="12" sm="6" md="3">
                        <v-text-field
                            v-model="filterEndDate"
                            label="To Date"
                            type="date"
                            clearable
                            hide-details
                            density="compact"
                        ></v-text-field>
                    </v-col>
                    <v-col cols="12" sm="6" md="3">
                        <v-select
                            v-model="selectedCustomer"
                            :items="customerOptions"
                            item-title="name"
                            item-value="id"
                            label="Filter by Customer"
                            clearable
                            hide-details
                            density="compact"
                        ></v-select>
                    </v-col>
                    <v-col cols="auto">
                        <v-btn size="small" variant="tonal" @click="clearFilters">Clear</v-btn>
                    </v-col>
                </v-row>
                <div class="table-responsive">
                <v-data-table
                    :headers="responsiveHeaders"
                    :items="filteredSales"
                    :loading="loading"
                    density="comfortable"
                    :items-per-page="10"
                    class="elevation-0"
                >
                    <template v-slot:item.sl="{ index }">
                        {{ index + 1 }}
                    </template>
                    <template v-slot:item.total="{ item }">
                        ৳{{ formatNumber(item.total) }}
                    </template>
                    <template v-slot:item.due="{ item }">
                        <v-chip :color="item.due > 0 ? 'warning' : 'success'" size="small">
                            ৳{{ formatNumber(item.due) }}
                        </v-chip>
                    </template>
                    <template v-slot:item.actions="{ item }">
                        <v-btn icon size="small" @click="viewSale(item)">
                            <v-icon>mdi-eye</v-icon>
                        </v-btn>
                        <v-btn icon size="small" color="primary" :to="{ name: 'sale-edit', params: { id: item.id } }">
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
            <v-card v-if="selectedSale">
                <v-card-title class="d-flex justify-space-between align-center">
                    <span>Challan: {{ selectedSale.challan_no }}</span>
                    <v-btn v-if="$vuetify.display.xs" icon size="small" @click="viewDialog = false">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-card-text class="pa-2 pa-sm-4">
                    <v-row dense>
                        <v-col cols="6">
                            <strong>Date:</strong> {{ selectedSale.date }}
                        </v-col>
                        <v-col cols="6">
                            <strong>Customer:</strong> {{ selectedSale.customer?.name || 'Walk-in' }}
                        </v-col>
                        <v-col cols="6">
                            <strong>Project:</strong> {{ selectedSale.project?.name }}
                        </v-col>
                        <v-col cols="6">
                            <strong>Created By:</strong> {{ selectedSale.creator?.name }}
                        </v-col>
                    </v-row>
                    <v-divider class="my-2 my-sm-4"></v-divider>
                    <div class="table-responsive">
                    <v-table density="compact">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in selectedSale.items" :key="item.id">
                                <td>{{ item.product?.name }}</td>
                                <td>{{ item.quantity }}</td>
                                <td>৳{{ formatNumber(item.unit_price) }}</td>
                                <td>৳{{ formatNumber(item.total) }}</td>
                            </tr>
                        </tbody>
                    </v-table>
                    </div>
                    <v-divider class="my-2 my-sm-4"></v-divider>
                    <v-row dense>
                        <v-col cols="12" sm="6" :offset-sm="6">
                            <div class="d-flex justify-space-between"><span>Subtotal:</span> <span>৳{{ formatNumber(selectedSale.subtotal) }}</span></div>
                            <div class="d-flex justify-space-between"><span>Discount:</span> <span>৳{{ formatNumber(selectedSale.discount) }}</span></div>
                            <div class="d-flex justify-space-between text-h6"><span>Total:</span> <span>৳{{ formatNumber(selectedSale.total) }}</span></div>
                            <div class="d-flex justify-space-between text-success"><span>Paid:</span> <span>৳{{ formatNumber(selectedSale.paid) }}</span></div>
                            <div class="d-flex justify-space-between text-warning"><span>Due:</span> <span>৳{{ formatNumber(selectedSale.due) }}</span></div>
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
                <v-card-title class="text-error">Delete Sale</v-card-title>
                <v-card-text>
                    Are you sure you want to delete this sale?
                    <div v-if="saleToDelete" class="mt-2">
                        <strong>{{ saleToDelete.challan_no }}</strong>
                    </div>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" @click="deleteSale" :loading="deleting">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useDisplay } from 'vuetify'
import api from '../../services/api'

const display = useDisplay()
const sales = ref([])
const loading = ref(false)
const selectedCustomer = ref(null)
const filterStartDate = ref('')
const filterEndDate = ref('')
const viewDialog = ref(false)
const selectedSale = ref(null)
const deleteDialog = ref(false)
const saleToDelete = ref(null)
const deleting = ref(false)

const headers = [
    { title: 'SL', key: 'sl', width: '50px' },
    { title: 'Challan No.', key: 'challan_no' },
    { title: 'Date', key: 'date' },
    { title: 'Project', key: 'project.name' },
    { title: 'Customer', key: 'customer.name' },
    { title: 'Total', key: 'total' },
    { title: 'Due', key: 'due' },
    { title: 'Actions', key: 'actions', sortable: false, width: '120px' },
]

// Hide some columns on smaller screens
const responsiveHeaders = computed(() => {
    if (display.xs.value) {
        return headers.filter(h => ['challan_no', 'total', 'actions'].includes(h.key))
    }
    if (display.smAndDown.value) {
        return headers.filter(h => !['project.name'].includes(h.key))
    }
    return headers
})

const customerOptions = computed(() => {
    const seen = new Set()
    return sales.value
        .filter(s => s.customer && !seen.has(s.customer.id) && seen.add(s.customer.id))
        .map(s => ({ id: s.customer.id, name: s.customer.name }))
})

const filteredSales = computed(() => {
    return sales.value.filter(s => {
        if (selectedCustomer.value && s.customer?.id !== selectedCustomer.value) return false
        if (filterStartDate.value && s.date < filterStartDate.value) return false
        if (filterEndDate.value && s.date > filterEndDate.value) return false
        return true
    })
})

const clearFilters = () => {
    selectedCustomer.value = null
    filterStartDate.value = ''
    filterEndDate.value = ''
}

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD')

const fetchSales = async () => {
    loading.value = true
    try {
        const response = await api.get('/sales')
        sales.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
    loading.value = false
}

const viewSale = async (sale) => {
    try {
        const response = await api.get(`/sales/${sale.id}`)
        selectedSale.value = response.data
        viewDialog.value = true
    } catch (error) {
        console.error('Error:', error)
    }
}

const confirmDelete = (sale) => {
    saleToDelete.value = sale
    deleteDialog.value = true
}

const deleteSale = async () => {
    deleting.value = true
    try {
        await api.delete(`/sales/${saleToDelete.value.id}`)
        sales.value = sales.value.filter(s => s.id !== saleToDelete.value.id)
        deleteDialog.value = false
        saleToDelete.value = null
    } catch (error) {
        console.error('Error:', error)
        alert('Error deleting sale')
    }
    deleting.value = false
}

onMounted(() => fetchSales())
</script>

<style scoped>
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
</style>
