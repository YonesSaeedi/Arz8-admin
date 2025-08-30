<template>
    <div v-if="formData" id="st-application">
        <validation-observer
            v-if="accessUserLogin['setting']['application'] || activeUserInfo.role === 'admin'"
            #default="{ handleSubmit }"
            ref="refFormGeneralObserver"
        >
            <!-- Form -->
            <b-form
                class="px-2"
                @submit.prevent="handleSubmit(onSubmit)"
                autocomplete="off"
            >


                <b-card title="تنظیمات مربوط به اپلیکیشن">
                    <p>
                        <strong>نکته 1:</strong>
                        این بخش فقط توسط تیم فنی میبایست ویرایش شود.
                    </p>

                    <h5>بروزرسانی:</h5>
                    <b-tabs justified>
                        <b-tab title="اندروید android" active>
                            <template #title>
                                <img src="@/assets/images/icons/android.png" width="30px">
                                <span>اندروید android</span>
                            </template>
                            <b-tabs>
                                <b-tab :title="$t(key)" :active="key === 'googleplay'" v-for="(key) in listStoreAndroid">
                                    <p class="mb-0 mt-2">وضعیت آپدیت:</p>
                                    <div class="demo-inline-spacing w-50">
                                        <b-form-radio
                                            v-model="formData.application.update[key].status"
                                            :name="'status'+key"
                                            value="true"
                                            class="custom-control-warning mt-0"
                                        >
                                            فعال
                                        </b-form-radio>
                                        <b-form-radio
                                            v-model="formData.application.update[key].status"
                                            :name="'status'+key"
                                            value="false"
                                            class="custom-control-primary mt-0"
                                        >
                                            غیرفعال
                                        </b-form-radio>
                                    </div>
                                    <b-row class="mt-2" :class="formData.application.update[key].status === 'false'?'disable-block':''">
                                        <b-col cols="4">
                                            <validation-provider #default="{ errors }" rules="required|between:0,30000">
                                                <b-form-group label="ورژن کد">
                                                    <b-form-input dir="ltr" class="text-center"
                                                                  v-model="formData.application.update[key].version_code"
                                                                  :state="errors.length > 0 ? false:null"
                                                                  placeholder="ورژن کد"
                                                    />
                                                </b-form-group>
                                            </validation-provider>
                                        </b-col>
                                        <b-col cols="4">
                                            <validation-provider #default="{ errors }">
                                                <b-form-group label="وضعیت اجباری">
                                                    <b-form-select
                                                        v-model="formData.application.update[key].reqiuard"
                                                        :options="[{'text':'اجباری باشد','value':'true'},{'text':'اختیاری باشد','value':'false'}]"
                                                        :state="errors.length > 0 ? false:null"
                                                    />
                                                </b-form-group>
                                            </validation-provider>
                                        </b-col>
                                        <b-col cols="4">
                                            <validation-provider #default="{ errors }" rules="required|url">
                                                <b-form-group label="لینک فروشگاه">
                                                    <b-form-input dir="ltr" class="text-center"
                                                                  v-model="formData.application.update[key].link"
                                                                  :state="errors.length > 0 ? false:null"
                                                                  placeholder="لینک"
                                                    />
                                                </b-form-group>
                                            </validation-provider>
                                        </b-col>
                                        <b-col cols="12" class="mt-2">
                                            <b-tabs>
                                                <b-tab :title="lang.name" v-for="lang in getGeneralInfo.locales">
                                                    <vue-editor dir="ltr" v-model="formData.application.update[key].change_item_message[lang.symbol]"></vue-editor>
                                                </b-tab>
                                            </b-tabs>
                                        </b-col>
                                    </b-row>
                                </b-tab>
                            </b-tabs>
                        </b-tab>
                        <b-tab title="آیفون ios">
                            <template #title>
                                <img src="@/assets/images/icons/ios.png" width="30px">
                                <span class="font-weight-bold">آیفون ios</span>
                            </template>
                            <b-tabs>
                                <b-tab :title="$t(key)" :active="key === 'sibapp'" v-for="(key) in listStoreIos">
                                    <p class="mb-0 mt-2">وضعیت آپدیت:</p>
                                    <div class="demo-inline-spacing w-50">
                                        <b-form-radio
                                            v-model="formData.application.update[key].status"
                                            :name="'status'+key"
                                            value="true"
                                            class="custom-control-warning mt-0"
                                        >
                                            فعال
                                        </b-form-radio>
                                        <b-form-radio
                                            v-model="formData.application.update[key].status"
                                            :name="'status'+key"
                                            value="false"
                                            class="custom-control-primary mt-0"
                                        >
                                            غیرفعال
                                        </b-form-radio>
                                    </div>
                                    <b-row class="mt-2" :class="formData.application.update[key].status === 'false'?'disable-block':''">
                                        <b-col cols="4">
                                            <validation-provider #default="{ errors }" rules="required|between:0,30000">
                                                <b-form-group label="ورژن کد">
                                                    <b-form-input dir="ltr" class="text-center"
                                                                  v-model="formData.application.update[key].version_code"
                                                                  :state="errors.length > 0 ? false:null"
                                                                  placeholder="ورژن کد"
                                                    />
                                                </b-form-group>
                                            </validation-provider>
                                        </b-col>
                                        <b-col cols="4">
                                            <validation-provider #default="{ errors }">
                                                <b-form-group label="وضعیت اجباری">
                                                    <b-form-select
                                                        v-model="formData.application.update[key].reqiuard"
                                                        :options="[{'text':'اجباری باشد','value':'true'},{'text':'اختیاری باشد','value':'false'}]"
                                                        :state="errors.length > 0 ? false:null"
                                                    />
                                                </b-form-group>
                                            </validation-provider>
                                        </b-col>
                                        <b-col cols="4">
                                            <validation-provider #default="{ errors }" rules="required|url">
                                                <b-form-group label="لینک فروشگاه">
                                                    <b-form-input dir="ltr" class="text-center"
                                                                  v-model="formData.application.update[key].link"
                                                                  :state="errors.length > 0 ? false:null"
                                                                  placeholder="لینک"
                                                    />
                                                </b-form-group>
                                            </validation-provider>
                                        </b-col>
                                        <b-col cols="12" class="mt-2">
                                            <b-tabs>
                                                <b-tab :title="lang.name" v-for="lang in getGeneralInfo.locales">
                                                    <vue-editor dir="ltr" v-model="formData.application.update[key].change_item_message[lang.symbol]"></vue-editor>
                                                </b-tab>
                                            </b-tabs>
                                        </b-col>
                                    </b-row>
                                </b-tab>
                            </b-tabs>
                        </b-tab>
                        <b-tab title="وبسایت web">
                            <template #title>
                                <img src="@/assets/images/icons/google-chrome.png" width="30px">
                                <span class="font-weight-bold">وبسایت web</span>
                            </template>
                            <b-tabs>
                                <b-tab :title="$t(key)" :active="key === 'website'" v-for="(key) in listStoreWebsite">
                                    <p class="mb-0 mt-2">وضعیت آپدیت:</p>
                                    <div class="demo-inline-spacing w-50">
                                        <b-form-radio
                                            v-model="formData.application.update[key].status"
                                            :name="'status'+key"
                                            value="true"
                                            class="custom-control-warning mt-0"
                                        >
                                            فعال
                                        </b-form-radio>
                                        <b-form-radio
                                            v-model="formData.application.update[key].status"
                                            :name="'status'+key"
                                            value="false"
                                            class="custom-control-primary mt-0"
                                        >
                                            غیرفعال
                                        </b-form-radio>
                                    </div>
                                    <b-row class="mt-2" :class="formData.application.update[key].status === 'false'?'disable-block':''">
                                        <b-col cols="4">
                                            <validation-provider #default="{ errors }" rules="required|between:0,30000">
                                                <b-form-group label="ورژن کد">
                                                    <b-form-input dir="ltr" class="text-center"
                                                                  v-model="formData.application.update[key].version_code"
                                                                  :state="errors.length > 0 ? false:null"
                                                                  placeholder="ورژن کد"
                                                    />
                                                </b-form-group>
                                            </validation-provider>
                                        </b-col>
                                        <b-col cols="4">
                                            <validation-provider #default="{ errors }">
                                                <b-form-group label="وضعیت اجباری">
                                                    <b-form-select
                                                        v-model="formData.application.update[key].reqiuard"
                                                        :options="[{'text':'اجباری باشد','value':'true'},{'text':'اختیاری باشد','value':'false'}]"
                                                        :state="errors.length > 0 ? false:null"
                                                    />
                                                </b-form-group>
                                            </validation-provider>
                                        </b-col>
                                        <b-col cols="12" class="mt-2">
                                            <b-tabs>
                                                <b-tab :title="lang.name" v-for="lang in getGeneralInfo.locales">
                                                    <vue-editor dir="ltr" v-model="formData.application.update[key].change_item_message[lang.symbol]"></vue-editor>
                                                </b-tab>
                                            </b-tabs>
                                        </b-col>
                                    </b-row>
                                </b-tab>
                            </b-tabs>
                        </b-tab>
                    </b-tabs>

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
        BFormRadio,BTabs,BTab
    } from 'bootstrap-vue'
    import {ValidationProvider, ValidationObserver} from 'vee-validate'
    import {ref} from '@vue/composition-api'
    import {required, alphaNum, between, url} from '@validations'
    import Ripple from 'vue-ripple-directive'
    import {MODEL_EVENT_NAME} from "bootstrap-vue/src/mixins/form-radio-check";
    import NotAccessed from "@/views/vuexy/pages/miscellaneous/NotAccessed";
    import { VueEditor } from "vue2-editor";

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
            BTabs,BTab,
            // Form Validation
            ValidationProvider,
            ValidationObserver,
            VueEditor
        },
        directives: {
            Ripple,
        },
        data() {
            return {
                formData:{
                    application: null
                }
            }
        },
        computed:{
          listStoreAndroid(){
              return  Object.keys(this.formData.application.update).filter((key) =>{
                  return this.formData.application.update[key].os === 'android';
              } );
          },
          listStoreIos(){
              return  Object.keys(this.formData.application.update).filter((key) =>{
                  return this.formData.application.update[key].os === 'ios';
              } );
          },
          listStoreWebsite(){
            return  Object.keys(this.formData.application.update).filter((key) =>{
                return this.formData.application.update[key].os === 'website';
            } );
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

<style lang="scss">
#st-application{
    .nav.nav-tabs.nav-justified li span {
        font-size: 17px;
        font-weight: 600 !important;
    }
}
</style>
