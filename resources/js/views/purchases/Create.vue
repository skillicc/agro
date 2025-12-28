<template>
    <div>
        <div class="d-flex align-center mb-4">
            <v-btn icon variant="text" @click="$router.back()">
                <v-icon>mdi-arrow-left</v-icon>
            </v-btn>
            <h1 class="text-h4 ml-2">New Purchase</h1>
        </div>

        <v-card>
            <v-card-text>
                <v-form @submit.prevent="savePurchase">
                    <v-row>
                        <v-col cols="12" sm="6" lg="3">
                            <v-select v-model="form.project_id" :items="projects" item-title="name" item-value="id" label="Project *" clearable hint="Project or Warehouse required" persistent-hint></v-select>
                        </v-col>
                        <v-col cols="12" sm="6" lg="3">
                            <v-select v-model="form.warehouse_id" :items="warehouses" item-title="name" item-value="id" label="Warehouse *" clearable hint="Project or Warehouse required" persistent-hint></v-select>
                        </v-col>
                        <v-col cols="6" sm="4" lg="2">
                            <v-select v-model="form.supplier_id" :items="suppliers" item-title="name" item-value="id" label="Supplier" clearable></v-select>
                        </v-col>
                        <v-col cols="6" sm="4" lg="2">
                            <v-text-field v-model="form.invoice_no" label="Invoice No."></v-text-field>
                        </v-col>
                        <v-col cols="6" sm="4" lg="2">
                            <v-text-field v-model="form.date" label="Date" type="date" required></v-text-field>
                        </v-col>
                    </v-row>

                    <v-divider class="my-4"></v-divider>

                    <h3 class="mb-4">Items</h3>

                    <!-- Desktop View -->
                    <div class="d-none d-xl-block">
                        <v-table density="compact">
                            <thead>
                                <tr class="text-caption">
                                    <th style="width: 180px">Product</th>
                                    <th style="width: 80px">Size</th>
                                    <th style="width: 70px">Pkg Qty</th>
                                    <th style="width: 70px">Unit/Pkg</th>
                                    <th style="width: 80px">Pkg Price</th>
                                    <th style="width: 70px">Total Qty</th>
                                    <th style="width: 80px">Unit (TP)</th>
                                    <th style="width: 80px">Unit (MRP)</th>
                                    <th style="width: 90px">Total (TP)</th>
                                    <th style="width: 90px">Total (MRP)</th>
                                    <th style="width: 50px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in form.items" :key="index">
                                    <td>
                                        <v-autocomplete
                                            v-model="item.product_id"
                                            :items="products"
                                            item-title="name"
                                            item-value="id"
                                            density="compact"
                                            hide-details
                                            variant="outlined"
                                            required
                                            @update:model-value="updatePrice(index)"
                                        >
                                            <template v-slot:item="{ props, item }">
                                                <v-list-item v-bind="props">
                                                    <template v-slot:subtitle>
                                                        {{ item.raw.unit }} | TP: ৳{{ formatNumber(item.raw.buying_price) }} | MRP: ৳{{ formatNumber(item.raw.selling_price) }}
                                                    </template>
                                                </v-list-item>
                                            </template>
                                        </v-autocomplete>
                                    </td>
                                    <td>
                                        <v-text-field v-model="item.size" density="compact" hide-details variant="outlined"></v-text-field>
                                    </td>
                                    <td>
                                        <v-text-field v-model.number="item.package_qty" type="number" min="1" density="compact" hide-details variant="outlined" @update:model-value="calculateFromPackage(index)"></v-text-field>
                                    </td>
                                    <td>
                                        <v-text-field v-model.number="item.unit_per_package" type="number" min="1" density="compact" hide-details variant="outlined" @update:model-value="calculateFromPackage(index)"></v-text-field>
                                    </td>
                                    <td>
                                        <v-text-field v-model.number="item.package_price" type="number" density="compact" hide-details variant="outlined" @update:model-value="calculateFromPackage(index)"></v-text-field>
                                    </td>
                                    <td>
                                        <v-text-field v-model.number="item.quantity" type="number" density="compact" hide-details variant="outlined" readonly></v-text-field>
                                    </td>
                                    <td>
                                        <v-text-field v-model.number="item.unit_price" type="number" density="compact" hide-details variant="outlined" readonly></v-text-field>
                                    </td>
                                    <td>
                                        <v-text-field v-model.number="item.unit_mrp" type="number" density="compact" hide-details variant="outlined" @update:model-value="calculateMrpTotal(index)"></v-text-field>
                                    </td>
                                    <td>
                                        <v-text-field :model-value="formatNumber(item.quantity * item.unit_price)" density="compact" hide-details variant="outlined" readonly class="text-primary"></v-text-field>
                                    </td>
                                    <td>
                                        <v-text-field :model-value="formatNumber(item.total_mrp)" density="compact" hide-details variant="outlined" readonly class="text-success"></v-text-field>
                                    </td>
                                    <td>
                                        <v-btn icon color="error" size="x-small" @click="removeItem(index)" :disabled="form.items.length === 1">
                                            <v-icon>mdi-delete</v-icon>
                                        </v-btn>
                                    </td>
                                </tr>
                            </tbody>
                        </v-table>
                    </div>

                    <!-- Mobile View -->
                    <div class="d-xl-none">
                        <v-card v-for="(item, index) in form.items" :key="index" class="mb-4" variant="outlined">
                            <v-card-text>
                                <v-autocomplete
                                    v-model="item.product_id"
                                    :items="products"
                                    item-title="name"
                                    item-value="id"
                                    label="Product"
                                    required
                                    @update:model-value="updatePrice(index)"
                                >
                                    <template v-slot:item="{ props, item }">
                                        <v-list-item v-bind="props">
                                            <template v-slot:subtitle>
                                                {{ item.raw.unit }} | TP: ৳{{ formatNumber(item.raw.buying_price) }} | MRP: ৳{{ formatNumber(item.raw.selling_price) }}
                                            </template>
                                        </v-list-item>
                                    </template>
                                </v-autocomplete>
                                <v-row>
                                    <v-col cols="6">
                                        <v-text-field v-model="item.size" label="Size"></v-text-field>
                                    </v-col>
                                    <v-col cols="6">
                                        <v-text-field
                                            v-model.number="item.package_qty"
                                            label="Package Qty"
                                            type="number"
                                            @update:model-value="calculateFromPackage(index)"
                                        ></v-text-field>
                                    </v-col>
                                </v-row>
                                <v-row>
                                    <v-col cols="6">
                                        <v-text-field
                                            v-model.number="item.unit_per_package"
                                            :label="`${getProductUnit(item.product_id).toUpperCase()} Per Package`"
                                            type="number"
                                            @update:model-value="calculateFromPackage(index)"
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="6">
                                        <v-text-field
                                            v-model.number="item.package_price"
                                            label="Package Price"
                                            type="number"
                                            prefix="৳"
                                            @update:model-value="calculateFromPackage(index)"
                                        ></v-text-field>
                                    </v-col>
                                </v-row>
                                <v-row>
                                    <v-col cols="6">
                                        <v-text-field
                                            v-model.number="item.quantity"
                                            label="Total Qty"
                                            type="number"
                                            readonly
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="6">
                                        <v-text-field
                                            v-model.number="item.unit_price"
                                            label="Unit (TP)"
                                            type="number"
                                            prefix="৳"
                                            readonly
                                        ></v-text-field>
                                    </v-col>
                                </v-row>
                                <v-row>
                                    <v-col cols="6">
                                        <v-text-field
                                            v-model.number="item.unit_mrp"
                                            label="Unit (MRP)"
                                            type="number"
                                            prefix="৳"
                                            @update:model-value="calculateMrpTotal(index)"
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="6">
                                        <v-text-field
                                            :model-value="formatNumber(item.quantity * item.unit_price)"
                                            label="Total (TP)"
                                            readonly
                                            class="text-primary"
                                        ></v-text-field>
                                    </v-col>
                                </v-row>
                                <v-row>
                                    <v-col cols="12">
                                        <v-text-field
                                            :model-value="formatNumber(item.total_mrp)"
                                            label="Total (MRP)"
                                            readonly
                                            class="text-success"
                                        ></v-text-field>
                                    </v-col>
                                </v-row>
                                <v-btn color="error" variant="outlined" block @click="removeItem(index)" :disabled="form.items.length === 1">
                                    <v-icon left>mdi-delete</v-icon> Remove
                                </v-btn>
                            </v-card-text>
                        </v-card>
                    </div>

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
                            <v-row>
                                <v-col cols="6">
                                    <v-text-field :model-value="formatNumber(subtotalTP)" label="Subtotal (TP)" readonly class="text-primary"></v-text-field>
                                </v-col>
                                <v-col cols="6">
                                    <v-text-field :model-value="formatNumber(subtotalMRP)" label="Subtotal (MRP)" readonly class="text-success"></v-text-field>
                                </v-col>
                            </v-row>
                            <v-text-field v-model.number="form.discount" label="Discount" type="number"></v-text-field>
                            <v-row>
                                <v-col cols="6">
                                    <v-text-field :model-value="formatNumber(totalTP)" label="Total (TP)" readonly class="text-h6 text-primary"></v-text-field>
                                </v-col>
                                <v-col cols="6">
                                    <v-text-field :model-value="formatNumber(totalMRP)" label="Total (MRP)" readonly class="text-h6 text-success"></v-text-field>
                                </v-col>
                            </v-row>
                            <v-text-field v-model.number="form.paid" label="Paid Amount" type="number"></v-text-field>
                            <v-text-field :model-value="formatNumber(totalTP - form.paid)" label="Due" readonly :class="{ 'text-error': totalTP - form.paid > 0 }"></v-text-field>
                        </v-col>
                    </v-row>

                    <v-btn color="primary" type="submit" :loading="saving" size="large" class="mt-4">
                        Save Purchase
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
const suppliers = ref([])
const products = ref([])
const saving = ref(false)

const form = reactive({
    project_id: null,
    warehouse_id: null,
    supplier_id: null,
    invoice_no: '',
    date: new Date().toISOString().split('T')[0],
    discount: 0,
    paid: 0,
    note: '',
    items: [{
        product_id: null,
        size: '',
        package_qty: 1,
        unit_per_package: 1,
        package_price: 0,
        quantity: 1,
        unit_price: 0,
        unit_mrp: 0,
        total_mrp: 0
    }],
})

const subtotalTP = computed(() => form.items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0))
const subtotalMRP = computed(() => form.items.reduce((sum, item) => sum + (item.total_mrp || 0), 0))
const totalTP = computed(() => subtotalTP.value - form.discount)
const totalMRP = computed(() => subtotalMRP.value)

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD')

const addItem = () => form.items.push({
    product_id: null,
    size: '',
    package_qty: 1,
    unit_per_package: 1,
    package_price: 0,
    quantity: 1,
    unit_price: 0,
    unit_mrp: 0,
    total_mrp: 0
})
const removeItem = (index) => form.items.splice(index, 1)

const updatePrice = (index) => {
    const product = products.value.find(p => p.id === form.items[index].product_id)
    if (product) {
        // Set package price from product buying price initially
        form.items[index].package_price = product.buying_price
        // Set unit MRP from product selling price
        form.items[index].unit_mrp = product.selling_price
        calculateFromPackage(index)
    }
}

// Calculate quantity and unit price from package inputs
const calculateFromPackage = (index) => {
    const item = form.items[index]
    // Total Quantity = Package Qty × Unit Per Package
    item.quantity = (item.package_qty || 1) * (item.unit_per_package || 1)
    // Unit Price (TP) = Package Price / Unit Per Package
    if (item.unit_per_package > 0) {
        item.unit_price = (item.package_price || 0) / item.unit_per_package
    }
    // Total MRP = Quantity × Unit MRP
    item.total_mrp = item.quantity * (item.unit_mrp || 0)
}

// Calculate MRP total when unit MRP changes
const calculateMrpTotal = (index) => {
    const item = form.items[index]
    item.total_mrp = item.quantity * (item.unit_mrp || 0)
}

const getProductUnit = (productId) => {
    if (!productId) return ''
    const product = products.value.find(p => p.id === productId)
    return product ? product.unit : ''
}

const fetchData = async () => {
    try {
        const [projectsRes, warehousesRes, suppliersRes, productsRes] = await Promise.all([
            api.get('/projects'),
            api.get('/warehouses'),
            api.get('/suppliers'),
            api.get('/products'),
        ])
        projects.value = projectsRes.data
        warehouses.value = warehousesRes.data
        suppliers.value = suppliersRes.data
        products.value = productsRes.data

        // Set project_id from query parameter if provided
        if (route.query.project_id) {
            form.project_id = parseInt(route.query.project_id)
        }
    } catch (error) {
        console.error('Error:', error)
    }
}

const savePurchase = async () => {
    if (!form.project_id && !form.warehouse_id) {
        alert('Please select either Project or Warehouse')
        return
    }

    saving.value = true
    try {
        await api.post('/purchases', form)
        router.push({ name: 'purchases' })
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
