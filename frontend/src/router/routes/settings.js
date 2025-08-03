export default [

    {
        path: '/cryptocurrency',
        name: 'cryptos',
        component: () => import('@/views/settings/cryptocurrency/crypto-list/CryptoList'),
    },

    {
        path: '/cryptocurrency/balance',
        name: 'cryptos-balance',
        component: () => import('@/views/settings/cryptocurrency/crypto-balance-list/CryptoList'),
    },
    {
        path: '/cryptocurrency/little',
        name: 'cryptos-little',
        component: () => import('@/views/settings/cryptocurrency/crypto-little-list/CryptoList'),
    },
    {
        path: '/cryptocurrency/wallets',
        name: 'cryptos-wallets',
        component: () => import('@/views/settings/cryptocurrency/crypto-wallets-list/CryptoList'),
    },

    {
        path: '/cryptocurrency/auto-trade',
        name: 'cryptos-auto-trade',
        component: () => import('@/views/settings/cryptocurrency/crypto-trade-auto/TradeList'),
    },

    {
        path: '/cryptocurrency/:id',
        name: 'st-crypto-view',
        component: () => import('@/views/settings/cryptocurrency/crypto/Crypto'),
        meta: {
            breadcrumb: [
                {
                    text: 'تنظیمات رمز ارز',
                    to: '/cryptocurrency'
                },
                {
                    text: 'جزئیات ارز',
                    active: true,
                },
            ],
        }
    },


    {
        path: '/networks',
        name: 'networks',
        component: () => import('@/views/settings/network/NetworkList'),
    },




    {
        path: '/markets',
        name: 'markets',
        component: () => import('@/views/settings/markets/market-list/MarketList'),
    },

    {
        path: '/markets/:id',
        name: 'markets-view',
        component: () => import('@/views/settings/markets/market/Market'),
        meta: {
            breadcrumb: [
                {
                    text: 'لیست مارکت ها',
                    to: '/markets'
                },
                {
                    text: 'جزئیات مارکت',
                    active: true,
                },
            ],
        }
    },

    {
        path: '/notification',
        name: 'notification',
        component: () => import('@/views/settings/notifications/notification-list/NotificationList'),
        meta: {
        }
    },


    {
        path: '/settings',
        name: 'settings',
        component: () => import('@/views/settings/setting/Setting'),
    },

    // Audit
    {
        path: '/audit',
        name: 'audit',
        component: () => import('@/views/settings/audit/orders/OrdersCrypto'),
    },
    {
        path: '/audit/digital',
        name: 'audit-digital',
        component: () => import('@/views/settings/audit/orders/OrdersDigital'),
    },
    {
        path: '/audit/wage',
        name: 'audit-wage',
        component: () => import('@/views/settings/audit/wage-list/WageList'),
    },
    {
        path: '/audit/factor',
        name: 'audit-factor',
        component: () => import('@/views/settings/audit/factor-list/FactorList.vue'),
    },
    {
        path: '/audit/factor/:id',
        name: 'audit-factor-single',
        component: () => import('@/views/settings/audit/factor-list/Factor.vue'),
        meta: {
            layout: 'full',
            resource: 'Auth',
            action: 'read',
        },
    },

    // Admins
    {
        path: '/admins',
        name: 'admins',
        component: () => import('@/views/settings/admins/admins-list/AdminList'),
    },
    {
        path: '/admins/admin/:id',
        name: 'admin-view',
        component: () => import('@/views/settings/admins/admin/Admin'),
    },
    {
        path: '/admins/activity',
        name: 'admins-log',
        component: () => import('@/views/settings/admins/admins-log-list/LogList'),
    },


    // Reports
    {
        path: '/reports',
        name: 'reports-users',
        component: () => import('@/views/reports/users/Users'),
    },
    {
        path: '/reports-orders',
        name: 'reports-orders',
        component: () => import('@/views/reports/orders/charts/Charts'),
    },
    {
        path: '/reports-trades',
        name: 'reports-trades',
        component: () => import('@/views/reports/trades/charts/Charts'),
    },
    {
        path: '/reports-referral',
        name: 'reports-referral',
        component: () => import('@/views/reports/referral/charts/Charts'),
    },
    {
        path: '/reports-payment-gateway',
        name: 'reports-payment-gateway',
        component: () => import('@/views/reports/payment-gateway/InternalList'),
    },
    {
        path: '/reports-shaparak',
        name: 'reports-shaparak',
        component: () => import('@/views/reports/shaparak/Shaparak.vue'),
    },
]
