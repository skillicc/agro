<template>
    <div>
        <div class="d-flex justify-space-between align-center mb-4">
            <h3 class="text-h6">Assets</h3>
            <v-btn color="primary" size="small" @click="openDialog()">
                <v-icon left>mdi-plus</v-icon>
                Add Asset
            </v-btn>
        </div>

        <v-data-table :headers="headers" :items="assets" :loading="loading" density="compact">
            <template v-slot:item.value="{ item }">
                ৳{{ Number(item.value).toLocaleString() }}
            </template>
            <template v-slot:item.current_value="{ item }">
                <span :class="{ 'text-warning': item.current_value < item.value }">
                    ৳{{ Number(item.current_value || item.value).toLocaleString() }}
                </span>
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
        <v-dialog v-model="dialog" max-width="500">
            <v-card>
                <v-card-title>{{ editMode ? 'Edit Asset' : 'Add Asset' }}</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="saveAsset">
                        <v-text-field
                            v-model="form.name"
                            label="Asset Name"
                            required
                        ></v-text-field>
                        <v-text-field
                            v-model="form.invoice_no"
                            label="Invoice No."
                        ></v-text-field>
                        <v-text-field
                            v-model="form.value"
                            label="Value"
                            type="number"
                            prefix="৳"
                            required
                        ></v-text-field>
                        <v-text-field
                            v-model.number="form.depreciation_rate"
                            label="Depreciation Rate (%/Year)"
                            type="number"
                            suffix="%"
                        ></v-text-field>
                        <v-text-field
                            v-model="form.purchase_date"
                            label="Purchase Date"
                            type="date"
                        ></v-text-field>
                        <v-textarea
                            v-model="form.description"
                            label="Description"
                            rows="2"
                        ></v-textarea>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="dialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveAsset" :loading="saving">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Confirm -->
        <v-dialog v-model="deleteDialog" max-width="400">
            <v-card>
                <v-card-title>Confirm Delete</v-card-title>
                <v-card-text>Are you sure you want to delete "{{ selectedAsset?.name }}"?</v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" @click="deleteAsset" :loading="deleting">Delete</v-btn>
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

const assets = ref([])
const loading = ref(false)
const dialog = ref(false)
const deleteDialog = ref(false)
const editMode = ref(false)
const selectedAsset = ref(null)
const saving = ref(false)
const deleting = ref(false)

const headers = [
    { title: 'Name', key: 'name' },
    { title: 'Invoice No.', key: 'invoice_no' },
    { title: 'Purchase Value', key: 'value' },
    { title: 'Current Value', key: 'current_value' },
    { title: 'Depreciation', key: 'depreciation_rate' },
    { title: 'Purchase Date', key: 'purchase_date' },
    { title: 'Actions', key: 'actions', sortable: false },
]

const form = reactive({
    name: '',
    invoice_no: '',
    value: '',
    depreciation_rate: 0,
    purchase_date: '',
    description: ''
})

const fetchAssets = async () => {
    loading.value = true
    try {
        const response = await api.get(`/assets?project_id=${props.projectId}`)
        assets.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
    loading.value = false
}

const openDialog = (asset = null) => {
    editMode.value = !!asset
    selectedAsset.value = asset
    if (asset) {
        Object.assign(form, {
            name: asset.name,
            invoice_no: asset.invoice_no || '',
            value: asset.value,
            depreciation_rate: asset.depreciation_rate || 0,
            purchase_date: asset.purchase_date || '',
            description: asset.description || ''
        })
    } else {
        Object.assign(form, {
            name: '',
            invoice_no: '',
            value: '',
            depreciation_rate: 0,
            purchase_date: '',
            description: ''
        })
    }
    dialog.value = true
}

const saveAsset = async () => {
    saving.value = true
    try {
        const data = { ...form, project_id: props.projectId }
        if (editMode.value) {
            await api.put(`/assets/${selectedAsset.value.id}`, data)
        } else {
            await api.post('/assets', data)
        }
        dialog.value = false
        fetchAssets()
    } catch (error) {
        console.error('Error:', error)
    }
    saving.value = false
}

const confirmDelete = (asset) => {
    selectedAsset.value = asset
    deleteDialog.value = true
}

const deleteAsset = async () => {
    deleting.value = true
    try {
        await api.delete(`/assets/${selectedAsset.value.id}`)
        deleteDialog.value = false
        fetchAssets()
    } catch (error) {
        console.error('Error:', error)
    }
    deleting.value = false
}

onMounted(() => {
    fetchAssets()
})
</script>
