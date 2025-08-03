<template>
    <div v-if="formData">
        <validation-observer
            v-if="accessUserLogin['setting']['binance'] || activeUserInfo.role === 'admin'"
            #default="{ handleSubmit }"
            ref="refFormGeneralObserver"
        >
            <!-- Form -->
            <b-form
                class="px-2"
                @submit.prevent="handleSubmit(onSubmit)"
                autocomplete="off"
            >


                <b-card title="تنظیمات کوینکس">
                    <b-row v-for="(item,key) in formData.coinex_new">
                        <b-col cols="12">
                            <h5 class="font-weight-bolder">تنظیمات مربوط به کوکوین {{key}}</h5>
                        </b-col>
                        <b-col cols="2">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-form-group label="نام" label-for="name">
                                    <b-form-input class="text-center"
                                                  v-model="item.name"
                                                  :state="errors.length > 0 ? false:null"
                                                  placeholder="عنوان"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="5">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-form-group label="ACCESS_ID" label-for="name">
                                    <b-form-input dir="ltr" class="text-center"
                                        v-model="item.ACCESS_ID"
                                        :state="errors.length > 0 ? false:null"
                                        placeholder="ACCESS_ID"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="5">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-form-group label="SECRET_KEY" label-for="name">
                                    <b-form-input dir="ltr" class="text-center"
                                        v-model="item.SECRET_KEY"
                                        :state="errors.length > 0 ? false:null"
                                        placeholder="SECRET_KEY"
                                    />
                                </b-form-group>
                            </validation-provider>
                         </b-col>
                        <hr class="w-100">
                    </b-row>
                    <b-button variant="outline-success" @click="addCoinex()">افزودن</b-button>
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
                    coinex: null,
                    coinex_new: null,
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
            addCoinex(){
                (this.formData.coinex_new).push({"name":"","ACCESS_ID":"","SECRET_KEY":""});
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
