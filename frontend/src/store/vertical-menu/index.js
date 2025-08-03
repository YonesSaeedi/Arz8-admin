import {$themeConfig} from '@themeConfig'

export default {
    namespaced: true,
    state: {
        isVerticalMenuCollapsed: $themeConfig.layout.menu.isCollapsed,
        notification: {
            count_sum: 0,
            transaction_crypto: null,
            transaction_internal: null,
            users: null,
            cardbank: null,
        },
    },
    getters: {},
    mutations: {
        UPDATE_VERTICAL_MENU_COLLAPSED(state, val) {
            state.isVerticalMenuCollapsed = val
        },
        UPDATE_PENDING_NOTIFICATION(state, val) {
            state.notification = val
        },
    },
    actions: {},
}
