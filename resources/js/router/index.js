import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

// Only import layouts and login eagerly (needed immediately)
import MainLayout from '../layouts/MainLayout.vue'
import AuthLayout from '../layouts/AuthLayout.vue'
import Login from '../views/auth/Login.vue'

// Lazy load all other views (loaded on demand)
const Dashboard = () => import('../views/Dashboard.vue')
const Projects = () => import('../views/projects/Index.vue')
const ProjectShow = () => import('../views/projects/Show.vue')
const Suppliers = () => import('../views/suppliers/Index.vue')
const Customers = () => import('../views/customers/Index.vue')
const Products = () => import('../views/products/Index.vue')
const Expenses = () => import('../views/expenses/Index.vue')
const Purchases = () => import('../views/purchases/Index.vue')
const PurchaseCreate = () => import('../views/purchases/Create.vue')
const PurchaseEdit = () => import('../views/purchases/Edit.vue')
const Sales = () => import('../views/sales/Index.vue')
const SaleCreate = () => import('../views/sales/Create.vue')
const SaleEdit = () => import('../views/sales/Edit.vue')
const Employees = () => import('../views/employees/Index.vue')
const Attendance = () => import('../views/attendance/Index.vue')
const AttendanceDetails = () => import('../views/attendance/Details.vue')
const Reports = () => import('../views/reports/Index.vue')
const Users = () => import('../views/users/Index.vue')
const Warehouses = () => import('../views/warehouses/Index.vue')
const InvestLoanLiability = () => import('../views/invest-loan-liability/Index.vue')
const Profile = () => import('../views/profile/Index.vue')

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
