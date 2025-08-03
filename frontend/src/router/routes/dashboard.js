export default [
    {
        path: '/dashboard',
        name: 'dashboard',
        component: () => import('@/views/dashboard/dashboard/Dashboard.vue'),
    },

    {
        path: '/action/transaction-crypto',
        name: 'action-transaction-crypto',
        component: () => import('@/views/lists/actions/CryptoTransaction'),
    },
    {
        path: '/action/transaction-internal',
        name: 'action-transaction-internal',
        component: () => import('@/views/lists/actions/InternalTransaction'),
    },
    {
        path: '/action/users',
        name: 'action-users',
        component: () => import('@/views/lists/actions/Users'),
    },
    {
        path: '/action/cardbank',
        name: 'action-cardbank',
        component: () => import('@/views/lists/actions/Cardbank'),
    },
    {
        path: '/action/tickets',
        name: 'action-tickets',
        component: () => import('@/views/lists/actions/Tickets'),
    },
]
