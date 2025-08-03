<template>
    <div v-if="formData">
        <validation-observer
            v-if="accessUserLogin['setting']['levels'] || activeUserInfo.role === 'admin'"
            #default="{ handleSubmit }"
            ref="refFormLevelsObserver"
        >
            <!-- Form -->
            <b-form
                class="px-2"
                @submit.prevent="handleSubmit(onSubmit)"
                autocomplete="off"
            >


                <b-card title="سطوح احراز هویت">

                    <app-collapse type="margin">

                        <app-collapse-item v-for="(level,key) in formData.levels" :title="'سطح احراز هویت '+key">
                            <b-row>
                                <b-col cols="3">
                                    <validation-provider #default="{ errors }" rules="required|between:0,1">
                                        <b-form-group label="کارمزد ترید خرید">
                                            <b-form-input
                                                v-model="level.feeBuy" class="text-center" dir="ltr"
                                                :state="errors.length > 0 ? false:null"
                                                placeholder="کارمزد ترید خرید"
                                            />
                                        </b-form-group>
                                    </validation-provider>
                                </b-col>
                                <b-col cols="3">
                                    <validation-provider #default="{ errors }" rules="required">
                                        <b-form-group label="کارمزد ترید فروش">
                                            <b-form-input
                                                v-model="level.feeSell" class="text-center" dir="ltr"
                                                :state="errors.length > 0 ? false:null"
                                                placeholder="کارمزد ترید فروش"
                                            />
                                        </b-form-group>
                                    </validation-provider>
                                </b-col>

                                <b-col cols="3">
                                    <validation-provider #default="{ errors }" rules="required">
                                        <b-form-group label="واریز روزانه تومان">
                                            <cleave
                                                v-model="level.dailyDeposit_toman"
                                                class="form-control text-center" dir="ltr"
                                                :raw="false"
                                                :options="options.number"
                                                :state="errors.length > 0 ? false:null"
                                                placeholder="واریز روزانه تومان"
                                            />
                                        </b-form-group>
                                    </validation-provider>
                                </b-col>
                                <b-col cols="3">
                                    <validation-provider #default="{ errors }" rules="required">
                                        <b-form-group label="برداشت روزانه تومان">
                                            <cleave
                                                v-model="level.dailyWithdrawalTomans"
                                                class="form-control text-center" dir="ltr"
                                                :raw="false"
                                                :options="options.number"
                                                :state="errors.length > 0 ? false:null"
                                                placeholder="برداشت روزانه تومان"
                                            />
                                        </b-form-group>
                                    </validation-provider>
                                </b-col>
                                <b-col cols="4" class="mt-1">
                                    <validation-provider #default="{ errors }" rules="required">
                                        <b-form-group label="برداشت روزانه رمز ارز">
                                            <cleave
                                                v-model="level.dailyWithdrawalCrypto"
                                                class="form-control text-center" dir="ltr"
                                                :raw="false"
                                                :options="options.number"
                                                :state="errors.length > 0 ? false:null"
                                                placeholder="برداشت روزانه رمز ارز"
                                            />
                                        </b-form-group>
                                    </validation-provider>
                                </b-col>
                                <b-col cols="4" class="mt-1">
                                    <validation-provider #default="{ errors }" rules="required">
                                        <b-form-group label="برداشت ماهانه تومان و رمز ارز">
                                            <cleave
                                                v-model="level.monthlyWithdrawal"
                                                class="form-control text-center" dir="ltr"
                                                :raw="false"
                                                :options="options.number"
                                                :state="errors.length > 0 ? false:null"
                                                placeholder="برداشت ماهانه تومان و رمز ارز"
                                            />
                                        </b-form-group>
                                    </validation-provider>
                                </b-col>
                                <b-col cols="4" class="mt-1">
                                    <validation-provider #default="{ errors }" rules="required">
                                        <b-form-group label="خرید روزانه ارزهای دلاری">
                                            <cleave
                                                v-model="level.dailyBuyDigitalCurrency"
                                                class="form-control text-center" dir="ltr"
                                                :raw="false"
                                                :options="options.number"
                                                :state="errors.length > 0 ? false:null"
                                                placeholder="خرید روزانه ارزهای دلاری"
                                            />
                                        </b-form-group>
                                    </validation-provider>
                                </b-col>
                            </b-row>
                        </app-collapse-item>

                    </app-collapse>


                </b-card>


                <!-- Form Actions -->
                <div class="my-2 w-50 mx-auto">
                    <b-button block
                              v-ripple.400="'rgba(255, 255, 255, 0.15)'"
                              variant="primary"
                              class="mr-2 d-flex align-items-center justify-content-center"
                              type="submit"
                              :disabled="formData.isLoading"
                    >
                        <div>ثبت تغییرات</div>
                        <div class="line-height-0 ml-25"><b-spinner v-if="formData.isLoading" small></b-spinner></div>
                    </b-button>
                </div>

            </b-form>
        </validation-observer>
        <div v-else>
            <NotAccessed/>
        </div>
    </div>
</template>

<script>
    import {
         BCard, BForm, BFormGroup, BFormInput,BFormSelect, BFormInvalidFeedback, BButton, BFormFile, BSpinner,BRow,BCol,
    } from 'bootstrap-vue'
    import {ValidationProvider, ValidationObserver} from 'vee-validate'
    import {ref} from '@vue/composition-api'
    import {required, alphaNum, between} from '@validations'
    import Ripple from 'vue-ripple-directive'
    import {MODEL_EVENT_NAME} from "bootstrap-vue/src/mixins/form-radio-check";
    import AppCollapse from '@core/components/app-collapse/AppCollapse.vue'
    import AppCollapseItem from '@core/components/app-collapse/AppCollapseItem.vue'
    import Cleave from 'vue-cleave-component'
    // eslint-disable-next-line import/no-extraneous-dependencies
    import 'cleave.js/dist/addons/cleave-phone.us'
    import NotAccessed from "@/views/vuexy/pages/miscellaneous/NotAccessed";

    export default {
        props:['settings'],
        components: {
            BCard,
            BForm,
            BFormGroup,
            BFormInput,
            BFormInvalidFeedback,
            BButton,
            BFormSelect,
            BFormFile,
            BSpinner,
            BRow,BCol,
            AppCollapse,
            AppCollapseItem,
            // Form Validation
            ValidationProvider,
            ValidationObserver,
            Cleave,NotAccessed
        },
        directives: {
            Ripple,
        },
        data() {
            return {
                formData:{
                    levels: null
                },
                options: {
                    number: {
                        numeral: true,
                        numeralThousandsGroupStyle: 'thousand',
                    },
                }
            }
        },
        methods:{
            onSubmit(){
                this.$refs.refFormLevelsObserver.validate().then(success => {
                    if (success) {
                        var levels = [];
                        Object.keys(this.formData.levels).forEach((level)=>{
                            var arr = {};
                            arr.dailyDeposit_toman = (this.formData.levels[level].dailyDeposit_toman).replace(/[^\d.-]/g, '');
                            arr.dailyWithdrawalTomans = (this.formData.levels[level].dailyWithdrawalTomans).replace(/[^\d.-]/g, '');
                            arr.dailyWithdrawalCrypto = (this.formData.levels[level].dailyWithdrawalCrypto).replace(/[^\d.-]/g, '');
                            arr.monthlyWithdrawal = (this.formData.levels[level].monthlyWithdrawal).replace(/[^\d.-]/g, '');
                            arr.dailyBuyDigitalCurrency = (this.formData.levels[level].dailyBuyDigitalCurrency).replace(/[^\d.-]/g, '');
                            arr.feeBuy = this.formData.levels[level].feeBuy
                            arr.feeSell = this.formData.levels[level].feeSell
                            levels.push(arr)
                        });
                        this.$emit('onSubmit', {'levels':levels})
                    }else{
                        this.$swal({icon: 'warning',title: 'توجه!',text: 'تمامی فیلد ها با تکمیل گردد!',confirmButtonText: 'باشه'});
                    }
                })
            }
        },
        created() {
            Object.keys(this.formData).map(function(key, index) {
                this.formData[key] = this.settings[key];
            },this)
        },
        watch:{

        }
    }
</script>

<style scoped>

</style>
