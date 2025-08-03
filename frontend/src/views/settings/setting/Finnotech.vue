<template>
    <div v-if="formData">
        <validation-observer
            v-if="accessUserLogin['setting']['finnotech'] || activeUserInfo.role === 'admin'"
            #default="{ handleSubmit }"
            ref="refFormFinnotechObserver"
        >
            <!-- Form -->
            <b-form
                class="px-2"
                @submit.prevent="handleSubmit(onSubmit)"
                autocomplete="off"
            >


                <b-card title="تنظیمات فینوتک">
                    <b-row>
                        <b-col cols="6">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-form-group label="شناسه اپلیکیشن">
                                    <b-form-input dir="ltr" class="text-center"
                                        v-model="formData.finnotech.client_id"
                                        :state="errors.length > 0 ? false:null"
                                        placeholder="شناسه اپلیکیشن"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="6">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-form-group label="گذرواژه">
                                    <b-form-input dir="ltr" class="text-center"
                                        v-model="formData.finnotech.password"
                                        :state="errors.length > 0 ? false:null"
                                        placeholder="گذرواژه"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="6" class="mt-1">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-form-group label="آدرس برگشت داده">
                                    <b-form-input dir="ltr" class="text-center"
                                                  v-model="formData.finnotech.url"
                                                  :state="errors.length > 0 ? false:null"
                                                  placeholder="آدرس برگشت داده"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="6" class="mt-1">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-form-group label="کد ملی">
                                    <b-form-input dir="ltr" class="text-center"
                                                  v-model="formData.finnotech.codemeli"
                                                  :state="errors.length > 0 ? false:null"
                                                  placeholder="کد ملی"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="12" class="mt-1">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-form-group label="توکن (به صورت اتوماتیک تولید میشود)">
                                    <b-form-input dir="ltr" class="text-center" readonly
                                                  v-model="formData.finnotech.key"
                                                  :state="errors.length > 0 ? false:null"
                                                  placeholder="توکن"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                    </b-row>
                </b-card>

                <b-card title="تنظیمات کارداینفو">
                    <b-row>
                        <b-col cols="12">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-form-group label="توکن">
                                    <b-form-input dir="ltr" class="text-center"
                                                  v-model="formData.cardinfo.token"
                                                  :state="errors.length > 0 ? false:null"
                                                  placeholder="توکن"
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
         BCard, BForm, BFormGroup, BFormInput,BFormSelect, BFormInvalidFeedback, BButton, BFormFile, BSpinner,BRow,BCol,
        BFormRadio,
    } from 'bootstrap-vue'
    import {ValidationProvider, ValidationObserver} from 'vee-validate'
    import {ref} from '@vue/composition-api'
    import {required, alphaNum, between} from '@validations'
    import Ripple from 'vue-ripple-directive'
    import {MODEL_EVENT_NAME} from "bootstrap-vue/src/mixins/form-radio-check";
    import { Base64 } from 'js-base64';
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
                    finnotech: null,
                    cardinfo: null,
                }
            }
        },
        methods:{
            onSubmit(){
                this.$refs.refFormFinnotechObserver.validate().then(success => {
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
        },
        watch:{
            'formData.finnotech.password'(value){
                this.formData.finnotech.key = Base64.encode(this.formData.finnotech.client_id+':'+value);
            },
            'formData.finnotech.client_id'(value){
                this.formData.finnotech.key = Base64.encode(value+':'+this.formData.finnotech.password);
            }
        }
    }
</script>

<style scoped>

</style>
