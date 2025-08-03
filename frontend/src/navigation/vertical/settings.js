export default [
    {
        header: 'تنظیمات',
    },

    {
        title: 'رمز ارز ها',
        icon: 'SlackIcon',
        children: [
            {
                title: 'رمز ارزها',
                route: 'cryptos',
            },
            {
                title: 'موجودی رمز ارزها',
                route: 'cryptos-balance',
            },
            {
                title: 'موجودی های اندک',
                route: 'cryptos-little',
            },
            {
                title: 'ولت های رمز ارز',
                route: 'cryptos-wallets',
            },
            {
                title: 'ترید های اتوماتیک',
                route: 'cryptos-auto-trade',
            },
            {
                title: 'شبکه های رمز ارز',
                route: 'networks',
            },
            {
                title: 'بازار ها',
                route: 'markets',
            },
        ],
    },

    {
        title: 'تنظیمات ',
        route: 'settings',
        icon: 'SettingsIcon',
    },
    {
        title: 'اعلانات ',
        route: 'notification',
        icon: 'BellIcon',
    },

    {
        title: 'حسابرسی',
        icon: 'DatabaseIcon',
        children: [
            {
                title: ' کارمزد ها',
                route: 'audit-wage',
            },
            {
                title: 'سفارشات رمزارز',
                route: 'audit',
            },
            {
                title: 'سفارشات دلاری',
                route: 'audit-digital',
            },
            {
                title: 'فاکتور حسابرسی',
                route: 'audit-factor',
            },
        ],
    },
    {
        title: 'گزارشات',
        icon: 'PieChartIcon',
        children: [
            {
                title: 'پرداخت یاری',
                route: 'reports-payment-gateway',
            },
            {
                title: 'کاربران',
                route: 'reports-users',
            },

            {
                title: 'سفارشات',
                route: 'reports-orders',
            },
            {
                title: 'معاملات',
                route: 'reports-trades',
            },
            {
                title: 'بازاریابی',
                route: 'reports-referral',
            },
            {
                title: 'شاپرک',
                route: 'reports-shaparak',
            },
        ],
    },
    {
        title: 'ادمین ها',
        icon: 'UserCheckIcon',
        children: [
            {
                title: 'لیست ادمین ها',
                route: 'admins',
            },
            {
                title: 'فعالیت ادمین ها',
                route: 'admins-log',
            },
        ],
    },


]
