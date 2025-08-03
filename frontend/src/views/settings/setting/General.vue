<template>
    <div v-if="formData">
        <validation-observer
            v-if="accessUserLogin['setting']['general'] || activeUserInfo.role === 'admin'"
            #default="{ handleSubmit }"
            ref="refFormGeneralObserver"
        >
            <!-- Form -->
            <b-form
                class="px-2"
                @submit.prevent="handleSubmit(onSubmit)"
                autocomplete="off"
            >


                <b-card title="تنظیمات عمومی">
                    <b-row>
                        <b-col cols="6">
                            <validation-provider #default="{ errors }" name="آی پی تست" rules="">
                                <b-form-group label="آی پی تست" label-for="name">
                                    <b-form-input
                                        v-model="formData.ipTest"
                                        :state="errors.length > 0 ? false:null"
                                        placeholder="برای درج چند ip میتوانید از , استفاده کنید"
                                    />
                                </b-form-group>
                                <small>با پر کردن این فیلد فقط ip های درج شده امکان مشاهده پلتفرم را دارند.</small>
                            </validation-provider>
                        </b-col>
                        <b-col cols="6">
                            <validation-provider #default="{ errors }" name="دسترسی کاربران" rules="required">
                                <b-form-group label="دسترسی کاربران" label-for="name">
                                    <b-form-select
                                        id="network"
                                        v-model="formData.countryAccess"
                                        :options="[{text:'فقط کاربران ایرانی',value:'iran'},{text:'همه کاربران',value:'all'}]"
                                        :state="errors.length > 0 ? false:null"
                                    />
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
         BCard, BForm, BFormGroup, BFormInput,BFormSelect, BFormInvalidFeedback, BButton, BFormFile, BSpinner,BRow,BCol
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
            BRow,BCol,NotAccessed,

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
                    ipTest: null,
                    countryAccess: null
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
