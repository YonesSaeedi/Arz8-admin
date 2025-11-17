<template>
    <b-card-actions ref="listFilters" action-refresh @refresh="$emit('refetchData')"
                    no-body action-collapse title="فیلترها" :collapsed="isSmallerScreen">
        <b-card-body>
            <b-row>
                <b-col cols="12" md="3" class="mb-md-0 mb-2">
                    <label>سطح کاربری</label>
                    <v-select
                        :dir="!$store.state.appConfig.isRTL ? 'rtl' : 'ltr'"
                        :value="levelFilter"
                        :options="levelOptions"
                        class="w-100"
                        :reduce="val => val.value"
                        placeholder="همه سطوح"
                        @input="(val) => $emit('update:levelFilter', val)"
                    />
                </b-col>

                <b-col cols="12" md="3" class="mb-md-0 mb-2" >
                    <label>وضعیت</label>
                    <v-select
                        :dir="!$store.state.appConfig.isRTL ? 'rtl' : 'ltr'"
                        :value="statusFilter"
                        :options="statusOptions"
                        class="w-100"
                        :reduce="val => val.value"
                        placeholder="همه وضعیت ها"
                        @input="(val) => $emit('update:statusFilter', val)"
                    />
                </b-col>
                <b-col cols="12" md="3" class="mb-md-0 mb-2" >
                    <b-form-group>
                        <label>مقدار از تاریخ</label>
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
                        <label>مقدار تا تاریخ</label>
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
                        <label>موجودی از</label>
                        <b-form-input placeholder="کف موجودی کاربران" v-model="balanceStart"/>
                </b-col>
                <b-col cols="12" md="3" class="mb-md-0 mb-2">
                    <label>موجودی تا</label>
                    <b-form-input placeholder="سقف موجودی کاربران" v-model="balanceStop"/>
                </b-col>
                <b-col cols="12" md="3" class="mb-md-0 mb-2" >
                    <label>سایر فیلترها</label>
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
                <b-col cols="12" md="3" class="mb-md-0 mb-2" >
                    <label>نمایش بر اساس</label>
                    <v-select
                        :dir="!$store.state.appConfig.isRTL ? 'rtl' : 'ltr'"
                        :value="sortFilter"
                        :options="sortOptions"
                        class="w-100"
                        :reduce="val => val.value"
                        :clearable="false"
                        @input="(val) => $emit('update:sortFilter', val)"
                    />
                </b-col>
            </b-row>
        </b-card-body>
    </b-card-actions>
</template>

<script>
    import VuePersianDatetimePicker from 'vue-persian-datetime-picker'
    import jalaliMmoment from "jalali-moment";
    const NowDate = jalaliMmoment()
    var day = NowDate.subtract(0, 'jDay');

    import BCardActions from '@core/components/b-card-actions/BCardActions.vue'
    import {
        BCardHeader, BCardBody, BRow, BCol, BInputGroup, BInputGroupAppend, BFormGroup, BFormInput,
    } from 'bootstrap-vue'
    import vSelect from 'vue-select'

    export default {
        data () {
            return {
                dateStart:day.format('jYYYY/jMM/jDD 00:00'),
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
                balanceStart:'',
                balanceStop:'',


            }
        },
        watch:{
            dateStart(val){
                this.$emit('update:dateStartFilter', val)
            },
            dateStop(val){
                this.$emit('update:dateStopFilter', val)
            },
            balanceStart(val){
                this.balanceStart = this.amountLocalFloat(val,0)
                this.$emit('update:balanceStartFilter', val.replace(/[^\d.-]/g, ''))
            },
            balanceStop(val){
                this.balanceStop = this.amountLocalFloat(val,0)
                this.$emit('update:balanceStopFilter', val.replace(/[^\d.-]/g, ''))
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
            levelFilter: {
                type: [String, null],
                default: null,
            },
            statusFilter: {
                type: [String, null],
                default: null,
            },
            otherFilter: {
                type: [String, null],
                default: null,
            },
            sortFilter: {
                type: [String, null],
                default: null,
            },
            levelOptions: {
                type: Array,
                required: true,
            },
            otherOptions: {
                type: Array,
                required: true,
            },
            sortOptions: {
                type: Array,
                required: true,
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
