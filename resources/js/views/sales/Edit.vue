<template>
    <div>
        <div class="d-flex align-center mb-4">
            <v-btn icon variant="text" @click="$router.back()">
                <v-icon>mdi-arrow-left</v-icon>
            </v-btn>
            <h1 class="text-h4 ml-2">Edit Sale</h1>
        </div>

        <v-card :loading="loading">
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
                            <v-select v-model="form.customer_id" :items="customers" item-title="name" item-value="id" label="Customer" clearable></v-select>
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
                        Update Sale
                    </v-btn>
                </v-form>
            </v-card-text>
        </v-card>
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
const loading = ref(false)

const form = reactive({
    project_id: null,
    warehouse_id: null,
    customer_id: null,
    challan_no: '',
    date: '',
    discount: 0,
    paid: 0,
    note: '',
    items: [],
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
    loading.value = true
    try {
        const [projectsRes, warehousesRes, customersRes, productsRes, saleRes] = await Promise.all([
            api.get('/projects'),
            api.get('/warehouses'),
            api.get('/customers'),
            api.get('/products'),
            api.get(`/sales/${route.params.id}`),
        ])
        projects.value = projectsRes.data
        warehouses.value = warehousesRes.data
        customers.value = customersRes.data
        products.value = productsRes.data

        // Populate form with existing data
        const sale = saleRes.data
        form.project_id = sale.project_id
        form.warehouse_id = sale.warehouse_id
        form.customer_id = sale.customer_id
        form.challan_no = sale.challan_no
        form.date = sale.date
        form.discount = parseFloat(sale.discount) || 0
        form.paid = parseFloat(sale.paid) || 0
        form.note = sale.note || ''
        form.items = sale.items.map(item => ({
            id: item.id,
            product_id: item.product_id,
            quantity: parseFloat(item.quantity) || 1,
            unit_price: parseFloat(item.unit_price) || 0,
        }))
    } catch (error) {
        console.error('Error:', error)
        alert('Error loading sale data')
    }
    loading.value = false
}

const saveSale = async () => {
    if (!form.project_id && !form.warehouse_id) {
        alert('Please select either Project or Warehouse')
        return
    }

    saving.value = true
    try {
        await api.put(`/sales/${route.params.id}`, form)
        router.push({ name: 'sales' })
    } catch (error) {
        console.error('Error:', error)
        if (error.response?.data?.message) {
            alert(error.response.data.message)
        }
    }
    saving.value = false
}

onMounted(() => fetchData())
</script>
