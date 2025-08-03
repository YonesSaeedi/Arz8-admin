<template>
    <div v-if="formData">
        <validation-observer
            v-if="accessUserLogin['setting']['levels_account'] || activeUserInfo.role === 'admin'"
            #default="{ handleSubmit }"
            ref="refFormLevelsObserver"
        >
            <!-- Form -->
            <b-form
                class="px-2"
                @submit.prevent="handleSubmit(onSubmit)"
                autocomplete="off"
            >

                <b-card title="سطوح کاربری">
                    <app-collapse type="margin">
                        <app-collapse-item v-for="(level,key) in formData.levels_account" :title="'سطح کاربری '+ $t('l-'+(key+1))">
                            <div class="text-center mb-2">
                                <img :src="require('@/assets/images/logo/logo-'+ levelAccount(key+1) +'.png')" width="100">
                                <b-row>
                                    <b-col cols="6" class="mx-auto">
                                        <validation-provider #default="{ errors }" rules="required">
                                            <b-form-group label="مبلغ کل معاملات و سفارشات از">
                                                <cleave
                                                    v-model="level.amount_start" class="form-control text-center" dir="ltr"
                                                    :raw="false"
                                                    :options="options.number"
                                                    :state="errors.length > 0 ? false:null"
                                                    placeholder="مبلغ کل معاملات و سفارشات از"
                                                />
                                            </b-form-group>
                                        </validation-provider>
                                    </b-col>
                                    <b-col cols="6" class="mx-auto">
                                        <validation-provider #default="{ errors }" rules="required">
                                            <b-form-group label="مبلغ کل معاملات و سفارشات تا">
                                                <cleave
                                                    v-model="level.amount_stop" class="form-control text-center" dir="ltr"
                                                    :raw="false"
                                                    :options="options.number"
                                                    :state="errors.length > 0 ? false:null"
                                                    placeholder="مبلغ کل معاملات و سفارشات تا"
                                                />
                                            </b-form-group>
                                        </validation-provider>
                                    </b-col>
                                </b-row>
                            </div>
                            <b-row>
                                <b-col cols="3">
                                    <validation-provider #default="{ errors }" rules="required">
                                        <b-form-group label="کارمزد سفارشات خرید">
                                            <b-form-input
                                                v-model="level.feeBuyOrder" class="text-center" dir="ltr"
                                                :state="errors.length > 0 ? false:null"
                                                placeholder="کارمزد سفارشات خرید"
                                            />
                                        </b-form-group>
                                    </validation-provider>
                                </b-col>
                                <b-col cols="3">
                                    <validation-provider #default="{ errors }" rules="required">
                                        <b-form-group label="کارمزد سفارشات فروش">
                                            <b-form-input
                                                v-model="level.feeSellOrder" class="text-center" dir="ltr"
                                                :state="errors.length > 0 ? false:null"
                                                placeholder="کارمزد سفارشات فروش"
                                            />
                                        </b-form-group>
                                    </validation-provider>
                                </b-col>

                                <b-col cols="3">
                                    <validation-provider #default="{ errors }" rules="required|between:0,1">
                                        <b-form-group label="کارمزد ترید خرید">
                                            <b-form-input
                                                v-model="level.feeBuyTrade" class="text-center" dir="ltr"
                                                :state="errors.length > 0 ? false:null"
                                                placeholder="کارمزد ترید خرید"
                                            />
                                        </b-form-group>
                                    </validation-provider>
                                </b-col>
                                <b-col cols="3">
                                    <validation-provider #default="{ errors }" rules="required">
                                        <b-form-group label="کارمزد ترید فروش">
                                            <b-form-input
                                                v-model="level.feeSellTrade" class="text-center" dir="ltr"
                                                :state="errors.length > 0 ? false:null"
                                                placeholder="کارمزد ترید فروش"
                                            />
                                        </b-form-group>
                                    </validation-provider>
                                </b-col>
                                <validation-provider rules="required" class="d-none">
                                    <b-form-input v-model="level.number"/>
                                </validation-provider>
                            </b-row>
                        </app-collapse-item>

                    </app-collapse>


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
    } from 'bootstrap-vue'
    import {ValidationProvider, ValidationObserver} from 'vee-validate'
    import {ref} from '@vue/composition-api'
    import {required, alphaNum, between} from '@validations'
    import Ripple from 'vue-ripple-directive'
    import {MODEL_EVENT_NAME} from "bootstrap-vue/src/mixins/form-radio-check";
    import AppCollapse from '@core/components/app-collapse/AppCollapse.vue'
    import AppCollapseItem from '@core/components/app-collapse/AppCollapseItem.vue'
    import Cleave from 'vue-cleave-component'
    // eslint-disable-next-line import/no-extraneous-dependencies
    import 'cleave.js/dist/addons/cleave-phone.us'
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
            AppCollapse,
            AppCollapseItem,
            // Form Validation
            ValidationProvider,
            ValidationObserver,
            Cleave,NotAccessed
        },
        directives: {
            Ripple,
        },
        data() {
            return {
                formData:{
                    levels_account: null
                },
                options: {
                    number: {
                        numeral: true,
                        numeralThousandsGroupStyle: 'thousand',
                    },
                }
            }
        },
        methods:{
            onSubmit(){
                this.$refs.refFormLevelsObserver.validate().then(success => {
                    if (success) {
                        var levels_account = [];
                        Object.keys(this.formData.levels_account).forEach((level)=>{
                            var arr = {};
                            arr.amount_start = (this.formData.levels_account[level].amount_start).replace(/[^\d.-]/g, '');
                            arr.amount_stop = (this.formData.levels_account[level].amount_stop).replace(/[^\d.-]/g, '');
                            arr.feeBuyOrder = this.formData.levels_account[level].feeBuyOrder;
                            arr.feeSellOrder = this.formData.levels_account[level].feeSellOrder;
                            arr.feeBuyTrade = this.formData.levels_account[level].feeBuyTrade;
                            arr.feeSellTrade = this.formData.levels_account[level].feeSellTrade;
                            arr.number = this.formData.levels_account[level].number;
                            levels_account.push(arr)
                        });
                        this.$emit('onSubmit', {'levels_account':levels_account})
                    }else{
                        this.$swal({icon: 'warning',title: 'توجه!',text: 'تمامی فیلد ها با تکمیل گردد!',confirmButtonText: 'باشه'});
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

        }
    }
</script>

<style scoped>

</style>
