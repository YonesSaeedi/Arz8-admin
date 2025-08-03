<template>
    <div v-if="formData">
        <validation-observer
            v-if="accessUserLogin['setting']['tether'] || activeUserInfo.role === 'admin'"
            #default="{ handleSubmit }"
            ref="refFormGeneralObserver"
        >
            <!-- Form -->
            <b-form
                class="px-2"
                @submit.prevent="handleSubmit(onSubmit)"
                autocomplete="off"
            >


                <b-card title="تنظیمات تتر">
                    <p class="mb-0 mt-2">نحوه استعلام تتر قیمت:</p>
                    <div class="demo-inline-spacing w-50">
                        <b-form-radio
                            v-model="formData.feeUsdtApi"
                            name="status"
                            value="true"
                            class="custom-control-warning mt-0"
                        >
                            از api استعلام شود
                        </b-form-radio>
                        <b-form-radio
                            v-model="formData.feeUsdtApi"
                            name="status"
                            value="false"
                            class="custom-control-primary mt-0"
                        >
                            به صورت دستی
                        </b-form-radio>
                    </div>

                    <b-row class="mt-2">
                        <b-col cols="6">
                            <validation-provider #default="{ errors }" rules="required|between:70000,150000">
                                <b-form-group label="قیمت خرید تتر" label-for="name">
                                    <b-form-input dir="ltr" class="text-center" :disabled="formData.feeUsdtApi === 'true'"
                                                  v-model="formData.feeUsdtBuy_toman"
                                                  :state="errors.length > 0 ? false:null"
                                                  placeholder="قیمت خرید تتر"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="6">
                            <validation-provider #default="{ errors }" rules="required|between:70000,150000">
                                <b-form-group label="قیمت فروش تتر" label-for="name">
                                    <b-form-input dir="ltr" class="text-center" :disabled="formData.feeUsdtApi === 'true'"
                                                  v-model="formData.feeUsdtSell_toman"
                                                  :state="errors.length > 0 ? false:null"
                                                  placeholder="قیمت فروش تتر"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>

                        <b-col cols="6" class="mt-2">
                            <validation-provider #default="{ errors }" rules="required|between:-5000,5000">
                                <b-form-group label="محاسبه درصد یا ضریب خرید(بالای صد به تومان)" label-for="name">
                                    <b-form-input dir="ltr" class="text-center" :disabled="formData.feeUsdtApi !== 'true'"
                                                  v-model="formData.percentUsdtBuy"
                                                  :state="errors.length > 0 ? false:null"
                                                  placeholder="بالای 100 به تومان"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="6" class="mt-2">
                            <validation-provider #default="{ errors }" rules="required|between:-5000,5000">
                                <b-form-group label="محاسبه درصد یا ضریب فروش(بالای صد به تومان)" label-for="name">
                                    <b-form-input dir="ltr" class="text-center" :disabled="formData.feeUsdtApi !== 'true'"
                                                  v-model="formData.percentUsdtSell"
                                                  :state="errors.length > 0 ? false:null"
                                                  placeholder="بالای 100 به تومان"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>

                        <b-col cols="6" class="mt-2">
                            <validation-provider #default="{ errors }">
                                <b-form-group label="استفاده از موجود کاربران">
                                    <b-form-select
                                        v-model="formData.useBalanceUserUsdt"
                                        :options="[{'text':'استفاده از موجودی کاربران','value':'true'},{'text':'عدم استفاده از موحود کاربران','value':'false'}]"
                                        :state="errors.length > 0 ? false:null"
                                    />
                                    <small>وضعیت خرید و فروش از موجود تتر کاربران کاربران</small>
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                    </b-row>

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
        BFormRadio,
    } from 'bootstrap-vue'
    import {ValidationProvider, ValidationObserver} from 'vee-validate'
    import {ref} from '@vue/composition-api'
    import {required, alphaNum, between} from '@validations'
    import Ripple from 'vue-ripple-directive'
    import {MODEL_EVENT_NAME} from "bootstrap-vue/src/mixins/form-radio-check";
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
            BFormRadio,
            NotAccessed,
            // Form Validation
            ValidationProvider,
            ValidationObserver,
        },
        directives: {
            Ripple,
        },
        data() {
            return {
                formData:{
                    feeUsdtApi: null,
                    feeUsdtBuy_toman: null,
                    feeUsdtSell_toman: null,
                    percentUsdtBuy: null,
                    percentUsdtSell: null,
                    useBalanceUserUsdt: null,
                }
            }
        },
        methods:{
            onSubmit(){
                this.$refs.refFormGeneralObserver.validate().then(success => {
                    if (success) {
                        this.$emit('onSubmit', this.formData)
                    }
                })
            }
        },
        created() {
            Object.keys(this.formData).map(function(key, index) {
                this.formData[key] = this.settings[key];
            },this)
        }
    }
</script>

<style scoped>

</style>
