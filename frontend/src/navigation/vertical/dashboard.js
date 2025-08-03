export default [
    {
        title: 'داشبورد',
        route: 'dashboard',
        icon: 'HomeIcon',
    },

    {
        title: 'اقدامات',
        icon: 'ActivityIcon',
        tagVariant: 'light-warning',
        children: [
            {
                title: 'تراکنش های رمز ارز',
                route: 'action-transaction-crypto',
                tagVariant: 'light-warning',
            },
            {
                title: 'تراکنش های داخلی',
                route: 'action-transaction-internal',
                tagVariant: 'light-success',
            },
            {
                title: 'کاربر در انتظار',
                route: 'action-users',
                tagVariant: 'light-danger',
            },
            {
                title: 'کارت بانکی در انتظار',
                route: 'action-cardbank',
                tagVariant: 'light-primary',
            },
            {
                title: 'تیکت های در انتظار',
                route: 'action-tickets',
                tagVariant: 'light-info',
            },
        ],
    },
]
