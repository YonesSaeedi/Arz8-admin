<template>
    <div v-if="formData">
        <validation-observer
            v-if="accessUserLogin['setting']['receipt'] || activeUserInfo.role === 'admin'"
            #default="{ handleSubmit }"
            ref="refFormGeneralObserver"
        >
            <!-- Form -->
            <b-form
                class="px-2"
                @submit.prevent="handleSubmit(onSubmit)"
                autocomplete="off"
            >


                <b-card title="واریز با فیش">
                    <div class="demo-inline-spacing">
                        <b-form-radio
                            v-model="formData.receipt.status"
                            name="status"
                            value="false"
                            class="custom-control-secondary"
                        >
                            غیر فعال
                        </b-form-radio>
                        <b-form-radio
                            v-model="formData.receipt.status"
                            name="status"
                            value="true"
                            class="custom-control-primary"
                        >
                            فعال
                        </b-form-radio>
                    </div>
                    <b-form-group class="mt-2" label="باکس توضیحات در قسمت فیش(اختیاری و در صورت پر بودن در قسمت فیش نشان میدهد)">
                        <b-form-input
                            v-model="formData.receipt.alert"
                            placeholder="توضیحات در فیش"
                        />
                    </b-form-group>
                    <hr class="w-100">
                    <b-row v-for="(item,key) in formData.receipt.list_cards" :class="formData.receipt.status==='false'?'disable-block':''">
                        <b-col cols="4">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-form-group label="نام بانک">
                                    <b-form-input
                                        v-model="item.name_bank"
                                        :state="errors.length > 0 ? false:null"
                                        placeholder="نام بانک"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="4">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-form-group label="شماره کارت">
                                    <b-form-input
                                        v-model="item.card_number"
                                        :state="errors.length > 0 ? false:null"
                                        placeholder="شماره کارت"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="4">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-form-group label="شماره حساب">
                                    <b-form-input
                                        v-model="item.account_number"
                                        :state="errors.length > 0 ? false:null"
                                        placeholder="شماره حساب"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="4" class=" mt-1">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-form-group label="شماره شبا">
                                    <b-form-input
                                        v-model="item.iban_number"
                                        :state="errors.length > 0 ? false:null"
                                        placeholder="شماره شبا"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="4" class=" mt-1">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-form-group label="نام صاحب حساب">
                                    <b-form-input
                                        v-model="item.account_name"
                                        :state="errors.length > 0 ? false:null"
                                        placeholder="نام صاحب حساب"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="4"  class="mt-1 d-flex align-items-center justify-content-between">
                            <validation-provider #default="{ errors }" rules="required" class="w-75">
                                <b-form-group label="وضعیت">
                                    <b-form-select
                                        v-model="item.active"
                                        :options="[{text:'فعال', value:'1' },{text:'غیرفعال',value:'0'}]"
                                        :state="errors.length > 0 ? false:null"
                                    />
                                </b-form-group>
                            </validation-provider>
                            <feather-icon @click="formData.receipt.list_cards.splice(key, 1)" icon="XIcon" size="25" class="text-danger mt-2 cursor-pointer"/>
                        </b-col>

                        <hr class="w-100">
                    </b-row>

                    <b-button variant="outline-success" @click="addCard()">افزودن حساب</b-button>

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
                    receipt: null,
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
            addCard(){
                (this.formData.receipt.list_cards).push({"name_bank":"","card_number":"","account_number":"","iban_number":"","active":"true"});
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
