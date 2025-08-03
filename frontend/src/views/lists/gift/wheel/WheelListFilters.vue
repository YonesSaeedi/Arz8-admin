<template>
    <b-card-actions ref="listFilters" action-refresh @refresh="$emit('refetchData')"
                    no-body action-collapse title="فیلترها" :collapsed="false">
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
                                <date-picker v-model="dateStart" :show="showDatePickerStart" @close="showDatePickerStart=false" :auto-submit="true" color="#7367f0"  type="datetime" :editable="true"
                                             :locale="localeHas" :locale-config="localeConfig" :format="(localeHas=='fa')?'jYYYY/jMM/jDD HH:mm' : 'YYYY-MM-DD HH:mm'" element="registeryDateStart">
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
                                <date-picker v-model="dateStop" :show="showDatePickerStop" @close="showDatePickerStop=false" :auto-submit="true" color="#7367f0"  type="datetime" :editable="true"
                                             :locale="localeHas" :locale-config="localeConfig" :format="(localeHas=='fa')?'jYYYY/jMM/jDD HH:mm' : 'YYYY-MM-DD HH:mm'" element="registeryDateStop">
                                </date-picker>
                            </b-input-group-append>
                        </b-input-group>
                    </b-form-group>
                </b-col>


                <b-col cols="12" md="3" class="mb-md-0 mb-2">
                    <label>مبلغ جایزه از</label>
                    <b-form-input placeholder="مبلغ کل از" v-model="amountStart"/>
                </b-col>
                <b-col cols="12" md="3" class="mb-md-0 mb-2">
                    <label>مبلغ جایزه تا</label>
                    <b-form-input placeholder="مبلغ کل تا" v-model="amountStop"/>
                </b-col>
                <!--
                <b-col cols="12" md="3" class="mb-md-0 mb-2">
                        <label>تعداد کل از</label>
                        <b-form-input placeholder="تعداد کل از" v-model="countStart"/>
                </b-col>
                <b-col cols="12" md="3" class="mb-md-0 mb-2">
                    <label>تعداد کل تا</label>
                    <b-form-input placeholder="تعداد کل تا" v-model="countStop"/>
                </b-col>
                -->

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
                countStart:'',
                countStop:'',
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
            },
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
            statusFilter: {
                type: [String, null],
                default: null,
            },
            statusOptions: {
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
