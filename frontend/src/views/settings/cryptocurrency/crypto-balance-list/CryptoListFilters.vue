<template>
    <b-card-actions no-body action-collapse title="فیلترها" :collapsed="false">
        <b-card-body>
            <b-row>
                <b-col cols="12" md="3" class="mb-md-0 mb-2">
                    <label>وضعیت نمایش</label>
                    <v-select
                        :dir="!$store.state.appConfig.isRTL ? 'rtl' : 'ltr'"
                        :value="hideFilter"
                        :options="hideOptions"
                        class="w-100"
                        :reduce="val => val.value"
                        placeholder="همه"
                        @input="(val) => $emit('update:hideFilter', val)"
                    />
                </b-col>

                <b-col cols="12" md="3" class="mb-md-0 mb-2" >
                    <label>وضعیت خرید و فروش</label>
                    <v-select
                        :dir="!$store.state.appConfig.isRTL ? 'rtl' : 'ltr'"
                        :value="statusBuySellFilter"
                        :options="statusBuySellOptions"
                        class="w-100"
                        :reduce="val => val.value"
                        placeholder="همه"
                        @input="(val) => $emit('update:statusBuySellFilter', val)"
                    />
                </b-col>


                <b-col cols="12" md="3" class="mb-md-0 mb-2" >
                    <label>وضعیت برداشت</label>
                    <v-select
                        :dir="!$store.state.appConfig.isRTL ? 'rtl' : 'ltr'"
                        :value="withdrawFilter"
                        :options="withdrawOptions"
                        class="w-100"
                        :reduce="val => val.value"
                        placeholder="همه"
                        @input="(val) => $emit('update:withdrawFilter', val)"
                    />
                </b-col>


                <b-col cols="12" md="3" class="mb-md-0 mb-2 mb-2" >
                    <label>وضعیت واریز</label>
                    <v-select
                        :dir="!$store.state.appConfig.isRTL ? 'rtl' : 'ltr'"
                        :value="depositFilter"
                        :options="depositOptions"
                        class="w-100"
                        :reduce="val => val.value"
                        placeholder="همه"
                        @input="(val) => $emit('update:depositFilter', val)"
                    />
                </b-col>



                <b-col cols="12" md="3" class="mb-md-0 mb-2 mt-1" >
                    <label>صرافی</label>
                    <v-select
                        :dir="!$store.state.appConfig.isRTL ? 'rtl' : 'ltr'"
                        :value="exchangeFilter"
                        :options="exchangeOptions"
                        class="w-100"
                        :reduce="val => val.value"
                        placeholder="همه"
                        @input="(val) => $emit('update:exchangeFilter', val)"
                    />
                </b-col>

                <b-col cols="12" md="3" class="mb-md-0 mb-2 mt-1" >
                    <label>اکانت</label>
                    <v-select
                        :dir="!$store.state.appConfig.isRTL ? 'rtl' : 'ltr'"
                        :value="exchangeAccountFilter"
                        :options="exchangeAccountOptions"
                        class="w-100"
                        :reduce="val => val.value"
                        placeholder="همه"
                        @input="(val) => $emit('update:exchangeAccountFilter', val)"
                    />
                </b-col>


                <b-col cols="12" md="3" class="mb-md-0 mb-2 mt-1">
                    <label>شبکه</label>
                    <v-select
                        :dir="!$store.state.appConfig.isRTL ? 'rtl' : 'ltr'"
                        :value="networkFilter"
                        :options="networkOptions"
                        class="w-100"
                        :reduce="val => val.value"
                        placeholder="همه"
                        @input="(val) => $emit('update:networkFilter', val)"
                    />
                </b-col>

                <b-col cols="12" md="3" class="mb-md-0 mt-1" >
                    <label>سایر</label>
                    <v-select
                        :dir="!$store.state.appConfig.isRTL ? 'rtl' : 'ltr'"
                        :value="otherFilter"
                        :options="otherOptions"
                        class="w-100"
                        :reduce="val => val.value"
                        placeholder="همه"
                        @input="(val) => $emit('update:otherFilter', val)"
                    />
                </b-col>

                <!--                <b-col cols="12" md="3" class="mb-md-0 mb-2 mt-1">
                                    <label>موجودی بر اساس</label>
                                    <v-select
                                        :dir="!$store.state.appConfig.isRTL ? 'rtl' : 'ltr'"
                                        :value="balanceFilter"
                                        :options="balanceOptions"
                                        class="w-100"
                                        :reduce="val => val.value"
                                        :clearable="false"
                                        @input="(val) => $emit('update:balanceFilter', val)"
                                    />
                                </b-col>-->
            </b-row>
        </b-card-body>
    </b-card-actions>
</template>

<script>
import VuePersianDatetimePicker from 'vue-persian-datetime-picker'
import jalaliMmoment from "jalali-moment";

import BCardActions from '@core/components/b-card-actions/BCardActions.vue'
import {
    BCardHeader, BCardBody, BRow, BCol, BInputGroup, BInputGroupAppend, BFormGroup, BFormInput,
} from 'bootstrap-vue'
import vSelect from 'vue-select'

export default {
    data () {
        return {

        }
    },
    watch:{

    },
    components: {
        BRow,
        BCol,
        BCardActions,
        BCardHeader,
        BCardBody,
        BInputGroup,BFormGroup, BInputGroupAppend,BFormInput,
        vSelect,
        datePicker: VuePersianDatetimePicker,
    },
    props: {
        hideFilter: {
            type: [String, null],
            default: null,
        },
        statusBuySellFilter: {
            type: [String, null],
            default: null,
        },
        otherFilter: {
            type: [String, null],
            default: null,
        },
        withdrawFilter: {
            type: [String, null],
            default: null,
        },
        depositFilter: {
            type: [String, null],
            default: null,
        },
        exchangeFilter: {
            type: [String, null],
            default: null,
        },
        balanceFilter: {
            type: [String, null],
            default: null,
        },
        networkFilter: {
            type: [String, null],
            default: null,
        },
        exchangeAccountFilter: {
            type: [String, null],
            default: null,
        },
        hideOptions: {
            type: Array,
            required: true,
        },
        statusBuySellOptions: {
            type: Array,
            required: true,
        },
        otherOptions: {
            type: Array,
            required: true,
        },
        withdrawOptions: {
            type: Array,
            required: true,
        },
        depositOptions: {
            type: Array,
            required: true,
        },
        exchangeOptions: {
            type: Array,
            required: true,
        },
        networkOptions: {
            type: Array,
            required: true,
        },
        exchangeAccountOptions: {
            type: Array,
            required: true,
        },
    },
}
</script>

<style lang="scss">
@import '@core/scss/vue/libs/vue-select.scss';
</style>
