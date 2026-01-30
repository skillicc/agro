<template>
    <div>
        <div class="d-flex justify-space-between align-center mb-4">
            <h3 class="text-h6">Purchases</h3>
            <v-btn color="primary" size="small" :to="{ name: 'purchase-create', query: { project_id: projectId } }">
                <v-icon left>mdi-plus</v-icon>
                Add Purchase
            </v-btn>
        </div>

        <v-data-table :headers="headers" :items="purchases" :loading="loading" density="compact">
            <template v-slot:item.supplier="{ item }">
                {{ item.supplier?.name }}
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
                <v-btn icon size="x-small" @click="viewPurchase(item)">
                    <v-icon>mdi-eye</v-icon>
                </v-btn>
                <v-btn icon size="x-small" color="error" @click="confirmDelete(item)">
                    <v-icon>mdi-delete</v-icon>
                </v-btn>
            </template>
        </v-data-table>

        <!-- View Dialog -->
        <v-dialog v-model="viewDialog" :max-width="$vuetify.display.xs ? '100%' : '600'" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title>Purchase Details</v-card-title>
                <v-card-text>
                    <v-table density="compact">
                        <tbody>
                            <tr>
                                <td class="font-weight-bold">Date:</td>
                                <td>{{ selectedPurchase?.date }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Supplier:</td>
                                <td>{{ selectedPurchase?.supplier?.name }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Invoice:</td>
                                <td>{{ selectedPurchase?.invoice_no }}</td>
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
                            <tr v-for="item in selectedPurchase?.items" :key="item.id">
                                <td>{{ item.product?.name }}</td>
                                <td>{{ item.quantity }}</td>
                                <td>৳{{ Number(item.unit_price).toLocaleString() }}</td>
                                <td>৳{{ Number(item.total).toLocaleString() }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-right font-weight-bold">Total:</td>
                                <td class="font-weight-bold">৳{{ Number(selectedPurchase?.total).toLocaleString() }}</td>
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
                <v-card-text>Are you sure you want to delete this purchase?</v-card-text>
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
import { ref, onMounted } from 'vue'
import api from '../services/api'

const props = defineProps({
    projectId: { type: [Number, String], required: true }
})

const purchases = ref([])
const loading = ref(false)
const viewDialog = ref(false)
const deleteDialog = ref(false)
const selectedPurchase = ref(null)
const deleting = ref(false)

const headers = [
    { title: 'Date', key: 'date' },
    { title: 'Invoice', key: 'invoice_no' },
    { title: 'Supplier', key: 'supplier' },
    { title: 'Total', key: 'total' },
    { title: 'Paid', key: 'paid' },
    { title: 'Due', key: 'due' },
    { title: 'Actions', key: 'actions', sortable: false },
]

const fetchPurchases = async () => {
    loading.value = true
    try {
        const response = await api.get(`/purchases?project_id=${props.projectId}`)
        purchases.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
    loading.value = false
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
    selectedPurchase.value = purchase
    deleteDialog.value = true
}

const deletePurchase = async () => {
    deleting.value = true
    try {
        await api.delete(`/purchases/${selectedPurchase.value.id}`)
        deleteDialog.value = false
        fetchPurchases()
    } catch (error) {
        console.error('Error:', error)
    }
    deleting.value = false
}

onMounted(() => {
    fetchPurchases()
})
</script>
