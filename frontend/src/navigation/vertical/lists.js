export default [
    {
        header: 'اطلاعات و لیست ها',
    },
    {
        title: 'کاربران',
        route: 'users',
        icon: 'UsersIcon',
    },
    {
        title: 'سفارشات',
        route: 'orders',
        icon: 'LayersIcon',
    },
    {
        title: 'معاملات',
        route: 'trades',
        icon: 'PercentIcon',
    },
    {
        title: 'تیکت ها',
        route: 'tickets',
        icon: 'MessageCircleIcon',
    },
    {
        title: 'ترانکنش های کیف پول',
        icon: 'FileTextIcon',
        children: [
            {
                title: 'کیف پول داخلی',
                route: 'tr-internal',
            },
            {
                title: 'کیف پول رمز ارز',
                route: 'tr-crypto',
            },
        ],
    },

    {
        title: 'کارتهای بانکی',
        route: 'card-bank',
        icon: 'CreditCardIcon',
    },

    {
        title: 'رفرال یا زیر مجموعه',
        icon: 'UserPlusIcon',
        children: [
            {
                title: 'کاربران معرفی شده',
                route: 'referral-users',
            },
            {
                title: 'تراکنش های رفرال',
                route: 'referral-transaction',
            },
        ],
    },

    {
        title: 'جوایز و هدایا',
        icon: 'GiftIcon',
        children: [
            {
                title: 'کد تخفیف',
                route: 'gift',
            },
            {
                title: 'گردونه شانس',
                route: 'gift-wheel',
            },
            {
                title: 'کارت هدیه',
                route: 'gift-card',
            },
        ]
    },

    {
        title: 'تسویه پایا اتوماتیک',
        route: 'payment-gateway',
        icon: 'ZapIcon',
    },
    {
        title: 'سایر لیست ها',
        icon: 'ListIcon',
        children: [
            {
                title: 'تاریخچه ورود کاربران',
                route: 'login-history',
            },
            {
                title: 'تاریخچه تماس کاربران',
                route: 'call-history',
            },
            {
                title: 'ولت های اختصاصی',
                route: 'wallets-users',
            },
            {
                title: 'شناسه های واریز',
                route: 'deposit-id',
            },
        ],
    },

]
