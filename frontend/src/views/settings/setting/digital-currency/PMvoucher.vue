<template>
    <div v-if="formData">
        <validation-observer
            v-if="accessUserLogin['setting']['pmvoucher'] || activeUserInfo.role === 'admin'"
            #default="{ handleSubmit }"
            ref="refFormGeneralObserver"
        >
            <!-- Form -->
            <b-form
                class="px-2"
                @submit.prevent="handleSubmit(onSubmit)"
                autocomplete="off"
            >

                <b-card title="تنظیمات ووچر پرفکت مانی">
                    <p>
                        <strong>نکته 1:</strong>
                        تمامی تنظیمات مربوط به اکانت پرفکت مانی و قیمت از قسمت تنظیمات پرفکت مانی بارگذرای میشود.
                    </p>

                    <b-row>
                        <b-col cols="6">
                            <validation-provider #default="{ errors }" rules="required|between:60000,120000">
                                <b-form-group label="حداقل مجاز برای خرید">
                                    <b-form-input dir="ltr" class="text-center"
                                                  v-model="formData.pmvoucher.min_buy"
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
                                    v-model="formData.pmvoucher.buy_status"
                                    name="buy_status"
                                    value="true"
                                    class="custom-control-warning mt-0"
                                >
                                    فعال
                                </b-form-radio>
                                <b-form-radio
                                    v-model="formData.pmvoucher.buy_status"
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
                                    v-model="formData.pmvoucher.sell_status"
                                    name="sell_status"
                                    value="true"
                                    class="custom-control-warning mt-0"
                                >
                                    فعال
                                </b-form-radio>
                                <b-form-radio
                                    v-model="formData.pmvoucher.sell_status"
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
                    pmvoucher: null,
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
