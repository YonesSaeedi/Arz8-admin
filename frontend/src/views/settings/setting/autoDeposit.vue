<template>
    <div v-if="formData">
        <validation-observer
            v-if="accessUserLogin['setting']['auto-deposit'] || activeUserInfo.role === 'admin'"
            #default="{ handleSubmit }"
            ref="refFormGatewayObserver"
        >
            <!-- Form -->
            <b-form
                class="px-2"
                @submit.prevent="handleSubmit(onSubmit)"
                autocomplete="off"
            >


                <b-card title="تنظیمات واریز اتوماتیک">
                    <p>
                        <strong>نکته 1:</strong>
                        این وضعیت فعال و غیر فعالی صرفا برای تسویه در هنگام ثبت درخواست کاربر برای برداشت وجه میباشد و برای ادمین ها دائما فعال است.
                    </p>
                    <p>
                        <strong>نکته 2:</strong>
                        درگاه هایی را میبینید که در قسمت تنظیمات درگاه، برداشت آن ها فعال میباشد.
                    </p>
                    <p>
                        <strong>نکته 3:</strong>
                        تنظیمات روز و ساعت های فعالی در هفته فقط برای برداشت کاربران است و ارتباطی با تسویه ادمین ها ندارد.
                    </p>
                    <b-row class="mt-1 mb-2">
                        <b-col cols="4" class="demo-inline-spacing">
                            <b-form-radio
                                v-model="formData.AutomaticDeposit.status"
                                name="status"
                                value="false"
                                class="custom-control-secondary"
                            >
                               غیر فعال
                            </b-form-radio>
                            <b-form-radio
                                v-model="formData.AutomaticDeposit.status"
                                name="status"
                                value="true"
                                class="custom-control-primary"
                            >
                                فعال
                            </b-form-radio>
                        </b-col>
                        <b-col cols="8" class="mx-auto" :class="formData.AutomaticDeposit.status==='false'?'disable-block':''">
                            <validation-provider #default="{ errors }" name="مبلغ کنترل کننده" rules="required">
                                <b-form-group label="از این مبلغ بیشتر اتوماتیک زده نمیشود">
                                    <b-form-input
                                        v-model="formData.AutomaticDeposit.amount"
                                        :state="errors.length > 0 ? false:null"
                                        placeholder="مبلغ"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                    </b-row>

                    <b-row class="mt-1 mb-2" :class="formData.AutomaticDeposit.status==='false'?'disable-block':''">
                        <hr class="w-100">

                        <b-col cols="6" class="mx-auto">
                            <validation-provider #default="{ errors }" name="وضعیت" rules="required">
                                <b-form-group label="درگاه پیش فرض برای تسویه" label-for="name">
                                    <b-form-select
                                        v-model="formData.AutomaticDeposit.GatewayWithdraw"
                                        :options="defaultOptions"
                                        :state="errors.length > 0 ? false:null"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                    </b-row>


                    <b-row v-if="formData.AutomaticDeposit.GatewayWithdraw === 'zibal'" :class="formData.AutomaticDeposit.status==='false'?'disable-block':''">
                        <b-col cols="4">
                            <validation-provider #default="{ errors }" name="توکن تسویه زیبال" rules="required">
                                <b-form-group label="توکن">
                                    <b-form-input
                                        v-model="paymentGatewayList.zibal.data.withdraw_token"
                                        :state="errors.length > 0 ? false:null"
                                        placeholder="توکن تسویه زیبال"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="4">
                            <validation-provider #default="{ errors }" name="شناسه کیف پول زیبال" rules="required">
                                <b-form-group label="شناسه کیف پول زیبال">
                                    <b-form-input
                                        v-model="paymentGatewayList.zibal.data.withdraw_id_wallet"
                                        :state="errors.length > 0 ? false:null"
                                        placeholder="شناسه کیف پول زیبال"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="4">
                            <validation-provider #default="{ errors }" name="نحوه واریز">
                                <b-form-group label="نحوه واریز">
                                    <b-form-select
                                        v-model="paymentGatewayList.zibal.data.withdraw_model"
                                        :options="[{text:'پیش فرض',value:null},{text:'برای تسویه در همان روز',value:0},{text:'برای تسویه لحظه ای پابا',value:-1}]"
                                        :state="errors.length > 0 ? false:null"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                    </b-row>

                    <b-row v-if="formData.AutomaticDeposit.GatewayWithdraw === 'baje'" :class="formData.AutomaticDeposit.status==='false'?'disable-block':''">
                        <b-col cols="4">
                            <validation-provider #default="{ errors }" name="نحوه واریز">
                                <b-form-group label="نحوه واریز">
                                    <b-form-select
                                        v-model="paymentGatewayList.baje.data.withdraw_model"
                                        :options="[{text:'پیش فرض',value:null},{text:'برای تسویه در همان روز',value:0},{text:'برای تسویه لحظه ای پابا',value:-1}]"
                                        :state="errors.length > 0 ? false:null"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="4">
                            <validation-provider #default="{ errors }" name="نحوه واریز">
                                <b-form-group label="نحوه واریز">
                                    <b-form-select
                                        v-model="paymentGatewayList.baje.data.accountId"
                                        :options="[ ...paymentGatewayList.baje.data.account.map(a => ({ text: a.accountName, value: a.accountId })) ]"
                                        :state="errors.length > 0 ? false:null"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="4">
                            <validation-provider #default="{ errors }" name="شناسه حساب پذیرنده" rules="required">
                                <b-form-group label="شناسه حساب پذیرنده">
                                    <b-form-input
                                        v-model="paymentGatewayList.baje.data.accountId"
                                        :state="errors.length > 0 ? false:null"
                                        placeholder="شناسه حساب پذیرند"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                    </b-row>


                    <b-row v-if="formData.AutomaticDeposit.GatewayWithdraw === 'vandar'" :class="formData.AutomaticDeposit.status==='false'?'disable-block':''">
                        <b-col cols="6">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-form-group label="نام بزنیس تعریف شده در وندار">
                                    <b-form-input
                                        v-model="paymentGatewayList.zibal.data.name_business"
                                        :state="errors.length > 0 ? false:null"
                                        placeholder="نام بزینس به انگلیسی"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="6">
                            <validation-provider #default="{ errors }">
                                <b-form-group label="مدل واریز">
                                    <b-form-select
                                        v-model="paymentGatewayList.zibal.data.model"
                                        :options="[{text:'پیش فرض',value:null},{text:'تسویه در لحظه',value:true}]"
                                        :state="errors.length > 0 ? false:null"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="6" class="mt-1">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-form-group label="موبایل اکانت وندار">
                                    <b-form-input
                                        v-model="paymentGatewayList.zibal.data.mobile"
                                        :state="errors.length > 0 ? false:null"
                                        placeholder="موبایل اکانت وندار"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="6" class="mt-1">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-form-group label="رمز عبور اکانت وندار">
                                    <b-form-input
                                        v-model="paymentGatewayList.zibal.data.password"
                                        :state="errors.length > 0 ? false:null"
                                        placeholder="رمز عبور اکانت وندار"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                    </b-row>

                    <hr class="w-100">

                    <b-row  :class="formData.AutomaticDeposit.status==='false'?'disable-block':''">
                        <b-col cols="6" v-for="num in 7">
                            <b-row class="mt-2">
                                <h6 class="w-100 px-1 font-weight-bold">{{getDayWeek(num)}}:</h6>
                                <b-col cols="6">
                                    <validation-provider #default="{ errors }" rules="required">
                                        <b-form-input
                                            v-model="formData.AutomaticDeposit[num-1+'0']"
                                            :state="errors.length > 0 ? false:null" dir="ltr"
                                            placeholder="از ساعت" v-mask="timeMask" class="text-center"
                                        />
                                    </validation-provider>
                                </b-col>
                                <b-col cols="6">
                                    <validation-provider #default="{ errors }"rules="required">
                                        <b-form-input
                                            v-model="formData.AutomaticDeposit[num-1+'1']"
                                            :state="errors.length > 0 ? false:null" dir="ltr"
                                            placeholder="تا ساعت" v-mask="timeMask" class="text-center"
                                        />
                                    </validation-provider>
                                </b-col>
                            </b-row>
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
         BCard, BForm, BFormGroup, BFormInput,BFormSelect, BFormInvalidFeedback, BButton, BFormFile, BSpinner,BRow,BCol, BFormRadio
    } from 'bootstrap-vue'
    import {ValidationProvider, ValidationObserver} from 'vee-validate'
    import {ref} from '@vue/composition-api'
    import {required, alphaNum, between} from '@validations'
    import Ripple from 'vue-ripple-directive'
    import {MODEL_EVENT_NAME} from "bootstrap-vue/src/mixins/form-radio-check";
    import ToastificationContent from "@core/components/toastification/ToastificationContent";
    import axiosIns from "@/libs/axios";
    import NotAccessed from "@/views/vuexy/pages/miscellaneous/NotAccessed";

    export default {
        props:['settings','paymentGatewayList'],
        components: {
            BFormRadio,
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
                defaultOptions: [],
                formData:{
                    AutomaticDeposit: null,
                },
                timeMask: {
                    mask: 'H#:M#',
                    tokens: {
                        'H': {
                            pattern: /[0-2]/,
                            transform (v) {
                                return v.toLocaleUpperCase()
                            }
                        },
                        'M': {
                            pattern: /[0-5]/,
                            transform (v) {
                                return v.toLocaleUpperCase()
                            }
                        },
                        '#': {
                            pattern: /[0-9]/,
                            transform (v) {
                                return v.toLocaleUpperCase()
                            }
                        }
                    }
                },
            }
        },
        methods:{
            onSubmit(){
                this.$refs.refFormGatewayObserver.validate().then(success => {
                    if (success) {
                        axiosIns.post('/setting/settings/auto-deposit',{gateways:this.paymentGatewayList,formData:this.formData})
                            .then(response => {
                                if(response.data.status == true){
                                    this.$toast({
                                        component: ToastificationContent,
                                        props: {
                                            title: 'موفق!',
                                            text: response.data.msg,
                                            icon: 'CheckIcon',
                                            variant: 'success',
                                        },
                                    })
                                }else{
                                    this.$toast({
                                        component: ToastificationContent,
                                        props: {
                                            title: 'خطا!',
                                            text: response.data.msg,
                                            icon: 'AlertTriangleIcon',
                                            variant: 'danger',
                                        },
                                    })
                                }
                            })
                            .catch((error) => {
                                this.errorFetching();
                            })
                    }
                })
            },
            getDayWeek(num){
                var str = '';
                switch (num-1) {
                    case 0: str =  'شنبه'; break;
                    case 1: str = 'یکشنبه'; break;
                    case 2: str = 'دوشنبه'; break;
                    case 3: str = 'سه شنبه'; break;
                    case 4: str = 'چهار شنبه'; break;
                    case 5: str = 'پنج شنبه'; break;
                    case 6: str = 'جمعه'; break;
                }
                return str;
            }
        },
        created() {
            Object.keys(this.paymentGatewayList).map(function(key, index) {
                var gateway = this.paymentGatewayList[key];
                if(gateway.withdraw === 1)
                    this.defaultOptions.push({text:this.$i18n.t(key),value:gateway.name});
            },this);


            Object.keys(this.formData).map(function(key, index) {
                this.formData[key] = this.settings[key];
            },this)
        }
    }
</script>

<style scoped>

</style>
