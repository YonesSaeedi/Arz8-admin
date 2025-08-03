<template>
    <div v-if="formData">
        <validation-observer
            v-if="accessUserLogin['setting']['psvouchers'] || activeUserInfo.role === 'admin'"
            #default="{ handleSubmit }"
            ref="refFormGeneralObserver"
        >
            <!-- Form -->
            <b-form
                class="px-2"
                @submit.prevent="handleSubmit(onSubmit)"
                autocomplete="off"
            >
                <b-card title="تنظیمات پی اس ووچرز">
                    <b-row>
                        <b-col cols="6">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-form-group label="apikey">
                                    <b-form-input dir="ltr" class="text-center"
                                        v-model="formData.psvouchers.apikey"
                                        :state="errors.length > 0 ? false:null"
                                        placeholder="apikey"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="6">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-form-group label="seckey">
                                    <b-form-input dir="ltr" class="text-center"
                                        v-model="formData.psvouchers.seckey"
                                        :state="errors.length > 0 ? false:null"
                                        placeholder="seckey"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                    </b-row>
                    <hr class="w-100">
                    <h4>تنظیمات قیمت</h4>
                    <p class="mb-0 mt-2">نحوه استعلام قیمت:</p>
                    <div class="demo-inline-spacing w-50">
                        <b-form-radio
                            v-model="formData.psvouchers.price_api_status"
                            name="api_status"
                            value="true"
                            class="custom-control-warning mt-0"
                        >
                            از api استعلام شود
                        </b-form-radio>
                        <b-form-radio
                            v-model="formData.psvouchers.price_api_status"
                            name="api_status"
                            value="false"
                            class="custom-control-primary mt-0"
                        >
                            به صورت دستی
                        </b-form-radio>
                    </div>

                    <b-row class="mt-2">
                        <b-col cols="6">
                            <validation-provider #default="{ errors }" rules="required|between:60000,120000">
                                <b-form-group label="قیمت خرید پرفکت مانی">
                                    <b-form-input dir="ltr" class="text-center" :disabled="formData.psvouchers.price_api_status === 'true'"
                                                  v-model="formData.psvouchers.price.buy"
                                                  :state="errors.length > 0 ? false:null"
                                                  placeholder="قیمت خرید پرفکت مانی"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="6">
                            <validation-provider #default="{ errors }" rules="required|between:60000,120000">
                                <b-form-group label="قیمت فروش پرفکت مانی">
                                    <b-form-input dir="ltr" class="text-center" :disabled="formData.psvouchers.price_api_status === 'true'"
                                                  v-model="formData.psvouchers.price.sell"
                                                  :state="errors.length > 0 ? false:null"
                                                  placeholder="قیمت خرید پرفکت مانی"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>

                        <b-col cols="6" class="mt-2">
                            <validation-provider #default="{ errors }" rules="required|between:-5000,5000">
                                <b-form-group label="محاسبه درصد یا ضریب خرید(بالای صد به تومان)" label-for="name">
                                    <b-form-input dir="ltr" class="text-center" :disabled="formData.psvouchers.price_api_status !== 'true'"
                                                  v-model="formData.psvouchers.price_percent_buy"
                                                  :state="errors.length > 0 ? false:null"
                                                  placeholder="بالای 100 به تومان"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="6" class="mt-2">
                            <validation-provider #default="{ errors }" rules="required|between:-5000,5000">
                                <b-form-group label="محاسبه درصد یا ضریب فروش(بالای صد به تومان)" label-for="name">
                                    <b-form-input dir="ltr" class="text-center" :disabled="formData.psvouchers.price_api_status !== 'true'"
                                                  v-model="formData.psvouchers.price_percent_sell"
                                                  :state="errors.length > 0 ? false:null"
                                                  placeholder="بالای 100 به تومان"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>

                    </b-row>

                    <hr class="w-100">
                    <h4>تنظیمات خرید و فروش</h4>
                    <b-row>
                        <b-col cols="6" class="mt-2">
                            <validation-provider #default="{ errors }" rules="required|between:-5000,5000">
                                <b-form-group label="کارمزد خرید(بالای صد به تومان)" >
                                    <b-form-input dir="ltr" class="text-center"
                                                  v-model="formData.psvouchers.wageBuy"
                                                  :state="errors.length > 0 ? false:null"
                                                  placeholder="بالای 100 به تومان"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="6" class="mt-2">
                            <validation-provider #default="{ errors }" rules="required|between:-5000,5000">
                                <b-form-group label="کارمزد فروش(بالای صد به تومان)">
                                    <b-form-input dir="ltr" class="text-center"
                                                  v-model="formData.psvouchers.wageSell"
                                                  :state="errors.length > 0 ? false:null"
                                                  placeholder="بالای 100 به تومان"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="6" class="mt-1">
                            <validation-provider #default="{ errors }" rules="required|between:60000,120000">
                                <b-form-group label="حداقل مجاز برای خرید">
                                    <b-form-input dir="ltr" class="text-center"
                                                  v-model="formData.psvouchers.min_buy"
                                                  :state="errors.length > 0 ? false:null"
                                                  placeholder="حداقل خرید"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="6"></b-col>
                        <b-col cols="6">
                            <p class="mb-0 mt-2">وضعیت خرید:</p>
                            <div class="demo-inline-spacing w-50">
                                <b-form-radio
                                    v-model="formData.psvouchers.buy_status"
                                    name="buy_status"
                                    value="true"
                                    class="custom-control-warning mt-0"
                                >
                                    فعال
                                </b-form-radio>
                                <b-form-radio
                                    v-model="formData.psvouchers.buy_status"
                                    name="buy_status"
                                    value="false"
                                    class="custom-control-primary mt-0"
                                >
                                    غیر فعال
                                </b-form-radio>
                            </div>
                        </b-col>
                        <b-col cols="6">
                            <p class="mb-0 mt-2">وضعیت فروش:</p>
                            <div class="demo-inline-spacing w-50">
                                <b-form-radio
                                    v-model="formData.psvouchers.sell_status"
                                    name="sell_status"
                                    value="true"
                                    class="custom-control-warning mt-0"
                                >
                                    فعال
                                </b-form-radio>
                                <b-form-radio
                                    v-model="formData.psvouchers.sell_status"
                                    name="sell_status"
                                    value="false"
                                    class="custom-control-primary mt-0"
                                >
                                   غیر فعال
                                </b-form-radio>
                            </div>
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
                    psvouchers: null,
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
