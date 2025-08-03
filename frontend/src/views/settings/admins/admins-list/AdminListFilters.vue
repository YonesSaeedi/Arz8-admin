<template>
    <b-card-actions no-body action-collapse title="فیلترها" :collapsed="false">
        <b-card-body>
            <b-row>
                <b-col cols="12" md="3" class="mb-md-0 mb-2">
                    <label>وضعیت تگ</label>
                    <v-select
                        :dir="!$store.state.appConfig.isRTL ? 'rtl' : 'ltr'"
                        :value="tagFilter"
                        :options="tagOptions"
                        class="w-100"
                        :reduce="val => val.value"
                        placeholder="همه"
                        @input="(val) => $emit('update:tagFilter', val)"
                    />
                </b-col>

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


                <b-col cols="12" md="3" class="mb-md-0 mb-2 mt-1" >
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
            networkFilter: {
                type: [String, null],
                default: null,
            },
            tagOptions: {
                type: Array,
                required: true,
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
            otherOptions: {
                type: Array,
                required: true,
            },
            networkOptions: {
                type: Array,
                required: true,
            },
        },
    }
</script>

<style lang="scss">
    @import '@core/scss/vue/libs/vue-select.scss';
</style>
