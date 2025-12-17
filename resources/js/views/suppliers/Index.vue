<template>
    <div>
        <div class="d-flex flex-column flex-sm-row justify-space-between align-start align-sm-center mb-4 ga-2">
            <h1 class="text-h5 text-sm-h4">Suppliers</h1>
            <div class="d-flex ga-2">
                <v-btn color="success" @click="openPaymentDialog()" :size="$vuetify.display.xs ? 'small' : 'default'">
                    <v-icon left>mdi-cash-plus</v-icon>
                    <span class="d-none d-sm-inline">Add Payment</span>
                    <span class="d-sm-none">Pay</span>
                </v-btn>
                <v-btn color="primary" @click="openDialog()" :size="$vuetify.display.xs ? 'small' : 'default'">
                    <v-icon left>mdi-plus</v-icon>
                    <span class="d-none d-sm-inline">Add Supplier</span>
                    <span class="d-sm-none">Add</span>
                </v-btn>
            </div>
        </div>

        <v-card>
            <v-card-text class="pa-2 pa-sm-4">
                <!-- Mobile Card View -->
                <div v-if="$vuetify.display.xs" class="mobile-list">
                    <v-card
                        v-for="supplier in suppliers"
                        :key="supplier.id"
                        variant="outlined"
                        class="mb-2"
                    >
                        <v-card-text class="pa-3">
                            <div class="d-flex justify-space-between align-start">
                                <div>
                                    <div class="font-weight-bold">{{ supplier.name }}</div>
                                    <div class="text-caption text-grey">{{ supplier.phone }}</div>
                                </div>
                                <v-chip :color="supplier.total_due > 0 ? 'error' : 'success'" size="small">
                                    ৳{{ formatNumber(supplier.total_due) }}
                                </v-chip>
                            </div>
                            <v-divider class="my-2"></v-divider>
                            <div class="d-flex justify-space-between text-caption">
                                <span>Purchase: ৳{{ formatNumber(supplier.total_purchase) }}</span>
                                <span class="text-success">Paid: ৳{{ formatNumber(supplier.total_paid) }}</span>
                            </div>
                            <div class="d-flex justify-end mt-2 ga-1">
                                <v-btn icon size="x-small" variant="tonal" @click="viewLedger(supplier)">
                                    <v-icon size="small">mdi-file-document</v-icon>
                                </v-btn>
                                <v-btn icon size="x-small" variant="tonal" @click="openDialog(supplier)">
                                    <v-icon size="small">mdi-pencil</v-icon>
                                </v-btn>
                                <v-btn icon size="x-small" variant="tonal" color="error" @click="confirmDelete(supplier)">
                                    <v-icon size="small">mdi-delete</v-icon>
                                </v-btn>
                            </div>
                        </v-card-text>
                    </v-card>
                    <div v-if="loading" class="text-center py-4">
                        <v-progress-circular indeterminate></v-progress-circular>
                    </div>
                </div>

                <!-- Desktop Table View -->
                <v-data-table
                    v-else
                    :headers="responsiveHeaders"
                    :items="suppliers"
                    :loading="loading"
                    class="elevation-0"
                    :density="$vuetify.display.smAndDown ? 'compact' : 'default'"
                >
                    <template v-slot:item.total_purchase="{ item }">
                        ৳{{ formatNumber(item.total_purchase) }}
                    </template>
                    <template v-slot:item.total_paid="{ item }">
                        <span class="text-success">৳{{ formatNumber(item.total_paid) }}</span>
                    </template>
                    <template v-slot:item.total_due="{ item }">
                        <v-chip :color="item.total_due > 0 ? 'error' : 'success'" size="small">
                            ৳{{ formatNumber(item.total_due) }}
                        </v-chip>
                    </template>
                    <template v-slot:item.actions="{ item }">
                        <div class="d-flex ga-1">
                            <v-btn icon size="x-small" variant="text" @click="viewLedger(item)">
                                <v-icon>mdi-file-document</v-icon>
                            </v-btn>
                            <v-btn icon size="x-small" variant="text" @click="openDialog(item)">
                                <v-icon>mdi-pencil</v-icon>
                            </v-btn>
                            <v-btn icon size="x-small" variant="text" color="error" @click="confirmDelete(item)">
                                <v-icon>mdi-delete</v-icon>
                            </v-btn>
                        </div>
                    </template>
                </v-data-table>
            </v-card-text>
        </v-card>

        <!-- Add/Edit Dialog - Responsive -->
        <v-dialog v-model="dialog" :max-width="$vuetify.display.xs ? '100%' : 500" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title class="d-flex justify-space-between align-center">
                    <span>{{ editMode ? 'Edit Supplier' : 'Add Supplier' }}</span>
                    <v-btn v-if="$vuetify.display.xs" icon variant="text" @click="dialog = false">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="saveSupplier">
                        <v-text-field v-model="form.name" label="Name" required density="comfortable"></v-text-field>
                        <v-text-field v-model="form.phone" label="Phone" density="comfortable"></v-text-field>
                        <v-text-field v-model="form.email" label="Email" type="email" density="comfortable"></v-text-field>
                        <v-textarea v-model="form.address" label="Address" rows="2" density="comfortable"></v-textarea>
                    </v-form>
                </v-card-text>
                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn @click="dialog = false" :size="$vuetify.display.xs ? 'small' : 'default'">Cancel</v-btn>
                    <v-btn color="primary" @click="saveSupplier" :loading="saving" :size="$vuetify.display.xs ? 'small' : 'default'">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Ledger Dialog - Responsive -->
        <v-dialog v-model="ledgerDialog" :max-width="$vuetify.display.xs ? '100%' : 800" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title class="d-flex justify-space-between align-center text-body-1 text-sm-h6">
                    <span>{{ selectedSupplier?.name }} - Ledger</span>
                    <v-btn v-if="$vuetify.display.xs" icon variant="text" @click="ledgerDialog = false">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-card-text>
                    <v-row>
                        <v-col cols="12" sm="4" class="text-center">
                            <div class="text-h6 text-sm-h5">৳{{ formatNumber(ledger.total_purchase) }}</div>
                            <div class="text-caption">Total Purchase</div>
                        </v-col>
                        <v-col cols="6" sm="4" class="text-center">
                            <div class="text-h6 text-sm-h5 text-success">৳{{ formatNumber(ledger.total_paid) }}</div>
                            <div class="text-caption">Total Paid</div>
                        </v-col>
                        <v-col cols="6" sm="4" class="text-center">
                            <div class="text-h6 text-sm-h5 text-error">৳{{ formatNumber(ledger.total_due) }}</div>
                            <div class="text-caption">Total Due</div>
                        </v-col>
                    </v-row>
                </v-card-text>
                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn @click="ledgerDialog = false">Close</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Confirm - Responsive -->
        <v-dialog v-model="deleteDialog" :max-width="$vuetify.display.xs ? '95%' : 400">
            <v-card>
                <v-card-title class="text-body-1 text-sm-h6">Confirm Delete</v-card-title>
                <v-card-text>Are you sure you want to delete "{{ selectedSupplier?.name }}"?</v-card-text>
                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn @click="deleteDialog = false" :size="$vuetify.display.xs ? 'small' : 'default'">Cancel</v-btn>
                    <v-btn color="error" @click="deleteSupplier" :loading="deleting" :size="$vuetify.display.xs ? 'small' : 'default'">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Payment Dialog -->
        <v-dialog v-model="paymentDialog" :max-width="$vuetify.display.xs ? '100%' : 500" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title class="d-flex justify-space-between align-center">
                    <span>Add Supplier Payment</span>
                    <v-btn v-if="$vuetify.display.xs" icon variant="text" @click="paymentDialog = false">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="savePayment">
                        <v-select
                            v-model="paymentForm.supplier_id"
                            :items="suppliers"
                            item-title="name"
                            item-value="id"
                            label="Supplier"
                            required
                            density="comfortable"
                        >
                            <template v-slot:item="{ item, props }">
                                <v-list-item v-bind="props">
                                    <template v-slot:append>
                                        <v-chip size="x-small" :color="item.raw.total_due > 0 ? 'error' : 'success'">
                                            ৳{{ formatNumber(item.raw.total_due) }} Due
                                        </v-chip>
                                    </template>
                                </v-list-item>
                            </template>
                        </v-select>
                        <v-text-field v-model.number="paymentForm.amount" label="Amount" type="number" prefix="৳" required density="comfortable"></v-text-field>
                        <v-text-field v-model="paymentForm.date" label="Date" type="date" required density="comfortable"></v-text-field>
                        <v-select
                            v-model="paymentForm.payment_method"
                            :items="paymentMethods"
                            label="Payment Method"
                            density="comfortable"
                        ></v-select>
                        <v-textarea v-model="paymentForm.note" label="Note (Optional)" rows="2" density="comfortable"></v-textarea>
                    </v-form>
                </v-card-text>
                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn @click="paymentDialog = false">Cancel</v-btn>
                    <v-btn color="success" @click="savePayment" :loading="savingPayment">Save Payment</v-btn>
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

const suppliers = ref([])
const loading = ref(false)
const dialog = ref(false)
const ledgerDialog = ref(false)
const deleteDialog = ref(false)
const paymentDialog = ref(false)
const editMode = ref(false)
const selectedSupplier = ref(null)
const saving = ref(false)
const deleting = ref(false)
const savingPayment = ref(false)
const ledger = ref({})

const paymentMethods = ['cash', 'bank', 'bkash', 'nagad', 'check', 'other']

const paymentForm = reactive({
    supplier_id: null,
    amount: 0,
    date: new Date().toISOString().split('T')[0],
    payment_method: 'cash',
    note: '',
})

const allHeaders = [
    { title: 'Name', key: 'name' },
    { title: 'Phone', key: 'phone' },
    { title: 'Total Purchase', key: 'total_purchase' },
    { title: 'Total Paid', key: 'total_paid' },
    { title: 'Total Due', key: 'total_due' },
    { title: 'Actions', key: 'actions', sortable: false },
]

// Responsive headers - hide some columns on smaller screens
const responsiveHeaders = computed(() => {
    if (display.smAndDown.value) {
        return allHeaders.filter(h => ['name', 'total_due', 'actions'].includes(h.key))
    }
    if (display.md.value) {
        return allHeaders.filter(h => h.key !== 'phone')
    }
    return allHeaders
})

const form = reactive({ name: '', phone: '', email: '', address: '' })

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD')

const fetchSuppliers = async () => {
    loading.value = true
    try {
        const response = await api.get('/suppliers')
        suppliers.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
    loading.value = false
}

const openDialog = (supplier = null) => {
    editMode.value = !!supplier
    selectedSupplier.value = supplier
    if (supplier) {
        Object.assign(form, supplier)
    } else {
        Object.assign(form, { name: '', phone: '', email: '', address: '' })
    }
    dialog.value = true
}

const saveSupplier = async () => {
    saving.value = true
    try {
        if (editMode.value) {
            await api.put(`/suppliers/${selectedSupplier.value.id}`, form)
        } else {
            await api.post('/suppliers', form)
        }
        dialog.value = false
        fetchSuppliers()
    } catch (error) {
        console.error('Error:', error)
    }
    saving.value = false
}

const viewLedger = async (supplier) => {
    selectedSupplier.value = supplier
    try {
        const response = await api.get(`/suppliers/${supplier.id}/ledger`)
        ledger.value = response.data
        ledgerDialog.value = true
    } catch (error) {
        console.error('Error:', error)
    }
}

const confirmDelete = (supplier) => {
    selectedSupplier.value = supplier
    deleteDialog.value = true
}

const deleteSupplier = async () => {
    deleting.value = true
    try {
        await api.delete(`/suppliers/${selectedSupplier.value.id}`)
        deleteDialog.value = false
        fetchSuppliers()
    } catch (error) {
        console.error('Error:', error)
    }
    deleting.value = false
}

const openPaymentDialog = (supplier = null) => {
    if (supplier) {
        paymentForm.supplier_id = supplier.id
    } else {
        paymentForm.supplier_id = null
    }
    paymentForm.amount = 0
    paymentForm.date = new Date().toISOString().split('T')[0]
    paymentForm.payment_method = 'cash'
    paymentForm.note = ''
    paymentDialog.value = true
}

const savePayment = async () => {
    if (!paymentForm.supplier_id || !paymentForm.amount) return

    savingPayment.value = true
    try {
        await api.post(`/suppliers/${paymentForm.supplier_id}/payment`, paymentForm)
        paymentDialog.value = false
        fetchSuppliers()
    } catch (error) {
        console.error('Error:', error)
    }
    savingPayment.value = false
}

onMounted(() => fetchSuppliers())
</script>

<style scoped>
.mobile-list {
    max-height: calc(100vh - 200px);
    overflow-y: auto;
}
</style>
