<template>
    <div>
        <div class="d-flex flex-column flex-sm-row justify-space-between align-start align-sm-center mb-4 ga-2">
            <h1 class="text-h5 text-sm-h4">Customers</h1>
            <div class="d-flex ga-2">
                <v-btn color="success" @click="openPaymentDialog()" :size="$vuetify.display.xs ? 'small' : 'default'">
                    <v-icon left>mdi-cash-plus</v-icon>
                    <span class="d-none d-sm-inline">Add Payment</span>
                    <span class="d-sm-none">Pay</span>
                </v-btn>
                <v-btn color="primary" @click="openDialog()" :size="$vuetify.display.xs ? 'small' : 'default'">
                    <v-icon left>mdi-plus</v-icon>
                    <span class="d-none d-sm-inline">Add Customer</span>
                    <span class="d-sm-none">Add</span>
                </v-btn>
            </div>
        </div>

        <v-card>
            <v-card-text class="pa-2 pa-sm-4">
                <!-- Mobile Card View -->
                <div v-if="$vuetify.display.xs" class="mobile-list">
                    <v-card
                        v-for="customer in customers"
                        :key="customer.id"
                        variant="outlined"
                        class="mb-2"
                    >
                        <v-card-text class="pa-3">
                            <div class="d-flex justify-space-between align-start">
                                <div>
                                    <div class="font-weight-bold">{{ customer.name }}</div>
                                    <div class="text-caption text-grey">{{ customer.phone }}</div>
                                </div>
                                <v-chip :color="customer.total_due > 0 ? 'warning' : 'success'" size="small">
                                    ৳{{ formatNumber(customer.total_due) }}
                                </v-chip>
                            </div>
                            <v-divider class="my-2"></v-divider>
                            <div class="d-flex justify-space-between text-caption">
                                <span>Sale: ৳{{ formatNumber(customer.total_sale) }}</span>
                                <span class="text-success">Paid: ৳{{ formatNumber(customer.total_paid) }}</span>
                            </div>
                            <div class="d-flex justify-end mt-2 ga-1">
                                <v-btn icon size="x-small" variant="tonal" @click="viewLedger(customer)">
                                    <v-icon size="small">mdi-file-document</v-icon>
                                </v-btn>
                                <v-btn icon size="x-small" variant="tonal" @click="openDialog(customer)">
                                    <v-icon size="small">mdi-pencil</v-icon>
                                </v-btn>
                                <v-btn icon size="x-small" variant="tonal" color="error" @click="confirmDelete(customer)">
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
                    :items="customers"
                    :loading="loading"
                    :density="$vuetify.display.smAndDown ? 'compact' : 'default'"
                >
                    <template v-slot:item.sl="{ index }">
                        {{ index + 1 }}
                    </template>
                    <template v-slot:item.total_sale="{ item }">
                        ৳{{ formatNumber(item.total_sale) }}
                    </template>
                    <template v-slot:item.total_paid="{ item }">
                        <span class="text-success">৳{{ formatNumber(item.total_paid) }}</span>
                    </template>
                    <template v-slot:item.total_due="{ item }">
                        <v-chip :color="item.total_due > 0 ? 'warning' : 'success'" size="small">
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

        <!-- Add/Edit Dialog -->
        <v-dialog v-model="dialog" :max-width="$vuetify.display.xs ? '100%' : '500'" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title>{{ editMode ? 'Edit Customer' : 'Add Customer' }}</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="saveCustomer">
                        <v-text-field v-model="form.name" label="Name" required></v-text-field>
                        <v-text-field v-model="form.phone" label="Phone"></v-text-field>
                        <v-text-field v-model="form.email" label="Email" type="email"></v-text-field>
                        <v-textarea v-model="form.address" label="Address" rows="2"></v-textarea>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="dialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveCustomer" :loading="saving">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Ledger Dialog -->
        <v-dialog v-model="ledgerDialog" :max-width="$vuetify.display.xs ? '100%' : '800'" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title>{{ selectedCustomer?.name }} - Ledger</v-card-title>
                <v-card-text>
                    <v-row>
                        <v-col cols="4" class="text-center">
                            <div class="text-h6">৳{{ formatNumber(ledger.total_sale) }}</div>
                            <div class="text-caption">Total Sale</div>
                        </v-col>
                        <v-col cols="4" class="text-center">
                            <div class="text-h6 text-success">৳{{ formatNumber(ledger.total_paid) }}</div>
                            <div class="text-caption">Total Paid</div>
                        </v-col>
                        <v-col cols="4" class="text-center">
                            <div class="text-h6 text-warning">৳{{ formatNumber(ledger.total_due) }}</div>
                            <div class="text-caption">Total Due</div>
                        </v-col>
                    </v-row>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="ledgerDialog = false">Close</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Confirm -->
        <v-dialog v-model="deleteDialog" max-width="400">
            <v-card>
                <v-card-title>Confirm Delete</v-card-title>
                <v-card-text>Are you sure you want to delete "{{ selectedCustomer?.name }}"?</v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" @click="deleteCustomer" :loading="deleting">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Payment Dialog -->
        <v-dialog v-model="paymentDialog" :max-width="$vuetify.display.xs ? '100%' : '500'" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title>Add Customer Payment</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="savePayment">
                        <v-select
                            v-model="paymentForm.customer_id"
                            :items="customers"
                            item-title="name"
                            item-value="id"
                            label="Customer"
                            required
                        >
                            <template v-slot:item="{ item, props }">
                                <v-list-item v-bind="props">
                                    <template v-slot:append>
                                        <v-chip size="x-small" :color="item.raw.total_due > 0 ? 'warning' : 'success'">
                                            ৳{{ formatNumber(item.raw.total_due) }} Due
                                        </v-chip>
                                    </template>
                                </v-list-item>
                            </template>
                        </v-select>
                        <v-text-field v-model.number="paymentForm.amount" label="Amount" type="number" prefix="৳" required></v-text-field>
                        <v-text-field v-model="paymentForm.date" label="Date" type="date" required></v-text-field>
                        <v-select
                            v-model="paymentForm.payment_method"
                            :items="paymentMethods"
                            label="Payment Method"
                        ></v-select>
                        <v-textarea v-model="paymentForm.note" label="Note (Optional)" rows="2"></v-textarea>
                    </v-form>
                </v-card-text>
                <v-card-actions>
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

const { smAndUp } = useDisplay()

const customers = ref([])
const loading = ref(false)
const dialog = ref(false)
const ledgerDialog = ref(false)
const deleteDialog = ref(false)
const paymentDialog = ref(false)
const editMode = ref(false)
const selectedCustomer = ref(null)
const saving = ref(false)
const deleting = ref(false)
const savingPayment = ref(false)
const ledger = ref({})

const paymentMethods = ['cash', 'bank', 'bkash', 'nagad', 'check', 'other']

const paymentForm = reactive({
    customer_id: null,
    amount: 0,
    date: new Date().toISOString().split('T')[0],
    payment_method: 'cash',
    note: '',
})

const responsiveHeaders = computed(() => {
    if (smAndUp.value) {
        return [
            { title: 'SL', key: 'sl', width: '60px' },
            { title: 'Name', key: 'name' },
            { title: 'Phone', key: 'phone' },
            { title: 'Total Sale', key: 'total_sale' },
            { title: 'Total Paid', key: 'total_paid' },
            { title: 'Total Due', key: 'total_due' },
            { title: 'Actions', key: 'actions', sortable: false },
        ]
    }
    // Compact view for tablets
    return [
        { title: 'SL', key: 'sl', width: '50px' },
        { title: 'Name', key: 'name' },
        { title: 'Due', key: 'total_due' },
        { title: 'Actions', key: 'actions', sortable: false },
    ]
})

const form = reactive({ name: '', phone: '', email: '', address: '' })

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD')

const fetchCustomers = async () => {
    loading.value = true
    try {
        const response = await api.get('/customers')
        customers.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
    loading.value = false
}

const openDialog = (customer = null) => {
    editMode.value = !!customer
    selectedCustomer.value = customer
    if (customer) {
        Object.assign(form, customer)
    } else {
        Object.assign(form, { name: '', phone: '', email: '', address: '' })
    }
    dialog.value = true
}

const saveCustomer = async () => {
    saving.value = true
    try {
        if (editMode.value) {
            await api.put(`/customers/${selectedCustomer.value.id}`, form)
        } else {
            await api.post('/customers', form)
        }
        dialog.value = false
        fetchCustomers()
    } catch (error) {
        console.error('Error:', error)
    }
    saving.value = false
}

const viewLedger = async (customer) => {
    selectedCustomer.value = customer
    try {
        const response = await api.get(`/customers/${customer.id}/ledger`)
        ledger.value = response.data
        ledgerDialog.value = true
    } catch (error) {
        console.error('Error:', error)
    }
}

const confirmDelete = (customer) => {
    selectedCustomer.value = customer
    deleteDialog.value = true
}

const deleteCustomer = async () => {
    deleting.value = true
    try {
        await api.delete(`/customers/${selectedCustomer.value.id}`)
        deleteDialog.value = false
        fetchCustomers()
    } catch (error) {
        console.error('Error:', error)
    }
    deleting.value = false
}

const openPaymentDialog = (customer = null) => {
    if (customer) {
        paymentForm.customer_id = customer.id
    } else {
        paymentForm.customer_id = null
    }
    paymentForm.amount = 0
    paymentForm.date = new Date().toISOString().split('T')[0]
    paymentForm.payment_method = 'cash'
    paymentForm.note = ''
    paymentDialog.value = true
}

const savePayment = async () => {
    if (!paymentForm.customer_id || !paymentForm.amount) return

    savingPayment.value = true
    try {
        await api.post(`/customers/${paymentForm.customer_id}/payment`, paymentForm)
        paymentDialog.value = false
        fetchCustomers()
    } catch (error) {
        console.error('Error:', error)
    }
    savingPayment.value = false
}

onMounted(() => fetchCustomers())
</script>
