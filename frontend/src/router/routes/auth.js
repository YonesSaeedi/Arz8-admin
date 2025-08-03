export default [

    {
        path: '/login',
        name: 'auth-login',
        component: () => import('@/views/auth/Login.vue'),
        meta: {
            layout: 'full',
            resource: 'Auth',
            redirectIfLoggedIn: true,
        },
    },
    {
        path: '/login/2fa-google',
        name: 'auth-2fa-google',
        component: () => import('@/views/auth/TwoFaGoogle'),
        meta: {
            layout: 'full',
            resource: 'Auth',
            redirectIfLoggedIn: true,
        },
    },
    {
        path: '/login/2fa-sms',
        name: 'auth-2fa-sms',
        component: () => import('@/views/auth/TwoFaSms'),
        meta: {
            layout: 'full',
            resource: 'Auth',
            redirectIfLoggedIn: true,
        },
    },

]
