<template>
    <div>
        <div class="d-flex flex-wrap justify-space-between align-center mb-4 ga-2">
            <h1 class="text-h5 text-sm-h4">Invest, Loan & Liability</h1>
            <v-btn color="primary" :size="$vuetify.display.xs ? 'small' : 'default'" @click="openDialog()">
                <v-icon left>mdi-plus</v-icon>
                Add New
            </v-btn>
        </div>

        <!-- Summary Cards -->
        <v-row class="mb-4">
            <v-col cols="6" sm="4" lg="2" v-for="card in summaryCards" :key="card.type">
                <v-card :color="card.color" variant="tonal" style="cursor: pointer;" @click="scrollToSection(card.type)">
                    <v-card-text class="text-center pa-3">
                        <v-icon :icon="card.icon" size="28" class="mb-1"></v-icon>
                        <div class="text-caption">{{ card.label }}</div>
                        <div class="text-subtitle-1 font-weight-bold">৳{{ formatNumber(summary[card.type] || 0) }}</div>
                        <div class="text-caption">({{ getTypeCount(card.type) }})</div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <!-- Search -->
        <v-card class="mb-4">
            <v-card-text class="pa-3">
                <v-row dense align="center">
                    <v-col cols="12" sm="6" md="4">
                        <v-text-field
                            v-model="searchQuery"
                            label="Search by Name"
                            prepend-inner-icon="mdi-magnify"
                            clearable
                            hide-details
                            density="compact"
                        ></v-text-field>
                    </v-col>
                </v-row>
            </v-card-text>
        </v-card>

        <!-- Sections Grid -->
        <template v-for="section in sections" :key="section.key">
            <div v-if="section.items.length" :id="'section-' + section.key" class="mb-6">
                <h2 class="text-h6 mb-3 d-flex align-center">
                    <v-icon :color="section.color" class="mr-2">{{ section.icon }}</v-icon>
                    {{ section.label }} ({{ section.items.length }})
                </h2>
                <v-row>
                    <v-col v-for="item in section.items" :key="item.id" cols="12" sm="6" lg="4" xl="3">
                        <v-card class="border-left-thick" :style="{ borderLeftColor: getTypeColorHex(item.type) }">
                            <v-card-title class="d-flex align-center">
                                <v-icon :color="getTypeColor(item.type)" class="mr-2">{{ getTypeIcon(item.type) }}</v-icon>
                                <span class="text-truncate">{{ item.name }}</span>
                                <v-spacer></v-spacer>
                                <v-chip :color="getStatusColor(item.status)" size="x-small">
                                    {{ item.status }}
                                </v-chip>
                            </v-card-title>
                            <v-card-subtitle>
                                <v-chip :color="getTypeColor(item.type)" size="small" variant="tonal" class="mr-1">
                                    {{ getTypeLabel(item.type) }}
                                </v-chip>
                                <span v-if="item.phone" class="text-caption">
                                    <v-icon size="x-small">mdi-phone</v-icon> {{ item.phone }}
                                </span>
                            </v-card-subtitle>
                            <v-card-text>
                                <v-row dense>
                                    <v-col cols="6">
                                        <div class="text-caption text-grey">Amount</div>
                                        <div class="text-subtitle-1 font-weight-bold">৳{{ formatNumber(item.amount) }}</div>
                                    </v-col>
                                    <v-col cols="6">
                                        <div class="text-caption text-grey">Date</div>
                                        <div class="text-subtitle-2">{{ formatDate(item.date) }}</div>
                                    </v-col>
                                </v-row>
                                <!-- Investor specific fields -->
                                <v-row dense class="mt-2" v-if="item.type === 'investor'">
                                    <v-col cols="6" v-if="item.invest_period">
                                        <div class="text-caption text-grey">Invest Period</div>
                                        <div class="text-subtitle-2">{{ item.invest_period }} Months</div>
                                    </v-col>
                                    <v-col cols="6" v-if="item.profit_rate">
                                        <div class="text-caption text-grey">Expected Profit Rate</div>
                                        <div class="text-subtitle-2">{{ item.profit_rate }}%</div>
                                    </v-col>
                                </v-row>
                                <!-- Share Value - for shareholder/partner -->
                                <v-row dense class="mt-2" v-if="['shareholder', 'partner'].includes(item.type) && item.share_value">
                                    <v-col cols="6">
                                        <div class="text-caption text-grey">Share Value</div>
                                        <div class="text-subtitle-2">৳{{ formatNumber(item.share_value) }}</div>
                                    </v-col>
                                </v-row>
                                <!-- Honorarium - only for partner -->
                                <v-row dense class="mt-2" v-if="item.type === 'partner' && item.honorarium">
                                    <v-col cols="6">
                                        <div class="text-caption text-grey">Honorarium ({{ item.honorarium_type }})</div>
                                        <div class="text-subtitle-2">৳{{ formatNumber(item.honorarium) }}</div>
                                    </v-col>
                                </v-row>
                                <!-- Share Paid & Profit Withdrawn (summary for shareholder/investor) -->
                                <v-row dense class="mt-2" v-if="['shareholder', 'investor'].includes(item.type)">
                                    <v-col cols="6" v-if="item.total_share_paid">
                                        <div class="text-caption text-grey">Total Share Paid</div>
                                        <div class="text-subtitle-2 text-success">৳{{ formatNumber(item.total_share_paid) }}</div>
                                    </v-col>
                                    <v-col cols="6" v-if="item.total_profit_withdrawn">
                                        <div class="text-caption text-grey">Profit Withdrawn</div>
                                        <div class="text-subtitle-2 text-warning">৳{{ formatNumber(item.total_profit_withdrawn) }}</div>
                                    </v-col>
                                </v-row>
                                <!-- Share Payment Installments - for partner -->
                                <div v-if="item.type === 'partner' && getSharePayments(item).length" class="mt-3">
                                    <div class="text-caption text-grey font-weight-bold mb-1">Share Payments</div>
                                    <div v-for="sp in getSharePayments(item)" :key="sp.id" class="d-flex justify-space-between text-body-2">
                                        <span>{{ formatDate(sp.date) }}</span>
                                        <span class="font-weight-medium">৳{{ formatNumber(sp.amount) }}</span>
                                    </div>
                                    <v-divider class="my-1"></v-divider>
                                    <div class="d-flex justify-space-between text-body-2 font-weight-bold">
                                        <span>Total</span>
                                        <span class="text-success">৳{{ formatNumber(item.total_share_paid) }}</span>
                                    </div>
                                </div>
                                <!-- Profit Withdrawals year-wise - for partner -->
                                <div v-if="item.type === 'partner' && getProfitWithdrawals(item).length" class="mt-3">
                                    <div class="text-caption text-grey font-weight-bold mb-1">Profit Withdrawn</div>
                                    <div v-for="pw in getProfitWithdrawals(item)" :key="pw.id" class="d-flex justify-space-between text-body-2">
                                        <span>{{ pw.for_year || formatDate(pw.date) }}</span>
                                        <span class="font-weight-medium">৳{{ formatNumber(pw.amount) }}</span>
                                    </div>
                                    <v-divider class="my-1"></v-divider>
                                    <div class="d-flex justify-space-between text-body-2 font-weight-bold">
                                        <span>Total</span>
                                        <span class="text-warning">৳{{ formatNumber(item.total_profit_withdrawn) }}</span>
                                    </div>
                                </div>
                                <!-- Loan specific fields -->
                                <v-row dense class="mt-2" v-if="item.type === 'loan'">
                                    <v-col cols="6">
                                        <div class="text-caption text-grey">Loan Type</div>
                                        <div class="text-subtitle-2">{{ item.loan_type === 'with_profit' ? 'With Profit' : 'Without Profit' }}</div>
                                    </v-col>
                                    <v-col cols="6" v-if="item.loan_type === 'with_profit' && item.contact_person">
                                        <div class="text-caption text-grey">Contact Person</div>
                                        <div class="text-subtitle-2">{{ item.contact_person }}</div>
                                    </v-col>
                                </v-row>
                                <v-row dense class="mt-2" v-if="item.type === 'loan' && item.loan_type === 'with_profit'">
                                    <v-col cols="6">
                                        <div class="text-caption text-grey">Received Amount</div>
                                        <div class="text-subtitle-2">৳{{ formatNumber(item.received_amount) }}</div>
                                    </v-col>
                                </v-row>
                                <v-row dense class="mt-2" v-if="item.type === 'loan' && item.loan_type === 'with_profit'">
                                    <v-col cols="6">
                                        <div class="text-caption text-grey">Total Payable</div>
                                        <div class="text-subtitle-2">৳{{ formatNumber(item.total_payable) }}</div>
                                    </v-col>
                                    <v-col cols="6" v-if="item.receive_date">
                                        <div class="text-caption text-grey">Receive Date</div>
                                        <div class="text-subtitle-2">{{ formatDate(item.receive_date) }}</div>
                                    </v-col>
                                </v-row>
                                <v-row dense class="mt-2" v-if="item.type === 'loan'">
                                    <v-col cols="6">
                                        <div class="text-caption text-grey">Paid Amount</div>
                                        <div class="text-subtitle-2 text-success">৳{{ formatNumber(item.total_loan_paid || 0) }}</div>
                                    </v-col>
                                    <v-col cols="6">
                                        <div class="text-caption text-grey">Rest Amount</div>
                                        <div class="text-subtitle-2 text-error">৳{{ formatNumber(item.loan_rest_amount || 0) }}</div>
                                    </v-col>
                                </v-row>
                                <v-row dense class="mt-2" v-if="item.appoint_date || item.due_date">
                                    <v-col cols="6" v-if="item.appoint_date">
                                        <div class="text-caption text-grey">Appoint Date</div>
                                        <div class="text-subtitle-2">{{ formatDate(item.appoint_date) }}</div>
                                    </v-col>
                                    <v-col cols="6" v-if="item.due_date">
                                        <div class="text-caption text-grey">Due Date</div>
                                        <div class="text-subtitle-2">{{ formatDate(item.due_date) }}</div>
                                    </v-col>
                                </v-row>
                                <div v-if="item.description" class="mt-2 text-caption text-truncate">
                                    {{ item.description }}
                                </div>
                            </v-card-text>
                            <v-card-actions>
                                <!-- Payment buttons for shareholders/partners/investors/loans -->
                                <v-btn
                                    v-if="['shareholder', 'partner', 'investor', 'loan'].includes(item.type)"
                                    size="small"
                                    color="success"
                                    variant="tonal"
                                    @click="openPaymentDialog(item)"
                                    title="Add Payment"
                                >
                                    <v-icon left>mdi-cash-plus</v-icon>
                                    {{ item.type === 'loan' ? 'Pay' : 'Payment' }}
                                </v-btn>
                                <v-spacer></v-spacer>
                                <v-btn icon size="small" @click="viewPayments(item)" title="View Payments" v-if="['shareholder', 'partner', 'investor', 'loan'].includes(item.type)">
                                    <v-icon>mdi-history</v-icon>
                                </v-btn>
                                <v-btn icon size="small" @click="openDialog(item)" title="Edit">
                                    <v-icon>mdi-pencil</v-icon>
                                </v-btn>
                                <v-btn icon size="small" color="error" @click="confirmDelete(item)" title="Delete">
                                    <v-icon>mdi-delete</v-icon>
                                </v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-col>
                </v-row>
            </div>
        </template>

        <v-alert v-if="items.length === 0 && !loading" type="info" class="mt-4">
            No records found.
        </v-alert>

        <!-- Add/Edit Dialog -->
        <v-dialog v-model="dialog" :max-width="$vuetify.display.xs ? '100%' : '600'" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title>{{ editMode ? 'Edit Entry' : 'Add New Entry' }}</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="save">
                        <v-row>
                            <v-col cols="12" lg="6">
                                <v-text-field v-model="form.name" :label="form.type === 'loan' && form.loan_type === 'with_profit' ? 'Organization Name' : 'Name'" required></v-text-field>
                            </v-col>
                            <v-col cols="12" lg="6">
                                <v-select
                                    v-model="form.type"
                                    :items="typeOptions"
                                    label="Type"
                                    required
                                ></v-select>
                            </v-col>
                            <v-col cols="12" lg="6">
                                <v-text-field v-model="form.phone" label="Phone" type="tel"></v-text-field>
                            </v-col>
                            <v-col cols="12" lg="6" v-if="form.type === 'loan' && form.loan_type === 'with_profit'">
                                <v-text-field v-model="form.contact_person" label="Contact Person"></v-text-field>
                            </v-col>
                            <v-col cols="12" lg="6" v-if="form.type !== 'loan'">
                                <v-text-field v-model.number="form.amount" :label="form.type === 'investor' ? 'Invest Amount' : 'Amount'" type="number" prefix="৳" required></v-text-field>
                            </v-col>
                            <v-col cols="12" lg="6" v-if="form.type !== 'loan'">
                                <v-text-field v-model="form.date" label="Date" type="date" required></v-text-field>
                            </v-col>
                            <!-- Loan Type - only for loan -->
                            <v-col cols="12" lg="6" v-if="form.type === 'loan'">
                                <v-select
                                    v-model="form.loan_type"
                                    :items="loanTypeOptions"
                                    label="Loan Type"
                                    required
                                ></v-select>
                            </v-col>
                            <!-- Loan Amount (total_payable for with_profit) - only for loan -->
                            <v-col cols="12" lg="6" v-if="form.type === 'loan'">
                                <v-text-field
                                    v-model.number="form.total_payable"
                                    :label="form.loan_type === 'with_profit' ? 'Loan Amount (Total Payable)' : 'Loan Amount'"
                                    type="number"
                                    prefix="৳"
                                    required
                                ></v-text-field>
                            </v-col>
                            <!-- Received Amount - only for loan with profit -->
                            <v-col cols="12" lg="6" v-if="form.type === 'loan' && form.loan_type === 'with_profit'">
                                <v-text-field v-model.number="form.received_amount" label="Received Amount" type="number" prefix="৳"></v-text-field>
                            </v-col>
                            <!-- Receive Date - only for loan with profit -->
                            <v-col cols="12" lg="6" v-if="form.type === 'loan' && form.loan_type === 'with_profit'">
                                <v-text-field v-model="form.receive_date" label="Receive Date" type="date"></v-text-field>
                            </v-col>
                            <!-- Date for loan -->
                            <v-col cols="12" lg="6" v-if="form.type === 'loan'">
                                <v-text-field v-model="form.date" label="Loan Date" type="date" required></v-text-field>
                            </v-col>
                            <!-- Invest Period - only for investor -->
                            <v-col cols="12" lg="6" v-if="form.type === 'investor'">
                                <v-select
                                    v-model="form.invest_period"
                                    :items="investPeriodOptions"
                                    label="Invest Period"
                                    clearable
                                ></v-select>
                            </v-col>
                            <!-- Profit Rate - for investor -->
                            <v-col cols="12" lg="6" v-if="form.type === 'investor'">
                                <v-text-field v-model.number="form.profit_rate" label="Expected Profit Rate" type="number" suffix="%" hint="Expected yearly profit rate"></v-text-field>
                            </v-col>
                            <!-- Share Value - for shareholder/partner -->
                            <v-col cols="12" lg="6" v-if="['shareholder', 'partner'].includes(form.type)">
                                <v-text-field v-model.number="form.share_value" label="Share Value" type="number" prefix="৳"></v-text-field>
                            </v-col>
                            <!-- Honorarium - only for partner -->
                            <v-col cols="12" lg="6" v-if="form.type === 'partner'">
                                <v-text-field v-model.number="form.honorarium" label="Honorarium" type="number" prefix="৳"></v-text-field>
                            </v-col>
                            <v-col cols="12" lg="6" v-if="form.type === 'partner' && form.honorarium > 0">
                                <v-select
                                    v-model="form.honorarium_type"
                                    :items="honorariumTypes"
                                    label="Honorarium Type"
                                ></v-select>
                            </v-col>
                            <v-col cols="12" lg="6" v-if="form.type !== 'loan'">
                                <v-text-field v-model="form.appoint_date" :label="form.type === 'investor' ? 'Start Date' : 'Appoint Date'" type="date"></v-text-field>
                            </v-col>
                            <v-col cols="12" lg="6" v-if="!['investor', 'loan'].includes(form.type)">
                                <v-text-field v-model="form.due_date" label="Due Date (Optional)" type="date"></v-text-field>
                            </v-col>
                            <v-col cols="12" lg="6" v-if="form.type === 'investor'">
                                <v-text-field v-model="form.due_date" label="Due Date (Auto-calculated)" type="date" readonly hint="Auto-calculated from Start Date + Invest Period"></v-text-field>
                            </v-col>
                            <!-- Due Date for loan -->
                            <v-col cols="12" lg="6" v-if="form.type === 'loan'">
                                <v-text-field v-model="form.due_date" label="Due Date" type="date"></v-text-field>
                            </v-col>
                            <v-col cols="12" lg="6">
                                <v-select
                                    v-model="form.status"
                                    :items="statusOptions"
                                    label="Status"
                                ></v-select>
                            </v-col>
                            <v-col cols="12">
                                <v-textarea v-model="form.description" label="Description (Optional)" rows="2"></v-textarea>
                            </v-col>
                        </v-row>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="dialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="save" :loading="saving">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Payment Dialog -->
        <v-dialog v-model="paymentDialog" :max-width="$vuetify.display.xs ? '100%' : '500'" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title>Add Payment - {{ selectedItem?.name }}</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="savePayment">
                        <v-select
                            v-model="paymentForm.type"
                            :items="getPaymentTypes"
                            label="Payment Type"
                            required
                        ></v-select>
                        <v-text-field
                            v-model.number="paymentForm.amount"
                            label="Amount"
                            type="number"
                            prefix="৳"
                            required
                        ></v-text-field>
                        <v-text-field
                            v-model="paymentForm.date"
                            label="Date"
                            type="date"
                            required
                        ></v-text-field>
                        <v-text-field
                            v-if="paymentForm.type === 'profit_withdrawal'"
                            v-model.number="paymentForm.for_year"
                            label="For Year"
                            type="number"
                            :placeholder="new Date().getFullYear().toString()"
                        ></v-text-field>
                        <v-text-field
                            v-model="paymentForm.note"
                            label="Note (Optional)"
                        ></v-text-field>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="paymentDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="savePayment" :loading="savingPayment">Save Payment</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Payment History Dialog -->
        <v-dialog v-model="paymentsHistoryDialog" :max-width="$vuetify.display.xs ? '100%' : '700'" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title class="d-flex align-center">
                    Payment History - {{ selectedItem?.name }}
                    <v-spacer></v-spacer>
                    <v-btn icon size="small" @click="paymentsHistoryDialog = false">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-card-text>
                    <v-data-table
                        :headers="paymentHeaders"
                        :items="payments"
                        :loading="loadingPayments"
                        density="compact"
                    >
                        <template v-slot:item.type="{ item }">
                            <v-chip :color="getPaymentTypeColor(item.type)" size="small">
                                {{ getPaymentTypeLabel(item.type) }}
                            </v-chip>
                        </template>
                        <template v-slot:item.amount="{ item }">
                            ৳{{ formatNumber(item.amount) }}
                        </template>
                        <template v-slot:item.date="{ item }">
                            {{ formatDate(item.date) }}
                        </template>
                        <template v-slot:item.for_year="{ item }">
                            {{ item.for_year || '-' }}
                        </template>
                        <template v-slot:item.actions="{ item }">
                            <v-btn icon size="x-small" color="error" @click="confirmDeletePayment(item)">
                                <v-icon>mdi-delete</v-icon>
                            </v-btn>
                        </template>
                    </v-data-table>
                </v-card-text>
            </v-card>
        </v-dialog>

        <!-- Delete Confirm Dialog -->
        <v-dialog v-model="deleteDialog" max-width="400" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title>Confirm Delete</v-card-title>
                <v-card-text>Are you sure you want to delete "{{ selectedItem?.name }}"?</v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" @click="deleteItem" :loading="deleting">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Payment Confirm Dialog -->
        <v-dialog v-model="deletePaymentDialog" max-width="400" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title>Confirm Delete Payment</v-card-title>
                <v-card-text>Are you sure you want to delete this payment of ৳{{ formatNumber(selectedPayment?.amount) }}?</v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="deletePaymentDialog = false">Cancel</v-btn>
                    <v-btn color="error" @click="deletePayment" :loading="deletingPayment">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import api from '../../services/api'

const items = ref([])
const summary = ref({})
const loading = ref(false)
const dialog = ref(false)
const deleteDialog = ref(false)
const editMode = ref(false)
const selectedItem = ref(null)
const saving = ref(false)
const deleting = ref(false)
const searchQuery = ref('')

// Payment related
const paymentDialog = ref(false)
const paymentsHistoryDialog = ref(false)
const payments = ref([])
const loadingPayments = ref(false)
const savingPayment = ref(false)
const deletePaymentDialog = ref(false)
const selectedPayment = ref(null)
const deletingPayment = ref(false)

const typeOptions = [
    { title: 'Investor', value: 'investor' },
    { title: 'Partner', value: 'partner' },
    { title: 'Shareholder', value: 'shareholder' },
    { title: 'Investment Day Term', value: 'investment_day_term' },
    { title: 'Loan', value: 'loan' },
    { title: 'Account Payable (From Total)', value: 'account_payable' },
    { title: 'Account Receivable (From Total)', value: 'account_receivable' },
]

const statusOptions = [
    { title: 'Active', value: 'active' },
    { title: 'Completed', value: 'completed' },
    { title: 'Cancelled', value: 'cancelled' },
]

const honorariumTypes = [
    { title: 'Monthly', value: 'monthly' },
    { title: 'Yearly', value: 'yearly' },
]

const investPeriodOptions = [
    { title: '4 Months', value: 4 },
    { title: '6 Months', value: 6 },
    { title: '12 Months (1 Year)', value: 12 },
    { title: '18 Months', value: 18 },
    { title: '24 Months (2 Years)', value: 24 },
]

const loanTypeOptions = [
    { title: 'With Profit (Sharia-based)', value: 'with_profit' },
    { title: 'Without Profit (Personal)', value: 'without_profit' },
]

// Payment types - computed based on selected item type
const getPaymentTypes = computed(() => {
    const itemType = selectedItem.value?.type
    const types = []

    // Share Payment - for shareholder/partner (not investor)
    if (['shareholder', 'partner'].includes(itemType)) {
        types.push({ title: 'Share Payment', value: 'share_payment' })
    }

    // Profit/Loss Withdrawal - for shareholder/partner/investor
    if (['shareholder', 'partner', 'investor'].includes(itemType)) {
        types.push({ title: 'Profit / Loss', value: 'profit_withdrawal' })
    }

    // Honorarium Payment - only for partners
    if (itemType === 'partner') {
        types.push({ title: 'Honorarium Payment', value: 'honorarium_payment' })
    }

    // Loan Payment - only for loans
    if (itemType === 'loan') {
        types.push({ title: 'Loan Payment', value: 'loan_payment' })
    }

    return types
})

const paymentHeaders = [
    { title: 'Type', key: 'type', width: '150px' },
    { title: 'Amount', key: 'amount', width: '120px' },
    { title: 'Date', key: 'date', width: '100px' },
    { title: 'For Year', key: 'for_year', width: '80px' },
    { title: 'Note', key: 'note' },
    { title: '', key: 'actions', width: '50px', sortable: false },
]

const summaryCards = [
    { type: 'investor', label: 'Investor', color: 'deep-purple', icon: 'mdi-cash-multiple' },
    { type: 'partner', label: 'Partner', color: 'primary', icon: 'mdi-handshake' },
    { type: 'shareholder', label: 'Shareholder', color: 'info', icon: 'mdi-account-group' },
    { type: 'investment_day_term', label: 'Day Term', color: 'success', icon: 'mdi-calendar-clock' },
    { type: 'loan', label: 'Loan', color: 'warning', icon: 'mdi-bank' },
    { type: 'account_payable', label: 'Payable', color: 'error', icon: 'mdi-arrow-up-circle' },
]

const form = reactive({
    name: '',
    phone: '',
    contact_person: '',
    type: 'investor',
    amount: 0,
    share_value: 0,
    honorarium: 0,
    honorarium_type: 'monthly',
    invest_period: null,
    profit_rate: 0,
    loan_type: 'with_profit',
    received_amount: 0,
    total_payable: 0,
    receive_date: '',
    date: new Date().toISOString().split('T')[0],
    appoint_date: '',
    due_date: '',
    description: '',
    status: 'active',
})

const paymentForm = reactive({
    type: 'share_payment',
    amount: 0,
    date: new Date().toISOString().split('T')[0],
    for_year: null,
    note: '',
})

const matchesSearch = (item) => {
    if (!searchQuery.value) return true
    return item.name.toLowerCase().includes(searchQuery.value.toLowerCase())
}

const partnerItems = computed(() => items.value.filter(i => i.type === 'partner' && matchesSearch(i)))
const shareholderItems = computed(() => items.value.filter(i => i.type === 'shareholder' && matchesSearch(i)))
const investorItems = computed(() => items.value.filter(i => i.type === 'investor' && matchesSearch(i)))
const loanItems = computed(() => items.value.filter(i => i.type === 'loan' && matchesSearch(i)))
const otherItems = computed(() => items.value.filter(i => !['partner', 'shareholder', 'investor', 'loan'].includes(i.type) && matchesSearch(i)))

const sections = computed(() => [
    { key: 'partner', label: 'Partner', icon: 'mdi-handshake', color: 'primary', items: partnerItems.value },
    { key: 'shareholder', label: 'Shareholder', icon: 'mdi-account-group', color: 'info', items: shareholderItems.value },
    { key: 'investor', label: 'Investor', icon: 'mdi-cash-multiple', color: 'deep-purple', items: investorItems.value },
    { key: 'loan', label: 'Loan', icon: 'mdi-bank', color: 'warning', items: loanItems.value },
    { key: 'other', label: 'Others', icon: 'mdi-dots-horizontal', color: 'grey', items: otherItems.value },
])

const scrollToSection = (type) => {
    const sectionMap = {
        investor: 'investor',
        partner: 'partner',
        shareholder: 'shareholder',
        investment_day_term: 'other',
        loan: 'loan',
        account_payable: 'other',
        account_receivable: 'other',
    }
    const key = sectionMap[type] || 'other'
    const el = document.getElementById('section-' + key)
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
}

const getTypeCount = (type) => {
    return items.value.filter(item => item.type === type).length
}

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD')

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('en-GB')
}

const getTypeLabel = (type) => {
    const option = typeOptions.find(o => o.value === type)
    return option ? option.title : type
}

const getTypeIcon = (type) => {
    const icons = {
        investor: 'mdi-cash-multiple',
        partner: 'mdi-handshake',
        shareholder: 'mdi-account-group',
        investment_day_term: 'mdi-calendar-clock',
        loan: 'mdi-bank',
        account_payable: 'mdi-arrow-up-circle',
        account_receivable: 'mdi-arrow-down-circle',
    }
    return icons[type] || 'mdi-file-document'
}

const getSharePayments = (item) => {
    if (!item.payments) return []
    return item.payments.filter(p => p.type === 'share_payment').sort((a, b) => new Date(a.date) - new Date(b.date))
}

const getProfitWithdrawals = (item) => {
    if (!item.payments) return []
    return item.payments.filter(p => p.type === 'profit_withdrawal').sort((a, b) => (a.for_year || 0) - (b.for_year || 0))
}

const getTypeColor = (type) => {
    const colors = {
        investor: 'deep-purple',
        partner: 'primary',
        shareholder: 'info',
        investment_day_term: 'success',
        loan: 'warning',
        account_payable: 'error',
        account_receivable: 'teal',
    }
    return colors[type] || 'grey'
}

const getTypeColorHex = (type) => {
    const colors = {
        investor: '#673AB7',
        partner: '#1976D2',
        shareholder: '#0288D1',
        investment_day_term: '#4CAF50',
        loan: '#FF9800',
        account_payable: '#F44336',
        account_receivable: '#009688',
    }
    return colors[type] || '#9E9E9E'
}

const getStatusColor = (status) => {
    const colors = {
        active: 'success',
        completed: 'info',
        cancelled: 'error',
    }
    return colors[status] || 'grey'
}

const getPaymentTypeColor = (type) => {
    const colors = {
        share_payment: 'success',
        profit_withdrawal: 'warning',
        honorarium_payment: 'info',
        loan_payment: 'primary',
    }
    return colors[type] || 'grey'
}

const getPaymentTypeLabel = (type) => {
    const labels = {
        share_payment: 'Share Payment',
        profit_withdrawal: 'Profit Withdrawal',
        honorarium_payment: 'Honorarium',
        loan_payment: 'Loan Payment',
    }
    return labels[type] || type
}

const fetchItems = async () => {
    loading.value = true
    try {
        const params = {}
        if (searchQuery.value) params.search = searchQuery.value
        const response = await api.get('/invest-loan-liabilities', { params })
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

const openDialog = (item = null) => {
    editMode.value = !!item
    selectedItem.value = item
    if (item) {
        Object.assign(form, {
            name: item.name,
            phone: item.phone || '',
            contact_person: item.contact_person || '',
            type: item.type,
            amount: item.amount,
            share_value: item.share_value || 0,
            honorarium: item.honorarium || 0,
            honorarium_type: item.honorarium_type || 'monthly',
            invest_period: item.invest_period || null,
            profit_rate: item.profit_rate || 0,
            loan_type: item.loan_type || 'with_profit',
            received_amount: item.received_amount || 0,
            total_payable: item.total_payable || 0,
            receive_date: item.receive_date ? item.receive_date.split('T')[0] : '',
            date: item.date ? item.date.split('T')[0] : '',
            appoint_date: item.appoint_date ? item.appoint_date.split('T')[0] : '',
            due_date: item.due_date ? item.due_date.split('T')[0] : '',
            description: item.description || '',
            status: item.status,
        })
    } else {
        Object.assign(form, {
            name: '',
            phone: '',
            contact_person: '',
            type: 'investor',
            amount: 0,
            share_value: 0,
            honorarium: 0,
            honorarium_type: 'monthly',
            invest_period: null,
            profit_rate: 0,
            loan_type: 'with_profit',
            received_amount: 0,
            total_payable: 0,
            receive_date: '',
            date: new Date().toISOString().split('T')[0],
            appoint_date: '',
            due_date: '',
            description: '',
            status: 'active',
        })
    }
    dialog.value = true
}

const save = async () => {
    saving.value = true
    try {
        const payload = { ...form }
        if (!payload.appoint_date) delete payload.appoint_date
        if (!payload.due_date) delete payload.due_date
        if (!payload.receive_date) delete payload.receive_date

        // For loan without profit, copy total_payable to received_amount
        if (payload.type === 'loan' && payload.loan_type === 'without_profit') {
            payload.received_amount = payload.total_payable
        }

        if (editMode.value) {
            await api.put(`/invest-loan-liabilities/${selectedItem.value.id}`, payload)
        } else {
            await api.post('/invest-loan-liabilities', payload)
        }
        dialog.value = false
        fetchItems()
        fetchSummary()
    } catch (error) {
        console.error('Error:', error)
    }
    saving.value = false
}

const confirmDelete = (item) => {
    selectedItem.value = item
    deleteDialog.value = true
}

const deleteItem = async () => {
    deleting.value = true
    try {
        await api.delete(`/invest-loan-liabilities/${selectedItem.value.id}`)
        deleteDialog.value = false
        fetchItems()
        fetchSummary()
    } catch (error) {
        console.error('Error:', error)
    }
    deleting.value = false
}

// Payment functions
const openPaymentDialog = (item) => {
    selectedItem.value = item
    // Set default payment type based on item type
    let defaultPaymentType = 'share_payment'
    if (item.type === 'loan') {
        defaultPaymentType = 'loan_payment'
    } else if (item.type === 'investor') {
        defaultPaymentType = 'profit_withdrawal'
    }
    Object.assign(paymentForm, {
        type: defaultPaymentType,
        amount: 0,
        date: new Date().toISOString().split('T')[0],
        for_year: new Date().getFullYear(),
        note: '',
    })
    paymentDialog.value = true
}

const savePayment = async () => {
    savingPayment.value = true
    try {
        await api.post(`/invest-loan-liabilities/${selectedItem.value.id}/payment`, paymentForm)
        paymentDialog.value = false
        fetchItems()
        fetchSummary()
    } catch (error) {
        console.error('Error:', error)
    }
    savingPayment.value = false
}

const viewPayments = async (item) => {
    selectedItem.value = item
    loadingPayments.value = true
    paymentsHistoryDialog.value = true
    try {
        const response = await api.get(`/invest-loan-liabilities/${item.id}/payments`)
        payments.value = response.data
    } catch (error) {
        console.error('Error:', error)
    }
    loadingPayments.value = false
}

const confirmDeletePayment = (payment) => {
    selectedPayment.value = payment
    deletePaymentDialog.value = true
}

const deletePayment = async () => {
    deletingPayment.value = true
    try {
        await api.delete(`/invest-loan-liability-payments/${selectedPayment.value.id}`)
        deletePaymentDialog.value = false
        // Refresh the payments list
        viewPayments(selectedItem.value)
        fetchItems()
        fetchSummary()
    } catch (error) {
        console.error('Error:', error)
    }
    deletingPayment.value = false
}

onMounted(() => {
    fetchItems()
    fetchSummary()
})
</script>

<style scoped>
.border-left-thick {
    border-left: 4px solid;
}
</style>
