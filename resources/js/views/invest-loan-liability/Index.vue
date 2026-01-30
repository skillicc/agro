<template>
    <div>
        <div class="d-flex flex-wrap justify-space-between align-center mb-4 ga-2">
            <h1 class="text-h5 text-sm-h4">Invest, Loan & Liability</h1>
        </div>

        <v-row>
            <v-col v-for="card in typeCards" :key="card.type" cols="12" sm="6" lg="4">
                <v-card
                    :to="{ name: 'invest-loan-liability-show', params: { type: card.type } }"
                    :color="card.color"
                    variant="tonal"
                >
                    <v-card-title>
                        <v-icon class="mr-2" :color="card.color">{{ card.icon }}</v-icon>
                        {{ card.label }}
                    </v-card-title>
                    <v-card-text>
                        <v-row dense class="mt-2">
                            <v-col cols="6" class="text-center">
                                <div class="text-h6">{{ getTypeCount(card.type) }}</div>
                                <div class="text-caption">Records</div>
                            </v-col>
                            <v-col cols="6" class="text-center">
                                <div class="text-h6">৳{{ formatNumber(summary[card.type] || 0) }}</div>
                                <div class="text-caption">Total Amount</div>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <v-alert v-if="!loading && items.length === 0" type="info" class="mt-4">
            No records found.
        </v-alert>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'

const items = ref([])
const summary = ref({})
const loading = ref(false)

const typeCards = [
    { type: 'partner', label: 'Partner', color: 'primary', icon: 'mdi-handshake' },
    { type: 'shareholder', label: 'Shareholder', color: 'info', icon: 'mdi-account-group' },
    { type: 'investor', label: 'Investor', color: 'deep-purple', icon: 'mdi-cash-multiple' },
    { type: 'loan', label: 'Loan', color: 'warning', icon: 'mdi-bank' },
    { type: 'others', label: 'Others', color: 'grey', icon: 'mdi-dots-horizontal' },
]

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD')

const getTypeCount = (type) => {
    if (type === 'others') {
        return items.value.filter(item => !['partner', 'shareholder', 'investor', 'loan'].includes(item.type)).length
    }
    return items.value.filter(item => item.type === type).length
}

const fetchItems = async () => {
    loading.value = true
    try {
        const response = await api.get('/invest-loan-liabilities')
        items.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
    loading.value = false
}

const fetchSummary = async () => {
    try {
        const response = await api.get('/invest-loan-liabilities-summary')
        summary.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
}

onMounted(() => {
    fetchItems()
    fetchSummary()
})
</script>
