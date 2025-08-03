<template>
    <div v-if="formData">
        <validation-observer
            v-if="accessUserLogin['setting']['alert'] || activeUserInfo.role === 'admin'"
            #default="{ handleSubmit }"
            ref="refFormGeneralObserver"
        >
            <!-- Form -->
            <b-form
                class="px-2"
                @submit.prevent="handleSubmit(onSubmit)"
                autocomplete="off"
            >


                <b-card title="تنظیمات هشدار یا آلرت">
                    <p>
                        <strong>نکته 1:</strong>
                        برای انتخاب آیکن میتوانید به لینک
                        <a href="https://dropways.github.io/feathericons/" target="_blank">https://dropways.github.io/feathericons</a>
                        مراجعه کنید.
                    </p>
                    <p>
                        <strong>نکته 2:</strong>
                       عدد مرتب سازی اختیاری است و هر چه عدد مرتب سازی بزرگتر باشد در بالای لیست قرار میگیرد.
                    </p>

                    <div class="demo-inline-spacing">
                        <b-form-radio
                            v-model="formData.alert.status"
                            name="status"
                            value="false"
                            class="custom-control-secondary"
                        >
                            غیر فعال
                        </b-form-radio>
                        <b-form-radio
                            v-model="formData.alert.status"
                            name="status"
                            value="true"
                            class="custom-control-primary"
                        >
                            فعال
                        </b-form-radio>
                    </div>
                    <hr class="w-100">
                    <b-row v-for="(item,key) in formData.alert.list" :class="formData.alert.status==='false'?'disable-block':''">
                        <b-col cols="3">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-form-group label="عنوان">
                                    <b-form-input
                                        v-model="item.title"
                                        :state="errors.length > 0 ? false:null"
                                        placeholder="عنوان کوتاه"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="9">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-form-group label="متن پیغام">
                                    <b-form-input
                                        v-model="item.msg"
                                        :state="errors.length > 0 ? false:null"
                                        placeholder="پیغام"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="3" class="mt-1">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-form-group label="رنگ">
                                    <b-form-select
                                        v-model="item.color"
                                        :options="[{'text':'رنگ اصلی','value':'primary'},{'text':'سبز','value':'success'}
                                                    ,{'text':'قرمز','value':'danger'},{'text':'زرد','value':'warning'},{'text':'مشکی','value':'dark'}]"
                                        :state="errors.length > 0 ? false:null"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="3" class="mt-1">
                            <validation-provider #default="{ errors }">
                                <b-form-group label="آیکن(اختیاری)">
                                    <b-form-input
                                        v-model="item.icon"
                                        :state="errors.length > 0 ? false:null"
                                        placeholder="icon"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="3" class="mt-1">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-form-group label="نحوه نمایش">
                                    <b-form-select
                                        v-model="item.fixed"
                                        :options="[{'text':'ثابت یا فیکس','value':'fixed'},{'text':'قابلیت حذف برای کاربر','value':'unfixed'}]"
                                        :state="errors.length > 0 ? false:null"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="2" class="mt-1">
                            <validation-provider #default="{ errors }">
                                <b-form-group label="مرتب سازی">
                                    <b-form-input
                                        v-model="item.sort"
                                        :state="errors.length > 0 ? false:null"
                                        placeholder="اختیاری"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="1" class="mt-2">
                            <feather-icon @click="formData.alert.list.splice(key, 1)" icon="XIcon" size="25" class="mt-2 text-danger cursor-pointer"/>
                        </b-col>
                        <hr class="w-100">
                    </b-row>

                    <b-button variant="outline-success" @click="addProxy()">افزودن هشدار</b-button>
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
                    alert: null,
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
                (this.formData.alert.list).push({"title":"","msg":"","color":"primary","icon":"","sort":"","fixed":"unfixed"});
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
