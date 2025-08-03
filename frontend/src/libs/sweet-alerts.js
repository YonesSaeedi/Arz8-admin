import Vue from 'vue'
import VueSweetalert2 from 'vue-sweetalert2'
import '@core/scss/vue/libs/vue-sweetalert.scss'

import i18n from '@/libs/i18n'
/*
if (i18n.locale == 'fa') {
    const options = {
        confirmButtonText:'تایید',
        cancelButtonText:'انصراف',
        customClass: {
            confirmButton: 'btn btn-primary',
            cancelButton: 'btn btn-outline-danger ml-1',
        },
    }
}
 */

const options = {

    confirmButtonText:'تایید',
    cancelButtonText:'انصراف',
    customClass: {
        confirmButton: 'btn btn-primary px-2',
        cancelButton: 'btn btn-outline-dark ml-1',
    },
}
Vue.use(VueSweetalert2, options)
