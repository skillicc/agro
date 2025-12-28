<template>
    <div>
        <div class="d-flex justify-space-between align-center mb-4">
            <h1 class="text-h4">Projects</h1>
            <div class="d-flex align-center ga-2">
                <v-switch
                    v-model="showInactive"
                    label="Show Inactive"
                    color="primary"
                    density="compact"
                    hide-details
                ></v-switch>
                <v-btn color="primary" @click="openDialog()">
                    <v-icon left>mdi-plus</v-icon>
                    Add Project
                </v-btn>
            </div>
        </div>

        <v-row>
            <v-col v-for="project in filteredProjects" :key="project.id" cols="12" sm="6" lg="4">
                <v-card :to="{ name: 'project-show', params: { id: project.id } }" :class="{ 'opacity-60': !project.is_active }">
                    <v-card-title>
                        <v-icon class="mr-2" :color="getProjectColor(project.type)">
                            {{ getProjectIcon(project.type) }}
                        </v-icon>
                        {{ project.name }}
                        <v-chip v-if="!project.is_active" size="x-small" color="error" class="ml-2">Inactive</v-chip>
                    </v-card-title>
                    <v-card-subtitle>
                        <v-chip size="small" :color="getProjectColor(project.type)">
                            {{ getTypeLabel(project.type) }}
                        </v-chip>
                        <span class="ml-2">{{ project.location }}</span>
                    </v-card-subtitle>
                    <v-card-text>
                        <p class="text-truncate">{{ project.description }}</p>
                        <v-row dense class="mt-2">
                            <v-col cols="4" class="text-center">
                                <div class="text-h6">{{ project.expenses_count || 0 }}</div>
                                <div class="text-caption">Expenses</div>
                            </v-col>
                            <v-col cols="4" class="text-center">
                                <div class="text-h6">{{ project.purchases_count || 0 }}</div>
                                <div class="text-caption">Purchases</div>
                            </v-col>
                            <v-col cols="4" class="text-center">
                                <div class="text-h6">{{ project.sales_count || 0 }}</div>
                                <div class="text-caption">Sales</div>
                            </v-col>
                        </v-row>
                    </v-card-text>
                    <v-card-actions>
                        <v-btn
                            size="small"
                            :color="project.is_active ? 'warning' : 'success'"
                            variant="tonal"
                            @click.prevent="toggleStatus(project)"
                            :loading="toggling === project.id"
                        >
                            <v-icon left>{{ project.is_active ? 'mdi-close-circle' : 'mdi-check-circle' }}</v-icon>
                            {{ project.is_active ? 'Close' : 'Open' }}
                        </v-btn>
                        <v-spacer></v-spacer>
                        <v-btn icon size="small" @click.prevent="openDialog(project)">
                            <v-icon>mdi-pencil</v-icon>
                        </v-btn>
                        <v-btn icon size="small" color="error" @click.prevent="confirmDelete(project)">
                            <v-icon>mdi-delete</v-icon>
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>

        <v-alert v-if="filteredProjects.length === 0" type="info" class="mt-4">
            No projects found. {{ showInactive ? '' : 'Try enabling "Show Inactive" to see closed projects.' }}
        </v-alert>

        <!-- Add/Edit Dialog -->
        <v-dialog v-model="dialog" max-width="500">
            <v-card>
                <v-card-title>{{ editMode ? 'Edit Project' : 'Add Project' }}</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="saveProject">
                        <v-text-field
                            v-model="form.name"
                            label="Project Name"
                            required
                        ></v-text-field>
                        <v-select
                            v-model="form.type"
                            :items="projectTypes"
                            item-title="title"
                            item-value="value"
                            label="Type"
                            required
                        ></v-select>
                        <v-text-field
                            v-model="form.location"
                            label="Location"
                        ></v-text-field>
                        <v-textarea
                            v-model="form.description"
                            label="Description"
                            rows="3"
                        ></v-textarea>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="dialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveProject" :loading="saving">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Confirm Dialog -->
        <v-dialog v-model="deleteDialog" max-width="400">
            <v-card>
                <v-card-title class="text-error">
                    <v-icon left color="error">mdi-alert</v-icon>
                    Confirm Delete
                </v-card-title>
                <v-card-text>
                    <v-alert type="warning" variant="tonal" class="mb-4">
                        Are you sure you want to delete "{{ selectedProject?.name }}"? This action cannot be undone.
                    </v-alert>
                    <v-text-field
                        v-model="adminPassword"
                        label="Admin Password"
                        type="password"
                        prepend-inner-icon="mdi-lock"
                        required
                        :error-messages="deleteError"
                        @keyup.enter="deleteProject"
                    ></v-text-field>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="closeDeleteDialog">Cancel</v-btn>
                    <v-btn color="error" @click="deleteProject" :loading="deleting" :disabled="!adminPassword">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import api from '../../services/api'

const projects = ref([])
const dialog = ref(false)
const deleteDialog = ref(false)
const editMode = ref(false)
const selectedProject = ref(null)
const saving = ref(false)
const deleting = ref(false)
const toggling = ref(null)
const showInactive = ref(false)
const adminPassword = ref('')
const deleteError = ref('')

const projectTypes = [
    { title: 'Field', value: 'field' },
    { title: 'Nursery', value: 'nursery' },
    { title: 'Shop', value: 'shop' },
    { title: 'Administration', value: 'administration' },
    { title: 'Central', value: 'central' },
]

const form = reactive({
    name: '',
    type: 'field',
    location: '',
    description: '',
})

const filteredProjects = computed(() => {
    if (showInactive.value) {
        return projects.value
    }
    return projects.value.filter(p => p.is_active)
})

const getProjectIcon = (type) => {
    const icons = {
        field: 'mdi-sprout',
        nursery: 'mdi-flower',
        shop: 'mdi-store',
        administration: 'mdi-office-building',
        central: 'mdi-domain',
    }
    return icons[type] || 'mdi-folder'
}

const getProjectColor = (type) => {
    const colors = {
        field: 'green',
        nursery: 'teal',
        shop: 'blue',
        administration: 'primary',
        central: 'warning',
    }
    return colors[type] || 'grey'
}

const getTypeLabel = (type) => {
    const labels = {
        field: 'Field',
        nursery: 'Nursery',
        shop: 'Shop',
        administration: 'Administration',
        central: 'Central',
    }
    return labels[type] || type
}

const fetchProjects = async () => {
    try {
        const response = await api.get('/projects')
        projects.value = response.data
    } catch (error) {
        console.error('Error fetching projects:', error)
    }
}

const openDialog = (project = null) => {
    editMode.value = !!project
    selectedProject.value = project
    if (project) {
        form.name = project.name
        form.type = project.type
        form.location = project.location
        form.description = project.description
    } else {
        form.name = ''
        form.type = 'field'
        form.location = ''
        form.description = ''
    }
    dialog.value = true
}

const saveProject = async () => {
    saving.value = true
    try {
        if (editMode.value) {
            await api.put(`/projects/${selectedProject.value.id}`, form)
        } else {
            await api.post('/projects', form)
        }
        dialog.value = false
        fetchProjects()
    } catch (error) {
        console.error('Error saving project:', error)
    }
    saving.value = false
}

const confirmDelete = (project) => {
    selectedProject.value = project
    adminPassword.value = ''
    deleteError.value = ''
    deleteDialog.value = true
}

const closeDeleteDialog = () => {
    deleteDialog.value = false
    adminPassword.value = ''
    deleteError.value = ''
}

const deleteProject = async () => {
    if (!adminPassword.value) {
        deleteError.value = 'Admin password is required'
        return
    }

    deleting.value = true
    deleteError.value = ''
    try {
        await api.delete(`/projects/${selectedProject.value.id}`, {
            data: { admin_password: adminPassword.value }
        })
        deleteDialog.value = false
        adminPassword.value = ''
        fetchProjects()
    } catch (error) {
        console.error('Error deleting project:', error)
        if (error.response?.status === 403) {
            deleteError.value = 'Invalid admin password'
        } else {
            deleteError.value = error.response?.data?.message || 'Failed to delete project'
        }
    }
    deleting.value = false
}

const toggleStatus = async (project) => {
    toggling.value = project.id
    try {
        await api.post(`/projects/${project.id}/toggle-status`)
        fetchProjects()
    } catch (error) {
        console.error('Error toggling project status:', error)
    }
    toggling.value = null
}

onMounted(() => {
    fetchProjects()
})
</script>

<style scoped>
.opacity-60 {
    opacity: 0.6;
}
</style>
