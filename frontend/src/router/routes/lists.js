export default [

    {
        path: '/users',
        name: 'users',
        component: () => import('@/views/lists/users/users-list/UsersList'),
    },
    {
        path: '/users/:id',
        name: 'user-single',
        component: () => import('@/views/lists/users/user/User'),
    },

    {
        path: '/orders',
        name: 'orders',
        component: () => import('@/views/lists/orders/orders-list/OrdersList'),
    },
    {
        path: '/orders/:id',
        name: 'order-view',
        component: () => import('@/views/lists/orders/order/Order'),
        meta: {
            breadcrumb: [
                {
                    text: 'سفارشات',
                    to: '/orders'
                },
                {
                    text: 'جزئیات سفارش',
                    active: true,
                },
            ],
        }
    },



    {
        path: '/trades',
        name: 'trades',
        component: () => import('@/views/lists/trades/trades-list/TradesList'),
    },
    {
        path: '/trades/:id',
        name: 'trade-view',
        component: () => import('@/views/lists/trades/trade/Trade'),
        meta: {
            breadcrumb: [
                {
                    text: 'معاملات',
                    to: '/trades'
                },
                {
                    text: 'جزئیات معامله',
                    active: true,
                },
            ],
        }
    },

    {
        path: '/tickets',
        name: 'tickets',
        component: () => import('@/views/lists/tickets/tickets-list/TicketsList'),
    },
    {
        path: '/tickets/:id',
        name: 'ticket-view',
        component: () => import('@/views/lists/tickets/ticket/Ticket'),
        meta: {
            contentClass: 'chat-application',
            breadcrumb: [
                {
                    text: 'تیکت ها',
                    to: '/trades'
                },
                {
                    text: 'جزئیات تیکت',
                    active: true,
                },
            ],
        }
    },


    {
        path: '/transaction-internal',
        name: 'tr-internal',
        component: () => import('@/views/lists/transaction-internal/internal-list/InternalList'),
    },
    {
        path: '/transaction-internal/:id',
        name: 'tr-internal-view',
        component: () => import('@/views/lists/transaction-internal/Internal/Internal'),
        meta: {
            breadcrumb: [
                {
                    text: 'تراکنش های کیف پول داخلی',
                    to: '/transaction-internal'
                },
                {
                    text: 'جزئیات تراکنش',
                    active: true,
                },
            ],
        }
    },


    {
        path: '/transaction-crypto',
        name: 'tr-crypto',
        component: () => import('@/views/lists/transaction-crypto/crypto-list/CryptoList'),
    },
    {
        path: '/transaction-crypto/:id',
        name: 'tr-crypto-view',
        component: () => import('@/views/lists/transaction-crypto/crypto/Crypto'),
        meta: {
            breadcrumb: [
                {
                    text: 'تراکنش های رمز ارز',
                    to: '/transaction-crypto'
                },
                {
                    text: 'جزئیات تراکنش',
                    active: true,
                },
            ],
        }
    },


    {
        path: '/card-bank',
        name: 'card-bank',
        component: () => import('@/views/lists/card-bank/CardList'),
    },


    {
        path: '/referral',
        name: 'referral-users',
        component: () => import('@/views/lists/referral/referral-user-list/ReferralUsersList'),
    },
    {
        path: '/referral/transaction',
        name: 'referral-transaction',
        component: () => import('@/views/lists/referral/referral-transaction-list/ReferralTransactionList'),
    },

    {
        path: '/gift',
        name: 'gift',
        component: () => import('@/views/lists/gift/Gift'),
    },
    {
        path: '/gift/wheel',
        name: 'gift-wheel',
        component: () => import('@/views/lists/gift/wheel/WheelList.vue'),
    },
    {
        path: '/gift/wheel',
        name: 'gift-wheel',
        component: () => import('@/views/lists/gift/wheel/WheelList.vue'),
    },
    {
        path: '/gift/card',
        name: 'gift-card',
        component: () => import('@/views/lists/gift/gift-card/GiftList.vue'),
    },
    {
        path: '/payment-gateway',
        name: 'payment-gateway',
        component: () => import('@/views/lists/payment-gateway/WithdrawList'),
    },

    {
        path: '/login-history',
        name: 'login-history',
        component: () => import('@/views/lists/login-history/LoginHistoryList'),
    },



    {
        path: '/call-history',
        name: 'call-history',
        component: () => import('@/views/lists/call-history/CallHistoryList.vue'),
    },

    {
        path: '/wallets-users',
        name: 'wallets-users',
        component: () => import('@/views/lists/users-wallets/WalletsUsersList.vue'),
    },
    {
        path: '/deposit-id',
        name: 'deposit-id',
        component: () => import('@/views/lists/deposit-id/DepositIdList.vue'),
    },
]
