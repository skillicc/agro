<template>
    <div>
        <div class="d-flex justify-space-between align-center mb-4">
            <h3 class="text-h6">Sales</h3>
            <v-btn color="primary" size="small" :to="{ name: 'sale-create', query: { project_id: projectId } }">
                <v-icon left>mdi-plus</v-icon>
                Add Sale
            </v-btn>
        </div>

        <v-data-table :headers="headers" :items="sales" :loading="loading" density="compact">
            <template v-slot:item.customer="{ item }">
                {{ item.customer?.name }}
            </template>
            <template v-slot:item.total="{ item }">
                ৳{{ Number(item.total).toLocaleString() }}
            </template>
            <template v-slot:item.paid="{ item }">
                ৳{{ Number(item.paid).toLocaleString() }}
            </template>
            <template v-slot:item.due="{ item }">
                <v-chip :color="item.due > 0 ? 'error' : 'success'" size="small">
                    ৳{{ Number(item.due).toLocaleString() }}
                </v-chip>
            </template>
            <template v-slot:item.actions="{ item }">
                <v-btn icon size="x-small" @click="viewSale(item)">
                    <v-icon>mdi-eye</v-icon>
                </v-btn>
                <v-btn icon size="x-small" color="error" @click="confirmDelete(item)">
                    <v-icon>mdi-delete</v-icon>
                </v-btn>
            </template>
        </v-data-table>

        <!-- View Dialog -->
        <v-dialog v-model="viewDialog" max-width="600">
            <v-card>
                <v-card-title>Sale Details</v-card-title>
                <v-card-text>
                    <v-table density="compact">
                        <tbody>
                            <tr>
                                <td class="font-weight-bold">Date:</td>
                                <td>{{ selectedSale?.date }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Customer:</td>
                                <td>{{ selectedSale?.customer?.name }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Challan No.:</td>
                                <td>{{ selectedSale?.challan_no }}</td>
                            </tr>
                        </tbody>
                    </v-table>

                    <h4 class="mt-4 mb-2">Items</h4>
                    <v-table density="compact">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in selectedSale?.items" :key="item.id">
                                <td>{{ item.product?.name }}</td>
                                <td>{{ item.quantity }}</td>
                                <td>৳{{ Number(item.unit_price).toLocaleString() }}</td>
                                <td>৳{{ Number(item.total).toLocaleString() }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-right font-weight-bold">Total:</td>
                                <td class="font-weight-bold">৳{{ Number(selectedSale?.total).toLocaleString() }}</td>
                            </tr>
                        </tfoot>
                    </v-table>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="viewDialog = false">Close</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Confirm -->
        <v-dialog v-model="deleteDialog" max-width="400">
            <v-card>
                <v-card-title>Confirm Delete</v-card-title>
                <v-card-text>Are you sure you want to delete this sale?</v-card-text>
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
import { ref, onMounted } from 'vue'
import api from '../services/api'

const props = defineProps({
    projectId: { type: [Number, String], required: true }
})

const sales = ref([])
const loading = ref(false)
const viewDialog = ref(false)
const deleteDialog = ref(false)
const selectedSale = ref(null)
const deleting = ref(false)

const headers = [
    { title: 'Date', key: 'date' },
    { title: 'Challan No.', key: 'challan_no' },
    { title: 'Customer', key: 'customer' },
    { title: 'Total', key: 'total' },
    { title: 'Paid', key: 'paid' },
    { title: 'Due', key: 'due' },
    { title: 'Actions', key: 'actions', sortable: false },
]

const fetchSales = async () => {
    loading.value = true
    try {
        const response = await api.get(`/sales?project_id=${props.projectId}`)
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
    selectedSale.value = sale
    deleteDialog.value = true
}

const deleteSale = async () => {
    deleting.value = true
    try {
        await api.delete(`/sales/${selectedSale.value.id}`)
        deleteDialog.value = false
        fetchSales()
    } catch (error) {
        console.error('Error:', error)
    }
    deleting.value = false
}

onMounted(() => {
    fetchSales()
})
</script>
