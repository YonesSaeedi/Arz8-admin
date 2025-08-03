<template>
    <b-card-actions no-body action-collapse title="فیلترها" :collapsed="false">
        <b-card-body>
            <b-row>
                <b-col cols="12" md="3" class="mb-md-0 mb-2">
                    <label>وضعیت</label>
                    <v-select
                        :dir="!$store.state.appConfig.isRTL ? 'rtl' : 'ltr'"
                        :value="statusFilter"
                        :options="statusOptions"
                        class="w-100"
                        :reduce="val => val.value"
                        placeholder="همه"
                        @input="(val) => $emit('update:statusFilter', val)"
                    />
                </b-col>

                <b-col cols="12" md="3" class="mb-md-0 mb-2">
                    <label>وضعیت اتوماتیک</label>
                    <v-select
                        :dir="!$store.state.appConfig.isRTL ? 'rtl' : 'ltr'"
                        :value="statusAutoFilter"
                        :options="statusOptions"
                        class="w-100"
                        :reduce="val => val.value"
                        placeholder="همه"
                        @input="(val) => $emit('update:statusAutoFilter', val)"
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

            statusFilter: {
                type: [String, null],
                default: null,
            },
            statusAutoFilter: {
                type: [String, null],
                default: null,
            },

            statusOptions: {
                type: Array,
                required: true,
            },

        },
    }
</script>

<style lang="scss">
    @import '@core/scss/vue/libs/vue-select.scss';
</style>
