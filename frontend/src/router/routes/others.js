export default [
  {
    path: '/access-control',
    name: 'access-control',
    component: () => import('@/views/vuexy/extensions/acl/AccessControl.vue'),
    meta: {
      resource: 'ACL',
      action: 'read',
    },
  },
]
