<template>
    <div>
        <div class="d-flex justify-space-between align-center mb-4">
            <h3 class="text-h6">Damages</h3>
            <v-btn color="primary" size="small" @click="openDialog()">
                <v-icon left>mdi-plus</v-icon>
                Record Damage
            </v-btn>
        </div>

        <v-data-table :headers="headers" :items="damages" :loading="loading" density="compact">
            <template v-slot:item.product="{ item }">
                {{ item.product?.name }}
            </template>
            <template v-slot:item.value="{ item }">
                ৳{{ Number(item.value).toLocaleString() }}
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
                <v-card-title>{{ editMode ? 'Edit Damage' : 'Record Damage' }}</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="saveDamage">
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
                            v-model="form.value"
                            label="Damage Value"
                            type="number"
                            prefix="৳"
                            required
                        ></v-text-field>
                        <v-text-field
                            v-model="form.date"
                            label="Date"
                            type="date"
                            required
                        ></v-text-field>
                        <v-textarea
                            v-model="form.reason"
                            label="Note"
                            rows="2"
                        ></v-textarea>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="dialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveDamage" :loading="saving">Save</v-btn>
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
                <v-card-text>Are you sure you want to delete this damage record?</v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" @click="deleteDamage" :loading="deleting">Delete</v-btn>
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

const damages = ref([])
const products = ref([])
const categories = ref([])
const loading = ref(false)
const dialog = ref(false)
const deleteDialog = ref(false)
const categoryDialog = ref(false)
const editMode = ref(false)
const selectedDamage = ref(null)
const saving = ref(false)
const deleting = ref(false)
const savingCategory = ref(false)

const headers = [
    { title: 'Date', key: 'date' },
    { title: 'Product', key: 'product' },
    { title: 'Quantity', key: 'quantity' },
    { title: 'Value', key: 'value' },
    { title: 'Reason', key: 'reason' },
    { title: 'Actions', key: 'actions', sortable: false },
]

const form = reactive({
    category_id: null,
    name: '',
    quantity: '',
    value: '',
    date: new Date().toISOString().split('T')[0],
    reason: ''
})

const categoryForm = reactive({
    name: ''
})

const productionProducts = computed(() => {
    return products.value.filter(product => product.type === 'own_production')
})

const filteredCategories = computed(() => {
    const map = new Map()

    productionProducts.value.forEach(product => {
        if (product.category_id && product.category?.name) {
            map.set(product.category_id, {
                id: product.category_id,
                name: product.category.name,
            })
        }
    })

    const selectedCategory = categories.value.find(category => category.id === form.category_id)
    if (selectedCategory) {
        map.set(selectedCategory.id, selectedCategory)
    }

    return Array.from(map.values())
})

const findMatchingProduct = () => {
    const name = form.name.trim().toLowerCase()
    return productionProducts.value.find(product =>
        product.name?.trim().toLowerCase() === name && (product.category_id || null) === (form.category_id || null)
    )
}

const fetchDamages = async () => {
    loading.value = true
    try {
        const response = await api.get(`/damages?project_id=${props.projectId}`)
        damages.value = response.data
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
        const response = await api.post('/categories', { name: categoryForm.name })
        await fetchCategories()
        form.category_id = response.data.id
        categoryDialog.value = false
    } catch (error) {
        console.error('Error:', error)
    }
    savingCategory.value = false
}

const openDialog = (damage = null) => {
    editMode.value = !!damage
    selectedDamage.value = damage
    if (damage) {
        Object.assign(form, {
            category_id: damage.product?.category_id || null,
            name: damage.product?.name || '',
            quantity: damage.quantity,
            value: damage.value,
            date: damage.date,
            reason: damage.reason || ''
        })
    } else {
        Object.assign(form, {
            category_id: null,
            name: '',
            quantity: '',
            value: '',
            date: new Date().toISOString().split('T')[0],
            reason: ''
        })
    }
    dialog.value = true
}

const saveDamage = async () => {
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
                selling_price: 0,
                alert_quantity: 0,
                description: '',
            })
            product = response.data
            await fetchProducts()
        }

        const data = {
            project_id: props.projectId,
            product_id: product.id,
            quantity: form.quantity,
            value: form.value,
            date: form.date,
            reason: form.reason,
        }

        if (editMode.value) {
            await api.put(`/damages/${selectedDamage.value.id}`, data)
        } else {
            await api.post('/damages', data)
        }
        dialog.value = false
        fetchDamages()
    } catch (error) {
        console.error('Error:', error)
    }
    saving.value = false
}

const confirmDelete = (damage) => {
    selectedDamage.value = damage
    deleteDialog.value = true
}

const deleteDamage = async () => {
    deleting.value = true
    try {
        await api.delete(`/damages/${selectedDamage.value.id}`)
        deleteDialog.value = false
        fetchDamages()
    } catch (error) {
        console.error('Error:', error)
    }
    deleting.value = false
}

onMounted(() => {
    fetchDamages()
    fetchProducts()
    fetchCategories()
})
</script>
