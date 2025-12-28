<template>
    <div>
        <div class="d-flex align-center mb-4">
            <v-btn icon variant="text" @click="$router.back()">
                <v-icon>mdi-arrow-left</v-icon>
            </v-btn>
            <h1 class="text-h4 ml-2">New Sale</h1>
        </div>

        <v-card>
            <v-card-text>
                <v-form @submit.prevent="saveSale">
                    <v-row>
                        <v-col cols="12" sm="6" lg="3">
                            <v-select v-model="form.project_id" :items="projects" item-title="name" item-value="id" label="Project *" clearable hint="Project or Warehouse required" persistent-hint></v-select>
                        </v-col>
                        <v-col cols="12" sm="6" lg="3">
                            <v-select v-model="form.warehouse_id" :items="warehouses" item-title="name" item-value="id" label="Warehouse *" clearable hint="Project or Warehouse required" persistent-hint></v-select>
                        </v-col>
                        <v-col cols="6" sm="4" lg="2">
                            <v-select v-model="form.customer_id" :items="customers" item-title="name" item-value="id" label="Customer" clearable>
                                <template v-slot:append>
                                    <v-btn icon size="small" color="primary" @click="showCustomerDialog = true">
                                        <v-icon>mdi-plus</v-icon>
                                    </v-btn>
                                </template>
                            </v-select>
                        </v-col>
                        <v-col cols="6" sm="4" lg="2">
                            <v-text-field v-model="form.challan_no" label="Challan No."></v-text-field>
                        </v-col>
                        <v-col cols="6" sm="4" lg="2">
                            <v-text-field v-model="form.date" label="Date" type="date" required></v-text-field>
                        </v-col>
                    </v-row>

                    <v-divider class="my-4"></v-divider>

                    <h3 class="mb-4">Items</h3>
                    <v-row v-for="(item, index) in form.items" :key="index" align="center">
                        <v-col cols="12" sm="6" lg="4">
                            <v-autocomplete v-model="item.product_id" :items="products" item-title="name" item-value="id" label="Product" required @update:model-value="updatePrice(index)">
                                <template v-slot:item="{ props, item }">
                                    <v-list-item v-bind="props">
                                        <template v-slot:subtitle>
                                            {{ item.raw.unit }} | ৳{{ formatNumber(item.raw.selling_price) }} | Stock: {{ item.raw.stock_quantity }}
                                        </template>
                                    </v-list-item>
                                </template>
                            </v-autocomplete>
                        </v-col>
                        <v-col cols="6" sm="4" lg="2">
                            <v-text-field v-model.number="item.quantity" :label="`Qty (${getProductUnit(item.product_id).toUpperCase()})`" type="number" min="1" step="0.01" required></v-text-field>
                        </v-col>
                        <v-col cols="6" sm="4" lg="2">
                            <v-text-field v-model.number="item.unit_price" label="Unit Price" type="number" required></v-text-field>
                        </v-col>
                        <v-col cols="6" sm="4" lg="2">
                            <v-text-field :model-value="formatNumber(item.quantity * item.unit_price)" label="Total" readonly></v-text-field>
                        </v-col>
                        <v-col cols="6" sm="4" lg="2">
                            <v-btn icon color="error" @click="removeItem(index)" :disabled="form.items.length === 1">
                                <v-icon>mdi-delete</v-icon>
                            </v-btn>
                        </v-col>
                    </v-row>

                    <v-btn color="secondary" @click="addItem" class="mt-2">
                        <v-icon left>mdi-plus</v-icon>
                        Add Item
                    </v-btn>

                    <v-divider class="my-4"></v-divider>

                    <v-row>
                        <v-col cols="12" lg="6">
                            <v-textarea v-model="form.note" label="Note" rows="2"></v-textarea>
                        </v-col>
                        <v-col cols="12" lg="6">
                            <v-text-field :model-value="formatNumber(subtotal)" label="Subtotal" readonly></v-text-field>
                            <v-text-field v-model.number="form.discount" label="Discount" type="number"></v-text-field>
                            <v-text-field :model-value="formatNumber(total)" label="Total" readonly class="text-h6"></v-text-field>
                            <v-text-field v-model.number="form.paid" label="Paid Amount" type="number"></v-text-field>
                            <v-text-field :model-value="formatNumber(total - form.paid)" label="Due" readonly :class="{ 'text-warning': total - form.paid > 0 }"></v-text-field>
                        </v-col>
                    </v-row>

                    <v-btn color="primary" type="submit" :loading="saving" size="large" class="mt-4">
                        Save Sale
                    </v-btn>
                </v-form>
            </v-card-text>
        </v-card>

        <!-- Quick Customer Create Dialog -->
        <v-dialog v-model="showCustomerDialog" max-width="500px">
            <v-card>
                <v-card-title>
                    <span class="text-h5">Add New Customer</span>
                </v-card-title>
                <v-card-text>
                    <v-form ref="customerForm">
                        <v-text-field v-model="customerForm.name" label="Customer Name *" required></v-text-field>
                        <v-text-field v-model="customerForm.phone" label="Phone"></v-text-field>
                        <v-text-field v-model="customerForm.email" label="Email" type="email"></v-text-field>
                        <v-textarea v-model="customerForm.address" label="Address" rows="2"></v-textarea>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="secondary" @click="showCustomerDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveCustomer" :loading="savingCustomer">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '../../services/api'

const router = useRouter()
const route = useRoute()
const projects = ref([])
const warehouses = ref([])
const customers = ref([])
const products = ref([])
const saving = ref(false)
const showCustomerDialog = ref(false)
const savingCustomer = ref(false)

const form = reactive({
    project_id: null,
    warehouse_id: null,
    customer_id: null,
    challan_no: '',
    date: new Date().toISOString().split('T')[0],
    discount: 0,
    paid: 0,
    note: '',
    items: [{ product_id: null, quantity: 1, unit_price: 0 }],
})

const customerForm = reactive({
    name: '',
    phone: '',
    email: '',
    address: '',
})

const subtotal = computed(() => form.items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0))
const total = computed(() => subtotal.value - form.discount)

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD')

const addItem = () => form.items.push({ product_id: null, quantity: 1, unit_price: 0 })
const removeItem = (index) => form.items.splice(index, 1)

const updatePrice = (index) => {
    const product = products.value.find(p => p.id === form.items[index].product_id)
    if (product) {
        form.items[index].unit_price = product.selling_price
    }
}

const getProductUnit = (productId) => {
    if (!productId) return ''
    const product = products.value.find(p => p.id === productId)
    return product ? product.unit : ''
}

const fetchData = async () => {
    try {
        const [projectsRes, warehousesRes, customersRes, productsRes] = await Promise.all([
            api.get('/projects'),
            api.get('/warehouses'),
            api.get('/customers'),
            api.get('/products'),
        ])
        projects.value = projectsRes.data
        warehouses.value = warehousesRes.data
        customers.value = customersRes.data
        products.value = productsRes.data

        // Set default Walk-in Customer
        const walkInCustomer = customers.value.find(c => c.name === 'Walk-in Customer')
        if (walkInCustomer) {
            form.customer_id = walkInCustomer.id
        }

        // Set project_id from query parameter if provided
        if (route.query.project_id) {
            form.project_id = parseInt(route.query.project_id)
        }
    } catch (error) {
        console.error('Error:', error)
    }
}

const saveSale = async () => {
    if (!form.project_id && !form.warehouse_id) {
        alert('Please select either Project or Warehouse')
        return
    }

    saving.value = true
    try {
        await api.post('/sales', form)
        router.push({ name: 'sales' })
    } catch (error) {
        console.error('Error:', error)
        if (error.response?.data?.message) {
            alert(error.response.data.message)
        }
    }
    saving.value = false
}

const saveCustomer = async () => {
    if (!customerForm.name) {
        alert('Please enter customer name')
        return
    }

    savingCustomer.value = true
    try {
        const response = await api.post('/customers', customerForm)
        customers.value.push(response.data)
        form.customer_id = response.data.id
        
        // Reset form
        customerForm.name = ''
        customerForm.phone = ''
        customerForm.email = ''
        customerForm.address = ''
        
        showCustomerDialog.value = false
    } catch (error) {
        console.error('Error:', error)
        alert('Error creating customer')
    }
    savingCustomer.value = false
}

onMounted(() => fetchData())
</script>
