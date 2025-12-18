<template>
    <div>
        <div class="d-flex justify-space-between align-center mb-4">
            <h1 class="text-h4">Employees</h1>
            <v-btn color="primary" @click="openDialog()">
                <v-icon left>mdi-plus</v-icon>
                Add Employee
            </v-btn>
        </div>

        <!-- Summary Cards -->
        <v-row class="mb-4">
            <v-col cols="12" md="3">
                <v-card color="primary" variant="tonal">
                    <v-card-text class="d-flex align-center">
                        <v-icon size="40" class="mr-3">mdi-account-group</v-icon>
                        <div>
                            <div class="text-h5 font-weight-bold">{{ totalEmployees }}</div>
                            <div class="text-caption">Total Employees</div>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="3">
                <v-card color="success" variant="tonal">
                    <v-card-text class="d-flex align-center">
                        <v-icon size="40" class="mr-3">mdi-account-check</v-icon>
                        <div>
                            <div class="text-h5 font-weight-bold">{{ activeEmployees }}</div>
                            <div class="text-caption">Active Employees</div>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="3">
                <v-card color="info" variant="tonal">
                    <v-card-text class="d-flex align-center">
                        <v-icon size="40" class="mr-3">mdi-cash-multiple</v-icon>
                        <div>
                            <div class="text-h5 font-weight-bold">৳{{ formatNumber(totalMonthlySalary) }}</div>
                            <div class="text-caption">Monthly Salary (Active)</div>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="3">
                <v-card color="warning" variant="tonal" @click="showAllSummary" style="cursor: pointer;">
                    <v-card-text class="d-flex align-center">
                        <v-icon size="40" class="mr-3">mdi-chart-bar</v-icon>
                        <div>
                            <div class="text-h5 font-weight-bold">View All</div>
                            <div class="text-caption">Salary & Advance Summary</div>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <v-card>
            <v-card-text>
                <v-data-table
                    :headers="headers"
                    :items="employees"
                    :loading="loading"
                    :group-by="groupBy"
                    items-per-page="-1"
                >
                    <template v-slot:group-header="{ item, columns, toggleGroup, isGroupOpen }">
                        <tr class="bg-primary-lighten-5">
                            <td :colspan="columns.length">
                                <v-btn
                                    size="small"
                                    variant="text"
                                    :icon="isGroupOpen(item) ? 'mdi-chevron-down' : 'mdi-chevron-right'"
                                    @click="toggleGroup(item)"
                                ></v-btn>
                                <v-icon class="mr-2" color="primary">mdi-folder</v-icon>
                                <span class="font-weight-bold text-primary">{{ item.value }}</span>
                                <v-chip size="x-small" class="ml-2" color="primary">
                                    {{ item.items.length }} employees
                                </v-chip>
                                <v-chip size="x-small" class="ml-1" color="info">
                                    Salary: ৳{{ formatNumber(getGroupTotalSalary(item.items)) }}
                                </v-chip>
                            </td>
                        </tr>
                    </template>
                    <template v-slot:item.salary_amount="{ item }">
                        ৳{{ formatNumber(item.salary_amount) }}
                    </template>
                    <template v-slot:item.total_paid="{ item }">
                        <span class="text-success font-weight-bold">৳{{ formatNumber(item.total_paid) }}</span>
                    </template>
                    <template v-slot:item.current_month_due="{ item }">
                        <v-chip :color="item.current_month_due > 0 ? 'error' : 'success'" size="small">
                            {{ item.current_month_due > 0 ? '৳' + formatNumber(item.current_month_due) : 'Paid' }}
                        </v-chip>
                    </template>
                    <template v-slot:item.is_active="{ item }">
                        <v-chip :color="item.is_active ? 'success' : 'error'" size="small">
                            {{ item.is_active ? 'Active' : 'Inactive' }}
                        </v-chip>
                    </template>
                    <template v-slot:item.actions="{ item }">
                        <v-btn icon size="small" color="info" @click="viewHistory(item)" title="View History">
                            <v-icon>mdi-history</v-icon>
                        </v-btn>
                        <v-btn icon size="small" color="success" @click="openSalaryDialog(item)" title="Pay Salary">
                            <v-icon>mdi-cash</v-icon>
                        </v-btn>
                        <v-btn icon size="small" color="warning" @click="openAdvanceDialog(item)" title="Give Advance">
                            <v-icon>mdi-cash-minus</v-icon>
                        </v-btn>
                        <v-btn icon size="small" @click="openDialog(item)" title="Edit">
                            <v-icon>mdi-pencil</v-icon>
                        </v-btn>
                    </template>
                </v-data-table>
            </v-card-text>
        </v-card>

        <!-- Add/Edit Dialog -->
        <v-dialog v-model="dialog" max-width="500">
            <v-card>
                <v-card-title>{{ editMode ? 'Edit Employee' : 'Add Employee' }}</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="saveEmployee">
                        <v-select v-model="form.project_id" :items="projects" item-title="name" item-value="id" label="Project" required></v-select>
                        <v-text-field v-model="form.name" label="Name" required></v-text-field>
                        <v-text-field v-model="form.phone" label="Phone"></v-text-field>
                        <v-text-field v-model="form.position" label="Position"></v-text-field>
                        <v-text-field v-model.number="form.salary_amount" label="Salary Amount" type="number" required></v-text-field>
                        <v-text-field v-model="form.joining_date" label="Joining Date" type="date"></v-text-field>
                        <v-switch v-model="form.is_active" label="Active" color="success"></v-switch>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="dialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveEmployee" :loading="saving">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Salary Dialog -->
        <v-dialog v-model="salaryDialog" max-width="400">
            <v-card>
                <v-card-title>Pay Salary - {{ selectedEmployee?.name }}</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="paySalary">
                        <v-text-field v-model.number="salaryForm.amount" label="Amount" type="number" required></v-text-field>
                        <v-text-field v-model="salaryForm.month" label="Month (YYYY-MM)" placeholder="2025-01" required></v-text-field>
                        <v-text-field v-model="salaryForm.payment_date" label="Payment Date" type="date" required></v-text-field>
                        <v-textarea v-model="salaryForm.note" label="Note" rows="2"></v-textarea>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="salaryDialog = false">Cancel</v-btn>
                    <v-btn color="success" @click="paySalary" :loading="payingSalary">Pay</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Advance Dialog -->
        <v-dialog v-model="advanceDialog" max-width="400">
            <v-card>
                <v-card-title>Give Advance - {{ selectedEmployee?.name }}</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="giveAdvance">
                        <v-text-field v-model.number="advanceForm.amount" label="Amount" type="number" required></v-text-field>
                        <v-text-field v-model="advanceForm.date" label="Date" type="date" required></v-text-field>
                        <v-textarea v-model="advanceForm.reason" label="Reason" rows="2"></v-textarea>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="advanceDialog = false">Cancel</v-btn>
                    <v-btn color="warning" @click="giveAdvance" :loading="givingAdvance">Give</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- History Dialog -->
        <v-dialog v-model="historyDialog" max-width="800">
            <v-card>
                <v-card-title>
                    Payment History - {{ selectedEmployee?.name }}
                </v-card-title>
                <v-card-text>
                    <v-tabs v-model="historyTab" color="primary">
                        <v-tab value="salaries">Salaries ({{ salaryHistory.length }})</v-tab>
                        <v-tab value="advances">Advances ({{ advanceHistory.length }})</v-tab>
                    </v-tabs>

                    <v-window v-model="historyTab">
                        <!-- Salary History -->
                        <v-window-item value="salaries">
                            <v-table density="compact" class="mt-4">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th>Amount</th>
                                        <th>Payment Date</th>
                                        <th>Note</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="salary in salaryHistory" :key="salary.id">
                                        <td>{{ salary.month }}</td>
                                        <td>৳{{ formatNumber(salary.amount) }}</td>
                                        <td>{{ salary.payment_date }}</td>
                                        <td>{{ salary.note || '-' }}</td>
                                        <td>
                                            <v-btn icon size="x-small" color="primary" @click="editSalary(salary)" title="Edit">
                                                <v-icon size="small">mdi-pencil</v-icon>
                                            </v-btn>
                                            <v-btn icon size="x-small" color="error" @click="confirmDeleteSalary(salary)" title="Delete">
                                                <v-icon size="small">mdi-delete</v-icon>
                                            </v-btn>
                                        </td>
                                    </tr>
                                    <tr v-if="salaryHistory.length === 0">
                                        <td colspan="5" class="text-center text-grey">No salary records found</td>
                                    </tr>
                                </tbody>
                                <tfoot v-if="salaryHistory.length > 0">
                                    <tr class="font-weight-bold">
                                        <td>Total</td>
                                        <td>৳{{ formatNumber(totalSalary) }}</td>
                                        <td colspan="3"></td>
                                    </tr>
                                </tfoot>
                            </v-table>
                        </v-window-item>

                        <!-- Advance History -->
                        <v-window-item value="advances">
                            <v-table density="compact" class="mt-4">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Reason</th>
                                        <th>Deducted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="advance in advanceHistory" :key="advance.id">
                                        <td>{{ formatDate(advance.date) }}</td>
                                        <td>৳{{ formatNumber(advance.amount) }}</td>
                                        <td>{{ advance.reason || '-' }}</td>
                                        <td>
                                            <v-chip :color="advance.is_deducted ? 'success' : 'warning'" size="x-small">
                                                {{ advance.is_deducted ? 'Yes' : 'No' }}
                                            </v-chip>
                                        </td>
                                        <td>
                                            <v-btn icon size="x-small" color="primary" @click="editAdvance(advance)" title="Edit">
                                                <v-icon size="small">mdi-pencil</v-icon>
                                            </v-btn>
                                            <v-btn icon size="x-small" color="error" @click="confirmDeleteAdvance(advance)" title="Delete">
                                                <v-icon size="small">mdi-delete</v-icon>
                                            </v-btn>
                                        </td>
                                    </tr>
                                    <tr v-if="advanceHistory.length === 0">
                                        <td colspan="5" class="text-center text-grey">No advance records found</td>
                                    </tr>
                                </tbody>
                                <tfoot v-if="advanceHistory.length > 0">
                                    <tr class="font-weight-bold">
                                        <td>Total</td>
                                        <td>৳{{ formatNumber(totalAdvance) }}</td>
                                        <td colspan="3"></td>
                                    </tr>
                                </tfoot>
                            </v-table>
                        </v-window-item>
                    </v-window>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="historyDialog = false">Close</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Edit Salary Dialog -->
        <v-dialog v-model="editSalaryDialog" max-width="400">
            <v-card>
                <v-card-title>Edit Salary</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="updateSalary">
                        <v-text-field v-model.number="editSalaryForm.amount" label="Amount" type="number" required></v-text-field>
                        <v-text-field v-model="editSalaryForm.month" label="Month (YYYY-MM)" placeholder="2025-01" required></v-text-field>
                        <v-text-field v-model="editSalaryForm.payment_date" label="Payment Date" type="date" required></v-text-field>
                        <v-textarea v-model="editSalaryForm.note" label="Note" rows="2"></v-textarea>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="editSalaryDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="updateSalary" :loading="updatingSalary">Update</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Edit Advance Dialog -->
        <v-dialog v-model="editAdvanceDialog" max-width="400">
            <v-card>
                <v-card-title>Edit Advance</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="updateAdvance">
                        <v-text-field v-model.number="editAdvanceForm.amount" label="Amount" type="number" required></v-text-field>
                        <v-text-field v-model="editAdvanceForm.date" label="Date" type="date" required></v-text-field>
                        <v-textarea v-model="editAdvanceForm.reason" label="Reason" rows="2"></v-textarea>
                        <v-switch v-model="editAdvanceForm.is_deducted" label="Is Deducted" color="success"></v-switch>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="editAdvanceDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="updateAdvance" :loading="updatingAdvance">Update</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Salary Confirmation Dialog -->
        <v-dialog v-model="deleteSalaryDialog" max-width="400">
            <v-card>
                <v-card-title class="text-error">Delete Salary</v-card-title>
                <v-card-text>
                    Are you sure you want to delete this salary payment of <strong>৳{{ formatNumber(selectedSalaryForDelete?.amount) }}</strong>?
                    <br>
                    This action cannot be undone.
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="deleteSalaryDialog = false">Cancel</v-btn>
                    <v-btn color="error" @click="deleteSalary" :loading="deletingSalary">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Advance Confirmation Dialog -->
        <v-dialog v-model="deleteAdvanceDialog" max-width="400">
            <v-card>
                <v-card-title class="text-error">Delete Advance</v-card-title>
                <v-card-text>
                    Are you sure you want to delete this advance of <strong>৳{{ formatNumber(selectedAdvanceForDelete?.amount) }}</strong>?
                    <br>
                    This action cannot be undone.
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="deleteAdvanceDialog = false">Cancel</v-btn>
                    <v-btn color="error" @click="deleteAdvance" :loading="deletingAdvance">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- All Summary Dialog -->
        <v-dialog v-model="summaryDialog" max-width="900">
            <v-card>
                <v-card-title class="d-flex align-center">
                    <v-icon class="mr-2">mdi-chart-box</v-icon>
                    All Employees - Salary & Advance History
                </v-card-title>
                <v-card-text>
                    <!-- Date Filters -->
                    <v-row class="mb-4">
                        <v-col cols="12" md="4">
                            <v-text-field
                                v-model="filterMonth"
                                label="Filter by Month (YYYY-MM)"
                                placeholder="2025-01"
                                clearable
                                density="compact"
                                hide-details
                            ></v-text-field>
                        </v-col>
                        <v-col cols="12" md="4">
                            <v-select
                                v-model="filterEmployee"
                                :items="employeeFilterOptions"
                                item-title="name"
                                item-value="id"
                                label="Filter by Employee"
                                clearable
                                density="compact"
                                hide-details
                            ></v-select>
                        </v-col>
                        <v-col cols="12" md="4">
                            <v-btn color="primary" @click="applyFilters" block>
                                <v-icon left>mdi-filter</v-icon>
                                Apply Filter
                            </v-btn>
                        </v-col>
                    </v-row>

                    <v-row class="mb-4">
                        <v-col cols="6" md="3">
                            <v-card color="success" variant="outlined" class="text-center pa-3">
                                <div class="text-h6 font-weight-bold">৳{{ formatNumber(allTotalSalaryPaid) }}</div>
                                <div class="text-caption">Total Salary Paid</div>
                            </v-card>
                        </v-col>
                        <v-col cols="6" md="3">
                            <v-card color="warning" variant="outlined" class="text-center pa-3">
                                <div class="text-h6 font-weight-bold">৳{{ formatNumber(allTotalAdvanceGiven) }}</div>
                                <div class="text-caption">Total Advance Given</div>
                            </v-card>
                        </v-col>
                        <v-col cols="6" md="3">
                            <v-card color="info" variant="outlined" class="text-center pa-3">
                                <div class="text-h6 font-weight-bold">{{ allSalaries.length }}</div>
                                <div class="text-caption">Salary Payments</div>
                            </v-card>
                        </v-col>
                        <v-col cols="6" md="3">
                            <v-card color="primary" variant="outlined" class="text-center pa-3">
                                <div class="text-h6 font-weight-bold">{{ allAdvances.length }}</div>
                                <div class="text-caption">Advance Payments</div>
                            </v-card>
                        </v-col>
                    </v-row>

                    <v-tabs v-model="summaryTab" color="primary">
                        <v-tab value="salaries">All Salaries</v-tab>
                        <v-tab value="advances">All Advances</v-tab>
                    </v-tabs>

                    <v-window v-model="summaryTab">
                        <v-window-item value="salaries">
                            <v-data-table
                                :headers="salaryHeaders"
                                :items="allSalaries"
                                density="compact"
                                class="mt-2"
                            >
                                <template v-slot:item.amount="{ item }">
                                    ৳{{ formatNumber(item.amount) }}
                                </template>
                            </v-data-table>
                        </v-window-item>
                        <v-window-item value="advances">
                            <v-data-table
                                :headers="advanceHeaders"
                                :items="allAdvances"
                                density="compact"
                                class="mt-2"
                            >
                                <template v-slot:item.amount="{ item }">
                                    ৳{{ formatNumber(item.amount) }}
                                </template>
                                <template v-slot:item.is_deducted="{ item }">
                                    <v-chip :color="item.is_deducted ? 'success' : 'warning'" size="x-small">
                                        {{ item.is_deducted ? 'Yes' : 'No' }}
                                    </v-chip>
                                </template>
                            </v-data-table>
                        </v-window-item>
                    </v-window>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="summaryDialog = false">Close</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import api from '../../services/api'

const employees = ref([])
const projects = ref([])
const loading = ref(false)
const dialog = ref(false)
const salaryDialog = ref(false)
const advanceDialog = ref(false)
const historyDialog = ref(false)
const summaryDialog = ref(false)
const historyTab = ref('salaries')
const summaryTab = ref('salaries')
const editMode = ref(false)
const selectedEmployee = ref(null)
const saving = ref(false)
const payingSalary = ref(false)
const givingAdvance = ref(false)
const salaryHistory = ref([])
const advanceHistory = ref([])
const allSalaries = ref([])
const allAdvances = ref([])
const filterMonth = ref('')
const filterEmployee = ref(null)
const allSalariesOriginal = ref([])
const allAdvancesOriginal = ref([])

// Edit/Delete states
const editSalaryDialog = ref(false)
const editAdvanceDialog = ref(false)
const deleteSalaryDialog = ref(false)
const deleteAdvanceDialog = ref(false)
const selectedSalaryForDelete = ref(null)
const selectedAdvanceForDelete = ref(null)
const updatingSalary = ref(false)
const updatingAdvance = ref(false)
const deletingSalary = ref(false)
const deletingAdvance = ref(false)
const editSalaryForm = reactive({ id: null, amount: 0, month: '', payment_date: '', note: '' })
const editAdvanceForm = reactive({ id: null, amount: 0, date: '', reason: '', is_deducted: false })

// Group by project
const groupBy = [{ key: 'project.name', order: 'asc' }]

const headers = [
    { title: 'ID', key: 'id', width: '60px' },
    { title: 'Name', key: 'name' },
    { title: 'Position', key: 'position' },
    { title: 'Phone', key: 'phone' },
    { title: 'Salary', key: 'salary_amount' },
    { title: 'Total Paid', key: 'total_paid' },
    { title: 'This Month Due', key: 'current_month_due' },
    { title: 'Status', key: 'is_active' },
    { title: 'Actions', key: 'actions', sortable: false },
]

const salaryHeaders = [
    { title: 'Employee', key: 'employee.name' },
    { title: 'Month', key: 'month' },
    { title: 'Amount', key: 'amount' },
    { title: 'Payment Date', key: 'payment_date' },
    { title: 'Note', key: 'note' },
]

const advanceHeaders = [
    { title: 'Employee', key: 'employee.name' },
    { title: 'Date', key: 'date' },
    { title: 'Amount', key: 'amount' },
    { title: 'Reason', key: 'reason' },
    { title: 'Deducted', key: 'is_deducted' },
]

const form = reactive({ project_id: null, name: '', phone: '', position: '', salary_amount: 0, joining_date: '', is_active: true })
const salaryForm = reactive({ amount: 0, month: '', payment_date: new Date().toISOString().split('T')[0], note: '' })
const advanceForm = reactive({ amount: 0, date: new Date().toISOString().split('T')[0], reason: '' })

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD')

// Format date
const formatDate = (dateStr) => {
    if (!dateStr) return '-'
    const date = new Date(dateStr)
    return date.toLocaleDateString('en-CA') // YYYY-MM-DD format
}

// Get group total salary
const getGroupTotalSalary = (items) => {
    return items.filter(e => e.is_active).reduce((sum, e) => sum + Number(e.salary_amount || 0), 0)
}

// Computed for summary cards
const totalEmployees = computed(() => employees.value.length)
const activeEmployees = computed(() => employees.value.filter(e => e.is_active).length)
const totalMonthlySalary = computed(() => employees.value.filter(e => e.is_active).reduce((sum, e) => sum + Number(e.salary_amount || 0), 0))

// Computed for individual history
const totalSalary = computed(() => salaryHistory.value.reduce((sum, s) => sum + Number(s.amount), 0))
const totalAdvance = computed(() => advanceHistory.value.reduce((sum, a) => sum + Number(a.amount), 0))

// Computed for all summary
const allTotalSalaryPaid = computed(() => allSalaries.value.reduce((sum, s) => sum + Number(s.amount), 0))
const allTotalAdvanceGiven = computed(() => allAdvances.value.reduce((sum, a) => sum + Number(a.amount), 0))

// Employee filter options
const employeeFilterOptions = computed(() => employees.value.map(e => ({ id: e.id, name: e.name })))

// Apply filters function
const applyFilters = () => {
    let filteredSalaries = [...allSalariesOriginal.value]
    let filteredAdvances = [...allAdvancesOriginal.value]

    // Filter by month
    if (filterMonth.value) {
        filteredSalaries = filteredSalaries.filter(s => s.month === filterMonth.value || s.payment_date?.startsWith(filterMonth.value))
        filteredAdvances = filteredAdvances.filter(a => a.date?.startsWith(filterMonth.value))
    }

    // Filter by employee
    if (filterEmployee.value) {
        filteredSalaries = filteredSalaries.filter(s => s.employee_id === filterEmployee.value)
        filteredAdvances = filteredAdvances.filter(a => a.employee_id === filterEmployee.value)
    }

    allSalaries.value = filteredSalaries
    allAdvances.value = filteredAdvances
}

const fetchEmployees = async () => {
    loading.value = true
    try {
        const response = await api.get('/employees')
        employees.value = response.data
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

const openDialog = (employee = null) => {
    editMode.value = !!employee
    selectedEmployee.value = employee
    if (employee) {
        Object.assign(form, employee)
    } else {
        Object.assign(form, { project_id: null, name: '', phone: '', position: '', salary_amount: 0, joining_date: '', is_active: true })
    }
    dialog.value = true
}

const saveEmployee = async () => {
    saving.value = true
    try {
        if (editMode.value) {
            await api.put(`/employees/${selectedEmployee.value.id}`, form)
        } else {
            await api.post('/employees', form)
        }
        dialog.value = false
        fetchEmployees()
    } catch (error) {
        console.error('Error:', error)
    }
    saving.value = false
}

const openSalaryDialog = (employee) => {
    selectedEmployee.value = employee
    salaryForm.amount = employee.salary_amount
    salaryForm.month = new Date().toISOString().slice(0, 7)
    salaryDialog.value = true
}

const paySalary = async () => {
    payingSalary.value = true
    try {
        await api.post(`/employees/${selectedEmployee.value.id}/salary`, salaryForm)
        salaryDialog.value = false
        fetchEmployees()
        alert('Salary paid successfully!')
    } catch (error) {
        console.error('Error:', error)
        alert('Error paying salary')
    }
    payingSalary.value = false
}

const openAdvanceDialog = (employee) => {
    selectedEmployee.value = employee
    advanceForm.amount = 0
    advanceDialog.value = true
}

const giveAdvance = async () => {
    givingAdvance.value = true
    try {
        await api.post(`/employees/${selectedEmployee.value.id}/advance`, advanceForm)
        advanceDialog.value = false
        fetchEmployees()
        alert('Advance given successfully!')
    } catch (error) {
        console.error('Error:', error)
        alert('Error giving advance')
    }
    givingAdvance.value = false
}

const viewHistory = async (employee) => {
    selectedEmployee.value = employee
    historyTab.value = 'salaries'
    salaryHistory.value = []
    advanceHistory.value = []
    historyDialog.value = true

    try {
        const [salariesRes, advancesRes] = await Promise.all([
            api.get(`/employees/${employee.id}/salaries`),
            api.get(`/employees/${employee.id}/advances`)
        ])
        salaryHistory.value = salariesRes.data
        advanceHistory.value = advancesRes.data
    } catch (error) {
        console.error('Error fetching history:', error)
    }
}

const showAllSummary = async () => {
    summaryTab.value = 'salaries'
    allSalaries.value = []
    allAdvances.value = []
    allSalariesOriginal.value = []
    allAdvancesOriginal.value = []
    filterMonth.value = ''
    filterEmployee.value = null
    summaryDialog.value = true

    try {
        const [salariesRes, advancesRes] = await Promise.all([
            api.get('/salaries'),
            api.get('/advances')
        ])
        allSalaries.value = salariesRes.data
        allAdvances.value = advancesRes.data
        allSalariesOriginal.value = salariesRes.data
        allAdvancesOriginal.value = advancesRes.data
    } catch (error) {
        console.error('Error fetching summary:', error)
    }
}

// Edit Salary
const editSalary = (salary) => {
    Object.assign(editSalaryForm, {
        id: salary.id,
        amount: salary.amount,
        month: salary.month,
        payment_date: salary.payment_date,
        note: salary.note || ''
    })
    editSalaryDialog.value = true
}

// Update Salary
const updateSalary = async () => {
    updatingSalary.value = true
    try {
        await api.put(`/salaries/${editSalaryForm.id}`, editSalaryForm)
        editSalaryDialog.value = false
        // Refresh history
        if (selectedEmployee.value) {
            const salariesRes = await api.get(`/employees/${selectedEmployee.value.id}/salaries`)
            salaryHistory.value = salariesRes.data
        }
        fetchEmployees()
        alert('Salary updated successfully!')
    } catch (error) {
        console.error('Error updating salary:', error)
        alert('Error updating salary')
    }
    updatingSalary.value = false
}

// Confirm Delete Salary
const confirmDeleteSalary = (salary) => {
    selectedSalaryForDelete.value = salary
    deleteSalaryDialog.value = true
}

// Delete Salary
const deleteSalary = async () => {
    deletingSalary.value = true
    try {
        await api.delete(`/salaries/${selectedSalaryForDelete.value.id}`)
        deleteSalaryDialog.value = false
        // Refresh history
        if (selectedEmployee.value) {
            const salariesRes = await api.get(`/employees/${selectedEmployee.value.id}/salaries`)
            salaryHistory.value = salariesRes.data
        }
        fetchEmployees()
        alert('Salary deleted successfully!')
    } catch (error) {
        console.error('Error deleting salary:', error)
        alert('Error deleting salary')
    }
    deletingSalary.value = false
}

// Edit Advance
const editAdvance = (advance) => {
    Object.assign(editAdvanceForm, {
        id: advance.id,
        amount: advance.amount,
        date: advance.date,
        reason: advance.reason || '',
        is_deducted: advance.is_deducted || false
    })
    editAdvanceDialog.value = true
}

// Update Advance
const updateAdvance = async () => {
    updatingAdvance.value = true
    try {
        await api.put(`/advances/${editAdvanceForm.id}`, editAdvanceForm)
        editAdvanceDialog.value = false
        // Refresh history
        if (selectedEmployee.value) {
            const advancesRes = await api.get(`/employees/${selectedEmployee.value.id}/advances`)
            advanceHistory.value = advancesRes.data
        }
        fetchEmployees()
        alert('Advance updated successfully!')
    } catch (error) {
        console.error('Error updating advance:', error)
        alert('Error updating advance')
    }
    updatingAdvance.value = false
}

// Confirm Delete Advance
const confirmDeleteAdvance = (advance) => {
    selectedAdvanceForDelete.value = advance
    deleteAdvanceDialog.value = true
}

// Delete Advance
const deleteAdvance = async () => {
    deletingAdvance.value = true
    try {
        await api.delete(`/advances/${selectedAdvanceForDelete.value.id}`)
        deleteAdvanceDialog.value = false
        // Refresh history
        if (selectedEmployee.value) {
            const advancesRes = await api.get(`/employees/${selectedEmployee.value.id}/advances`)
            advanceHistory.value = advancesRes.data
        }
        fetchEmployees()
        alert('Advance deleted successfully!')
    } catch (error) {
        console.error('Error deleting advance:', error)
        alert('Error deleting advance')
    }
    deletingAdvance.value = false
}

onMounted(() => {
    fetchEmployees()
    fetchProjects()
})
</script>
