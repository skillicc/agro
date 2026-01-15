<template>
    <div>
        <div class="d-flex justify-space-between align-center mb-4">
            <h1 class="text-h4">Purchases</h1>
            <v-btn color="primary" :to="{ name: 'purchase-create' }">
                <v-icon left>mdi-plus</v-icon>
                New Purchase
            </v-btn>
        </div>

        <v-card>
            <v-card-text>
                <v-data-table :headers="headers" :items="purchases" :loading="loading">
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
                    </template>
                </v-data-table>
            </v-card-text>
        </v-card>

        <!-- View Dialog -->
        <v-dialog v-model="viewDialog" max-width="700">
            <v-card v-if="selectedPurchase">
                <v-card-title>{{ selectedPurchase.invoice_no }}</v-card-title>
                <v-card-text>
                    <v-row>
                        <v-col cols="6">
                            <strong>Date:</strong> {{ selectedPurchase.date }}
                        </v-col>
                        <v-col cols="6">
                            <strong>Supplier:</strong> {{ selectedPurchase.supplier?.name || 'N/A' }}
                        </v-col>
                        <v-col cols="6">
                            <strong>Project:</strong> {{ selectedPurchase.project?.name }}
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
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'

const purchases = ref([])
const loading = ref(false)
const viewDialog = ref(false)
const selectedPurchase = ref(null)

const headers = [
    { title: 'SL', key: 'sl', width: '60px' },
    { title: 'Invoice', key: 'invoice_no' },
    { title: 'Date', key: 'date' },
    { title: 'Project', key: 'project.name' },
    { title: 'Supplier', key: 'supplier.name' },
    { title: 'Total', key: 'total' },
    { title: 'Due', key: 'due' },
    { title: 'Actions', key: 'actions', sortable: false },
]

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD')

const calculateTotalMRP = (items) => {
    if (!items) return 0
    return items.reduce((sum, item) => sum + (parseFloat(item.total_mrp) || 0), 0)
}

const fetchPurchases = async () => {
    loading.value = true
    try {
        const response = await api.get('/purchases')
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

onMounted(() => fetchPurchases())
</script>
