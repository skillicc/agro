<template>
    <div>
        <div class="d-flex flex-wrap justify-space-between align-center mb-4 ga-2">
            <h1 class="text-h5 text-sm-h4">Employees</h1>
            <div class="d-flex flex-wrap ga-2">
                <v-btn color="info" :size="$vuetify.display.xs ? 'small' : 'default'" @click="openCalculateELDialog()">
                    <v-icon left>mdi-calculator</v-icon>
                    Calculate EL
                </v-btn>
                <v-btn color="primary" :size="$vuetify.display.xs ? 'small' : 'default'" @click="openDialog()">
                    <v-icon left>mdi-plus</v-icon>
                    Add Employee
                </v-btn>
            </div>
        </div>

        <!-- Summary Cards -->
        <v-row class="mb-4">
            <v-col cols="6" sm="4" lg="2">
                <v-card color="primary" variant="tonal">
                    <v-card-text class="d-flex align-center pa-3">
                        <v-icon size="32" class="mr-2">mdi-account-group</v-icon>
                        <div>
                            <div class="text-h6 font-weight-bold">{{ totalEmployees }}</div>
                            <div class="text-caption">Total</div>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" sm="4" lg="2">
                <v-card color="blue" variant="tonal">
                    <v-card-text class="d-flex align-center pa-3">
                        <v-icon size="32" class="mr-2">mdi-account-tie</v-icon>
                        <div>
                            <div class="text-h6 font-weight-bold">{{ regularCount }}</div>
                            <div class="text-caption">Regular</div>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" sm="4" lg="2">
                <v-card color="orange" variant="tonal">
                    <v-card-text class="d-flex align-center pa-3">
                        <v-icon size="32" class="mr-2">mdi-account-clock</v-icon>
                        <div>
                            <div class="text-h6 font-weight-bold">{{ contractualCount }}</div>
                            <div class="text-caption">Contractual</div>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" sm="4" lg="2">
                <v-card color="success" variant="tonal">
                    <v-card-text class="d-flex align-center pa-3">
                        <v-icon size="32" class="mr-2">mdi-account-check</v-icon>
                        <div>
                            <div class="text-h6 font-weight-bold">{{ activeEmployees }}</div>
                            <div class="text-caption">Active</div>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" sm="4" lg="2">
                <v-card color="info" variant="tonal">
                    <v-card-text class="d-flex align-center pa-3">
                        <v-icon size="32" class="mr-2">mdi-cash-multiple</v-icon>
                        <div>
                            <div class="text-h6 font-weight-bold">৳{{ formatNumber(totalMonthlySalary) }}</div>
                            <div class="text-caption">Monthly</div>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" sm="4" lg="2">
                <v-card color="warning" variant="tonal" @click="showAllSummary" style="cursor: pointer;">
                    <v-card-text class="d-flex align-center pa-3">
                        <v-icon size="32" class="mr-2">mdi-chart-bar</v-icon>
                        <div>
                            <div class="text-h6 font-weight-bold">View</div>
                            <div class="text-caption">Summary</div>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <!-- Filter Row -->
        <v-row class="mb-4">
            <v-col cols="12" sm="4" md="3">
                <v-select
                    v-model="filterType"
                    :items="employeeTypes"
                    item-title="label"
                    item-value="value"
                    label="Employee Type"
                    clearable
                    density="compact"
                    hide-details
                    @update:model-value="fetchEmployees"
                ></v-select>
            </v-col>
        </v-row>

        <v-card>
            <v-card-text>
                <v-data-table
                    :headers="headers"
                    :items="sortedEmployees"
                    :loading="loading"
                    items-per-page="-1"
                >
                    <template v-slot:item.sl="{ index }">
                        {{ index + 1 }}
                    </template>
                    <template v-slot:item.name_position="{ item }">
                        <div>
                            <div class="font-weight-medium">{{ item.name }}</div>
                            <div class="text-caption text-grey">{{ item.position || '-' }}</div>
                        </div>
                    </template>
                    <template v-slot:item.employee_type="{ item }">
                        <v-chip :color="item.employee_type === 'regular' ? 'blue' : 'orange'" size="small">
                            {{ item.employee_type === 'regular' ? 'Regular' : 'Contractual' }}
                        </v-chip>
                    </template>
                    <template v-slot:item.salary_display="{ item }">
                        <div v-if="item.employee_type === 'regular'">
                            ৳{{ formatNumber(item.salary_amount) }}/month
                        </div>
                        <div v-else>
                            ৳{{ formatNumber(item.daily_rate) }}/day
                            <div class="text-caption text-grey" v-if="item.present_days !== null">
                                {{ item.present_days }} days = ৳{{ formatNumber(item.calculated_salary) }}
                            </div>
                        </div>
                    </template>
                    <template v-slot:item.earn_leave="{ item }">
                        <span
                            :style="item.absent_dates && item.absent_dates.length > 0 ? 'cursor: help; text-decoration: underline dotted;' : ''"
                            :title="item.absent_dates && item.absent_dates.length > 0 ? 'Absent: ' + item.absent_dates.join(', ') : ''"
                        >{{ item.earn_leave || 0 }}</span>
                    </template>
                    <template v-slot:item.absent_count="{ item }">
                        <v-chip
                            :color="item.absent_dates && item.absent_dates.length > 0 ? 'error' : 'success'"
                            size="small"
                            :style="item.absent_dates && item.absent_dates.length > 0 ? 'cursor: help' : ''"
                            :title="item.absent_dates && item.absent_dates.length > 0 ? 'Absent: ' + item.absent_dates.join(', ') : 'No absences'"
                        >
                            {{ item.absent_dates ? item.absent_dates.length : 0 }}
                        </v-chip>
                    </template>
                    <template v-slot:item.total_advance_paid="{ item }">
                        <span class="text-warning font-weight-bold">৳{{ formatNumber(item.total_advance_paid) }}</span>
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
                        <v-btn v-if="item.employee_type === 'regular'" icon size="small" color="purple" @click="openAdjustSalaryDialog(item)" title="Adjust Salary">
                            <v-icon>mdi-trending-up</v-icon>
                        </v-btn>
                        <v-btn icon size="small" color="warning" @click="openBonusDialog(item)" title="Bonus/Incentive">
                            <v-icon>mdi-gift</v-icon>
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
                        <v-select
                            v-model="form.employee_type"
                            :items="employeeTypes"
                            item-title="label"
                            item-value="value"
                            label="Employee Type"
                            required
                        ></v-select>
                        <v-select
                            v-model="form.project_id"
                            :items="projects"
                            item-title="name"
                            item-value="id"
                            :label="form.employee_type === 'regular' ? 'Project *' : 'Project (Optional)'"
                            :clearable="form.employee_type === 'contractual'"
                        ></v-select>
                        <v-text-field v-model="form.name" label="Name" required></v-text-field>
                        <v-text-field v-model="form.phone" label="Phone"></v-text-field>
                        <v-text-field v-model="form.position" label="Position"></v-text-field>
                        <v-text-field
                            v-if="form.employee_type === 'regular'"
                            v-model.number="form.salary_amount"
                            label="Monthly Salary"
                            type="number"
                            required
                        ></v-text-field>
                        <v-text-field
                            v-if="form.employee_type === 'contractual'"
                            v-model.number="form.daily_rate"
                            label="Daily Rate"
                            type="number"
                            required
                        ></v-text-field>
                        <v-text-field v-model="form.joining_date" label="Joining Date" type="date"></v-text-field>
                        <v-text-field v-model.number="form.earn_leave" label="Earn Leave (EL)" type="number" step="0.5"></v-text-field>
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
        <v-dialog v-model="salaryDialog" max-width="450">
            <v-card>
                <v-card-title>Pay Salary - {{ selectedEmployee?.name }}</v-card-title>
                <v-card-text>
                    <!-- Contractual Employee Calculation Info -->
                    <v-alert
                        v-if="selectedEmployee?.employee_type === 'contractual'"
                        type="info"
                        density="compact"
                        class="mb-4"
                    >
                        <div class="font-weight-bold">Contractual Employee</div>
                        <div v-if="salaryCalculation">
                            {{ salaryCalculation.present_days }} days x ৳{{ formatNumber(selectedEmployee.daily_rate) }} =
                            <strong>৳{{ formatNumber(salaryCalculation.calculated_salary) }}</strong>
                        </div>
                        <div v-else class="text-caption">Select month to calculate</div>
                    </v-alert>

                    <v-form @submit.prevent="paySalary">
                        <v-select
                            v-model="salaryForm.month"
                            :items="salaryMonthOptions"
                            item-title="label"
                            item-value="value"
                            label="Salary For"
                            required
                            @update:model-value="calculateContractualSalary"
                        ></v-select>
                        <v-text-field
                            v-model.number="salaryForm.amount"
                            label="Amount"
                            type="number"
                            required
                            :hint="selectedEmployee?.employee_type === 'contractual' ? 'Auto-calculated based on attendance' : ''"
                        ></v-text-field>
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

        <!-- History Dialog -->
        <v-dialog v-model="historyDialog" :max-width="$vuetify.display.xs ? '100%' : '800'" :fullscreen="$vuetify.display.xs">
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
                                        <th>Salary For</th>
                                        <th>Amount</th>
                                        <th>Paid On</th>
                                        <th>Note</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="salary in salaryHistory" :key="salary.id">
                                        <td>{{ formatMonthShort(salary.month) }}</td>
                                        <td>৳{{ formatNumber(salary.amount) }}</td>
                                        <td>{{ formatDate(salary.payment_date) }}</td>
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
                        <v-select
                            v-model="editSalaryForm.month"
                            :items="salaryMonthOptions"
                            item-title="label"
                            item-value="value"
                            label="Salary For"
                            required
                        ></v-select>
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

        <!-- Calculate EL Dialog -->
        <v-dialog v-model="calculateELDialog" :max-width="$vuetify.display.xs ? '100%' : '800'" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title>
                    <v-icon class="mr-2">mdi-calculator</v-icon>
                    Calculate Earn Leave (EL)
                </v-card-title>
                <v-card-text>
                    <v-alert type="info" density="compact" class="mb-4">
                        <div><strong>Rules:</strong></div>
                        <div>• Regular employees: 5 days leave/month</div>
                        <div>• Administration: 6 days leave/month</div>
                        <div>• If absent > allowance: EL decreases</div>
                        <div>• If absent < allowance: EL increases</div>
                    </v-alert>
                    <v-select
                        v-model="elMonth"
                        :items="elMonthOptions"
                        item-title="label"
                        item-value="value"
                        label="Select Month"
                        required
                    ></v-select>

                    <!-- Results Table -->
                    <div v-if="elResults.length > 0" class="mt-4">
                        <h4 class="mb-2">Calculation Results:</h4>
                        <div class="el-results-container">
                            <v-table density="compact">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Project</th>
                                        <th class="text-center">Allowance</th>
                                        <th class="text-center">Absent</th>
                                        <th class="text-center">Adjustment</th>
                                        <th class="text-center">Old EL</th>
                                        <th class="text-center">New EL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="result in elResults" :key="result.employee_id">
                                        <td>{{ result.employee_name }}</td>
                                        <td>{{ result.project || '-' }}</td>
                                        <td class="text-center">{{ result.leave_allowance }}</td>
                                        <td class="text-center">{{ result.absent_days }}</td>
                                        <td class="text-center">
                                            <v-chip :color="result.el_adjustment >= 0 ? 'success' : 'error'" size="x-small">
                                                {{ result.el_adjustment >= 0 ? '+' : '' }}{{ result.el_adjustment }}
                                            </v-chip>
                                        </td>
                                        <td class="text-center">{{ result.old_el }}</td>
                                        <td class="text-center font-weight-bold">{{ result.new_el }}</td>
                                    </tr>
                                </tbody>
                            </v-table>
                        </div>
                    </div>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="calculateELDialog = false">Close</v-btn>
                    <v-btn color="info" @click="calculateEL" :loading="calculatingEL" :disabled="!elMonth || elResults.length > 0">
                        Calculate
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Adjust Salary Dialog -->
        <v-dialog v-model="adjustSalaryDialog" max-width="450">
            <v-card>
                <v-card-title>
                    <v-icon class="mr-2">mdi-trending-up</v-icon>
                    Adjust Salary - {{ selectedEmployee?.name }}
                </v-card-title>
                <v-card-text>
                    <v-alert type="info" density="compact" class="mb-4">
                        Current Salary: <strong>৳{{ formatNumber(selectedEmployee?.salary_amount) }}</strong>
                    </v-alert>
                    <v-form @submit.prevent="adjustSalary">
                        <v-select
                            v-model="adjustSalaryForm.type"
                            :items="[{ value: 'increase', title: 'Increase' }, { value: 'decrease', title: 'Decrease' }]"
                            item-title="title"
                            item-value="value"
                            label="Type"
                            required
                        ></v-select>
                        <v-text-field
                            v-model.number="adjustSalaryForm.new_salary"
                            label="New Salary"
                            type="number"
                            required
                            :hint="adjustSalaryForm.new_salary && selectedEmployee ? 'Change: ৳' + formatNumber(Math.abs(adjustSalaryForm.new_salary - selectedEmployee.salary_amount)) : ''"
                            persistent-hint
                        ></v-text-field>
                        <v-text-field v-model="adjustSalaryForm.effective_date" label="Effective Date" type="date" required></v-text-field>
                        <v-textarea v-model="adjustSalaryForm.reason" label="Reason" rows="2"></v-textarea>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="adjustSalaryDialog = false">Cancel</v-btn>
                    <v-btn color="purple" @click="adjustSalary" :loading="adjustingSalary">Adjust</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Bonus/Incentive Dialog -->
        <v-dialog v-model="bonusDialog" max-width="450">
            <v-card>
                <v-card-title>
                    <v-icon class="mr-2">mdi-gift</v-icon>
                    Bonus/Incentive - {{ selectedEmployee?.name }}
                </v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="giveBonus">
                        <v-select
                            v-model="bonusForm.type"
                            :items="[{ value: 'bonus', title: 'Bonus' }, { value: 'incentive', title: 'Incentive' }]"
                            item-title="title"
                            item-value="value"
                            label="Type"
                            required
                        ></v-select>
                        <v-text-field v-model.number="bonusForm.amount" label="Amount" type="number" required></v-text-field>
                        <v-text-field v-model="bonusForm.date" label="Date" type="date" required></v-text-field>
                        <v-textarea v-model="bonusForm.reason" label="Reason" rows="2"></v-textarea>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="bonusDialog = false">Cancel</v-btn>
                    <v-btn color="warning" @click="giveBonus" :loading="givingBonus">Give</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- All Summary Dialog -->
        <v-dialog v-model="summaryDialog" :max-width="$vuetify.display.xs ? '100%' : '900'" :fullscreen="$vuetify.display.xs">
            <v-card>
                <v-card-title class="d-flex align-center">
                    <v-icon class="mr-2">mdi-chart-box</v-icon>
                    All Employees - Salary & Advance History
                </v-card-title>
                <v-card-text>
                    <!-- Date Filters -->
                    <v-row class="mb-4">
                        <v-col cols="12" sm="6" lg="4">
                            <v-text-field
                                v-model="filterMonth"
                                label="Filter by Month (YYYY-MM)"
                                placeholder="2025-01"
                                clearable
                                density="compact"
                                hide-details
                            ></v-text-field>
                        </v-col>
                        <v-col cols="12" sm="6" lg="4">
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
                        <v-col cols="12" sm="6" lg="4">
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
                        <v-tab value="matrix">Salary Matrix</v-tab>
                        <v-tab value="salaries">All Salaries</v-tab>
                        <v-tab value="advances">All Advances</v-tab>
                    </v-tabs>

                    <v-window v-model="summaryTab">
                        <!-- Salary Matrix -->
                        <v-window-item value="matrix">
                            <div class="mt-4">
                                <div class="d-flex flex-wrap ga-2 ga-sm-4 mb-3">
                                    <div class="d-flex align-center"><div class="status-box bg-success mr-2"></div> Paid</div>
                                    <div class="d-flex align-center"><div class="status-box bg-error mr-2"></div> Not Paid</div>
                                    <div class="d-flex align-center"><div class="status-box bg-warning mr-2"></div> Pending</div>
                                    <div class="d-flex align-center"><div class="status-box bg-grey mr-2"></div> Not Joined</div>
                                </div>
                                <div class="salary-matrix-container">
                                    <table class="salary-matrix-table">
                                        <thead>
                                            <tr>
                                                <th class="employee-col">Employee</th>
                                                <th v-for="month in salaryMatrixMonths" :key="month">
                                                    {{ formatMonthShort(month) }}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="emp in activeEmployeesList" :key="emp.id">
                                                <td class="employee-col">
                                                    <div class="emp-name">{{ emp.name }}</div>
                                                    <div class="emp-project">{{ emp.project?.name || '-' }}</div>
                                                </td>
                                                <td
                                                    v-for="month in salaryMatrixMonths"
                                                    :key="month"
                                                    :class="getSalaryStatusClass(emp.id, month)"
                                                    :title="getSalaryStatus(emp.id, month).tooltip"
                                                >
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </v-window-item>
                        <v-window-item value="salaries">
                            <v-data-table
                                :headers="salaryHeaders"
                                :items="allSalaries"
                                density="compact"
                                class="mt-2"
                            >
                                <template v-slot:item.month="{ item }">
                                    {{ formatMonthShort(item.month) }}
                                </template>
                                <template v-slot:item.amount="{ item }">
                                    ৳{{ formatNumber(item.amount) }}
                                </template>
                                <template v-slot:item.payment_date="{ item }">
                                    {{ formatDate(item.payment_date) }}
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
import { useDisplay } from 'vuetify'
import api from '../../services/api'

const { smAndUp, mdAndUp, lgAndUp } = useDisplay()

const employees = ref([])
const projects = ref([])
const loading = ref(false)
const dialog = ref(false)
const salaryDialog = ref(false)
const historyDialog = ref(false)
const summaryDialog = ref(false)
const historyTab = ref('salaries')
const summaryTab = ref('matrix')
const editMode = ref(false)
const selectedEmployee = ref(null)
const saving = ref(false)
const payingSalary = ref(false)
const salaryHistory = ref([])
const advanceHistory = ref([])
const allSalaries = ref([])
const allAdvances = ref([])
const filterMonth = ref('')
const filterEmployee = ref(null)
const allSalariesOriginal = ref([])
const allAdvancesOriginal = ref([])
const salaryCalculation = ref(null)

// Calculate EL states
const calculateELDialog = ref(false)
const calculatingEL = ref(false)
const elMonth = ref('')
const elResults = ref([])

// Salary Adjustment states
const adjustSalaryDialog = ref(false)
const adjustingSalary = ref(false)
const adjustSalaryForm = reactive({ type: 'increase', new_salary: 0, effective_date: new Date().toISOString().split('T')[0], reason: '' })

// Bonus/Incentive states
const bonusDialog = ref(false)
const givingBonus = ref(false)
const bonusForm = reactive({ type: 'bonus', amount: 0, date: new Date().toISOString().split('T')[0], reason: '' })

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

const headers = computed(() => {
    if (lgAndUp.value) {
        // Large Desktop (1280px+): show all columns
        return [
            { title: '#', key: 'sl', width: '50px' },
            { title: 'Name', key: 'name_position' },
            { title: 'Type', key: 'employee_type' },
            { title: 'Project', key: 'project.name' },
            { title: 'Salary', key: 'salary_display' },
            { title: 'EL', key: 'earn_leave' },
            { title: 'Absent', key: 'absent_count' },
            { title: 'Advance', key: 'total_advance_paid' },
            { title: 'Paid', key: 'total_paid' },
            { title: 'Due', key: 'current_month_due' },
            { title: 'Status', key: 'is_active' },
            { title: 'Actions', key: 'actions', sortable: false },
        ]
    }
    if (mdAndUp.value) {
        // Medium Desktop/Laptop (960px-1279px): fewer columns
        return [
            { title: '#', key: 'sl', width: '50px' },
            { title: 'Name', key: 'name_position' },
            { title: 'Salary', key: 'salary_display' },
            { title: 'Advance', key: 'total_advance_paid' },
            { title: 'Paid', key: 'total_paid' },
            { title: 'Due', key: 'current_month_due' },
            { title: 'Status', key: 'is_active' },
            { title: 'Actions', key: 'actions', sortable: false },
        ]
    }
    // Mobile/Tablet: minimal columns
    return [
        { title: '#', key: 'sl', width: '50px' },
        { title: 'Name', key: 'name_position' },
        { title: 'Salary', key: 'salary_display' },
        { title: 'Due', key: 'current_month_due' },
        { title: 'Actions', key: 'actions', sortable: false },
    ]
})

const employeeTypes = [
    { value: 'regular', label: 'Regular' },
    { value: 'contractual', label: 'Contractual' },
]

const filterType = ref(null)

// Sort employees by salary (highest first)
const sortedEmployees = computed(() => {
    return [...employees.value].sort((a, b) => {
        // Get salary amount - for regular use salary_amount, for contractual use calculated_salary or daily_rate
        const getSalary = (emp) => {
            if (emp.employee_type === 'regular') {
                return Number(emp.salary_amount || 0)
            } else {
                // For contractual, use calculated_salary if available, otherwise use daily_rate * 30 as estimate
                return Number(emp.calculated_salary || (emp.daily_rate * 30) || 0)
            }
        }

        const salaryA = getSalary(a)
        const salaryB = getSalary(b)

        // Sort descending (highest salary first)
        return salaryB - salaryA
    })
})

const salaryHeaders = [
    { title: 'Employee', key: 'employee.name' },
    { title: 'Salary For', key: 'month' },
    { title: 'Amount', key: 'amount' },
    { title: 'Paid On', key: 'payment_date' },
    { title: 'Note', key: 'note' },
]

const advanceHeaders = [
    { title: 'Employee', key: 'employee.name' },
    { title: 'Date', key: 'date' },
    { title: 'Amount', key: 'amount' },
    { title: 'Reason', key: 'reason' },
    { title: 'Deducted', key: 'is_deducted' },
]

const form = reactive({ project_id: null, employee_type: 'regular', name: '', phone: '', position: '', salary_amount: 0, daily_rate: 0, joining_date: '', earn_leave: 0, is_active: true })
const salaryForm = reactive({ amount: 0, month: '', payment_date: new Date().toISOString().split('T')[0], note: '' })

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD')

// Generate salary month options (last 24 months + current month for backdating)
const salaryMonthOptions = computed(() => {
    const months = []
    const now = new Date()
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']

    // Add last 24 months including current (allows backdating up to 2 years)
    for (let i = 23; i >= 0; i--) {
        const d = new Date(now.getFullYear(), now.getMonth() - i, 1)
        const year = d.getFullYear()
        const month = String(d.getMonth() + 1).padStart(2, '0')
        const value = `${year}-${month}` // YYYY-MM
        const label = `${monthNames[d.getMonth()]}'${String(year).slice(2)} (${value})`
        months.push({ value, label })
    }

    return months
})

// Generate EL month options (last 12 months)
const elMonthOptions = computed(() => {
    const months = []
    const now = new Date()
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']

    for (let i = 0; i < 12; i++) {
        const d = new Date(now.getFullYear(), now.getMonth() - i, 1)
        const year = d.getFullYear()
        const month = String(d.getMonth() + 1).padStart(2, '0')
        const value = `${year}-${month}` // YYYY-MM
        const label = `${monthNames[d.getMonth()]}'${String(year).slice(2)} (${value})`
        months.push({ value, label })
    }

    return months
})

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
const regularCount = computed(() => employees.value.filter(e => e.employee_type === 'regular').length)
const contractualCount = computed(() => employees.value.filter(e => e.employee_type === 'contractual').length)
const totalMonthlySalary = computed(() => employees.value.filter(e => e.is_active).reduce((sum, e) => sum + Number(e.calculated_salary || e.salary_amount || 0), 0))

// Computed for individual history
const totalSalary = computed(() => salaryHistory.value.reduce((sum, s) => sum + Number(s.amount), 0))
const totalAdvance = computed(() => advanceHistory.value.reduce((sum, a) => sum + Number(a.amount), 0))

// Computed for all summary
const allTotalSalaryPaid = computed(() => allSalaries.value.reduce((sum, s) => sum + Number(s.amount), 0))
const allTotalAdvanceGiven = computed(() => allAdvances.value.reduce((sum, a) => sum + Number(a.amount), 0))

// Employee filter options
const employeeFilterOptions = computed(() => employees.value.map(e => ({ id: e.id, name: e.name })))

// Active employees list for matrix
const activeEmployeesList = computed(() => employees.value.filter(e => e.is_active))

// Generate last 12 months for salary matrix
const salaryMatrixMonths = computed(() => {
    const months = []
    const now = new Date()
    for (let i = 11; i >= 0; i--) {
        const d = new Date(now.getFullYear(), now.getMonth() - i, 1)
        months.push(d.toISOString().slice(0, 7)) // YYYY-MM format
    }
    return months
})

// Format month short (e.g., "Dec'24")
const formatMonthShort = (monthStr) => {
    const [year, month] = monthStr.split('-')
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
    return `${monthNames[parseInt(month) - 1]}'${year.slice(2)}`
}

// Get salary status for an employee in a specific month
const getSalaryStatus = (employeeId, month) => {
    const emp = employees.value.find(e => e.id === employeeId)

    // Check if employee was joined before this month
    if (emp?.joining_date) {
        const joiningMonth = emp.joining_date.slice(0, 7)
        if (month < joiningMonth) {
            return { paid: false, status: 'not-joined', tooltip: 'Not yet joined' }
        }
    }

    // Check if salary was paid for this month
    const salary = allSalariesOriginal.value.find(s => s.employee_id === employeeId && s.month === month)

    if (salary) {
        return { paid: true, status: 'paid', tooltip: `Paid: ৳${formatNumber(salary.amount)}` }
    }

    // Salary is paid in the next month, so current month's salary is not due yet
    // e.g., Jan salary is paid in Feb, so if we're in Jan, Jan salary is "pending"
    const now = new Date()
    const currentMonth = now.toISOString().slice(0, 7) // YYYY-MM

    if (month >= currentMonth) {
        return { paid: false, status: 'pending', tooltip: 'Salary not due yet' }
    }

    return { paid: false, status: 'not-paid', tooltip: 'Not paid' }
}

// Get CSS class for salary status
const getSalaryStatusClass = (employeeId, month) => {
    const status = getSalaryStatus(employeeId, month)
    return `status-cell status-${status.status}`
}

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
        const params = {}
        if (filterType.value) {
            params.employee_type = filterType.value
        }
        const response = await api.get('/employees', { params })
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
        Object.assign(form, {
            ...employee,
            joining_date: employee.joining_date ? employee.joining_date.split('T')[0] : ''
        })
    } else {
        Object.assign(form, { project_id: null, employee_type: 'regular', name: '', phone: '', position: '', salary_amount: 0, daily_rate: 0, joining_date: '', earn_leave: 0, is_active: true })
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

const openSalaryDialog = async (employee) => {
    selectedEmployee.value = employee
    salaryForm.month = new Date().toISOString().slice(0, 7)
    salaryCalculation.value = null

    if (employee.employee_type === 'contractual') {
        // Calculate salary for contractual employee
        await calculateContractualSalary()
    } else {
        salaryForm.amount = employee.salary_amount
    }
    salaryDialog.value = true
}

const calculateContractualSalary = async () => {
    if (!selectedEmployee.value || selectedEmployee.value.employee_type !== 'contractual') {
        return
    }
    if (!salaryForm.month || !/^\d{4}-\d{2}$/.test(salaryForm.month)) {
        return
    }

    try {
        const response = await api.get(`/employees/${selectedEmployee.value.id}/calculate-salary`, {
            params: { month: salaryForm.month }
        })
        salaryCalculation.value = response.data
        salaryForm.amount = response.data.calculated_salary
    } catch (error) {
        console.error('Error calculating salary:', error)
    }
}

const paySalary = async () => {
    payingSalary.value = true
    try {
        const response = await api.post(`/employees/${selectedEmployee.value.id}/salary`, salaryForm)
        salaryDialog.value = false
        fetchEmployees()
        alert(response.data.message || 'Salary paid successfully!')
    } catch (error) {
        console.error('Error:', error)
        if (error.response?.data?.message) {
            alert(error.response.data.message)
        } else {
            alert('Error paying salary')
        }
    }
    payingSalary.value = false
}

// Open Adjust Salary Dialog
const openAdjustSalaryDialog = (employee) => {
    selectedEmployee.value = employee
    adjustSalaryForm.type = 'increase'
    adjustSalaryForm.new_salary = employee.salary_amount
    adjustSalaryForm.effective_date = new Date().toISOString().split('T')[0]
    adjustSalaryForm.reason = ''
    adjustSalaryDialog.value = true
}

// Adjust Salary
const adjustSalary = async () => {
    if (!adjustSalaryForm.new_salary || adjustSalaryForm.new_salary === selectedEmployee.value.salary_amount) {
        alert('Please enter a different salary amount')
        return
    }

    adjustingSalary.value = true
    try {
        const response = await api.post(`/employees/${selectedEmployee.value.id}/adjust-salary`, adjustSalaryForm)
        adjustSalaryDialog.value = false
        fetchEmployees()
        alert(response.data.message)
    } catch (error) {
        console.error('Error:', error)
        alert(error.response?.data?.message || 'Error adjusting salary')
    }
    adjustingSalary.value = false
}

// Open Bonus Dialog
const openBonusDialog = (employee) => {
    selectedEmployee.value = employee
    bonusForm.type = 'bonus'
    bonusForm.amount = 0
    bonusForm.date = new Date().toISOString().split('T')[0]
    bonusForm.reason = ''
    bonusDialog.value = true
}

// Give Bonus/Incentive
const giveBonus = async () => {
    if (!bonusForm.amount || bonusForm.amount <= 0) {
        alert('Please enter a valid amount')
        return
    }

    givingBonus.value = true
    try {
        const response = await api.post(`/employees/${selectedEmployee.value.id}/bonus`, bonusForm)
        bonusDialog.value = false
        fetchEmployees()
        alert(response.data.message)
    } catch (error) {
        console.error('Error:', error)
        alert(error.response?.data?.message || 'Error giving bonus/incentive')
    }
    givingBonus.value = false
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

// Open Calculate EL Dialog
const openCalculateELDialog = () => {
    elMonth.value = ''
    elResults.value = []
    calculateELDialog.value = true
}

// Calculate EL
const calculateEL = async () => {
    if (!elMonth.value) {
        alert('Please select a month')
        return
    }

    calculatingEL.value = true
    try {
        const response = await api.post('/employees/calculate-el', { month: elMonth.value })
        elResults.value = response.data.results
        fetchEmployees() // Refresh employee list to update EL values
        alert(response.data.message)
    } catch (error) {
        console.error('Error calculating EL:', error)
        const errorMsg = error.response?.data?.message || error.message || 'Unknown error'
        alert('Error calculating EL: ' + errorMsg)
    }
    calculatingEL.value = false
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

<style scoped>
.status-box {
    width: 20px;
    height: 20px;
    border-radius: 4px;
}
.bg-success { background-color: #4CAF50; }
.bg-error { background-color: #F44336; }
.bg-warning { background-color: #FF9800; }
.bg-grey { background-color: #4CAF50; }

.salary-matrix-container {
    overflow-x: auto;
    max-height: 400px;
    overflow-y: auto;
}

.salary-matrix-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.salary-matrix-table th,
.salary-matrix-table td {
    border: 1px solid #e0e0e0;
    padding: 8px 4px;
    text-align: center;
    white-space: nowrap;
}

.salary-matrix-table th {
    background: #f5f5f5;
    font-weight: 600;
    position: sticky;
    top: 0;
    z-index: 2;
}

.salary-matrix-table .employee-col {
    text-align: left;
    min-width: 150px;
    position: sticky;
    left: 0;
    background: #fff;
    z-index: 1;
}

.salary-matrix-table th.employee-col {
    z-index: 3;
}

.emp-name {
    font-weight: 600;
}

.emp-project {
    font-size: 11px;
    color: #666;
}

.status-cell {
    width: 60px;
    min-width: 60px;
}

.status-paid {
    background-color: #4CAF50 !important;
}

.status-not-paid {
    background-color: #F44336 !important;
}

.status-not-joined {
    background-color: #4CAF50 !important;
}

.status-pending {
    background-color: #FF9800 !important;
}

.el-results-container {
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
}

.el-results-container thead th {
    position: sticky;
    top: 0;
    background: #f5f5f5;
    z-index: 1;
}
</style>
