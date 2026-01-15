import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

// Layouts
import MainLayout from '../layouts/MainLayout.vue'
import AuthLayout from '../layouts/AuthLayout.vue'

// Auth Views
import Login from '../views/auth/Login.vue'

// Dashboard
import Dashboard from '../views/Dashboard.vue'

// Projects
import Projects from '../views/projects/Index.vue'
import ProjectShow from '../views/projects/Show.vue'

// Suppliers
import Suppliers from '../views/suppliers/Index.vue'

// Customers
import Customers from '../views/customers/Index.vue'

// Products
import Products from '../views/products/Index.vue'

// Expenses
import Expenses from '../views/expenses/Index.vue'

// Purchases
import Purchases from '../views/purchases/Index.vue'
import PurchaseCreate from '../views/purchases/Create.vue'
import PurchaseEdit from '../views/purchases/Edit.vue'

// Sales
import Sales from '../views/sales/Index.vue'
import SaleCreate from '../views/sales/Create.vue'
import SaleEdit from '../views/sales/Edit.vue'

// Employees
import Employees from '../views/employees/Index.vue'

// Attendance
import Attendance from '../views/attendance/Index.vue'
import AttendanceDetails from '../views/attendance/Details.vue'

// Reports
import Reports from '../views/reports/Index.vue'

// Users (Admin)
import Users from '../views/users/Index.vue'

// Warehouses
import Warehouses from '../views/warehouses/Index.vue'

// Invest, Loan & Liability
import InvestLoanLiability from '../views/invest-loan-liability/Index.vue'

// Profile
import Profile from '../views/profile/Index.vue'

const routes = [
    {
        path: '/',
        component: AuthLayout,
        children: [
            {
                path: 'login',
                name: 'login',
                component: Login,
            },
        ],
    },
    {
        path: '/',
        component: MainLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                redirect: '/dashboard',
            },
            {
                path: 'dashboard',
                name: 'dashboard',
                component: Dashboard,
            },
            {
                path: 'projects',
                name: 'projects',
                component: Projects,
            },
            {
                path: 'projects/:id',
                name: 'project-show',
                component: ProjectShow,
            },
            {
                path: 'suppliers',
                name: 'suppliers',
                component: Suppliers,
            },
            {
                path: 'customers',
                name: 'customers',
                component: Customers,
            },
            {
                path: 'products',
                name: 'products',
                component: Products,
            },
            {
                path: 'expenses',
                name: 'expenses',
                component: Expenses,
            },
            {
                path: 'purchases',
                name: 'purchases',
                component: Purchases,
            },
            {
                path: 'purchases/create',
                name: 'purchase-create',
                component: PurchaseCreate,
            },
            {
                path: 'purchases/:id/edit',
                name: 'purchase-edit',
                component: PurchaseEdit,
            },
            {
                path: 'sales',
                name: 'sales',
                component: Sales,
            },
            {
                path: 'sales/create',
                name: 'sale-create',
                component: SaleCreate,
            },
            {
                path: 'sales/:id/edit',
                name: 'sale-edit',
                component: SaleEdit,
            },
            {
                path: 'employees',
                name: 'employees',
                component: Employees,
            },
            {
                path: 'attendance',
                name: 'attendance',
                component: Attendance,
            },
            {
                path: 'attendance/details',
                name: 'attendance-details',
                component: AttendanceDetails,
            },
            {
                path: 'reports',
                name: 'reports',
                component: Reports,
            },
            {
                path: 'warehouses',
                name: 'warehouses',
                component: Warehouses,
            },
            {
                path: 'invest-loan-liability',
                name: 'invest-loan-liability',
                component: InvestLoanLiability,
            },
            {
                path: 'users',
                name: 'users',
                component: Users,
                meta: { requiresAdmin: true },
            },
            {
                path: 'profile',
                name: 'profile',
                component: Profile,
            },
        ],
    },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

router.beforeEach((to, from, next) => {
    const authStore = useAuthStore()

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        next({ name: 'login' })
    } else if (to.meta.requiresAdmin && !authStore.isAdmin) {
        next({ name: 'dashboard' })
    } else if (to.name === 'login' && authStore.isAuthenticated) {
        next({ name: 'dashboard' })
    } else {
        next()
    }
})

export default router
