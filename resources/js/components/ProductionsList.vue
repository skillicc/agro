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
                        <v-select
                            v-model="form.product_id"
                            :items="products"
                            item-title="name"
                            item-value="id"
                            label="Product"
                            required
                        ></v-select>
                        <v-text-field
                            v-model="form.quantity"
                            label="Quantity"
                            type="number"
                            required
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
import { ref, reactive, onMounted } from 'vue'
import api from '../services/api'

const props = defineProps({
    projectId: { type: [Number, String], required: true }
})

const productions = ref([])
const products = ref([])
const loading = ref(false)
const dialog = ref(false)
const deleteDialog = ref(false)
const editMode = ref(false)
const selectedProduction = ref(null)
const saving = ref(false)
const deleting = ref(false)

const headers = [
    { title: 'Date', key: 'date' },
    { title: 'Product', key: 'product' },
    { title: 'Quantity', key: 'quantity' },
    { title: 'Note', key: 'note' },
    { title: 'Actions', key: 'actions', sortable: false },
]

const form = reactive({
    product_id: null,
    quantity: '',
    date: new Date().toISOString().split('T')[0],
    note: ''
})

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

const openDialog = (production = null) => {
    editMode.value = !!production
    selectedProduction.value = production
    if (production) {
        Object.assign(form, {
            product_id: production.product_id,
            quantity: production.quantity,
            date: production.date,
            note: production.note || ''
        })
    } else {
        Object.assign(form, {
            product_id: null,
            quantity: '',
            date: new Date().toISOString().split('T')[0],
            note: ''
        })
    }
    dialog.value = true
}

const saveProduction = async () => {
    saving.value = true
    try {
        const data = { ...form, project_id: props.projectId }
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
})
</script>
