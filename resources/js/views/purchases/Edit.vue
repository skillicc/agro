<template>
    <div>
        <div class="d-flex flex-wrap align-center mb-4 ga-2">
            <v-btn icon variant="text" @click="$router.back()">
                <v-icon>mdi-arrow-left</v-icon>
            </v-btn>
            <h1 class="text-h5 text-sm-h4 ml-2">Edit Purchase</h1>
        </div>

        <v-card :loading="loading">
            <v-card-text class="pa-3 pa-md-4">
                <v-form @submit.prevent="savePurchase">
                    <!-- Header Fields -->
                    <v-row dense>
                        <v-col cols="12" sm="6" md="4" lg="2">
                            <v-select
                                v-model="form.project_id"
                                :items="projects"
                                item-title="name"
                                item-value="id"
                                label="Project *"
                                clearable
                                density="comfortable"
                                hint="Project or Warehouse required"
                                persistent-hint
                            ></v-select>
                        </v-col>
                        <v-col cols="12" sm="6" md="4" lg="2">
                            <v-select
                                v-model="form.warehouse_id"
                                :items="warehouses"
                                item-title="name"
                                item-value="id"
                                label="Warehouse *"
                                clearable
                                density="comfortable"
                                hint="Project or Warehouse required"
                                persistent-hint
                            ></v-select>
                        </v-col>
                        <v-col cols="12" sm="6" md="4" lg="2">
                            <v-select
                                v-model="form.supplier_id"
                                :items="suppliers"
                                item-title="name"
                                item-value="id"
                                label="Supplier"
                                clearable
                                density="comfortable"
                            ></v-select>
                        </v-col>
                        <v-col cols="6" sm="6" md="4" lg="2">
                            <v-text-field
                                v-model="form.invoice_no"
                                label="Invoice No."
                                density="comfortable"
                            ></v-text-field>
                        </v-col>
                        <v-col cols="6" sm="6" md="4" lg="2">
                            <v-text-field
                                v-model="form.reference_no"
                                label="Reference No."
                                density="comfortable"
                                readonly
                                bg-color="grey-lighten-4"
                            ></v-text-field>
                        </v-col>
                        <v-col cols="12" sm="6" md="4" lg="2">
                            <v-text-field
                                v-model="form.date"
                                label="Date"
                                type="date"
                                required
                                density="comfortable"
                            ></v-text-field>
                        </v-col>
                    </v-row>

                    <v-divider class="my-4"></v-divider>

                    <h3 class="mb-3 text-subtitle-1 text-md-h6">Items</h3>

                    <!-- Card View for all screens -->
                    <div>
                        <v-card
                            v-for="(item, index) in form.items"
                            :key="index"
                            class="mb-3"
                            variant="outlined"
                            :class="{ 'border-primary': item.product_id }"
                        >
                            <v-card-title class="py-2 px-3 bg-grey-lighten-4 d-flex justify-space-between align-center">
                                <span class="text-body-2 font-weight-medium">Item {{ index + 1 }}</span>
                                <v-btn
                                    icon
                                    color="error"
                                    size="x-small"
                                    variant="text"
                                    @click="removeItem(index)"
                                    :disabled="form.items.length === 1"
                                >
                                    <v-icon size="small">mdi-delete</v-icon>
                                </v-btn>
                            </v-card-title>
                            <v-card-text class="pa-3">
                                <!-- Product Selection -->
                                <v-autocomplete
                                    v-model="item.product_id"
                                    :items="products"
                                    item-title="name"
                                    item-value="id"
                                    label="Product *"
                                    density="comfortable"
                                    required
                                    class="mb-2"
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

                                <!-- Input Fields Row 1 -->
                                <v-row dense>
                                    <v-col cols="6" sm="3">
                                        <v-text-field
                                            v-model="item.size"
                                            label="Size"
                                            density="comfortable"
                                            hide-details
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="6" sm="3">
                                        <v-text-field
                                            v-model.number="item.package_qty"
                                            label="Pkg Qty"
                                            type="number"
                                            density="comfortable"
                                            hide-details
                                            @update:model-value="calculateFromPackage(index)"
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="6" sm="3">
                                        <v-text-field
                                            v-model.number="item.unit_per_package"
                                            label="Unit/Pkg"
                                            type="number"
                                            density="comfortable"
                                            hide-details
                                            @update:model-value="calculateFromPackage(index)"
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="6" sm="3">
                                        <v-text-field
                                            v-model.number="item.package_price"
                                            label="Pkg Price"
                                            type="number"
                                            density="comfortable"
                                            hide-details
                                            @update:model-value="calculateFromPackage(index)"
                                        ></v-text-field>
                                    </v-col>
                                </v-row>

                                <!-- Calculated Fields Row 2 -->
                                <v-row dense class="mt-2">
                                    <v-col cols="6" sm="3">
                                        <v-text-field
                                            v-model.number="item.quantity"
                                            label="Total Qty"
                                            type="number"
                                            density="comfortable"
                                            hide-details
                                            readonly
                                            bg-color="grey-lighten-4"
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="6" sm="3">
                                        <v-text-field
                                            v-model.number="item.unit_price"
                                            label="Unit TP"
                                            type="number"
                                            density="comfortable"
                                            hide-details
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="6" sm="3">
                                        <v-text-field
                                            v-model.number="item.unit_mrp"
                                            label="Unit MRP"
                                            type="number"
                                            density="comfortable"
                                            hide-details
                                            @update:model-value="calculateMrpTotal(index)"
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="6" sm="3">
                                        <v-text-field
                                            :model-value="formatNumber(item.quantity * item.unit_price)"
                                            label="Total TP"
                                            density="comfortable"
                                            hide-details
                                            readonly
                                            class="text-primary font-weight-bold"
                                            bg-color="blue-lighten-5"
                                        ></v-text-field>
                                    </v-col>
                                </v-row>

                                <!-- Total MRP Row -->
                                <v-row dense class="mt-2">
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            :model-value="formatNumber(item.total_mrp)"
                                            label="Total MRP"
                                            density="comfortable"
                                            hide-details
                                            readonly
                                            class="text-success font-weight-bold"
                                            bg-color="green-lighten-5"
                                        ></v-text-field>
                                    </v-col>
                                </v-row>
                            </v-card-text>
                        </v-card>
                    </div>

                    <v-btn color="secondary" variant="tonal" @click="addItem" class="mt-2">
                        <v-icon start>mdi-plus</v-icon>
                        Add Item
                    </v-btn>

                    <v-divider class="my-4"></v-divider>

                    <!-- Summary Section -->
                    <v-row dense>
                        <v-col cols="12" md="6">
                            <v-textarea
                                v-model="form.note"
                                label="Note"
                                rows="2"
                                density="comfortable"
                            ></v-textarea>
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-row dense>
                                <v-col cols="6">
                                    <v-text-field
                                        :model-value="formatNumber(subtotalTP)"
                                        label="Subtotal (TP)"
                                        readonly
                                        class="text-primary"
                                        density="comfortable"
                                        bg-color="blue-lighten-5"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="6">
                                    <v-text-field
                                        :model-value="formatNumber(subtotalMRP)"
                                        label="Subtotal (MRP)"
                                        readonly
                                        class="text-success"
                                        density="comfortable"
                                        bg-color="green-lighten-5"
                                    ></v-text-field>
                                </v-col>
                            </v-row>
                            <v-text-field
                                v-model.number="form.discount"
                                label="Discount"
                                type="number"
                                density="comfortable"
                            ></v-text-field>
                            <v-row dense>
                                <v-col cols="6">
                                    <v-text-field
                                        :model-value="formatNumber(totalTP)"
                                        label="Total (TP)"
                                        readonly
                                        class="text-primary font-weight-bold"
                                        density="comfortable"
                                        bg-color="blue-lighten-4"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="6">
                                    <v-text-field
                                        :model-value="formatNumber(totalMRP)"
                                        label="Total (MRP)"
                                        readonly
                                        class="text-success font-weight-bold"
                                        density="comfortable"
                                        bg-color="green-lighten-4"
                                    ></v-text-field>
                                </v-col>
                            </v-row>
                            <v-text-field
                                v-model.number="form.paid"
                                label="Paid Amount"
                                type="number"
                                density="comfortable"
                            ></v-text-field>
                            <v-text-field
                                :model-value="formatNumber(totalTP - form.paid)"
                                label="Due"
                                readonly
                                :class="{ 'text-error font-weight-bold': totalTP - form.paid > 0 }"
                                density="comfortable"
                                :bg-color="totalTP - form.paid > 0 ? 'red-lighten-5' : ''"
                            ></v-text-field>
                        </v-col>
                    </v-row>

                    <v-btn color="primary" type="submit" :loading="saving" size="large" class="mt-4">
                        <v-icon start>mdi-content-save</v-icon>
                        Update Purchase
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
const loading = ref(false)

const form = reactive({
    project_id: null,
    warehouse_id: null,
    supplier_id: null,
    invoice_no: '',
    reference_no: '',
    date: '',
    discount: 0,
    paid: 0,
    note: '',
    items: [],
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
        form.items[index].package_price = product.buying_price
        form.items[index].unit_mrp = product.selling_price
        calculateFromPackage(index)
    }
}

const calculateFromPackage = (index) => {
    const item = form.items[index]
    item.quantity = (item.package_qty || 1) * (item.unit_per_package || 1)
    if (item.unit_per_package > 0) {
        item.unit_price = (item.package_price || 0) / item.unit_per_package
    }
    item.total_mrp = item.quantity * (item.unit_mrp || 0)
}

const calculateMrpTotal = (index) => {
    const item = form.items[index]
    item.total_mrp = item.quantity * (item.unit_mrp || 0)
}

const fetchData = async () => {
    loading.value = true
    try {
        const [projectsRes, warehousesRes, suppliersRes, productsRes, purchaseRes] = await Promise.all([
            api.get('/projects'),
            api.get('/warehouses'),
            api.get('/suppliers'),
            api.get('/products'),
            api.get(`/purchases/${route.params.id}`),
        ])
        projects.value = projectsRes.data
        warehouses.value = warehousesRes.data
        suppliers.value = suppliersRes.data
        products.value = productsRes.data

        // Populate form with existing data
        const purchase = purchaseRes.data
        form.project_id = purchase.project_id
        form.warehouse_id = purchase.warehouse_id
        form.supplier_id = purchase.supplier_id
        form.invoice_no = purchase.invoice_no
        form.reference_no = purchase.reference_no
        form.date = purchase.date
        form.discount = parseFloat(purchase.discount) || 0
        form.paid = parseFloat(purchase.paid) || 0
        form.note = purchase.note || ''
        form.items = purchase.items.map(item => ({
            id: item.id,
            product_id: item.product_id,
            size: item.size || '',
            package_qty: item.package_qty || 1,
            unit_per_package: item.unit_per_package || 1,
            package_price: parseFloat(item.package_price) || 0,
            quantity: item.quantity,
            unit_price: parseFloat(item.unit_price) || 0,
            unit_mrp: parseFloat(item.unit_mrp) || 0,
            total_mrp: parseFloat(item.total_mrp) || 0,
        }))
    } catch (error) {
        console.error('Error:', error)
        alert('Error loading purchase data')
    }
    loading.value = false
}

const savePurchase = async () => {
    if (!form.project_id && !form.warehouse_id) {
        alert('Please select either Project or Warehouse')
        return
    }

    saving.value = true
    try {
        // Prepare data - ensure null values are properly handled
        const data = {
            project_id: form.project_id || null,
            warehouse_id: form.warehouse_id || null,
            supplier_id: form.supplier_id || null,
            invoice_no: form.invoice_no,
            date: form.date,
            discount: form.discount || 0,
            paid: form.paid || 0,
            note: form.note || '',
            items: form.items.map(item => ({
                product_id: item.product_id,
                size: item.size || '',
                package_qty: item.package_qty || 1,
                unit_per_package: item.unit_per_package || 1,
                package_price: item.package_price || 0,
                quantity: item.quantity,
                unit_price: item.unit_price,
                unit_mrp: item.unit_mrp || 0,
                total_mrp: item.total_mrp || 0,
            }))
        }

        const response = await api.put(`/purchases/${route.params.id}`, data)
        alert('Purchase updated successfully!')
        router.push({ name: 'purchases' })
    } catch (error) {
        console.error('Error:', error)
        if (error.response?.data?.message) {
            alert(error.response.data.message)
        } else {
            alert('Error updating purchase. Please try again.')
        }
    }
    saving.value = false
}

onMounted(() => fetchData())
</script>
