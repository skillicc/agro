<template>
    <div>
        <div class="d-flex flex-column flex-sm-row justify-space-between align-start align-sm-center mb-4 ga-2">
            <h1 class="text-h5 text-sm-h4">Warehouses</h1>
            <div class="d-flex ga-2">
                <v-btn color="warning" @click="projectTransferDialog = true" :size="$vuetify.display.xs ? 'small' : 'default'">
                    <v-icon left>mdi-truck-delivery</v-icon>
                    <span class="d-none d-sm-inline">To Project</span>
                </v-btn>
                <v-btn color="secondary" @click="transferDialog = true" :size="$vuetify.display.xs ? 'small' : 'default'">
                    <v-icon left>mdi-swap-horizontal</v-icon>
                    <span class="d-none d-sm-inline">Transfer</span>
                </v-btn>
                <v-btn color="primary" @click="openDialog()" :size="$vuetify.display.xs ? 'small' : 'default'">
                    <v-icon left>mdi-plus</v-icon>
                    <span class="d-none d-sm-inline">Add Warehouse</span>
                    <span class="d-sm-none">Add</span>
                </v-btn>
            </div>
        </div>

        <!-- Summary Cards -->
        <v-row class="mb-4">
            <v-col cols="6" sm="6" md="3">
                <v-card color="primary" variant="tonal">
                    <v-card-text class="pa-3">
                        <div class="text-h5 text-sm-h4">{{ warehouses.length }}</div>
                        <div class="text-body-2">Warehouses</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" sm="6" md="3">
                <v-card color="success" variant="tonal">
                    <v-card-text class="pa-3">
                        <div class="text-h5 text-sm-h4">{{ totalStockItems }}</div>
                        <div class="text-body-2">Stock Items</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" sm="6" md="3">
                <v-card color="warning" variant="tonal">
                    <v-card-text class="pa-3">
                        <div class="text-h5 text-sm-h4">{{ lowStockCount }}</div>
                        <div class="text-body-2">Low Stock</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" sm="6" md="3">
                <v-card color="info" variant="tonal">
                    <v-card-text class="pa-3">
                        <div class="text-h5 text-sm-h4">{{ pendingTransfers }}</div>
                        <div class="text-body-2">Pending Transfers</div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <!-- Warehouse Cards -->
        <v-row>
            <v-col v-for="warehouse in warehouses" :key="warehouse.id" cols="12" sm="6" md="4" lg="3">
                <v-card :class="{ 'opacity-50': !warehouse.is_active }">
                    <v-card-title class="d-flex justify-space-between align-center text-body-1 text-sm-h6">
                        <span>{{ warehouse.name }}</span>
                        <v-chip :color="warehouse.is_active ? 'success' : 'grey'" size="x-small">
                            {{ warehouse.is_active ? 'Active' : 'Inactive' }}
                        </v-chip>
                    </v-card-title>
                    <v-card-subtitle>{{ warehouse.code }}</v-card-subtitle>
                    <v-card-text class="pa-3">
                        <div class="text-caption text-grey mb-2">
                            <v-icon size="small">mdi-map-marker</v-icon>
                            {{ warehouse.address || 'No address' }}
                        </div>
                        <div class="text-caption text-grey mb-2" v-if="warehouse.manager_name">
                            <v-icon size="small">mdi-account</v-icon>
                            {{ warehouse.manager_name }}
                        </div>
                        <div class="text-caption text-grey mb-2" v-if="warehouse.phone">
                            <v-icon size="small">mdi-phone</v-icon>
                            {{ warehouse.phone }}
                        </div>
                        <v-divider class="my-2"></v-divider>
                        <div class="d-flex justify-space-between">
                            <span class="text-caption">Stock Items:</span>
                            <strong>{{ warehouse.stocks_count || 0 }}</strong>
                        </div>
                    </v-card-text>
                    <v-card-actions class="pa-2">
                        <v-btn size="small" variant="text" @click="viewStock(warehouse)">
                            <v-icon left size="small">mdi-package-variant</v-icon>
                            Stock
                        </v-btn>
                        <v-spacer></v-spacer>
                        <v-btn icon size="x-small" variant="text" @click="openDialog(warehouse)">
                            <v-icon>mdi-pencil</v-icon>
                        </v-btn>
                        <v-btn icon size="x-small" variant="text" color="error" @click="confirmDelete(warehouse)">
                            <v-icon>mdi-delete</v-icon>
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>

        <!-- Add/Edit Warehouse Dialog -->
        <v-dialog v-model="dialog" :max-width="$vuetify.display.xs ? '100%' : 500" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title class="d-flex justify-space-between align-center">
                    <span>{{ editMode ? 'Edit Warehouse' : 'Add Warehouse' }}</span>
                    <v-btn v-if="$vuetify.display.xs" icon variant="text" @click="dialog = false">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="saveWarehouse">
                        <v-text-field v-model="form.name" label="Warehouse Name" required density="comfortable"></v-text-field>
                        <v-text-field v-model="form.code" label="Code" required density="comfortable" hint="Unique identifier (e.g., WH-001)"></v-text-field>
                        <v-select v-model="form.project_id" :items="projects" item-title="name" item-value="id" label="Project (Optional)" clearable density="comfortable"></v-select>
                        <v-text-field v-model="form.manager_name" label="Manager Name" density="comfortable"></v-text-field>
                        <v-text-field v-model="form.phone" label="Phone" density="comfortable"></v-text-field>
                        <v-textarea v-model="form.address" label="Address" rows="2" density="comfortable"></v-textarea>
                        <v-switch v-model="form.is_active" label="Active" color="success"></v-switch>
                    </v-form>
                </v-card-text>
                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn @click="dialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveWarehouse" :loading="saving">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Stock Dialog -->
        <v-dialog v-model="stockDialog" :max-width="$vuetify.display.xs ? '100%' : 800" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title class="d-flex justify-space-between align-center">
                    <span>{{ selectedWarehouse?.name }} - Stock</span>
                    <div>
                        <v-btn size="small" color="primary" class="mr-2" @click="addStockDialog = true">
                            <v-icon left>mdi-plus</v-icon>
                            Add Stock
                        </v-btn>
                        <v-btn v-if="$vuetify.display.xs" icon variant="text" @click="stockDialog = false">
                            <v-icon>mdi-close</v-icon>
                        </v-btn>
                    </div>
                </v-card-title>
                <v-card-text class="pa-2 pa-sm-4">
                    <v-data-table
                        :headers="stockHeaders"
                        :items="warehouseStocks"
                        :loading="loadingStocks"
                        density="compact"
                    >
                        <template v-slot:item.quantity="{ item }">
                            <v-chip :color="item.quantity <= item.min_quantity ? 'error' : 'success'" size="small">
                                {{ item.quantity }}
                            </v-chip>
                        </template>
                        <template v-slot:item.actions="{ item }">
                            <v-btn icon size="x-small" @click="editStock(item)">
                                <v-icon>mdi-pencil</v-icon>
                            </v-btn>
                        </template>
                    </v-data-table>
                </v-card-text>
                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn @click="stockDialog = false">Close</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Add/Edit Stock Dialog -->
        <v-dialog v-model="addStockDialog" max-width="400">
            <v-card>
                <v-card-title>{{ editStockMode ? 'Edit Stock' : 'Add Stock' }}</v-card-title>
                <v-card-text>
                    <v-select v-model="stockForm.product_id" :items="products" item-title="name" item-value="id" label="Product" required :disabled="editStockMode" density="comfortable"></v-select>
                    <v-text-field v-model="stockForm.quantity" label="Quantity" type="number" required density="comfortable"></v-text-field>
                    <v-text-field v-model="stockForm.min_quantity" label="Minimum Quantity (Alert)" type="number" density="comfortable"></v-text-field>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="addStockDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveStock" :loading="savingStock">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Transfer Dialog -->
        <v-dialog v-model="transferDialog" :max-width="$vuetify.display.xs ? '100%' : 700" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title class="d-flex justify-space-between align-center">
                    <span>Stock Transfer</span>
                    <v-btn v-if="$vuetify.display.xs" icon variant="text" @click="transferDialog = false">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-card-text>
                    <v-row>
                        <v-col cols="12" sm="6">
                            <v-select v-model="transferForm.from_warehouse_id" :items="warehouses" item-title="name" item-value="id" label="From Warehouse" required density="comfortable"></v-select>
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-select v-model="transferForm.to_warehouse_id" :items="warehouses.filter(w => w.id !== transferForm.from_warehouse_id)" item-title="name" item-value="id" label="To Warehouse" required density="comfortable"></v-select>
                        </v-col>
                    </v-row>
                    <v-text-field v-model="transferForm.date" label="Date" type="date" required density="comfortable"></v-text-field>

                    <h4 class="mt-4 mb-2">Transfer Items</h4>
                    <v-card v-for="(item, index) in transferForm.items" :key="index" variant="outlined" class="mb-2 pa-2">
                        <v-row dense>
                            <v-col cols="7">
                                <v-select v-model="item.product_id" :items="products" item-title="name" item-value="id" label="Product" density="compact" hide-details></v-select>
                            </v-col>
                            <v-col cols="3">
                                <v-text-field v-model="item.quantity" label="Qty" type="number" density="compact" hide-details></v-text-field>
                            </v-col>
                            <v-col cols="2" class="d-flex align-center">
                                <v-btn icon size="small" color="error" @click="removeTransferItem(index)" :disabled="transferForm.items.length === 1">
                                    <v-icon>mdi-delete</v-icon>
                                </v-btn>
                            </v-col>
                        </v-row>
                    </v-card>
                    <v-btn size="small" @click="addTransferItem" class="mt-2">
                        <v-icon left>mdi-plus</v-icon>
                        Add Item
                    </v-btn>

                    <v-textarea v-model="transferForm.note" label="Note" rows="2" density="comfortable" class="mt-4"></v-textarea>
                </v-card-text>
                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn @click="transferDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="createTransfer" :loading="creatingTransfer">Create Transfer</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Confirm -->
        <v-dialog v-model="deleteDialog" :max-width="$vuetify.display.xs ? '95%' : 400">
            <v-card>
                <v-card-title>Confirm Delete</v-card-title>
                <v-card-text>Are you sure you want to delete "{{ selectedWarehouse?.name }}"?</v-card-text>
                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" @click="deleteWarehouse" :loading="deleting">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Transfer to Project Dialog -->
        <v-dialog v-model="projectTransferDialog" :max-width="$vuetify.display.xs ? '100%' : 700" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title class="d-flex justify-space-between align-center">
                    <span>Transfer to Project</span>
                    <v-btn v-if="$vuetify.display.xs" icon variant="text" @click="projectTransferDialog = false">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-card-text>
                    <v-row>
                        <v-col cols="12" sm="6">
                            <v-select
                                v-model="projectTransferForm.from_warehouse_id"
                                :items="warehouses"
                                item-title="name"
                                item-value="id"
                                label="From Warehouse"
                                required
                                density="comfortable"
                            ></v-select>
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-select
                                v-model="projectTransferForm.project_id"
                                :items="projects"
                                item-title="name"
                                item-value="id"
                                label="To Project"
                                required
                                density="comfortable"
                            >
                                <template v-slot:item="{ item, props }">
                                    <v-list-item v-bind="props">
                                        <template v-slot:append>
                                            <v-chip size="x-small" :color="item.raw.project_type === 'administration' ? 'primary' : 'warning'">
                                                {{ item.raw.project_type === 'administration' ? 'Admin' : 'Central' }}
                                            </v-chip>
                                        </template>
                                    </v-list-item>
                                </template>
                            </v-select>
                        </v-col>
                    </v-row>
                    <v-text-field v-model="projectTransferForm.date" label="Date" type="date" required density="comfortable"></v-text-field>

                    <h4 class="mt-4 mb-2">Transfer Items</h4>
                    <v-card v-for="(item, index) in projectTransferForm.items" :key="index" variant="outlined" class="mb-2 pa-2">
                        <v-row dense>
                            <v-col cols="7">
                                <v-select v-model="item.product_id" :items="products" item-title="name" item-value="id" label="Product" density="compact" hide-details></v-select>
                            </v-col>
                            <v-col cols="3">
                                <v-text-field v-model="item.quantity" label="Qty" type="number" density="compact" hide-details></v-text-field>
                            </v-col>
                            <v-col cols="2" class="d-flex align-center">
                                <v-btn icon size="small" color="error" @click="removeProjectTransferItem(index)" :disabled="projectTransferForm.items.length === 1">
                                    <v-icon>mdi-delete</v-icon>
                                </v-btn>
                            </v-col>
                        </v-row>
                    </v-card>
                    <v-btn size="small" @click="addProjectTransferItem" class="mt-2">
                        <v-icon left>mdi-plus</v-icon>
                        Add Item
                    </v-btn>

                    <v-textarea v-model="projectTransferForm.note" label="Note" rows="2" density="comfortable" class="mt-4"></v-textarea>
                </v-card-text>
                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn @click="projectTransferDialog = false">Cancel</v-btn>
                    <v-btn color="warning" @click="transferToProject" :loading="transferringToProject">Transfer to Project</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '../../services/api'

const warehouses = ref([])
const projects = ref([])
const products = ref([])
const warehouseStocks = ref([])
const loading = ref(false)
const loadingStocks = ref(false)
const dialog = ref(false)
const stockDialog = ref(false)
const addStockDialog = ref(false)
const transferDialog = ref(false)
const projectTransferDialog = ref(false)
const deleteDialog = ref(false)
const editMode = ref(false)
const editStockMode = ref(false)
const selectedWarehouse = ref(null)
const saving = ref(false)
const savingStock = ref(false)
const deleting = ref(false)
const creatingTransfer = ref(false)
const transferringToProject = ref(false)

// Summary data
const totalStockItems = ref(0)
const lowStockCount = ref(0)
const pendingTransfers = ref(0)

const stockHeaders = [
    { title: 'Product', key: 'product.name' },
    { title: 'Quantity', key: 'quantity' },
    { title: 'Min Qty', key: 'min_quantity' },
    { title: 'Actions', key: 'actions', sortable: false },
]

const form = reactive({
    name: '',
    code: '',
    address: '',
    phone: '',
    manager_name: '',
    project_id: null,
    is_active: true,
})

const stockForm = reactive({
    product_id: null,
    quantity: 0,
    min_quantity: 0,
})

const transferForm = reactive({
    from_warehouse_id: null,
    to_warehouse_id: null,
    date: new Date().toISOString().split('T')[0],
    note: '',
    items: [{ product_id: null, quantity: 1 }],
})

const projectTransferForm = reactive({
    from_warehouse_id: null,
    project_id: null,
    date: new Date().toISOString().split('T')[0],
    note: '',
    items: [{ product_id: null, quantity: 1 }],
})

const fetchWarehouses = async () => {
    loading.value = true
    try {
        const response = await api.get('/warehouses')
        warehouses.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
    loading.value = false
}

const fetchSummary = async () => {
    try {
        const response = await api.get('/warehouse-summary')
        totalStockItems.value = response.data.total_stock_items
        lowStockCount.value = response.data.low_stock_count
        pendingTransfers.value = response.data.pending_transfers
    } catch (error) {
        console.error('Error:', error)
    }
}

const fetchProjects = async () => {
    try {
        const response = await api.get('/projects')
        projects.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
}

const fetchProducts = async () => {
    try {
        const response = await api.get('/products')
        products.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
}

const openDialog = (warehouse = null) => {
    editMode.value = !!warehouse
    selectedWarehouse.value = warehouse
    if (warehouse) {
        Object.assign(form, warehouse)
    } else {
        Object.assign(form, {
            name: '',
            code: '',
            address: '',
            phone: '',
            manager_name: '',
            project_id: null,
            is_active: true,
        })
    }
    dialog.value = true
}

const saveWarehouse = async () => {
    saving.value = true
    try {
        if (editMode.value) {
            await api.put(`/warehouses/${selectedWarehouse.value.id}`, form)
        } else {
            await api.post('/warehouses', form)
        }
        dialog.value = false
        fetchWarehouses()
        fetchSummary()
    } catch (error) {
        console.error('Error:', error)
    }
    saving.value = false
}

const viewStock = async (warehouse) => {
    selectedWarehouse.value = warehouse
    loadingStocks.value = true
    stockDialog.value = true
    try {
        const response = await api.get(`/warehouses/${warehouse.id}/stocks`)
        warehouseStocks.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
    loadingStocks.value = false
}

const editStock = (stock) => {
    editStockMode.value = true
    Object.assign(stockForm, {
        product_id: stock.product_id,
        quantity: stock.quantity,
        min_quantity: stock.min_quantity,
    })
    addStockDialog.value = true
}

const saveStock = async () => {
    savingStock.value = true
    try {
        await api.post(`/warehouses/${selectedWarehouse.value.id}/stocks`, stockForm)
        addStockDialog.value = false
        editStockMode.value = false
        viewStock(selectedWarehouse.value)
        fetchSummary()
        Object.assign(stockForm, { product_id: null, quantity: 0, min_quantity: 0 })
    } catch (error) {
        console.error('Error:', error)
    }
    savingStock.value = false
}

const addTransferItem = () => {
    transferForm.items.push({ product_id: null, quantity: 1 })
}

const removeTransferItem = (index) => {
    transferForm.items.splice(index, 1)
}

const createTransfer = async () => {
    creatingTransfer.value = true
    try {
        await api.post('/stock-transfers', transferForm)
        transferDialog.value = false
        fetchSummary()
        // Reset form
        Object.assign(transferForm, {
            from_warehouse_id: null,
            to_warehouse_id: null,
            date: new Date().toISOString().split('T')[0],
            note: '',
            items: [{ product_id: null, quantity: 1 }],
        })
    } catch (error) {
        console.error('Error:', error)
    }
    creatingTransfer.value = false
}

const confirmDelete = (warehouse) => {
    selectedWarehouse.value = warehouse
    deleteDialog.value = true
}

const addProjectTransferItem = () => {
    projectTransferForm.items.push({ product_id: null, quantity: 1 })
}

const removeProjectTransferItem = (index) => {
    projectTransferForm.items.splice(index, 1)
}

const transferToProject = async () => {
    transferringToProject.value = true
    try {
        await api.post('/stock-transfers/to-project', projectTransferForm)
        projectTransferDialog.value = false
        fetchWarehouses()
        fetchSummary()
        // Reset form
        Object.assign(projectTransferForm, {
            from_warehouse_id: null,
            project_id: null,
            date: new Date().toISOString().split('T')[0],
            note: '',
            items: [{ product_id: null, quantity: 1 }],
        })
    } catch (error) {
        console.error('Error:', error)
        alert(error.response?.data?.message || 'Transfer failed')
    }
    transferringToProject.value = false
}

const deleteWarehouse = async () => {
    deleting.value = true
    try {
        await api.delete(`/warehouses/${selectedWarehouse.value.id}`)
        deleteDialog.value = false
        fetchWarehouses()
        fetchSummary()
    } catch (error) {
        console.error('Error:', error)
    }
    deleting.value = false
}

onMounted(() => {
    fetchWarehouses()
    fetchSummary()
    fetchProjects()
    fetchProducts()
})
</script>
