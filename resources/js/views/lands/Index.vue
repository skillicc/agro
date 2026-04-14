<template>
    <div>
        <div class="d-flex flex-wrap justify-space-between align-center mb-4 ga-2">
            <h1 class="text-h5 text-sm-h4">Lands</h1>
            <div class="d-flex align-center ga-2">
                <v-switch
                    v-model="showInactive"
                    label="Show Inactive"
                    color="primary"
                    density="compact"
                    hide-details
                    class="flex-shrink-0"
                ></v-switch>
                <v-btn color="primary" @click="openDialog()" :size="$vuetify.display.xs ? 'small' : 'default'">
                    <v-icon left>mdi-plus</v-icon>
                    <span class="d-none d-sm-inline">Add Land</span>
                    <span class="d-inline d-sm-none">Add</span>
                </v-btn>
            </div>
        </div>

        <v-data-table
            :headers="headers"
            :items="filteredLands"
            :loading="loading"
            class="elevation-1"
            :items-per-page="20"
        >
            <template v-slot:item.sl="{ index }">
                {{ index + 1 }}
            </template>
            <template v-slot:item.is_active="{ item }">
                <v-chip :color="item.is_active ? 'success' : 'error'" size="small">
                    {{ item.is_active ? 'Active' : 'Inactive' }}
                </v-chip>
            </template>
            <template v-slot:item.current_project="{ item }">
                <span v-if="item.projects?.length">
                    <v-chip size="small" color="primary" :to="{ name: 'project-show', params: { id: item.projects[0].id } }">
                        {{ item.projects[0].name }}
                    </v-chip>
                </span>
                <span v-else class="text-medium-emphasis">—</span>
            </template>
            <template v-slot:item.size="{ item }">
                {{ item.size ? `${item.size} ${item.unit}` : '—' }}
            </template>
            <template v-slot:item.actions="{ item }">
                <v-btn icon size="small" variant="text" @click="openDialog(item)">
                    <v-icon>mdi-pencil</v-icon>
                </v-btn>
            </template>
        </v-data-table>

        <!-- Add / Edit Dialog -->
        <v-dialog v-model="dialog" max-width="480">
            <v-card>
                <v-card-title>{{ editingLand ? 'Edit Land' : 'Add Land' }}</v-card-title>
                <v-card-text>
                    <v-form ref="formRef">
                        <v-text-field v-model="form.name" label="Name" required></v-text-field>
                        <v-text-field v-model="form.code" label="Code"></v-text-field>
                        <v-text-field v-model="form.location" label="Location"></v-text-field>
                        <v-row dense>
                            <v-col cols="8">
                                <v-text-field v-model.number="form.size" label="Size" type="number"></v-text-field>
                            </v-col>
                            <v-col cols="4">
                                <v-select v-model="form.unit" :items="['acre', 'bigha', 'hectare', 'katha', 'decimal']" label="Unit"></v-select>
                            </v-col>
                        </v-row>
                        <v-textarea v-model="form.notes" label="Notes" rows="2"></v-textarea>
                        <v-switch v-model="form.is_active" label="Active" color="primary"></v-switch>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="dialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="save" :loading="saving">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../services/api'

const lands = ref([])
const loading = ref(false)
const showInactive = ref(false)
const dialog = ref(false)
const saving = ref(false)
const editingLand = ref(null)
const formRef = ref(null)

const defaultForm = () => ({ name: '', code: '', location: '', size: null, unit: 'acre', notes: '', is_active: true })
const form = ref(defaultForm())

const headers = [
    { title: 'SL', key: 'sl', sortable: false, width: '60px' },
    { title: 'Name', key: 'name' },
    { title: 'Code', key: 'code' },
    { title: 'Location', key: 'location' },
    { title: 'Size', key: 'size' },
    { title: 'Latest Project', key: 'current_project', sortable: false },
    { title: 'Status', key: 'is_active' },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' },
]

const filteredLands = computed(() =>
    showInactive.value ? lands.value : lands.value.filter(l => l.is_active)
)

async function fetchLands() {
    loading.value = true
    try {
        const { data } = await api.get('/lands', { params: { include_inactive: true } })
        lands.value = Array.isArray(data) ? data : (data?.data ?? [])
    } finally {
        loading.value = false
    }
}

function openDialog(land = null) {
    editingLand.value = land
    form.value = land
        ? { name: land.name, code: land.code ?? '', location: land.location ?? '', size: land.size, unit: land.unit ?? 'acre', notes: land.notes ?? '', is_active: land.is_active }
        : defaultForm()
    dialog.value = true
}

async function save() {
    saving.value = true
    try {
        if (editingLand.value) {
            const { data } = await api.put(`/lands/${editingLand.value.id}`, form.value)
            const idx = lands.value.findIndex(l => l.id === editingLand.value.id)
            if (idx !== -1) lands.value[idx] = { ...lands.value[idx], ...data }
        } else {
            const { data } = await api.post('/lands', form.value)
            lands.value.push(data)
        }
        dialog.value = false
    } finally {
        saving.value = false
    }
}

onMounted(fetchLands)
</script>
