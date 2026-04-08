<template>
    <div>
        <div class="d-flex flex-wrap justify-space-between align-center mb-2 mb-sm-4 ga-2">
            <h1 class="text-h5 text-sm-h4">Product Returns</h1>
            <v-btn color="primary" :size="$vuetify.display.xs ? 'small' : 'default'" @click="openDialog()">
                <v-icon left>mdi-keyboard-return</v-icon>
                Add Return
            </v-btn>
        </div>

        <v-card class="mb-4">
            <v-card-text>
                <v-row>
                    <v-col cols="12" sm="6" lg="3">
                        <v-select
                            v-model="filters.project_id"
                            :items="projects"
                            item-title="name"
                            item-value="id"
                            label="Project"
                            clearable
                            @update:model-value="fetchReturns"
                        ></v-select>
                    </v-col>
                    <v-col cols="12" sm="6" lg="3">
                        <v-select
                            v-model="filters.warehouse_id"
                            :items="warehouses"
                            item-title="name"
                            item-value="id"
                            label="Warehouse"
                            clearable
                            @update:model-value="fetchReturns"
                        ></v-select>
                    </v-col>
                    <v-col cols="12" sm="6" lg="3">
                        <v-select
                            v-model="filters.product_id"
                            :items="products"
                            item-title="name"
                            item-value="id"
                            label="Product"
                            clearable
                            @update:model-value="fetchReturns"
                        ></v-select>
                    </v-col>
                    <v-col cols="12" sm="6" lg="3">
                        <v-text-field
                            v-model="filters.start_date"
                            label="Start Date"
                            type="date"
                            @update:model-value="fetchReturns"
                        ></v-text-field>
                    </v-col>
                    <v-col cols="12" sm="6" lg="3">
                        <v-text-field
                            v-model="filters.end_date"
                            label="End Date"
                            type="date"
                            @update:model-value="fetchReturns"
                        ></v-text-field>
                    </v-col>
                    <v-col cols="12" sm="6" lg="2" class="d-flex align-center">
                        <v-btn variant="tonal" @click="clearFilters">Clear</v-btn>
                    </v-col>
                </v-row>
            </v-card-text>
        </v-card>

        <v-row dense class="mb-3">
            <v-col cols="12" sm="6" md="3">
                <v-card variant="tonal" color="primary">
                    <v-card-text>
                        <div class="text-caption text-medium-emphasis">Return Count</div>
                        <div class="text-h6 font-weight-bold">{{ returnSummary.count }}</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" sm="6" md="3">
                <v-card variant="tonal" color="warning">
                    <v-card-text>
                        <div class="text-caption text-medium-emphasis">Returned Qty</div>
                        <div class="text-h6 font-weight-bold">{{ formatNumber(returnSummary.quantity) }}</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" sm="6" md="3">
                <v-card variant="tonal" color="error">
                    <v-card-text>
                        <div class="text-caption text-medium-emphasis">Total Value</div>
                        <div class="text-h6 font-weight-bold">৳{{ formatNumber(returnSummary.value) }}</div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <v-card>
            <v-card-text>
                <v-data-table :headers="headers" :items="safeProductReturns" :loading="loading" :density="$vuetify.display.smAndDown ? 'compact' : 'default'">
                    <template v-slot:item.sl="{ index }">
                        {{ index + 1 }}
                    </template>
                    <template v-slot:item.source="{ item }">
                        <span v-if="item.project">{{ item.project.name }}</span>
                        <span v-else-if="item.warehouse" class="text-warning">{{ item.warehouse.name }}</span>
                        <span v-else>-</span>
                    </template>
                    <template v-slot:item.product="{ item }">
                        {{ item.product?.name || '-' }}
                    </template>
                    <template v-slot:item.quantity="{ item }">
                        {{ formatNumber(item.quantity) }} {{ item.product?.unit || '' }}
                    </template>
                    <template v-slot:item.value="{ item }">
                        ৳{{ formatNumber(item.value) }}
                    </template>
                    <template v-slot:item.actions="{ item }">
                        <v-btn icon size="small" @click="openDialog(item)">
                            <v-icon>mdi-pencil</v-icon>
                        </v-btn>
                        <v-btn icon size="small" color="error" @click="confirmDelete(item)">
                            <v-icon>mdi-delete</v-icon>
                        </v-btn>
                    </template>
                </v-data-table>
            </v-card-text>
        </v-card>

        <v-dialog v-model="dialog" :max-width="$vuetify.display.xs ? '100%' : '560'" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title>{{ editMode ? 'Edit Product Return' : 'Add Product Return' }}</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="saveReturn">
                        <v-radio-group v-model="form.source_type" inline class="mb-2" @update:model-value="handleSourceTypeChange">
                            <v-radio label="Project" value="project"></v-radio>
                            <v-radio label="Warehouse" value="warehouse"></v-radio>
                        </v-radio-group>

                        <v-select
                            v-if="form.source_type === 'project'"
                            v-model="form.project_id"
                            :items="projects"
                            item-title="name"
                            item-value="id"
                            label="Project"
                            required
                            @update:model-value="fetchPurchaseRates"
                        ></v-select>

                        <v-select
                            v-else
                            v-model="form.warehouse_id"
                            :items="warehouses"
                            item-title="name"
                            item-value="id"
                            label="Warehouse"
                            required
                            @update:model-value="fetchPurchaseRates"
                        ></v-select>

                        <v-select
                            v-model="form.product_id"
                            :items="products"
                            item-title="name"
                            item-value="id"
                            label="Product"
                            required
                            @update:model-value="handleProductChange"
                        ></v-select>
                        <v-select
                            v-model="selectedUnitPrice"
                            :items="rateOptions"
                            item-title="label"
                            item-value="value"
                            label="Purchase Rate"
                            :loading="loadingRates"
                            :disabled="!form.product_id || rateOptions.length === 0"
                            clearable
                            no-data-text="No purchase rate found"
                            hint="Last purchase rate auto-fills total value. You can choose another rate or edit Total Value manually."
                            persistent-hint
                            @update:model-value="applySelectedRate"
                        ></v-select>
                        <v-text-field v-model.number="form.quantity" label="Quantity" type="number" min="0.01" step="0.01" required @update:model-value="handleQuantityChange"></v-text-field>
                        <v-text-field v-model.number="form.value" label="Total Value" type="number" min="0" prefix="৳" required hint="Auto-calculated from purchase rate, but you can change it manually." persistent-hint @update:model-value="markValueAsManual"></v-text-field>
                        <v-text-field v-model="form.date" label="Date" type="date" required></v-text-field>
                        <v-textarea v-model="form.reason" label="Reason / Note" rows="2"></v-textarea>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="dialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveReturn" :loading="saving">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog v-model="deleteDialog" max-width="400" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title>Confirm Delete</v-card-title>
                <v-card-text>Are you sure you want to delete this product return?</v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" @click="deleteReturn" :loading="deleting">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import api from '../../services/api'

const productReturns = ref([])
const projects = ref([])
const warehouses = ref([])
const products = ref([])
const loading = ref(false)
const dialog = ref(false)
const deleteDialog = ref(false)
const editMode = ref(false)
const selectedReturn = ref(null)
const saving = ref(false)
const deleting = ref(false)
const rateOptions = ref([])
const loadingRates = ref(false)
const selectedUnitPrice = ref(null)
const manualValueOverride = ref(false)

const filters = reactive({
    project_id: null,
    warehouse_id: null,
    product_id: null,
    start_date: '',
    end_date: '',
})

const form = reactive({
    source_type: 'project',
    project_id: null,
    warehouse_id: null,
    product_id: null,
    quantity: 1,
    value: 0,
    date: new Date().toISOString().split('T')[0],
    reason: '',
})

const headers = [
    { title: 'SL', key: 'sl', width: '60px' },
    { title: 'Date', key: 'date' },
    { title: 'Source', key: 'source' },
    { title: 'Product', key: 'product' },
    { title: 'Quantity', key: 'quantity' },
    { title: 'Value', key: 'value' },
    { title: 'Reason', key: 'reason' },
    { title: 'Created By', key: 'creator.name' },
    { title: 'Actions', key: 'actions', sortable: false },
]

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD')

const getSelectedSourceWarehouseId = () => {
    if (form.source_type === 'warehouse') {
        return form.warehouse_id || null
    }

    if (form.project_id) {
        return warehouses.value.find((warehouse) => Number(warehouse.project_id) === Number(form.project_id))?.id || null
    }

    return null
}

const calculateSuggestedValue = (quantity = form.quantity, unitPrice = selectedUnitPrice.value) => {
    return Number((Number(quantity || 0) * Number(unitPrice || 0)).toFixed(2))
}

const applySelectedRate = (unitPrice = selectedUnitPrice.value) => {
    if (unitPrice === null || unitPrice === '' || Number.isNaN(Number(unitPrice))) {
        selectedUnitPrice.value = null
        return
    }

    selectedUnitPrice.value = Number(unitPrice)
    manualValueOverride.value = false
    form.value = calculateSuggestedValue(form.quantity, selectedUnitPrice.value)
}

const markValueAsManual = () => {
    if (selectedUnitPrice.value === null) {
        return
    }

    const expectedValue = calculateSuggestedValue(form.quantity, selectedUnitPrice.value)
    manualValueOverride.value = Math.abs(Number(form.value || 0) - expectedValue) > 0.009
}

const handleQuantityChange = () => {
    if (!manualValueOverride.value && selectedUnitPrice.value !== null) {
        form.value = calculateSuggestedValue(form.quantity, selectedUnitPrice.value)
    }
}

const fetchPurchaseRates = async () => {
    if (!form.product_id) {
        rateOptions.value = []
        selectedUnitPrice.value = null
        manualValueOverride.value = false
        return
    }

    loadingRates.value = true

    try {
        const params = {}
        const warehouseId = getSelectedSourceWarehouseId()

        if (warehouseId) {
            params.warehouse_id = warehouseId
        }

        const response = await api.get(`/products/${form.product_id}/batches`, { params })
        const batches = Array.isArray(response.data?.batches) ? response.data.batches : []
        const byPrice = Array.isArray(response.data?.by_price) ? response.data.by_price : []
        const unit = products.value.find((product) => product.id === form.product_id)?.unit || ''

        rateOptions.value = byPrice.map((entry) => ({
            label: `৳${formatNumber(entry.unit_price)}${unit ? ` / ${unit}` : ''} (Qty: ${formatNumber(entry.quantity)})`,
            value: Number(entry.unit_price),
        }))

        if (!rateOptions.value.length) {
            selectedUnitPrice.value = null
            return
        }

        const currentUnitPrice = form.quantity > 0
            ? Number((Number(form.value || 0) / Number(form.quantity || 1)).toFixed(2))
            : null

        const matchedRate = currentUnitPrice !== null
            ? rateOptions.value.find((option) => Math.abs(option.value - currentUnitPrice) < 0.01)?.value ?? null
            : null

        const latestRate = Number(batches[batches.length - 1]?.unit_price ?? rateOptions.value[0].value)
        selectedUnitPrice.value = matchedRate ?? latestRate
        manualValueOverride.value = currentUnitPrice !== null && matchedRate === null

        if (!manualValueOverride.value) {
            form.value = calculateSuggestedValue(form.quantity, selectedUnitPrice.value)
        }
    } catch (error) {
        console.error('Error fetching purchase rates:', error)
        rateOptions.value = []
        selectedUnitPrice.value = null
    } finally {
        loadingRates.value = false
    }
}

const handleProductChange = async () => {
    manualValueOverride.value = false
    await fetchPurchaseRates()
}

const normalizeCollection = (payload) => {
    if (Array.isArray(payload)) {
        return payload
    }

    if (Array.isArray(payload?.data)) {
        return payload.data
    }

    return []
}

const safeProductReturns = computed(() => normalizeCollection(productReturns.value))

const returnSummary = computed(() => {
    return safeProductReturns.value.reduce((summary, item) => {
        summary.count += 1
        summary.quantity += Number(item.quantity || 0)
        summary.value += Number(item.value || 0)
        return summary
    }, {
        count: 0,
        quantity: 0,
        value: 0,
    })
})

const fetchReturns = async () => {
    loading.value = true
    try {
        const params = new URLSearchParams()
        if (filters.project_id) params.append('project_id', filters.project_id)
        if (filters.warehouse_id) params.append('warehouse_id', filters.warehouse_id)
        if (filters.product_id) params.append('product_id', filters.product_id)
        if (filters.start_date) params.append('start_date', filters.start_date)
        if (filters.end_date) params.append('end_date', filters.end_date)

        const response = await api.get(`/product-returns?${params.toString()}`)
        productReturns.value = normalizeCollection(response.data)
    } catch (error) {
        console.error('Error:', error)
    }
    loading.value = false
}

const fetchProjects = async () => {
    try {
        const response = await api.get('/projects')
        projects.value = normalizeCollection(response.data)
    } catch (error) {
        console.error('Error:', error)
    }
}

const fetchWarehouses = async () => {
    try {
        const response = await api.get('/warehouses')
        warehouses.value = normalizeCollection(response.data)
    } catch (error) {
        console.error('Error:', error)
    }
}

const fetchProducts = async () => {
    try {
        const response = await api.get('/products')
        products.value = normalizeCollection(response.data)
    } catch (error) {
        console.error('Error:', error)
    }
}

const clearFilters = () => {
    Object.assign(filters, {
        project_id: null,
        warehouse_id: null,
        product_id: null,
        start_date: '',
        end_date: '',
    })
    fetchReturns()
}

const handleSourceTypeChange = async () => {
    if (form.source_type === 'project') {
        form.warehouse_id = null
    } else {
        form.project_id = null
    }

    await fetchPurchaseRates()
}

const openDialog = (item = null) => {
    editMode.value = !!item
    selectedReturn.value = item
    rateOptions.value = []
    selectedUnitPrice.value = null
    manualValueOverride.value = false

    if (item) {
        Object.assign(form, {
            source_type: item.warehouse_id ? 'warehouse' : 'project',
            project_id: item.project_id || null,
            warehouse_id: item.warehouse_id || null,
            product_id: item.product_id,
            quantity: Number(item.quantity || 0),
            value: Number(item.value || 0),
            date: item.date,
            reason: item.reason || '',
        })
    } else {
        Object.assign(form, {
            source_type: 'project',
            project_id: filters.project_id || null,
            warehouse_id: filters.warehouse_id || null,
            product_id: filters.product_id || null,
            quantity: 1,
            value: 0,
            date: new Date().toISOString().split('T')[0],
            reason: '',
        })
    }

    dialog.value = true
    fetchPurchaseRates()
}

const saveReturn = async () => {
    saving.value = true
    try {
        const data = { ...form }

        if (form.source_type === 'project') {
            data.warehouse_id = null
        } else {
            data.project_id = null
        }

        delete data.source_type

        if (editMode.value) {
            await api.put(`/product-returns/${selectedReturn.value.id}`, data)
        } else {
            await api.post('/product-returns', data)
        }

        dialog.value = false
        fetchReturns()
    } catch (error) {
        console.error('Error:', error)
        alert(error.response?.data?.message || 'Failed to save product return')
    }
    saving.value = false
}

const confirmDelete = (item) => {
    selectedReturn.value = item
    deleteDialog.value = true
}

const deleteReturn = async () => {
    deleting.value = true
    try {
        await api.delete(`/product-returns/${selectedReturn.value.id}`)
        deleteDialog.value = false
        fetchReturns()
    } catch (error) {
        console.error('Error:', error)
    }
    deleting.value = false
}

onMounted(() => {
    fetchReturns()
    fetchProjects()
    fetchWarehouses()
    fetchProducts()
})
</script>
