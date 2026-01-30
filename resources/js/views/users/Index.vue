<template>
    <div>
        <div class="d-flex flex-wrap justify-space-between align-center mb-4 ga-2">
            <h1 class="text-h5 text-sm-h4">User Management</h1>
            <v-btn color="primary" :size="$vuetify.display.xs ? 'small' : 'default'" @click="openDialog()">
                <v-icon left>mdi-plus</v-icon>
                Add User
            </v-btn>
        </div>

        <v-card>
            <v-card-text>
                <v-data-table :headers="headers" :items="users" :loading="loading">
                    <template v-slot:item.role="{ item }">
                        <v-chip :color="getRoleColor(item.role)" size="small">
                            {{ item.role }}
                        </v-chip>
                    </template>
                    <template v-slot:item.is_active="{ item }">
                        <v-switch v-model="item.is_active" color="success" hide-details @update:model-value="toggleStatus(item)"></v-switch>
                    </template>
                    <template v-slot:item.projects="{ item }">
                        <v-chip v-for="project in item.projects" :key="project.id" size="small" class="mr-1">
                            {{ project.name }}
                        </v-chip>
                    </template>
                    <template v-slot:item.actions="{ item }">
                        <v-btn icon size="small" @click="openProjectDialog(item)">
                            <v-icon>mdi-folder-account</v-icon>
                        </v-btn>
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

        <!-- Add/Edit Dialog -->
        <v-dialog v-model="dialog" :max-width="$vuetify.display.xs ? '100%' : '500'" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title>{{ editMode ? 'Edit User' : 'Add User' }}</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="saveUser">
                        <v-text-field v-model="form.name" label="Name" required></v-text-field>
                        <v-text-field v-model="form.email" label="Email" type="email" required></v-text-field>
                        <v-text-field v-model="form.password" label="Password" type="password" :required="!editMode" :hint="editMode ? 'Leave empty to keep current' : ''"></v-text-field>
                        <v-select v-model="form.role" :items="roles" label="Role" required></v-select>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="dialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveUser" :loading="saving">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Project Assignment Dialog -->
        <v-dialog v-model="projectDialog" :max-width="$vuetify.display.xs ? '100%' : '600'" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title>Assign Projects - {{ selectedUser?.name }}</v-card-title>
                <v-card-text>
                    <v-table>
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Permission</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="project in projects" :key="project.id">
                                <td>{{ project.name }}</td>
                                <td>
                                    <v-select
                                        v-model="projectAssignments[project.id]"
                                        :items="permissions"
                                        item-title="text"
                                        item-value="value"
                                        density="compact"
                                        hide-details
                                    ></v-select>
                                </td>
                                <td>
                                    <v-checkbox
                                        v-model="selectedProjects"
                                        :value="project.id"
                                        hide-details
                                    ></v-checkbox>
                                </td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="projectDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="assignProjects" :loading="assigning">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Confirm -->
        <v-dialog v-model="deleteDialog" max-width="400">
            <v-card>
                <v-card-title>Confirm Delete</v-card-title>
                <v-card-text>Are you sure you want to delete "{{ selectedUser?.name }}"?</v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" @click="deleteUser" :loading="deleting">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '../../services/api'

const users = ref([])
const projects = ref([])
const loading = ref(false)
const dialog = ref(false)
const projectDialog = ref(false)
const deleteDialog = ref(false)
const editMode = ref(false)
const selectedUser = ref(null)
const saving = ref(false)
const deleting = ref(false)
const assigning = ref(false)

const roles = ['admin', 'manager', 'user', 'viewer']
const permissions = [
    { text: 'Full Access', value: 'full' },
    { text: 'Read/Write', value: 'read_write' },
    { text: 'Read Only', value: 'read_only' },
]

const headers = [
    { title: 'Name', key: 'name' },
    { title: 'Email', key: 'email' },
    { title: 'Role', key: 'role' },
    { title: 'Projects', key: 'projects' },
    { title: 'Active', key: 'is_active' },
    { title: 'Actions', key: 'actions', sortable: false },
]

const form = reactive({ name: '', email: '', password: '', role: 'user' })
const projectAssignments = reactive({})
const selectedProjects = ref([])

const getRoleColor = (role) => {
    const colors = { admin: 'error', manager: 'warning', user: 'primary', viewer: 'grey' }
    return colors[role] || 'grey'
}

const fetchUsers = async () => {
    loading.value = true
    try {
        const response = await api.get('/users')
        users.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
    loading.value = false
}

const fetchProjects = async () => {
    try {
        const response = await api.get('/projects')
        projects.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
}

const openDialog = (user = null) => {
    editMode.value = !!user
    selectedUser.value = user
    if (user) {
        Object.assign(form, { name: user.name, email: user.email, password: '', role: user.role })
    } else {
        Object.assign(form, { name: '', email: '', password: '', role: 'user' })
    }
    dialog.value = true
}

const saveUser = async () => {
    saving.value = true
    try {
        if (editMode.value) {
            await api.put(`/users/${selectedUser.value.id}`, form)
        } else {
            await api.post('/users', form)
        }
        dialog.value = false
        fetchUsers()
    } catch (error) {
        console.error('Error:', error)
    }
    saving.value = false
}

const openProjectDialog = (user) => {
    selectedUser.value = user
    selectedProjects.value = user.projects.map(p => p.id)
    user.projects.forEach(p => {
        projectAssignments[p.id] = p.pivot?.permission || 'read_only'
    })
    projectDialog.value = true
}

const assignProjects = async () => {
    assigning.value = true
    try {
        const projectsData = selectedProjects.value.map(id => ({
            project_id: id,
            permission: projectAssignments[id] || 'read_only'
        }))
        await api.post(`/users/${selectedUser.value.id}/assign-projects`, { projects: projectsData })
        projectDialog.value = false
        fetchUsers()
    } catch (error) {
        console.error('Error:', error)
    }
    assigning.value = false
}

const toggleStatus = async (user) => {
    try {
        await api.post(`/users/${user.id}/toggle-status`)
    } catch (error) {
        console.error('Error:', error)
        user.is_active = !user.is_active
    }
}

const confirmDelete = (user) => {
    selectedUser.value = user
    deleteDialog.value = true
}

const deleteUser = async () => {
    deleting.value = true
    try {
        await api.delete(`/users/${selectedUser.value.id}`)
        deleteDialog.value = false
        fetchUsers()
    } catch (error) {
        console.error('Error:', error)
    }
    deleting.value = false
}

onMounted(() => {
    fetchUsers()
    fetchProjects()
})
</script>
