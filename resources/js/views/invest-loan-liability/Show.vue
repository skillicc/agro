<template>
    <div>
        <!-- Header -->
        <div class="d-flex justify-space-between align-center mb-4">
            <div>
                <v-btn icon variant="text" @click="$router.push({ name: 'invest-loan-liability' })">
                    <v-icon>mdi-arrow-left</v-icon>
                </v-btn>
                <span class="text-h5 text-sm-h4 ml-2">{{ sectionConfig.label }}</span>
                <v-chip class="ml-2" :color="sectionConfig.color">
                    {{ items.length }} Records
                </v-chip>
            </div>
        </div>

        <!-- Summary Cards -->
        <v-row>
            <v-col cols="12" sm="6" :md="summaryCardSize">
                <v-card :color="sectionConfig.color" variant="tonal">
                    <v-card-text class="text-center">
                        <div class="text-h5">৳{{ formatNumber(totalAmount) }}</div>
                        <div>Total Amount</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" sm="6" :md="summaryCardSize" v-if="isPartnerOrShareholder">
                <v-card color="info" variant="tonal">
                    <v-card-text class="text-center">
                        <div class="text-h5">৳{{ formatNumber(totalSharePaid) }}</div>
                        <div>Total Share Paid</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" sm="6" :md="summaryCardSize" v-if="isPartnerOrShareholder || routeType === 'investor'">
                <v-card color="warning" variant="tonal">
                    <v-card-text class="text-center">
                        <div class="text-h5">৳{{ formatNumber(totalProfitWithdrawn) }}</div>
                        <div>Profit Withdrawn</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" sm="6" :md="summaryCardSize" v-if="routeType === 'partner'">
                <v-card color="success" variant="tonal">
                    <v-card-text class="text-center">
                        <div class="text-h5">৳{{ formatNumber(totalHonorarium) }}</div>
                        <div>Total Honorarium Paid</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" sm="6" :md="summaryCardSize" v-if="routeType === 'loan'">
                <v-card color="success" variant="tonal">
                    <v-card-text class="text-center">
                        <div class="text-h5">৳{{ formatNumber(totalLoanPaid) }}</div>
                        <div>Total Paid</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" sm="6" :md="summaryCardSize" v-if="routeType === 'loan'">
                <v-card color="error" variant="tonal">
                    <v-card-text class="text-center">
                        <div class="text-h5">৳{{ formatNumber(totalAmount - totalLoanPaid) }}</div>
                        <div>Total Rest</div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <!-- Tabs -->
        <v-card class="mt-4">
            <v-tabs v-model="tab" color="primary">
                <v-tab value="members">{{ sectionConfig.label }}</v-tab>
                <v-tab v-if="hasSharePayments" value="share_payments">Share Amount</v-tab>
                <v-tab v-if="hasProfitWithdrawals" value="profit_withdrawals">Profit Withdrawals</v-tab>
                <v-tab v-if="routeType === 'partner'" value="honorarium">Honorarium</v-tab>
                <v-tab v-if="routeType === 'loan'" value="loan_payments">Loan Payments</v-tab>
            </v-tabs>

            <v-card-text>
                <v-window v-model="tab">
                    <!-- Members Tab -->
                    <v-window-item value="members">
                        <div class="d-flex flex-wrap justify-space-between align-center mb-4 ga-2">
                            <h2 class="text-h6">{{ sectionConfig.label }}</h2>
                            <v-btn color="primary" @click="openDialog()" :size="$vuetify.display.xs ? 'small' : 'default'">
                                <v-icon left>mdi-plus</v-icon>
                                Add {{ sectionConfig.label }}
                            </v-btn>
                        </div>
                        <v-text-field
                            v-model="searchQuery"
                            label="Search by Name"
                            prepend-inner-icon="mdi-magnify"
                            clearable
                            hide-details
                            density="compact"
                            class="mb-4"
                        ></v-text-field>
                        <v-data-table
                            :headers="memberHeaders"
                            :items="filteredItems"
                            :loading="loading"
                            density="compact"
                            :search="searchQuery"
                        >
                            <template v-slot:item.name="{ item }">
                                <strong>{{ item.name }}</strong>
                                <div v-if="item.phone" class="text-caption text-grey">{{ item.phone }}</div>
                            </template>
                            <template v-slot:item.appoint_date="{ item }">
                                {{ formatDate(item.appoint_date) }}
                            </template>
                            <template v-slot:item.date="{ item }">
                                {{ formatDate(item.date) }}
                            </template>
                            <template v-slot:item.amount="{ item }">
                                ৳{{ formatNumber(item.amount) }}
                            </template>
                            <template v-slot:item.share_value="{ item }">
                                ৳{{ formatNumber(item.share_value) }}
                            </template>
                            <template v-slot:item.honorarium="{ item }">
                                <span v-if="item.honorarium">৳{{ formatNumber(item.honorarium) }} / {{ item.honorarium_type }}</span>
                                <span v-else>-</span>
                            </template>
                            <template v-slot:item.total_share_paid="{ item }">
                                <span class="text-success">৳{{ formatNumber(item.total_share_paid) }}</span>
                            </template>
                            <template v-slot:item.total_profit_withdrawn="{ item }">
                                <span class="text-warning">৳{{ formatNumber(item.total_profit_withdrawn) }}</span>
                            </template>
                            <template v-slot:item.total_loan_paid="{ item }">
                                <span class="text-success">৳{{ formatNumber(item.total_loan_paid) }}</span>
                            </template>
                            <template v-slot:item.loan_rest_amount="{ item }">
                                <span class="text-error">৳{{ formatNumber(item.loan_rest_amount) }}</span>
                            </template>
                            <template v-slot:item.loan_type="{ item }">
                                {{ item.loan_type === 'with_profit' ? 'With Profit' : 'Without Profit' }}
                            </template>
                            <template v-slot:item.total_payable="{ item }">
                                ৳{{ formatNumber(item.total_payable) }}
                            </template>
                            <template v-slot:item.invest_period="{ item }">
                                {{ item.invest_period ? item.invest_period + ' Months' : '-' }}
                            </template>
                            <template v-slot:item.profit_rate="{ item }">
                                {{ item.profit_rate ? item.profit_rate + '%' : '-' }}
                            </template>
                            <template v-slot:item.status="{ item }">
                                <v-chip :color="getStatusColor(item.status)" size="x-small">{{ item.status }}</v-chip>
                            </template>
                            <template v-slot:item.actions="{ item }">
                                <v-btn v-if="hasPayments" icon size="x-small" color="success" @click="openPaymentDialog(item)" title="Add Payment" class="mr-1">
                                    <v-icon>mdi-cash-plus</v-icon>
                                </v-btn>
                                <v-btn icon size="x-small" @click="openDialog(item)" title="Edit" class="mr-1">
                                    <v-icon>mdi-pencil</v-icon>
                                </v-btn>
                                <v-btn icon size="x-small" color="error" @click="confirmDelete(item)" title="Delete">
                                    <v-icon>mdi-delete</v-icon>
                                </v-btn>
                            </template>
                        </v-data-table>
                    </v-window-item>

                    <!-- Share Amount Tab -->
                    <v-window-item v-if="hasSharePayments" value="share_payments">
                        <div class="d-flex flex-wrap justify-space-between align-center mb-4 ga-2">
                            <h2 class="text-h6">Share Amount</h2>
                            <v-btn color="primary" @click="openShareAmountDialog()" :size="$vuetify.display.xs ? 'small' : 'default'">
                                <v-icon left>mdi-plus</v-icon>
                                Add Share Amount
                            </v-btn>
                        </div>
                        <v-data-table
                            :headers="sharePaymentHeaders"
                            :items="allSharePayments"
                            :loading="loading"
                            density="compact"
                        >
                            <template v-slot:item.member_name="{ item }">
                                <strong>{{ item.member_name }}</strong>
                            </template>
                            <template v-slot:item.amount="{ item }">
                                ৳{{ formatNumber(item.amount) }}
                            </template>
                            <template v-slot:item.date="{ item }">
                                {{ formatDate(item.date) }}
                            </template>
                            <template v-slot:item.actions="{ item }">
                                <v-btn icon size="x-small" color="error" @click="confirmDeletePayment(item)" title="Delete">
                                    <v-icon>mdi-delete</v-icon>
                                </v-btn>
                            </template>
                        </v-data-table>
                    </v-window-item>

                    <!-- Profit Withdrawals Tab -->
                    <v-window-item v-if="hasProfitWithdrawals" value="profit_withdrawals">
                        <div class="d-flex flex-wrap justify-space-between align-center mb-4 ga-2">
                            <h2 class="text-h6">Profit Withdrawals</h2>
                            <v-btn color="primary" @click="openProfitWithdrawalDialog()" :size="$vuetify.display.xs ? 'small' : 'default'">
                                <v-icon left>mdi-plus</v-icon>
                                Add Profit Withdrawal
                            </v-btn>
                        </div>
                        <v-data-table
                            :headers="profitWithdrawalHeaders"
                            :items="allProfitWithdrawals"
                            :loading="loading"
                            density="compact"
                        >
                            <template v-slot:item.member_name="{ item }">
                                <strong>{{ item.member_name }}</strong>
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
                                <v-btn icon size="x-small" color="error" @click="confirmDeletePayment(item)" title="Delete">
                                    <v-icon>mdi-delete</v-icon>
                                </v-btn>
                            </template>
                        </v-data-table>
                    </v-window-item>

                    <!-- Honorarium Tab -->
                    <v-window-item v-if="routeType === 'partner'" value="honorarium">
                        <div class="d-flex flex-wrap justify-space-between align-center mb-4 ga-2">
                            <h2 class="text-h6">Honorarium Payments</h2>
                        </div>
                        <v-data-table
                            :headers="honorariumHeaders"
                            :items="allHonorariumPayments"
                            :loading="loading"
                            density="compact"
                        >
                            <template v-slot:item.member_name="{ item }">
                                <strong>{{ item.member_name }}</strong>
                            </template>
                            <template v-slot:item.amount="{ item }">
                                ৳{{ formatNumber(item.amount) }}
                            </template>
                            <template v-slot:item.date="{ item }">
                                {{ formatDate(item.date) }}
                            </template>
                            <template v-slot:item.actions="{ item }">
                                <v-btn icon size="x-small" color="error" @click="confirmDeletePayment(item)" title="Delete">
                                    <v-icon>mdi-delete</v-icon>
                                </v-btn>
                            </template>
                        </v-data-table>
                    </v-window-item>

                    <!-- Loan Payments Tab -->
                    <v-window-item v-if="routeType === 'loan'" value="loan_payments">
                        <div class="d-flex flex-wrap justify-space-between align-center mb-4 ga-2">
                            <h2 class="text-h6">Loan Payments</h2>
                        </div>
                        <v-data-table
                            :headers="loanPaymentHeaders"
                            :items="allLoanPayments"
                            :loading="loading"
                            density="compact"
                        >
                            <template v-slot:item.member_name="{ item }">
                                <strong>{{ item.member_name }}</strong>
                            </template>
                            <template v-slot:item.amount="{ item }">
                                ৳{{ formatNumber(item.amount) }}
                            </template>
                            <template v-slot:item.date="{ item }">
                                {{ formatDate(item.date) }}
                            </template>
                            <template v-slot:item.actions="{ item }">
                                <v-btn icon size="x-small" color="error" @click="confirmDeletePayment(item)" title="Delete">
                                    <v-icon>mdi-delete</v-icon>
                                </v-btn>
                            </template>
                        </v-data-table>
                    </v-window-item>
                </v-window>
            </v-card-text>
        </v-card>

        <!-- Add/Edit Dialog -->
        <v-dialog v-model="dialog" :max-width="$vuetify.display.xs ? '100%' : '600'" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title>{{ editMode ? 'Edit' : 'Add' }} {{ sectionConfig.label }}</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="save">
                        <v-row>
                            <v-col cols="12" lg="6">
                                <v-text-field v-model="form.name" :label="form.type === 'loan' && form.loan_type === 'with_profit' ? 'Organization Name' : 'Name'" required></v-text-field>
                            </v-col>
                            <v-col cols="12" lg="6" v-if="routeType === 'others'">
                                <v-select v-model="form.type" :items="otherTypeOptions" label="Type" required></v-select>
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
                            <v-col cols="12" lg="6" v-if="form.type === 'loan'">
                                <v-select v-model="form.loan_type" :items="loanTypeOptions" label="Loan Type" required></v-select>
                            </v-col>
                            <v-col cols="12" lg="6" v-if="form.type === 'loan'">
                                <v-text-field v-model.number="form.total_payable" :label="form.loan_type === 'with_profit' ? 'Loan Amount (Total Payable)' : 'Loan Amount'" type="number" prefix="৳" required></v-text-field>
                            </v-col>
                            <v-col cols="12" lg="6" v-if="form.type === 'loan' && form.loan_type === 'with_profit'">
                                <v-text-field v-model.number="form.received_amount" label="Received Amount" type="number" prefix="৳"></v-text-field>
                            </v-col>
                            <v-col cols="12" lg="6" v-if="form.type === 'loan' && form.loan_type === 'with_profit'">
                                <v-text-field v-model="form.receive_date" label="Receive Date" type="date"></v-text-field>
                            </v-col>
                            <v-col cols="12" lg="6" v-if="form.type === 'loan'">
                                <v-text-field v-model="form.date" label="Loan Date" type="date" required></v-text-field>
                            </v-col>
                            <v-col cols="12" lg="6" v-if="form.type === 'investor'">
                                <v-select v-model="form.invest_period" :items="investPeriodOptions" label="Invest Period" clearable></v-select>
                            </v-col>
                            <v-col cols="12" lg="6" v-if="form.type === 'investor'">
                                <v-text-field v-model.number="form.profit_rate" label="Expected Profit Rate" type="number" suffix="%"></v-text-field>
                            </v-col>
                            <v-col cols="12" lg="6" v-if="['shareholder', 'partner'].includes(form.type)">
                                <v-text-field v-model.number="form.share_value" label="Share Value" type="number" prefix="৳"></v-text-field>
                            </v-col>
                            <v-col cols="12" lg="6" v-if="form.type === 'partner'">
                                <v-text-field v-model.number="form.honorarium" label="Honorarium" type="number" prefix="৳"></v-text-field>
                            </v-col>
                            <v-col cols="12" lg="6" v-if="form.type === 'partner' && form.honorarium > 0">
                                <v-select v-model="form.honorarium_type" :items="honorariumTypes" label="Honorarium Type"></v-select>
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
                            <v-col cols="12" lg="6" v-if="form.type === 'loan'">
                                <v-text-field v-model="form.due_date" label="Due Date" type="date"></v-text-field>
                            </v-col>
                            <v-col cols="12" lg="6">
                                <v-select v-model="form.status" :items="statusOptions" label="Status"></v-select>
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
                        <v-select v-model="paymentForm.type" :items="getPaymentTypes" label="Payment Type" required></v-select>
                        <v-text-field v-model.number="paymentForm.amount" label="Amount" type="number" prefix="৳" required></v-text-field>
                        <v-text-field v-model="paymentForm.date" label="Date" type="date" required></v-text-field>
                        <v-text-field v-if="paymentForm.type === 'profit_withdrawal'" v-model.number="paymentForm.for_year" label="For Year" type="number" :placeholder="new Date().getFullYear().toString()"></v-text-field>
                        <v-text-field v-model="paymentForm.note" label="Note (Optional)"></v-text-field>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="paymentDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="savePayment" :loading="savingPayment">Save Payment</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Share Amount Dialog -->
        <v-dialog v-model="shareAmountDialog" :max-width="$vuetify.display.xs ? '100%' : '500'" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title>Add Share Amount</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="saveShareAmount">
                        <v-select v-model="shareAmountForm.member_id" :items="memberOptions" item-title="name" item-value="id" label="Select Member" required></v-select>
                        <v-text-field v-model.number="shareAmountForm.amount" label="Amount" type="number" prefix="৳" required></v-text-field>
                        <v-text-field v-model="shareAmountForm.date" label="Date" type="date" required></v-text-field>
                        <v-text-field v-model="shareAmountForm.note" label="Note (Optional)"></v-text-field>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="shareAmountDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveShareAmount" :loading="savingShareAmount">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Profit Withdrawal Dialog -->
        <v-dialog v-model="profitWithdrawalDialog" :max-width="$vuetify.display.xs ? '100%' : '500'" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title>Add Profit Withdrawal</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="saveProfitWithdrawal">
                        <v-select v-model="profitWithdrawalForm.member_id" :items="memberOptions" item-title="name" item-value="id" label="Select Member" required></v-select>
                        <v-text-field v-model.number="profitWithdrawalForm.amount" label="Amount" type="number" prefix="৳" required></v-text-field>
                        <v-text-field v-model.number="profitWithdrawalForm.for_year" label="For Year" type="number" :placeholder="new Date().getFullYear().toString()" required></v-text-field>
                        <v-text-field v-model="profitWithdrawalForm.date" label="Date" type="date" required></v-text-field>
                        <v-text-field v-model="profitWithdrawalForm.note" label="Note (Optional)"></v-text-field>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="profitWithdrawalDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveProfitWithdrawal" :loading="savingProfitWithdrawal">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Confirm Dialog -->
        <v-dialog v-model="deleteDialog" max-width="400">
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
        <v-dialog v-model="deletePaymentDialog" max-width="400">
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
import { useRoute } from 'vue-router'
import api from '../../services/api'

const route = useRoute()
const routeType = computed(() => route.params.type)

const sectionConfigs = {
    partner: { label: 'Partner', color: 'primary', icon: 'mdi-handshake' },
    shareholder: { label: 'Shareholder', color: 'info', icon: 'mdi-account-group' },
    investor: { label: 'Investor', color: 'deep-purple', icon: 'mdi-cash-multiple' },
    loan: { label: 'Loan', color: 'warning', icon: 'mdi-bank' },
    others: { label: 'Others', color: 'grey', icon: 'mdi-dots-horizontal' },
}

const sectionConfig = computed(() => sectionConfigs[routeType.value] || sectionConfigs.others)
const isPartnerOrShareholder = computed(() => ['partner', 'shareholder'].includes(routeType.value))
const hasSharePayments = computed(() => ['partner', 'shareholder'].includes(routeType.value))
const hasProfitWithdrawals = computed(() => ['partner', 'shareholder', 'investor'].includes(routeType.value))
const hasPayments = computed(() => ['partner', 'shareholder', 'investor', 'loan'].includes(routeType.value))

const summaryCardSize = computed(() => {
    if (routeType.value === 'partner') return 3
    if (routeType.value === 'loan') return 4
    if (routeType.value === 'shareholder') return 4
    if (routeType.value === 'investor') return 6
    return 6
})

const items = ref([])
const loading = ref(false)
const dialog = ref(false)
const deleteDialog = ref(false)
const editMode = ref(false)
const selectedItem = ref(null)
const saving = ref(false)
const deleting = ref(false)
const searchQuery = ref('')
const tab = ref('members')

const paymentDialog = ref(false)
const savingPayment = ref(false)
const deletePaymentDialog = ref(false)
const selectedPayment = ref(null)
const deletingPayment = ref(false)

const shareAmountDialog = ref(false)
const savingShareAmount = ref(false)

const profitWithdrawalDialog = ref(false)
const savingProfitWithdrawal = ref(false)

// Member table headers based on type
const memberHeaders = computed(() => {
    const type = routeType.value
    if (type === 'partner') {
        return [
            { title: 'Name', key: 'name' },
            { title: 'Appoint Date', key: 'appoint_date', width: '120px' },
            { title: 'Amount', key: 'amount', width: '120px' },
            { title: 'Share Value', key: 'share_value', width: '120px' },
            { title: 'Honorarium', key: 'honorarium', width: '150px' },
            { title: 'Share Paid', key: 'total_share_paid', width: '120px' },
            { title: 'Profit', key: 'total_profit_withdrawn', width: '120px' },
            { title: 'Status', key: 'status', width: '90px' },
            { title: 'Actions', key: 'actions', width: '120px', sortable: false },
        ]
    }
    if (type === 'shareholder') {
        return [
            { title: 'Name', key: 'name' },
            { title: 'Appoint Date', key: 'appoint_date', width: '120px' },
            { title: 'Amount', key: 'amount', width: '120px' },
            { title: 'Share Value', key: 'share_value', width: '120px' },
            { title: 'Share Paid', key: 'total_share_paid', width: '120px' },
            { title: 'Profit', key: 'total_profit_withdrawn', width: '120px' },
            { title: 'Status', key: 'status', width: '90px' },
            { title: 'Actions', key: 'actions', width: '120px', sortable: false },
        ]
    }
    if (type === 'investor') {
        return [
            { title: 'Name', key: 'name' },
            { title: 'Date', key: 'date', width: '110px' },
            { title: 'Amount', key: 'amount', width: '120px' },
            { title: 'Period', key: 'invest_period', width: '110px' },
            { title: 'Profit Rate', key: 'profit_rate', width: '100px' },
            { title: 'Profit', key: 'total_profit_withdrawn', width: '120px' },
            { title: 'Status', key: 'status', width: '90px' },
            { title: 'Actions', key: 'actions', width: '120px', sortable: false },
        ]
    }
    if (type === 'loan') {
        return [
            { title: 'Name', key: 'name' },
            { title: 'Date', key: 'date', width: '110px' },
            { title: 'Loan Type', key: 'loan_type', width: '120px' },
            { title: 'Total Payable', key: 'total_payable', width: '130px' },
            { title: 'Paid', key: 'total_loan_paid', width: '110px' },
            { title: 'Rest', key: 'loan_rest_amount', width: '110px' },
            { title: 'Status', key: 'status', width: '90px' },
            { title: 'Actions', key: 'actions', width: '120px', sortable: false },
        ]
    }
    // Others
    return [
        { title: 'Name', key: 'name' },
        { title: 'Date', key: 'date', width: '110px' },
        { title: 'Amount', key: 'amount', width: '120px' },
        { title: 'Status', key: 'status', width: '90px' },
        { title: 'Actions', key: 'actions', width: '120px', sortable: false },
    ]
})

const sharePaymentHeaders = [
    { title: 'Name', key: 'member_name' },
    { title: 'Amount', key: 'amount', width: '130px' },
    { title: 'Date', key: 'date', width: '120px' },
    { title: 'Note', key: 'note' },
    { title: '', key: 'actions', width: '50px', sortable: false },
]

const profitWithdrawalHeaders = [
    { title: 'Name', key: 'member_name' },
    { title: 'Amount', key: 'amount', width: '130px' },
    { title: 'For Year', key: 'for_year', width: '100px' },
    { title: 'Date', key: 'date', width: '120px' },
    { title: 'Note', key: 'note' },
    { title: '', key: 'actions', width: '50px', sortable: false },
]

const honorariumHeaders = [
    { title: 'Name', key: 'member_name' },
    { title: 'Amount', key: 'amount', width: '130px' },
    { title: 'Date', key: 'date', width: '120px' },
    { title: 'Note', key: 'note' },
    { title: '', key: 'actions', width: '50px', sortable: false },
]

const loanPaymentHeaders = [
    { title: 'Name', key: 'member_name' },
    { title: 'Amount', key: 'amount', width: '130px' },
    { title: 'Date', key: 'date', width: '120px' },
    { title: 'Note', key: 'note' },
    { title: '', key: 'actions', width: '50px', sortable: false },
]

// Extract all payments by type across all members
const allSharePayments = computed(() => {
    const payments = []
    items.value.forEach(item => {
        if (item.payments) {
            item.payments.filter(p => p.type === 'share_payment').forEach(p => {
                payments.push({ ...p, member_name: item.name, member_id: item.id })
            })
        }
    })
    return payments.sort((a, b) => new Date(b.date) - new Date(a.date))
})

const allProfitWithdrawals = computed(() => {
    const payments = []
    items.value.forEach(item => {
        if (item.payments) {
            item.payments.filter(p => p.type === 'profit_withdrawal').forEach(p => {
                payments.push({ ...p, member_name: item.name, member_id: item.id })
            })
        }
    })
    return payments.sort((a, b) => new Date(b.date) - new Date(a.date))
})

const allHonorariumPayments = computed(() => {
    const payments = []
    items.value.forEach(item => {
        if (item.payments) {
            item.payments.filter(p => p.type === 'honorarium_payment').forEach(p => {
                payments.push({ ...p, member_name: item.name, member_id: item.id })
            })
        }
    })
    return payments.sort((a, b) => new Date(b.date) - new Date(a.date))
})

const allLoanPayments = computed(() => {
    const payments = []
    items.value.forEach(item => {
        if (item.payments) {
            item.payments.filter(p => p.type === 'loan_payment').forEach(p => {
                payments.push({ ...p, member_name: item.name, member_id: item.id })
            })
        }
    })
    return payments.sort((a, b) => new Date(b.date) - new Date(a.date))
})

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

const otherTypeOptions = [
    { title: 'Investment Day Term', value: 'investment_day_term' },
    { title: 'Account Payable (From Total)', value: 'account_payable' },
    { title: 'Account Receivable (From Total)', value: 'account_receivable' },
]

const getPaymentTypes = computed(() => {
    const itemType = selectedItem.value?.type
    const types = []
    if (['shareholder', 'partner'].includes(itemType)) types.push({ title: 'Share Payment', value: 'share_payment' })
    if (['shareholder', 'partner', 'investor'].includes(itemType)) types.push({ title: 'Profit / Loss', value: 'profit_withdrawal' })
    if (itemType === 'partner') types.push({ title: 'Honorarium Payment', value: 'honorarium_payment' })
    if (itemType === 'loan') types.push({ title: 'Loan Payment', value: 'loan_payment' })
    return types
})

const form = reactive({
    name: '', phone: '', contact_person: '', type: 'investor',
    amount: 0, share_value: 0, honorarium: 0, honorarium_type: 'monthly',
    invest_period: null, profit_rate: 0, loan_type: 'with_profit',
    received_amount: 0, total_payable: 0, receive_date: '',
    date: new Date().toISOString().split('T')[0],
    appoint_date: '', due_date: '', description: '', status: 'active',
})

const paymentForm = reactive({
    type: 'share_payment', amount: 0,
    date: new Date().toISOString().split('T')[0],
    for_year: null, note: '',
})

const shareAmountForm = reactive({
    member_id: null, amount: 0,
    date: new Date().toISOString().split('T')[0],
    note: '',
})

const profitWithdrawalForm = reactive({
    member_id: null, amount: 0,
    for_year: new Date().getFullYear(),
    date: new Date().toISOString().split('T')[0],
    note: '',
})

const filteredItems = computed(() => {
    if (!searchQuery.value) return items.value
    return items.value.filter(item => item.name.toLowerCase().includes(searchQuery.value.toLowerCase()))
})

const memberOptions = computed(() => {
    return items.value.map(item => ({ id: item.id, name: item.name }))
})

const totalAmount = computed(() => items.value.reduce((sum, i) => sum + Number(i.amount || 0), 0))
const totalSharePaid = computed(() => items.value.reduce((sum, i) => sum + Number(i.total_share_paid || 0), 0))
const totalProfitWithdrawn = computed(() => items.value.reduce((sum, i) => sum + Number(i.total_profit_withdrawn || 0), 0))
const totalHonorarium = computed(() => allHonorariumPayments.value.reduce((sum, p) => sum + Number(p.amount || 0), 0))
const totalLoanPaid = computed(() => items.value.reduce((sum, i) => sum + Number(i.total_loan_paid || 0), 0))

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD')
const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('en-GB')
}

const getStatusColor = (status) => {
    const colors = { active: 'success', completed: 'info', cancelled: 'error' }
    return colors[status] || 'grey'
}

const getDefaultType = () => {
    const map = { partner: 'partner', shareholder: 'shareholder', investor: 'investor', loan: 'loan', others: 'investment_day_term' }
    return map[routeType.value] || 'investor'
}

const fetchItems = async () => {
    loading.value = true
    try {
        if (routeType.value === 'others') {
            const response = await api.get('/invest-loan-liabilities')
            items.value = response.data.filter(i => !['partner', 'shareholder', 'investor', 'loan'].includes(i.type))
        } else {
            const response = await api.get('/invest-loan-liabilities', { params: { type: routeType.value } })
            items.value = response.data
        }
    } catch (error) {
        console.error('Error:', error)
    }
    loading.value = false
}

const openDialog = (item = null) => {
    editMode.value = !!item
    selectedItem.value = item
    if (item) {
        Object.assign(form, {
            name: item.name, phone: item.phone || '', contact_person: item.contact_person || '',
            type: item.type, amount: item.amount, share_value: item.share_value || 0,
            honorarium: item.honorarium || 0, honorarium_type: item.honorarium_type || 'monthly',
            invest_period: item.invest_period || null, profit_rate: item.profit_rate || 0,
            loan_type: item.loan_type || 'with_profit', received_amount: item.received_amount || 0,
            total_payable: item.total_payable || 0,
            receive_date: item.receive_date ? item.receive_date.split('T')[0] : '',
            date: item.date ? item.date.split('T')[0] : '',
            appoint_date: item.appoint_date ? item.appoint_date.split('T')[0] : '',
            due_date: item.due_date ? item.due_date.split('T')[0] : '',
            description: item.description || '', status: item.status,
        })
    } else {
        Object.assign(form, {
            name: '', phone: '', contact_person: '', type: getDefaultType(),
            amount: 0, share_value: 0, honorarium: 0, honorarium_type: 'monthly',
            invest_period: null, profit_rate: 0, loan_type: 'with_profit',
            received_amount: 0, total_payable: 0, receive_date: '',
            date: new Date().toISOString().split('T')[0],
            appoint_date: '', due_date: '', description: '', status: 'active',
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
        if (payload.type === 'loan' && payload.loan_type === 'without_profit') payload.received_amount = payload.total_payable

        if (editMode.value) {
            await api.put(`/invest-loan-liabilities/${selectedItem.value.id}`, payload)
        } else {
            await api.post('/invest-loan-liabilities', payload)
        }
        dialog.value = false
        fetchItems()
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
    } catch (error) {
        console.error('Error:', error)
    }
    deleting.value = false
}

const openPaymentDialog = (item) => {
    selectedItem.value = item
    let defaultType = 'share_payment'
    if (item.type === 'loan') defaultType = 'loan_payment'
    else if (item.type === 'investor') defaultType = 'profit_withdrawal'
    Object.assign(paymentForm, {
        type: defaultType, amount: 0,
        date: new Date().toISOString().split('T')[0],
        for_year: new Date().getFullYear(), note: '',
    })
    paymentDialog.value = true
}

const savePayment = async () => {
    savingPayment.value = true
    try {
        await api.post(`/invest-loan-liabilities/${selectedItem.value.id}/payment`, paymentForm)
        paymentDialog.value = false
        fetchItems()
    } catch (error) {
        console.error('Error:', error)
    }
    savingPayment.value = false
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
        fetchItems()
    } catch (error) {
        console.error('Error:', error)
    }
    deletingPayment.value = false
}

const openShareAmountDialog = () => {
    Object.assign(shareAmountForm, {
        member_id: null, amount: 0,
        date: new Date().toISOString().split('T')[0],
        note: '',
    })
    shareAmountDialog.value = true
}

const saveShareAmount = async () => {
    if (!shareAmountForm.member_id) {
        alert('Please select a member')
        return
    }
    savingShareAmount.value = true
    try {
        await api.post(`/invest-loan-liabilities/${shareAmountForm.member_id}/payment`, {
            type: 'share_payment',
            amount: shareAmountForm.amount,
            date: shareAmountForm.date,
            note: shareAmountForm.note,
        })
        shareAmountDialog.value = false
        fetchItems()
    } catch (error) {
        console.error('Error:', error)
    }
    savingShareAmount.value = false
}

const openProfitWithdrawalDialog = () => {
    Object.assign(profitWithdrawalForm, {
        member_id: null, amount: 0,
        for_year: new Date().getFullYear(),
        date: new Date().toISOString().split('T')[0],
        note: '',
    })
    profitWithdrawalDialog.value = true
}

const saveProfitWithdrawal = async () => {
    if (!profitWithdrawalForm.member_id) {
        alert('Please select a member')
        return
    }
    savingProfitWithdrawal.value = true
    try {
        await api.post(`/invest-loan-liabilities/${profitWithdrawalForm.member_id}/payment`, {
            type: 'profit_withdrawal',
            amount: profitWithdrawalForm.amount,
            for_year: profitWithdrawalForm.for_year,
            date: profitWithdrawalForm.date,
            note: profitWithdrawalForm.note,
        })
        profitWithdrawalDialog.value = false
        fetchItems()
    } catch (error) {
        console.error('Error:', error)
    }
    savingProfitWithdrawal.value = false
}

onMounted(() => {
    fetchItems()
})
</script>
