<template>
    <div>
        <div class="d-flex justify-space-between align-center mb-4">
            <h1 class="text-h4">Products</h1>
            <div class="d-flex ga-2">
                <v-btn color="secondary" variant="outlined" @click="categoryDialog = true">
                    <v-icon left>mdi-shape</v-icon>
                    Categories
                </v-btn>
                <v-btn color="primary" @click="openDialog()">
                    <v-icon left>mdi-plus</v-icon>
                    Add Product
                </v-btn>
            </div>
        </div>

        <v-card>
            <v-card-text>
                <v-data-table :headers="headers" :items="products" :loading="loading">
                    <template v-slot:item.type="{ item }">
                        <v-chip :color="item.type === 'own_production' ? 'success' : 'info'" size="small">
                            {{ item.type === 'own_production' ? 'উৎপাদন' : 'ক্রয়-বিক্রয়' }}
                        </v-chip>
                    </template>
                    <template v-slot:item.categories="{ item }">
                        <div class="d-flex flex-wrap ga-1">
                            <v-chip v-for="cat in item.categories" :key="cat.id" size="small" color="primary" variant="tonal">
                                {{ cat.name }}
                            </v-chip>
                            <span v-if="!item.categories || item.categories.length === 0" class="text-grey">-</span>
                        </div>
                    </template>
                    <template v-slot:item.stock_quantity="{ item }">
                        <v-chip :color="item.stock_quantity <= item.alert_quantity ? 'error' : 'success'" size="small">
                            {{ item.stock_quantity }} {{ item.unit }}
                        </v-chip>
                    </template>
                    <template v-slot:item.cost_price="{ item }">
                        <span v-if="item.type === 'own_production'">
                            ৳{{ formatNumber(item.production_cost || 0) }}
                        </span>
                        <span v-else>
                            ৳{{ formatNumber(item.buying_price) }}
                        </span>
                    </template>
                    <template v-slot:item.selling_price="{ item }">
                        ৳{{ formatNumber(item.selling_price) }}
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

        <!-- Add/Edit Product Dialog -->
        <v-dialog v-model="dialog" max-width="600">
            <v-card>
                <v-card-title>{{ editMode ? 'Edit Product' : 'Add Product' }}</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="saveProduct">
                        <v-row>
                            <v-col cols="12" md="6">
                                <v-text-field v-model="form.name" label="Product Name" required></v-text-field>
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-select
                                    v-model="form.type"
                                    :items="productTypes"
                                    label="Product Type"
                                    required
                                ></v-select>
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-select
                                    v-model="form.category_ids"
                                    :items="categories"
                                    item-title="name"
                                    item-value="id"
                                    label="Categories (Multiple)"
                                    multiple
                                    chips
                                    closable-chips
                                    clearable
                                >
                                    <template v-slot:prepend-item>
                                        <v-list-item @click="openCategoryCreateDialog">
                                            <template v-slot:prepend>
                                                <v-icon color="primary">mdi-plus-circle</v-icon>
                                            </template>
                                            <v-list-item-title class="text-primary">Add New Category</v-list-item-title>
                                        </v-list-item>
                                        <v-divider class="mt-2"></v-divider>
                                    </template>
                                </v-select>
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-select
                                    v-model="form.unit"
                                    :items="unitOptions"
                                    label="Unit"
                                    required
                                ></v-select>
                            </v-col>
                            <!-- Buying Price - only for Trading products -->
                            <v-col cols="12" md="6" v-if="form.type === 'trading'">
                                <v-text-field v-model.number="form.buying_price" label="Buying Price (ক্রয় মূল্য)" type="number" required></v-text-field>
                            </v-col>
                            <!-- Production Cost - only for Own Production products -->
                            <v-col cols="12" md="6" v-if="form.type === 'own_production'">
                                <v-text-field v-model.number="form.production_cost" label="Production Cost (উৎপাদন খরচ)" type="number" hint="Optional - for profit calculation"></v-text-field>
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-text-field v-model.number="form.selling_price" label="Selling Price (বিক্রয় মূল্য)" type="number" required></v-text-field>
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-text-field v-model.number="form.alert_quantity" label="Alert Quantity" type="number"></v-text-field>
                            </v-col>
                            <v-col cols="12">
                                <v-textarea v-model="form.description" label="Description" rows="2"></v-textarea>
                            </v-col>
                        </v-row>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="dialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveProduct" :loading="saving">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Product Confirm -->
        <v-dialog v-model="deleteDialog" max-width="400">
            <v-card>
                <v-card-title>Confirm Delete</v-card-title>
                <v-card-text>Are you sure you want to delete "{{ selectedProduct?.name }}"?</v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" @click="deleteProduct" :loading="deleting">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Category Management Dialog -->
        <v-dialog v-model="categoryDialog" max-width="600">
            <v-card>
                <v-card-title class="d-flex justify-space-between align-center">
                    <span>Product Categories</span>
                    <v-btn color="primary" size="small" @click="openCategoryForm()">
                        <v-icon left>mdi-plus</v-icon>
                        Add Category
                    </v-btn>
                </v-card-title>
                <v-card-text>
                    <v-list v-if="categories.length > 0">
                        <v-list-item v-for="cat in categories" :key="cat.id">
                            <template v-slot:prepend>
                                <v-icon>mdi-shape</v-icon>
                            </template>
                            <v-list-item-title>{{ cat.name }}</v-list-item-title>
                            <v-list-item-subtitle v-if="cat.description">{{ cat.description }}</v-list-item-subtitle>
                            <template v-slot:append>
                                <v-btn icon size="small" @click="openCategoryForm(cat)">
                                    <v-icon>mdi-pencil</v-icon>
                                </v-btn>
                                <v-btn icon size="small" color="error" @click="confirmDeleteCategory(cat)">
                                    <v-icon>mdi-delete</v-icon>
                                </v-btn>
                            </template>
                        </v-list-item>
                    </v-list>
                    <v-alert v-else type="info" variant="tonal">
                        No categories yet. Create one to organize your products.
                    </v-alert>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="categoryDialog = false">Close</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Add/Edit Category Dialog -->
        <v-dialog v-model="categoryFormDialog" max-width="400">
            <v-card>
                <v-card-title>{{ editCategoryMode ? 'Edit Category' : 'Add Category' }}</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="saveCategory">
                        <v-text-field
                            v-model="categoryForm.name"
                            label="Category Name"
                            required
                            autofocus
                        ></v-text-field>
                        <v-textarea
                            v-model="categoryForm.description"
                            label="Description (optional)"
                            rows="2"
                        ></v-textarea>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="categoryFormDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveCategory" :loading="savingCategory">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Category Confirm -->
        <v-dialog v-model="deleteCategoryDialog" max-width="400">
            <v-card>
                <v-card-title>Confirm Delete</v-card-title>
                <v-card-text>
                    Are you sure you want to delete category "{{ selectedCategory?.name }}"?
                    <v-alert type="warning" variant="tonal" density="compact" class="mt-2">
                        Products in this category will become uncategorized.
                    </v-alert>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="deleteCategoryDialog = false">Cancel</v-btn>
                    <v-btn color="error" @click="deleteCategory" :loading="deletingCategory">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '../../services/api'

const products = ref([])
const categories = ref([])
const loading = ref(false)
const dialog = ref(false)
const deleteDialog = ref(false)
const editMode = ref(false)
const selectedProduct = ref(null)
const saving = ref(false)
const deleting = ref(false)

// Category management
const categoryDialog = ref(false)
const categoryFormDialog = ref(false)
const deleteCategoryDialog = ref(false)
const editCategoryMode = ref(false)
const selectedCategory = ref(null)
const savingCategory = ref(false)
const deletingCategory = ref(false)

const unitOptions = [
    { title: 'Piece (পিস)', value: 'pcs' },
    { title: 'Kg (কেজি)', value: 'kg' },
    { title: 'Gram (গ্রাম)', value: 'gm' },
    { title: 'Liter (লিটার)', value: 'liter' },
    { title: 'Meter (মিটার)', value: 'meter' },
    { title: 'Bosta (বস্তা)', value: 'bst' },
    { title: 'Box (বক্স)', value: 'box' },
    { title: 'Dozen (ডজন)', value: 'dozen' },
    { title: 'Packet (প্যাকেট)', value: 'packet' },
]

const productTypes = [
    { title: 'নিজস্ব উৎপাদন (Own Production)', value: 'own_production' },
    { title: 'ক্রয়-বিক্রয় (Trading)', value: 'trading' },
]

const headers = [
    { title: 'Name', key: 'name' },
    { title: 'Type', key: 'type' },
    { title: 'Categories', key: 'categories' },
    { title: 'Unit', key: 'unit' },
    { title: 'Cost Price', key: 'cost_price' },
    { title: 'Selling Price', key: 'selling_price' },
    { title: 'Stock', key: 'stock_quantity' },
    { title: 'Actions', key: 'actions', sortable: false },
]

const form = reactive({
    name: '',
    category_id: null,
    category_ids: [],
    type: 'trading',
    unit: 'pcs',
    buying_price: 0,
    production_cost: 0,
    selling_price: 0,
    alert_quantity: 10,
    description: '',
})

const categoryForm = reactive({
    name: '',
    description: '',
})

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD')

const fetchProducts = async () => {
    loading.value = true
    try {
        const response = await api.get('/products')
        products.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
    loading.value = false
}

const fetchCategories = async () => {
    try {
        const response = await api.get('/categories')
        categories.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
}

const openDialog = (product = null) => {
    editMode.value = !!product
    selectedProduct.value = product
    if (product) {
        Object.assign(form, product)
        // Extract category IDs from categories array
        form.category_ids = product.categories ? product.categories.map(c => c.id) : []
    } else {
        Object.assign(form, { name: '', category_id: null, category_ids: [], type: 'trading', unit: 'pcs', buying_price: 0, production_cost: 0, selling_price: 0, alert_quantity: 10, description: '' })
    }
    dialog.value = true
}

const saveProduct = async () => {
    saving.value = true
    try {
        if (editMode.value) {
            await api.put(`/products/${selectedProduct.value.id}`, form)
        } else {
            await api.post('/products', form)
        }
        dialog.value = false
        fetchProducts()
    } catch (error) {
        console.error('Error:', error)
    }
    saving.value = false
}

const confirmDelete = (product) => {
    selectedProduct.value = product
    deleteDialog.value = true
}

const deleteProduct = async () => {
    deleting.value = true
    try {
        await api.delete(`/products/${selectedProduct.value.id}`)
        deleteDialog.value = false
        fetchProducts()
    } catch (error) {
        console.error('Error:', error)
    }
    deleting.value = false
}

// Category management functions
const openCategoryCreateDialog = () => {
    editCategoryMode.value = false
    selectedCategory.value = null
    Object.assign(categoryForm, { name: '', description: '' })
    categoryFormDialog.value = true
}

const openCategoryForm = (category = null) => {
    editCategoryMode.value = !!category
    selectedCategory.value = category
    if (category) {
        Object.assign(categoryForm, { name: category.name, description: category.description || '' })
    } else {
        Object.assign(categoryForm, { name: '', description: '' })
    }
    categoryFormDialog.value = true
}

const saveCategory = async () => {
    if (!categoryForm.name.trim()) return

    savingCategory.value = true
    try {
        if (editCategoryMode.value) {
            await api.put(`/categories/${selectedCategory.value.id}`, categoryForm)
        } else {
            await api.post('/categories', categoryForm)
        }
        categoryFormDialog.value = false
        fetchCategories()
    } catch (error) {
        console.error('Error:', error)
    }
    savingCategory.value = false
}

const confirmDeleteCategory = (category) => {
    selectedCategory.value = category
    deleteCategoryDialog.value = true
}

const deleteCategory = async () => {
    deletingCategory.value = true
    try {
        await api.delete(`/categories/${selectedCategory.value.id}`)
        deleteCategoryDialog.value = false
        fetchCategories()
    } catch (error) {
        console.error('Error:', error)
    }
    deletingCategory.value = false
}

onMounted(() => {
    fetchProducts()
    fetchCategories()
})
</script>
