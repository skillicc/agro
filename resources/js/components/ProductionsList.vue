<template>
    <div>
        <div class="d-flex justify-space-between align-center mb-4">
            <h3 class="text-h6">Productions</h3>
            <v-btn color="primary" size="small" @click="openDialog()">
                <v-icon left>mdi-plus</v-icon>
                Add Production
            </v-btn>
        </div>

        <v-data-table :headers="headers" :items="productions" :loading="loading" density="compact">
            <template v-slot:item.product="{ item }">
                {{ item.product?.name }}
            </template>
            <template v-slot:item.actions="{ item }">
                <v-btn icon size="x-small" @click="openDialog(item)">
                    <v-icon>mdi-pencil</v-icon>
                </v-btn>
                <v-btn icon size="x-small" color="error" @click="confirmDelete(item)">
                    <v-icon>mdi-delete</v-icon>
                </v-btn>
            </template>
        </v-data-table>

        <!-- Add/Edit Dialog -->
        <v-dialog v-model="dialog" :max-width="$vuetify.display.xs ? '100%' : '500'" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title>{{ editMode ? 'Edit Production' : 'Add Production' }}</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="saveProduction">
                        <v-text-field
                            v-model="form.name"
                            label="Name"
                            required
                        ></v-text-field>
                        <div class="d-flex align-center ga-2">
                            <v-select
                                v-model="form.category_id"
                                :items="filteredCategories"
                                item-title="name"
                                item-value="id"
                                label="Category"
                                clearable
                                class="flex-grow-1"
                            ></v-select>
                            <v-btn
                                icon
                                size="small"
                                color="primary"
                                variant="tonal"
                                @click="openCategoryDialog"
                                title="Add Category"
                            >
                                <v-icon>mdi-plus</v-icon>
                            </v-btn>
                        </div>
                        <v-text-field
                            v-model="form.quantity"
                            label="Quantity"
                            type="number"
                            required
                        ></v-text-field>
                        <v-text-field
                            v-model="form.sale_value"
                            label="Sale Value"
                            type="number"
                            prefix="৳"
                        ></v-text-field>
                        <v-text-field
                            v-model="form.date"
                            label="Date"
                            type="date"
                            required
                        ></v-text-field>
                        <v-textarea
                            v-model="form.note"
                            label="Note"
                            rows="2"
                        ></v-textarea>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="dialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveProduction" :loading="saving">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog v-model="categoryDialog" max-width="400">
            <v-card>
                <v-card-title>Add Category</v-card-title>
                <v-card-text>
                    <v-text-field
                        v-model="categoryForm.name"
                        label="Category Name"
                        required
                    ></v-text-field>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="categoryDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveCategory" :loading="savingCategory">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Confirm -->
        <v-dialog v-model="deleteDialog" max-width="400">
            <v-card>
                <v-card-title>Confirm Delete</v-card-title>
                <v-card-text>Are you sure you want to delete this production record?</v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" @click="deleteProduction" :loading="deleting">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import api from '../services/api'

const props = defineProps({
    projectId: { type: [Number, String], required: true }
})

const productions = ref([])
const products = ref([])
const categories = ref([])
const loading = ref(false)
const dialog = ref(false)
const deleteDialog = ref(false)
const categoryDialog = ref(false)
const editMode = ref(false)
const selectedProduction = ref(null)
const saving = ref(false)
const deleting = ref(false)
const savingCategory = ref(false)

const headers = [
    { title: 'Date', key: 'date' },
    { title: 'Product', key: 'product' },
    { title: 'Quantity', key: 'quantity' },
    { title: 'Note', key: 'note' },
    { title: 'Actions', key: 'actions', sortable: false },
]

const form = reactive({
    category_id: null,
    name: '',
    quantity: '',
    sale_value: '',
    date: new Date().toISOString().split('T')[0],
    note: ''
})

const categoryForm = reactive({
    name: ''
})

const productionProducts = computed(() => {
    return products.value.filter(product => product.type === 'own_production')
})

const filteredCategories = computed(() => {
    const map = new Map()

    categories.value.forEach(category => {
        if (category?.id && category?.name && category.type === 'own_production') {
            map.set(String(category.id), {
                id: category.id,
                name: category.name,
            })
        }
    })

    productionProducts.value.forEach(product => {
        if (product.category_id && product.category?.name) {
            map.set(String(product.category_id), {
                id: product.category_id,
                name: product.category.name,
            })
        }
    })

    const selectedCategory = categories.value.find(category => String(category.id) === String(form.category_id))
    if (selectedCategory) {
        map.set(String(selectedCategory.id), {
            id: selectedCategory.id,
            name: selectedCategory.name,
        })
    }

    return Array.from(map.values()).sort((a, b) => a.name.localeCompare(b.name))
})

const findMatchingProduct = () => {
    const name = form.name.trim().toLowerCase()
    return productionProducts.value.find(product =>
        product.name?.trim().toLowerCase() === name && (product.category_id || null) === (form.category_id || null)
    )
}

const fetchProductions = async () => {
    loading.value = true
    try {
        const response = await api.get(`/productions?project_id=${props.projectId}`)
        productions.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
    loading.value = false
}

const fetchProducts = async () => {
    try {
        const response = await api.get('/products')
        products.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
}

const fetchCategories = async () => {
    try {
        const response = await api.get('/categories')
        categories.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
}

const openCategoryDialog = () => {
    categoryForm.name = ''
    categoryDialog.value = true
}

const saveCategory = async () => {
    if (!categoryForm.name.trim()) return

    savingCategory.value = true
    try {
        const response = await api.post('/categories', {
            name: categoryForm.name.trim(),
            type: 'own_production',
        })

        const createdCategory = response.data
        categories.value = [
            ...categories.value.filter(category => String(category.id) !== String(createdCategory.id)),
            createdCategory,
        ]
        form.category_id = createdCategory.id
        categoryDialog.value = false
    } catch (error) {
        console.error('Error:', error)
    }
    savingCategory.value = false
}

const openDialog = (production = null) => {
    editMode.value = !!production
    selectedProduction.value = production
    if (production) {
        Object.assign(form, {
            category_id: production.product?.category_id || null,
            name: production.product?.name || '',
            quantity: production.quantity,
            sale_value: production.product?.selling_price || '',
            date: production.date,
            note: production.note || ''
        })
    } else {
        Object.assign(form, {
            category_id: null,
            name: '',
            quantity: '',
            sale_value: '',
            date: new Date().toISOString().split('T')[0],
            note: ''
        })
    }
    dialog.value = true
}

const saveProduction = async () => {
    if (!form.name.trim()) return

    saving.value = true
    try {
        let product = findMatchingProduct()

        if (!product) {
            const response = await api.post('/products', {
                name: form.name.trim(),
                category_id: form.category_id,
                type: 'own_production',
                unit: 'kg',
                buying_price: 0,
                production_cost: 0,
                selling_price: Number(form.sale_value || 0),
                alert_quantity: 0,
                description: '',
            })
            product = response.data
            await fetchProducts()
        } else {
            await api.put(`/products/${product.id}`, {
                selling_price: Number(form.sale_value || 0),
                category_id: form.category_id,
            })
        }

        const data = {
            project_id: props.projectId,
            product_id: product.id,
            quantity: form.quantity,
            date: form.date,
            note: form.note,
        }

        if (editMode.value) {
            await api.put(`/productions/${selectedProduction.value.id}`, data)
        } else {
            await api.post('/productions', data)
        }
        dialog.value = false
        fetchProductions()
    } catch (error) {
        console.error('Error:', error)
    }
    saving.value = false
}

const confirmDelete = (production) => {
    selectedProduction.value = production
    deleteDialog.value = true
}

const deleteProduction = async () => {
    deleting.value = true
    try {
        await api.delete(`/productions/${selectedProduction.value.id}`)
        deleteDialog.value = false
        fetchProductions()
    } catch (error) {
        console.error('Error:', error)
    }
    deleting.value = false
}

onMounted(() => {
    fetchProductions()
    fetchProducts()
    fetchCategories()
})
</script>
