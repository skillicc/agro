<template>
    <div>
        <div class="d-flex flex-wrap align-center mb-4 ga-2">
            <v-btn icon variant="text" @click="$router.back()">
                <v-icon>mdi-arrow-left</v-icon>
            </v-btn>
            <h1 class="text-h5 text-sm-h4 ml-2">Edit Sale</h1>
        </div>

        <v-card :loading="loading">
            <v-card-text>
                <v-form @submit.prevent="saveSale">
                    <v-row>
                        <v-col cols="12" sm="6" lg="3">
                            <v-select v-model="form.project_id" :items="projects" item-title="name" item-value="id" label="Project *" clearable hint="Project or Warehouse required" persistent-hint></v-select>
                        </v-col>
                        <v-col cols="12" sm="6" lg="3">
                            <v-select
                                v-model="form.land_id"
                                :items="projectLands"
                                item-title="name"
                                item-value="id"
                                label="Land / Plot"
                                clearable
                                :disabled="!form.project_id"
                                hint="Optional: track which land the sale came from"
                                persistent-hint
                            ></v-select>
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

                    <v-card v-for="(item, index) in form.items" :key="index" variant="outlined" class="mb-4 pa-3">
                        <v-row align="center">
                            <v-col cols="12" sm="6" lg="4">
                                <v-autocomplete v-model="item.product_id" :items="products" item-title="name" item-value="id" label="Product" required @update:model-value="onProductSelect(index)">
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
                                <v-text-field v-model.number="item.quantity" :label="`Qty (${getProductUnit(item.product_id).toUpperCase()})`" type="number" min="0.01" step="0.01" required :readonly="shouldUseBatchSelection(item)" @update:model-value="onQtyChange(index)"></v-text-field>
                            </v-col>
                            <v-col cols="6" sm="4" lg="2">
                                <v-text-field v-model.number="item.unit_price" label="Unit Price" type="number" min="0" step="0.01" @update:model-value="onUnitPriceChange(index)"></v-text-field>
                            </v-col>
                            <v-col cols="6" sm="4" lg="2">
                                <v-text-field v-model.number="item.total" label="Total" type="number" min="0" step="0.01" @update:model-value="onTotalChange(index)"></v-text-field>
                            </v-col>
                            <v-col cols="6" sm="4" lg="2">
                                <v-btn icon color="error" @click="removeItem(index)" :disabled="form.items.length === 1">
                                    <v-icon>mdi-delete</v-icon>
                                </v-btn>
                            </v-col>
                        </v-row>

                        <!-- Batch Selection Section (only for purchased products, not own production) -->
                        <div v-if="item.product_id && shouldUseBatchSelection(item) && item.batches && item.batches.length > 0" class="mt-3">
                            <v-divider class="mb-3"></v-divider>
                            <div class="d-flex flex-wrap align-center mb-2 ga-1">
                                <v-icon size="small" class="mr-1">mdi-package-variant</v-icon>
                                <span class="text-subtitle-2 font-weight-bold">Select Batches:</span>
                                <v-chip size="x-small" color="info">Available: {{ getTotalAvailable(item.batches) }}</v-chip>
                            </div>

                            <div style="overflow-x: auto;">
                            <v-table density="compact" class="batch-table">
                                <thead>
                                    <tr>
                                        <th>Batch</th>
                                        <th>Purchase Date</th>
                                        <th>Cost Price</th>
                                        <th>Available</th>
                                        <th style="width: 150px;">Sell Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="batch in item.batches" :key="batch.id">
                                        <td>
                                            <span class="text-caption">{{ batch.batch_number }}</span>
                                        </td>
                                        <td>{{ batch.purchase_date }}</td>
                                        <td class="font-weight-medium">৳{{ formatNumber(batch.unit_price) }}</td>
                                        <td>
                                            <v-chip size="x-small" :color="batch.remaining_quantity > 0 ? 'success' : 'error'">
                                                {{ batch.remaining_quantity }}
                                            </v-chip>
                                        </td>
                                        <td>
                                            <v-text-field
                                                v-model.number="batch.sell_quantity"
                                                type="number"
                                                min="0"
                                                :max="batch.remaining_quantity"
                                                step="0.01"
                                                density="compact"
                                                variant="outlined"
                                                hide-details
                                                @update:model-value="updateItemQuantity(index)"
                                                style="max-width: 120px;"
                                            ></v-text-field>
                                        </td>
                                    </tr>
                                </tbody>
                            </v-table>
                            </div>

                            <div v-if="getBatchSelectionTotal(index) > 0" class="mt-2 text-right">
                                <v-chip color="primary" size="small">
                                    Selected: {{ getBatchSelectionTotal(index) }} |
                                    Avg Cost: ৳{{ formatNumber(getAverageCost(index)) }}
                                </v-chip>
                            </div>
                        </div>

                        <div v-else-if="item.product_id && shouldUseBatchSelection(item) && (!item.batches || item.batches.length === 0)" class="mt-3">
                            <v-alert type="warning" density="compact" variant="tonal">
                                No available batches for this product
                            </v-alert>
                        </div>
                    </v-card>

                    <v-btn color="secondary" :size="$vuetify.display.xs ? 'small' : 'default'" @click="addItem" class="mt-2">
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
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '../../services/api'

const router = useRouter()
const route = useRoute()
const projects = ref([])
const projectLands = ref([])
const warehouses = ref([])
const customers = ref([])
const products = ref([])
const saving = ref(false)
const loading = ref(false)

const form = reactive({
    project_id: null,
    land_id: null,
    warehouse_id: null,
    customer_id: null,
    challan_no: '',
    date: '',
    discount: 0,
    paid: 0,
    note: '',
    items: [],
})

const subtotal = computed(() => form.items.reduce((sum, item) => sum + (item.total || 0), 0))
const total = computed(() => subtotal.value - form.discount)

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD')

const addItem = () => form.items.push({ product_id: null, quantity: 0, unit_price: 0, total: 0, batches: [], batch_selections: [] })
const removeItem = (index) => form.items.splice(index, 1)

const getSelectedSourceWarehouse = () => {
    if (form.warehouse_id) {
        return warehouses.value.find(warehouse => warehouse.id === form.warehouse_id) || null
    }

    if (form.project_id) {
        return warehouses.value.find(warehouse => warehouse.project_id === form.project_id) || null
    }

    return null
}

const shouldUseBatchSelection = (item) => {
    if (!item?.product_type) {
        return false
    }

    const sourceWarehouse = getSelectedSourceWarehouse()
    return item.product_type !== 'own_production' && sourceWarehouse?.project?.type !== 'shop'
}

const loadItemBatches = async (item) => {
    const sourceWarehouse = getSelectedSourceWarehouse()
    const params = sourceWarehouse?.id ? { warehouse_id: sourceWarehouse.id } : {}

    const response = await api.get(`/products/${item.product_id}/batches`, { params })
    item.batches = (response.data.batches || []).map(batch => ({
        ...batch,
        sell_quantity: 0,
    }))
}

const syncItemSource = async (index) => {
    const item = form.items[index]
    if (!item?.product_id) {
        return
    }

    const product = products.value.find(entry => entry.id === item.product_id)
    if (!product) {
        return
    }

    item.product_type = product.type
    item.batch_selections = []

    if (shouldUseBatchSelection(item)) {
        item.quantity = 0
        item.total = 0

        try {
            await loadItemBatches(item)
        } catch (error) {
            console.error('Error fetching batches:', error)
            item.batches = []
        }

        return
    }

    item.batches = []
    onQtyChange(index)
}

const onProductSelect = async (index) => {
    const item = form.items[index]
    const product = products.value.find(p => p.id === item.product_id)

    if (product) {
        item.unit_price = product.selling_price
        item.quantity = 0
        item.total = 0
        item.batches = []
        item.batch_selections = []
        item.product_type = product.type

        if (shouldUseBatchSelection(item)) {
            try {
                await loadItemBatches(item)
            } catch (error) {
                console.error('Error fetching batches:', error)
                item.batches = []
            }
        }
    }
}

const updateItemQuantity = (index) => {
    const item = form.items[index]
    // Calculate total quantity from batch selections
    let totalQty = 0
    item.batch_selections = []

    for (const batch of item.batches) {
        const sellQty = parseFloat(batch.sell_quantity) || 0
        if (sellQty > 0) {
            // Validate against available quantity
            if (sellQty > batch.remaining_quantity) {
                batch.sell_quantity = batch.remaining_quantity
            }
            totalQty += parseFloat(batch.sell_quantity) || 0
            item.batch_selections.push({
                batch_id: batch.id,
                quantity: parseFloat(batch.sell_quantity) || 0
            })
        }
    }

    item.quantity = totalQty
    item.total = parseFloat((item.quantity * item.unit_price).toFixed(2))
}

const onUnitPriceChange = (index) => {
    const item = form.items[index]
    item.total = parseFloat((item.quantity * item.unit_price).toFixed(2))
}

const onTotalChange = (index) => {
    const item = form.items[index]
    if (item.quantity > 0) {
        item.unit_price = parseFloat((item.total / item.quantity).toFixed(4))
    }
}

const onQtyChange = (index) => {
    const item = form.items[index]
    if (item.unit_price > 0) {
        item.total = parseFloat((item.quantity * item.unit_price).toFixed(2))
    } else if (item.total > 0 && item.quantity > 0) {
        item.unit_price = parseFloat((item.total / item.quantity).toFixed(4))
    }
}

const getBatchSelectionTotal = (index) => {
    const item = form.items[index]
    if (!item.batches) return 0
    return item.batches.reduce((sum, b) => sum + (parseFloat(b.sell_quantity) || 0), 0)
}

const getAverageCost = (index) => {
    const item = form.items[index]
    if (!item.batches) return 0

    let totalCost = 0
    let totalQty = 0

    for (const batch of item.batches) {
        const sellQty = parseFloat(batch.sell_quantity) || 0
        if (sellQty > 0) {
            totalCost += sellQty * batch.unit_price
            totalQty += sellQty
        }
    }

    return totalQty > 0 ? totalCost / totalQty : 0
}

const getTotalAvailable = (batches) => {
    if (!batches) return 0
    return batches.reduce((sum, b) => sum + (parseFloat(b.remaining_quantity) || 0), 0)
}

const getProductUnit = (productId) => {
    if (!productId) return ''
    const product = products.value.find(p => p.id === productId)
    return product ? product.unit : ''
}

const fetchProducts = async () => {
    try {
        const params = {}

        if (form.project_id) {
            params.project_id = form.project_id
        } else if (form.warehouse_id) {
            params.warehouse_id = form.warehouse_id
        }

        const response = await api.get('/products', { params })
        products.value = response.data
    } catch (error) {
        console.error('Error loading products:', error)
        products.value = []
    }
}

const loadProjectLands = async () => {
    if (!form.project_id) {
        projectLands.value = []
        form.land_id = null
        return
    }

    try {
        const response = await api.get(`/lands?project_id=${form.project_id}`)
        projectLands.value = response.data

        if (form.land_id && !projectLands.value.some(land => land.id === form.land_id)) {
            form.land_id = null
        }
    } catch (error) {
        console.error('Error loading lands:', error)
        projectLands.value = []
    }
}

const fetchData = async () => {
    loading.value = true
    try {
        const [projectsRes, warehousesRes, customersRes, saleRes] = await Promise.all([
            api.get('/projects'),
            api.get('/warehouses'),
            api.get('/customers'),
            api.get(`/sales/${route.params.id}`),
        ])
        projects.value = projectsRes.data
        warehouses.value = warehousesRes.data
        customers.value = customersRes.data

        // Populate form with existing data
        const sale = saleRes.data
        form.project_id = sale.project_id
        form.land_id = sale.land_id
        form.warehouse_id = sale.warehouse_id
        form.customer_id = sale.customer_id
        form.challan_no = sale.challan_no
        form.date = sale.date
        form.discount = parseFloat(sale.discount) || 0
        form.paid = parseFloat(sale.paid) || 0
        form.note = sale.note || ''

        await loadProjectLands()
        await fetchProducts()

        // Load items with batches
        form.items = []
        for (const item of sale.items) {
            const product = products.value.find(p => p.id === item.product_id)
            const qty = parseFloat(item.quantity) || 0
            const price = parseFloat(item.unit_price) || 0
            const newItem = {
                id: item.id,
                product_id: item.product_id,
                quantity: qty,
                unit_price: price,
                total: parseFloat((qty * price).toFixed(2)),
                product_type: product?.type || '',
                batches: [],
                batch_selections: []
            }

            if (shouldUseBatchSelection(newItem)) {
                try {
                    await loadItemBatches(newItem)
                } catch (error) {
                    console.error('Error fetching batches:', error)
                }
            }

            form.items.push(newItem)
        }
    } catch (error) {
        console.error('Error:', error)
        alert('Error loading sale data')
    }
    loading.value = false
}

watch(
    () => [form.project_id, form.warehouse_id],
    async ([projectId, warehouseId], previous = []) => {
        if (projectId === previous[0] && warehouseId === previous[1]) {
            return
        }

        await loadProjectLands()
        await fetchProducts()

        for (let index = 0; index < form.items.length; index += 1) {
            await syncItemSource(index)
        }
    }
)

const saveSale = async () => {
    if (!form.project_id && !form.warehouse_id) {
        alert('Please select either Project or Warehouse')
        return
    }

    for (const item of form.items) {
        if (item.product_id && item.quantity <= 0) {
            alert(shouldUseBatchSelection(item) ? 'Please select batch quantities for all products' : 'Please enter quantities for all products')
            return
        }
    }

    saving.value = true
    try {
        // Prepare data - ensure null values are properly handled
        const data = {
            project_id: form.project_id || null,
            land_id: form.land_id || null,
            warehouse_id: form.warehouse_id || null,
            customer_id: form.customer_id || null,
            challan_no: form.challan_no,
            date: form.date,
            discount: form.discount || 0,
            paid: form.paid || 0,
            note: form.note || '',
            items: form.items.map(item => ({
                product_id: item.product_id,
                quantity: item.quantity,
                unit_price: item.unit_price,
                batch_selections: item.batch_selections || []
            }))
        }

        await api.put(`/sales/${route.params.id}`, data)
        alert('Sale updated successfully!')
        router.push({ name: 'sales' })
    } catch (error) {
        console.error('Error:', error)
        if (error.response?.data?.message) {
            alert(error.response.data.message)
        } else {
            alert('Error updating sale. Please try again.')
        }
    }
    saving.value = false
}

onMounted(() => fetchData())
</script>

<style scoped>
.batch-table {
    border: 1px solid rgba(0, 0, 0, 0.12);
    border-radius: 4px;
}
.batch-table th {
    background-color: rgba(0, 0, 0, 0.04);
    font-size: 0.75rem;
}
.batch-table td {
    font-size: 0.875rem;
}
</style>
