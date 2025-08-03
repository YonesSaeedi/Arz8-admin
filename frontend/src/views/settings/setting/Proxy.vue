<template>
    <div v-if="formData">
        <validation-observer
            v-if="accessUserLogin['setting']['proxy'] || activeUserInfo.role === 'admin'"
            #default="{ handleSubmit }"
            ref="refFormGeneralObserver"
        >
            <!-- Form -->
            <b-form
                class="px-2"
                @submit.prevent="handleSubmit(onSubmit)"
                autocomplete="off"
            >



                <b-card title="تنظیمات پروکسی">
                    <p>
                        <strong>نکته 1:</strong>
                        اگر وضعیت روی غیرفعال باشد درخواست ها فقط از ip سرور به بایننس ارسال میشود.
                    </p>
                    <p>
                        <strong>نکته 2:</strong>
                        از بین پروکسی های فعال هر بار به صورت رندوم از آنها درخواست ها با بایننس ارسال میشود. هر پروکسی که تعریف میکنید باید ip پروکسی را به بایننس دسترسی داده باشید.
                    </p>
                    <div class="demo-inline-spacing">
                        <b-form-radio
                            v-model="formData.proxy.status"
                            name="status"
                            value="false"
                            class="custom-control-secondary"
                        >
                            غیر فعال
                        </b-form-radio>
                        <b-form-radio
                            v-model="formData.proxy.status"
                            name="status"
                            value="true"
                            class="custom-control-primary"
                        >
                            فعال
                        </b-form-radio>
                    </div>
                    <hr class="w-100">
                    <b-row v-for="(item,key) in formData.proxy.list_proxy" :class="formData.proxy.status==='false'?'disable-block':''">
                        <b-col cols="4">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-form-group label="مشخصات یا کشور">
                                    <b-form-input
                                        v-model="item.title"
                                        :state="errors.length > 0 ? false:null"
                                        placeholder="مشخصات یا کشور"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="4">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-form-group label="آی پی ip">
                                    <b-form-input
                                        v-model="item.ip"
                                        :state="errors.length > 0 ? false:null"
                                        placeholder="IP"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="4">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-form-group label="پورت">
                                    <b-form-input
                                        v-model="item.port"
                                        :state="errors.length > 0 ? false:null"
                                        placeholder="port"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="4 mt-1">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-form-group label="نام کاربری">
                                    <b-form-input
                                        v-model="item.username"
                                        :state="errors.length > 0 ? false:null"
                                        placeholder="username"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="4 mt-1">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-form-group label="پسورد">
                                    <b-form-input
                                        v-model="item.password"
                                        :state="errors.length > 0 ? false:null"
                                        placeholder="password"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="4" class="mt-1 d-flex align-items-center justify-content-between">
                            <validation-provider #default="{ errors }" rules="required" class="w-75">
                                <b-form-group label="وضعیت">
                                    <b-form-select
                                        v-model="item.status"
                                        :options="[{text:'فعال', value:'true' },{text:'غیرفعال',value:'false'}]"
                                        :state="errors.length > 0 ? false:null"
                                    />
                                </b-form-group>
                            </validation-provider>
                            <feather-icon @click="formData.proxy.list_proxy.splice(key, 1)" icon="XIcon" size="25" class="mt-2 text-danger cursor-pointer"/>
                        </b-col>

                        <hr class="w-100">
                    </b-row>

                    <b-button variant="outline-success" @click="addProxy()">افزودن پروکسی</b-button>
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
         BCard, BForm, BFormGroup, BFormInput,BFormSelect, BFormInvalidFeedback, BButton, BFormFile, BSpinner,BRow,BCol,BFormRadio
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
            BRow,BCol, BFormRadio,
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
                    proxy: null,
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
            },
            addProxy(){
                (this.formData.proxy.list_proxy).push({"title":"","ip":"","port":"","username":"","password":"","status":"true"});
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
