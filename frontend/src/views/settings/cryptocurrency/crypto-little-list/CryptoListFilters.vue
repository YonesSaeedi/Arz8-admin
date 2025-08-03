<template>
    <b-card-actions ref="listFilters" no-body action-collapse action-refresh @refresh="$emit('refetchData')" title="فیلترها" :collapsed="false">
        <b-card-body>
            <b-row>
                <b-col cols="12" md="3" class="mb-md-0 mb-2" >
                    <label>وضعیت خرید</label>
                    <v-select
                        :dir="!$store.state.appConfig.isRTL ? 'rtl' : 'ltr'"
                        :value="statusBuyFilter"
                        :options="statusBuyOptions"
                        class="w-100"
                        :reduce="val => val.value"
                        placeholder="همه"
                        @input="(val) => $emit('update:statusBuyFilter', val)"
                    />
                </b-col>

                <b-col cols="12" md="3" class="mb-md-0 mb-2" >
                    <label>وضعیت فروش</label>
                    <v-select
                        :dir="!$store.state.appConfig.isRTL ? 'rtl' : 'ltr'"
                        :value="statusSellFilter"
                        :options="statusSellOptions"
                        class="w-100"
                        :reduce="val => val.value"
                        placeholder="همه"
                        @input="(val) => $emit('update:statusSellFilter', val)"
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


                <b-col cols="12" md="3" class="mb-md-0 mb-2" >
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


                <b-col cols="12" md="3" class="mb-md-0 mb-2 mt-1">
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
            isLoading(val){
                if(!val)
                    this.$refs.listFilters.showLoading = false
            }
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
            tagFilter: {
                type: [String, null],
                default: null,
            },
            statusBuyFilter: {
                type: [String, null],
                default: null,
            },
            statusSellFilter: {
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
            otherFilter: {
                type: [String, null],
                default: null,
            },
            exchangeFilter: {
                type: [String, null],
                default: null,
            },
            statusBuyOptions: {
                type: Array,
                required: true,
            },
            statusSellOptions: {
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
            isLoading: {
                type: Boolean,
                required: true,
            },
        },
    }
</script>

<style lang="scss">
    @import '@core/scss/vue/libs/vue-select.scss';
</style>
