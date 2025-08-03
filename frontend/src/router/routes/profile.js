export default [
    {
        path: '/profile',
        name: 'profile',
        component: () => import('@/views/profile/Profile'),
    },
    {
        path: '/profile/2fa',
        name: 'profile-2fa',
        component: () => import('@/views/profile/TwoFA'),
    },
]
