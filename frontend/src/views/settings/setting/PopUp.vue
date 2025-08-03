<template>
    <div v-if="formData">
        <validation-observer
            v-if="accessUserLogin['setting']['pop-up'] || activeUserInfo.role === 'admin'"
            #default="{ handleSubmit }"
            ref="refFormGeneralObserver"
        >
            <!-- Form -->
            <b-form
                class="px-2"
                @submit.prevent="handleSubmit(onSubmit)"
                autocomplete="off"
            >


                <b-card title="پاپ آپ">
                    <div class="d-flex">
                        <div class="demo-inline-spacing w-50">
                            <b-form-radio
                                v-model="formData.popup.status"
                                name="status"
                                value="false"
                                class="custom-control-secondary"
                            >
                                غیر فعال
                            </b-form-radio>
                            <b-form-radio
                                v-model="formData.popup.status"
                                name="status"
                                value="true"
                                class="custom-control-primary"
                            >
                                فعال
                            </b-form-radio>
                        </div>
                        <div class="ml-auto w-25">
                            <validation-provider #default="{ errors }">
                                <b-form-group label="نحوه نمایش">
                                    <b-form-select
                                        v-model="formData.popup.showModel"
                                        :options="modelShowPopupOptions"
                                        :state="errors.length > 0 ? false:null"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </div>
                    </div>

                    <hr class="w-100">
                    <vue-editor dir="ltr" v-model="formData.popup.contentPopup" :class="formData.popup.status==='false'?'disable-block':''"></vue-editor>

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
    import { VueEditor } from "vue2-editor";
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
            VueEditor,
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
                    popup: null,
                },
                modelShowPopupOptions:[
                    {text:'هر بار ورود',value:'all'},
                    {text:'هر روز',value:'1'},
                    {text:'هر دو روز',value:'2'},
                    {text:'هر سه روز',value:'3'},
                    {text:'هر هفته',value:'7'},
                    {text:'هر ده روز',value:'10'},
                    {text:'هر ماه',value:'30'},
                ],
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
