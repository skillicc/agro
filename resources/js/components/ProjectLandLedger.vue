<template>
    <div>
        <div class="d-flex flex-wrap justify-space-between align-center mb-4 ga-2">
            <div>
                <h3 class="text-h6">Land-wise Ledger</h3>
                <div class="text-caption text-medium-emphasis">Track crop opening/closing dates and expense totals per land.</div>
            </div>
            <v-btn color="primary" size="small" @click="openCycleDialog()" :disabled="!ledger.lands.length">
                <v-icon left>mdi-sprout</v-icon>
                Start Crop Cycle
            </v-btn>
        </div>

        <v-row class="mb-2">
            <v-col cols="6" md="3">
                <v-card variant="tonal" color="primary">
                    <v-card-text class="text-center">
                        <div class="text-h6">{{ ledger.totals.land_count || 0 }}</div>
                        <div class="text-caption">Assigned Lands</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" md="3">
                <v-card variant="tonal" color="success">
                    <v-card-text class="text-center">
                        <div class="text-h6">{{ ledger.totals.active_cultivations || 0 }}</div>
                        <div class="text-caption">Active Crops</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" md="3">
                <v-card variant="tonal" color="warning">
                    <v-card-text class="text-center">
                        <div class="text-h6">৳{{ formatNumber(ledger.totals.total_expenses) }}</div>
                        <div class="text-caption">Land Expenses</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" md="3">
                <v-card variant="tonal" color="info">
                    <v-card-text class="text-center">
                        <div class="text-h6">৳{{ formatNumber(ledger.totals.unassigned_expenses) }}</div>
                        <div class="text-caption">Unassigned Expenses</div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <v-alert v-if="!ledger.lands.length" type="info" variant="tonal" class="mt-4">
            No land is assigned to this project yet. Add lands from the project edit form first.
        </v-alert>

        <v-row v-else>
            <v-col v-for="land in ledger.lands" :key="land.id" cols="12" lg="6">
                <v-card class="h-100">
                    <v-card-title class="d-flex flex-wrap align-center ga-2">
                        <span>{{ land.name }}</span>
                        <v-chip size="small" :color="land.current_cultivation ? 'success' : 'grey'">
                            {{ land.current_cultivation ? 'Active' : 'Available' }}
                        </v-chip>
                    </v-card-title>
                    <v-card-subtitle>
                        {{ land.location || 'No location' }}
                        <span v-if="land.size">• {{ land.size }} {{ land.unit }}</span>
                    </v-card-subtitle>
                    <v-card-text>
                        <div class="mb-3">
                            <div class="text-subtitle-2">Current Crop</div>
                            <div v-if="land.current_cultivation">
                                <strong>{{ land.current_cultivation.crop_name }}</strong>
                                <div class="text-caption">Opened: {{ formatDate(land.current_cultivation.opening_date) }}</div>
                                <div class="text-caption" v-if="land.current_cultivation.expected_closing_date">
                                    Expected Close: {{ formatDate(land.current_cultivation.expected_closing_date) }}
                                </div>
                            </div>
                            <div v-else class="text-caption text-medium-emphasis">No active crop cycle.</div>
                        </div>

                        <div class="d-flex flex-wrap ga-4 mb-3 text-body-2">
                            <div><strong>Expenses:</strong> ৳{{ formatNumber(land.total_expenses) }}</div>
                            <div><strong>Entries:</strong> {{ land.expense_count || 0 }}</div>
                        </div>

                        <div class="d-flex flex-wrap ga-2 mb-3">
                            <v-btn size="small" color="primary" variant="tonal" @click="openCycleDialog(land)">
                                <v-icon left>mdi-plus</v-icon>
                                Start Crop
                            </v-btn>
                            <v-btn
                                v-if="land.current_cultivation"
                                size="small"
                                color="success"
                                variant="tonal"
                                :loading="closingId === land.current_cultivation.id"
                                @click="closeCycle(land.current_cultivation.id)"
                            >
                                <v-icon left>mdi-check-circle</v-icon>
                                Close Crop
                            </v-btn>
                        </div>

                        <div class="mb-2 text-subtitle-2">Crop History</div>
                        <v-table density="compact" class="mb-3">
                            <thead>
                                <tr>
                                    <th>Crop</th>
                                    <th>Open</th>
                                    <th>Close</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!land.cultivations?.length">
                                    <td colspan="4" class="text-medium-emphasis">No crop history yet.</td>
                                </tr>
                                <tr v-for="cycle in land.cultivations" :key="cycle.id">
                                    <td>{{ cycle.crop_name }}</td>
                                    <td>{{ formatDate(cycle.opening_date) }}</td>
                                    <td>{{ formatDate(cycle.closing_date) }}</td>
                                    <td>
                                        <v-chip size="x-small" :color="cycle.status === 'active' ? 'success' : 'grey'">
                                            {{ cycle.status }}
                                        </v-chip>
                                    </td>
                                </tr>
                            </tbody>
                        </v-table>

                        <div class="mb-2 text-subtitle-2">Recent Expenses</div>
                        <v-table density="compact">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!land.recent_expenses?.length">
                                    <td colspan="3" class="text-medium-emphasis">No land expense added yet.</td>
                                </tr>
                                <tr v-for="expense in land.recent_expenses" :key="expense.id">
                                    <td>{{ formatDate(expense.date) }}</td>
                                    <td>{{ expense.category?.name || '-' }}</td>
                                    <td>৳{{ formatNumber(expense.amount) }}</td>
                                </tr>
                            </tbody>
                        </v-table>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <v-dialog v-model="cycleDialog" :max-width="$vuetify.display.xs ? '100%' : '500'" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title>Start Crop Cycle</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="saveCycle">
                        <v-select
                            v-model="cycleForm.land_id"
                            :items="ledger.lands"
                            item-title="name"
                            item-value="id"
                            label="Land"
                            required
                        ></v-select>
                        <v-text-field v-model="cycleForm.crop_name" label="Crop / Activity Name" required></v-text-field>
                        <v-text-field v-model="cycleForm.opening_date" label="Opening Date" type="date" required></v-text-field>
                        <v-text-field v-model="cycleForm.expected_closing_date" label="Expected Closing Date" type="date"></v-text-field>
                        <v-textarea v-model="cycleForm.notes" label="Notes" rows="2"></v-textarea>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="cycleDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveCycle" :loading="savingCycle">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import api from '../services/api'

const props = defineProps({
    projectId: { type: [Number, String], required: true }
})

const ledger = ref({
    lands: [],
    totals: { land_count: 0, active_cultivations: 0, total_expenses: 0, unassigned_expenses: 0 },
})
const cycleDialog = ref(false)
const savingCycle = ref(false)
const closingId = ref(null)
const cycleForm = reactive({
    land_id: null,
    crop_name: '',
    opening_date: new Date().toISOString().split('T')[0],
    expected_closing_date: '',
    notes: '',
})

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD')
const formatDate = (value) => value || '-'

const fetchLedger = async () => {
    try {
        const response = await api.get(`/projects/${props.projectId}/land-ledger`)
        ledger.value = response.data
    } catch (error) {
        console.error('Error fetching land ledger:', error)
    }
}

const openCycleDialog = (land = null) => {
    cycleForm.land_id = land?.id || ledger.value.lands[0]?.id || null
    cycleForm.crop_name = ''
    cycleForm.opening_date = new Date().toISOString().split('T')[0]
    cycleForm.expected_closing_date = ''
    cycleForm.notes = ''
    cycleDialog.value = true
}

const saveCycle = async () => {
    savingCycle.value = true
    try {
        await api.post(`/projects/${props.projectId}/land-cultivations`, cycleForm)
        cycleDialog.value = false
        await fetchLedger()
    } catch (error) {
        console.error('Error saving land cultivation:', error)
        alert(error.response?.data?.message || 'Failed to start crop cycle')
    }
    savingCycle.value = false
}

const closeCycle = async (cycleId) => {
    closingId.value = cycleId
    try {
        await api.put(`/land-cultivations/${cycleId}`, {
            status: 'closed',
            closing_date: new Date().toISOString().split('T')[0],
        })
        await fetchLedger()
    } catch (error) {
        console.error('Error closing crop cycle:', error)
        alert(error.response?.data?.message || 'Failed to close crop cycle')
    }
    closingId.value = null
}

onMounted(() => {
    fetchLedger()
})
</script>
