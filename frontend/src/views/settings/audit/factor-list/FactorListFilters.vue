<template>
    <b-card-actions no-body action-collapse title="فیلترها" sub-title="این فیلتر صرفا برای جدول زیر میباشد و ربطی به لیست ندارد" :collapsed="isSmallerScreen">
        <b-card-body>
            <b-row>
                <b-col cols="12" md="3" class="mb-md-0 mb-2" >
                    <b-form-group>
                        <label>ثبت از تاریخ</label>
                        <b-input-group>
                            <b-form-input
                                placeholder="از تاریخ"
                                v-model="dateStart"
                                id="registeryDateStart" name="registeryDateStart"
                            />
                            <b-input-group-append is-text>
                                <feather-icon icon="CalendarIcon" class="cursor-pointer" @click="showDatePickerStart=true"/>
                                <date-picker v-model="dateStart" :show="showDatePickerStart" @close="showDatePickerStart=false" :auto-submit="true" color="#7367f0" :editable="true"
                                             :locale="localeHas" :locale-config="localeConfig" :format="(localeHas=='fa')?'jYYYY/jMM/jDD' : 'YYYY-MM-DD'" element="registeryDateStart">
                                </date-picker>
                            </b-input-group-append>
                        </b-input-group>
                    </b-form-group>
                </b-col>
                <b-col cols="12" md="3" class="mb-md-0 mb-2">
                    <b-form-group>
                        <label>ثبت تا تاریخ</label>
                        <b-input-group>
                            <b-form-input
                                placeholder="تا تاریخ"
                                v-model="dateStop"
                                id="registeryDateStop" name="registeryDateStop"
                            />
                            <b-input-group-append is-text>
                                <feather-icon icon="CalendarIcon" class="cursor-pointer" @click="showDatePickerStop=true"/>
                                <date-picker v-model="dateStop" :show="showDatePickerStop" @close="showDatePickerStop=false" :auto-submit="true" color="#7367f0" :editable="true"
                                             :locale="localeHas" :locale-config="localeConfig" :format="(localeHas=='fa')?'jYYYY/jMM/jDD' : 'YYYY-MM-DD'" element="registeryDateStop">
                                </date-picker>
                            </b-input-group-append>
                        </b-input-group>
                    </b-form-group>
                </b-col>
                <b-col cols="12" md="3" class="mb-md-0 mb-2">
                    <label>نوع</label>
                    <v-select
                        :dir="!$store.state.appConfig.isRTL ? 'rtl' : 'ltr'"
                        :value="typeFilter"
                        :options="typeOptions"
                        class="w-100"
                        :reduce="val => val.value"
                        placeholder="خرید و فروش"
                        @input="(val) => $emit('update:typeFilter', val)"
                    />
                </b-col>

                <b-col cols="12" md="3" class="mb-md-0 mb-2">
                    <label>نوع واریز</label>
                    <v-select
                        :dir="!$store.state.appConfig.isRTL ? 'rtl' : 'ltr'"
                        :value="typeDepositFilter"
                        :options="typeDepositOptions"
                        class="w-100"
                        :reduce="val => val.value"
                        placeholder="همه"
                        @input="(val) => $emit('update:typeDepositFilter', val)"
                    />
                </b-col>

                <b-col cols="12" md="3" class="mb-md-0 mb-2">
                        <label>مبلغ از</label>
                        <b-form-input placeholder="مبلغ تبدیل از" v-model="amountStart"/>
                </b-col>
                <b-col cols="12" md="3" class="mb-md-0 mb-2">
                    <label>مبلغ تا</label>
                    <b-form-input placeholder="مبلغ تبدیل تا" v-model="amountStop"/>
                </b-col>
            </b-row>

            <b-row class="mt-1">
                <!-- balance -->
                <b-col class="border py-1" cols="12" md="6" offset-md="3">
                    <b-row class="align-items-center">
                        <b-col cols="6" md="6" class="font-medium-1">
                            جمع کل تراکنش ها:
                        </b-col>
                        <b-col class="text-center font-medium-1" cols="6" md="6">
                            <span v-if="tableCalculate">
                                {{tableCalculate.total.toLocaleString()}} تومان
                            </span>
                            <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                        </b-col>
                    </b-row>
                </b-col>
                <b-col class="border py-1" cols="12" md="3" offset-md="3">
                    <b-row class="align-items-center">
                        <b-col cols="6" md="6" class="font-medium-1">
                            جمع خرید ها:
                        </b-col>
                        <b-col class="text-center font-medium-1 text-success" cols="6" md="6">
                            <span v-if="tableCalculate">
                                {{tableCalculate.total_buy.toLocaleString()}}
                            </span>
                            <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                        </b-col>
                    </b-row>
                </b-col>
                <b-col class="border py-1" cols="12" md="3">
                    <b-row class="align-items-center">
                        <b-col cols="6" md="6" class="font-medium-1">
                            جمع فروش ها:
                        </b-col>
                        <b-col class="text-center font-medium-1 text-danger" cols="6" md="6">
                            <span v-if="tableCalculate">
                                {{tableCalculate.total_sell.toLocaleString()}}
                            </span>
                            <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                        </b-col>
                    </b-row>
                </b-col>
                <b-col class="border py-1" cols="12" md="6" offset-md="3">
                    <b-row class="align-items-center">
                        <b-col cols="6" md="6" class="font-medium-1">
                            جمع کارمزد:
                        </b-col>
                        <b-col class="text-center font-medium-1" cols="6" md="6">
                            <span v-if="tableCalculate ">
                                 {{tableCalculate.total_wage.toLocaleString()}} تومان
                            </span>
                            <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                        </b-col>
                    </b-row>
                </b-col>
                <b-col class="border py-1" cols="12" md="6" offset-md="3">
                    <b-row class="align-items-center">
                        <b-col cols="6" md="6" class="font-medium-1">
                            خالص:
                        </b-col>
                        <b-col class="text-center font-medium-1" cols="6" md="6">
                            <span v-if="tableCalculate ">
                                 {{(tableCalculate.total_wage).toLocaleString()}} تومان
                            </span>
                            <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                        </b-col>
                    </b-row>
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
        BCardHeader, BCardBody, BRow, BCol, BInputGroup, BInputGroupAppend, BFormGroup, BFormInput,BSkeleton
    } from 'bootstrap-vue'
    import vSelect from 'vue-select'

    export default {
        data () {
            return {
                dateStart:'',
                dateStop:'',
                showDatePickerStart: false,
                showDatePickerStop: false,
                localeConfig:{
                    fa: {
                        dow: 6,     //  first day of week
                        dir: 'rtl',
                        displayFormat: 'jYYYY/jMM/jDD'
                    },
                    en: {
                        dow: 0,
                        dir: 'ltr',
                        displayFormat: 'YYYY-MM-DD'
                    }
                },
                amountStart:'',
                amountStop:'',
                id:''

            }
        },
        watch:{
            dateStart(val){
                this.$emit('update:dateStartFilter', val)
            },
            dateStop(val){
                this.$emit('update:dateStopFilter', val)
            },
            amountStart(val){
                this.amountStart = this.amountLocalFloat(val,0)
                this.$emit('update:amountStartFilter', val.replace(/[^\d.-]/g, ''))
            },
            amountStop(val){
                this.amountStop = this.amountLocalFloat(val,0)
                this.$emit('update:amountStopFilter', val.replace(/[^\d.-]/g, ''))
            }
        },
        components: {
            BRow,
            BCol,
            BCardActions,
            BCardHeader,
            BCardBody,
            BSkeleton,
            BInputGroup,BFormGroup, BInputGroupAppend,BFormInput,
            vSelect,
            datePicker: VuePersianDatetimePicker,
        },
        props: {
            tableCalculate: {
                type: Object,
                default: null,
            },
            dateStartPriod: {
                type: [String, null],
                default: null,
            },
            typeFilter: {
                type: [String, null],
                default: null,
            },
            typeOptions: {
                type: Array,
                required: true,
            },
            typeDepositFilter: {
                type: [String, null],
                default: null,
            },
            typeDepositOptions: {
                type: Array,
                required: true,
            },
        },
        created() {
            this.dateStart = this.dateStartPriod
        }
    }
</script>

<style lang="scss">
    @import '@core/scss/vue/libs/vue-select.scss';
</style>
