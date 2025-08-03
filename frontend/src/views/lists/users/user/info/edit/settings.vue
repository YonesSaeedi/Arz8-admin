<template>
    <div>
        <validation-observer
            #default="{ handleSubmit }"
            ref="refFormLevel1Observer"
        >
            <b-form
                class="px-md-2"
                @submit.prevent="handleSubmit(onSubmit)"
                autocomplete="off"
            >
            <b-col md="8" class="mx-auto">
                <b-row>
                    <b-col md="6">
                        <validation-provider #default="{ errors }" rules="required">
                            <b-form-group label="سطح کاربر">
                                <b-form-select
                                    v-model="user.level"
                                    :options="[{'text':'صفر','value':0},{'text':'یک','value':1},{'text':'دو','value':2},{'text':'سه','value':3},{'text':'چهار','value':4},{'text':'پنج','value':5},{'text':'شش VIP','value':6}]"
                                    :state="errors.length > 0 ? false:null"
                                />
                            </b-form-group>
                        </validation-provider>
                    </b-col>

                    <b-col md="6">
                        <validation-provider #default="{ errors }" rules="required">
                            <b-form-group label="زبان">
                                <b-form-select
                                    v-model="locale"
                                    :options="localeOptions"
                                    :state="errors.length > 0 ? false:null"
                                />
                            </b-form-group>
                        </validation-provider>
                    </b-col>

                    <b-col md="6">
                        <validation-provider #default="{ errors }" rules="min:6">
                            <b-form-group label="تغییر رمز عبور">
                                <b-form-input dir="ltr" class="text-center"
                                              v-model="password"
                                              :state="errors.length > 0 ? false:null"
                                              placeholder="تغییر رمز عبور"
                                />
                            </b-form-group>
                        </validation-provider>
                    </b-col>
                    <b-col md="6">
                        <validation-provider #default="{ errors }" rules="required">
                            <b-form-group label="ورود دو مرحله ای">
                                <b-form-select
                                    v-model="twofa"
                                    :options="[{'text':'پیامک','value':'sms'},{'text':'گوگل','value':'google'},{'text':'تلگرام','value':'telegram'},{'text':'ایمیل','value':'email'},{'text':'غیرفعال','value':false}]"
                                    :state="errors.length > 0 ? false:null"
                                />
                            </b-form-group>
                        </validation-provider>
                    </b-col>

                    <b-col md="6">
                        <validation-provider #default="{ errors }">
                            <b-form-group label="شناسه معرف این کاربر">
                                <b-form-input dir="ltr" class="text-center"
                                              v-model="referralId" disabled=""
                                              :state="errors.length > 0 ? false:null"
                                              placeholder="شناسه معرف این کاربر"
                                />
                                <small v-if="user.referral">
                                    <b-link
                                        :to="{ name: 'user-single', params: { id: user.referral.id } }"
                                        class="font-weight-bold d-block text-nowrap" target="_blank"
                                    >
                                        {{user.referral.name ? user.referral.name+' '+user.referral.family : '-----'}} - {{user.referral.email}}
                                    </b-link>
                                </small>
                            </b-form-group>
                        </validation-provider>
                    </b-col>

                    <b-col md="6">
                        <validation-provider #default="{ errors }" rules="required">
                            <b-form-group label="امکان مشاهده ارزهای دلاری">
                                <b-form-select
                                    v-model="accessDigitalMoney"
                                    :options="[{'text':'عدم امکان مشاهده','value':false},{'text':'مجاز به مشاهده','value':true}]"
                                    :state="errors.length > 0 ? false:null"
                                />
                            </b-form-group>
                        </validation-provider>
                    </b-col>

                    <b-col md="6">
                        <validation-provider #default="{ errors }" rules="required">
                            <b-form-group label="محدودیت برداشت">
                                <b-form-select
                                    v-model="withdrawalLimit"
                                    :options="[{'text':'محدودیت ندارد','value':false},{'text':'محدود','value':true}]"
                                    :state="errors.length > 0 ? false:null"
                                />
                            </b-form-group>
                        </validation-provider>
                    </b-col>

                    <b-col md="6">
                        <validation-provider #default="{ errors }" rules="required">
                            <b-form-group label="امکان برداشت رمز ارز و خرید ارز دلاری">
                                <b-form-select
                                    v-model="withdrawalCrypto"
                                    :options="[{'text':'عدم امکان برداشت و خرید ارز دلاری','value':false},{'text':'مجاز به برداشت و خرید ارز دلاری','value':true}]"
                                    :state="errors.length > 0 ? false:null"
                                />
                            </b-form-group>
                        </validation-provider>
                    </b-col>
                    <b-col md="6">
                        <validation-provider #default="{ errors }" rules="required">
                            <b-form-group label="امکان واریز با فیش">
                                <b-form-select
                                    v-model="depositReceipt"
                                    :options="[{'text':'عدم امکان واریز با فیش','value':false},{'text':'مجاز به ثبت فیش','value':true}]"
                                    :state="errors.length > 0 ? false:null"
                                />
                            </b-form-group>
                        </validation-provider>
                    </b-col>

                    <hr class="w-100">
                    <h5 class="w-100">اعلانات</h5>
                    <b-col md="6" class="mb-1">
                        <b-form-checkbox :checked="Notifications.LoginEmail" v-model="Notifications.LoginEmail" name="check-button" switch inline>
                            دریافت ایمیل بعد از لاگین
                        </b-form-checkbox>
                    </b-col>
                    <b-col md="6" class="mb-1">
                        <b-form-checkbox :checked="Notifications.LoginSms" v-model="Notifications.LoginSms" name="check-button" switch inline>
                            دریافت پیامک بعد از لاگین
                        </b-form-checkbox>
                    </b-col>

                    <b-col md="6" class="mb-1">
                        <b-form-checkbox :checked="Notifications.TradesSms" v-model="Notifications.TradesSms" name="check-button" switch inline>
                            دریافت پیامک بعد از انجام معامله
                        </b-form-checkbox>
                    </b-col>
                    <b-col md="6" class="mb-1">
                        <b-form-checkbox :checked="Notifications.TradesEmail" v-model="Notifications.TradesEmail" name="check-button" switch inline>
                            دریافت ایمیل بعد از انجام معامله
                        </b-form-checkbox>
                    </b-col>
                    <b-col md="6" class="mb-1">
                        <b-form-checkbox :checked="Notifications.TradesNotif" v-model="Notifications.TradesNotif" name="check-button" switch inline>
                            دریافت نوتیفیکیشن بعد از انجام معامله
                        </b-form-checkbox>
                    </b-col>


                    <hr class="w-100">
                    <b-col md="6" class=" mx-auto">
                        <b-button block
                                  v-ripple.400="'rgba(255, 255, 255, 0.15)'"
                                  variant="primary"
                                  class="mr-2 d-flex align-items-center justify-content-center"
                                  type="submit"
                                  :disabled="isLoading"
                        >
                            <div>ثبت تغییرات</div>
                            <div class="line-height-0 ml-25"><b-spinner v-if="isLoading" small></b-spinner></div>
                        </b-button>
                    </b-col>
                </b-row>
            </b-col>

        </b-form>
        </validation-observer>


    </div>
</template>

<script>
    import {
        BCard, BCardHeader, BBadge, BCollapse, BLink, BCardBody, BRow, BCol, BInputGroup, BInputGroupAppend, BFormGroup, BFormInput, BTable, BButton,
        BMedia, BAvatar,BTab,BTabs,BForm,BFormSelect,BSpinner,BFormCheckbox
    } from 'bootstrap-vue';
    import Table from "@/views/vuexy/table/bs-table/Table";
    import BCardActions from "@core/components/b-card-actions/BCardActions";
    import {ValidationProvider, ValidationObserver} from 'vee-validate'
    import {required, alphaNum, between,min} from '@validations'
    import Ripple from "vue-ripple-directive";
    import axiosIns from "@/libs/axios";
    import ToastificationContent from "@core/components/toastification/ToastificationContent";

    export default {
        props:['user','data'],
        components: {
            Table,
            BTable,
            BLink,
            BCard,
            BBadge,
            BRow,
            BCol,
            BCardActions,
            BCardHeader,
            BCardBody,
            BCollapse,
            BButton,
            BInputGroup,BFormGroup, BInputGroupAppend,BFormInput,
            BMedia, BAvatar,BTab,BTabs,BForm,BFormSelect,BSpinner,
            BFormCheckbox,
            ValidationProvider,
            ValidationObserver,
        },
        directives: {
            Ripple,
        },
        data() {
            return {
                twofa: null,
                password: null,
                locale: null,
                localeOptions: [],
                isLoading:false,
                NotificationsDefault: {
                    TradesSms: false,
                    TradesEmail: true,
                    TradesNotif: true,
                    LoginSms:false,
                    LoginEmail:false
                },
                Notifications: {
                    TradesSms: false,
                    TradesEmail: true,
                    TradesNotif: true,
                    LoginSms:false,
                    LoginEmail:false
                },
                referralId:null,
                accessDigitalMoney:false,
                withdrawalLimit:false,
                withdrawalCrypto:true,
                depositReceipt:false,
            }
        },
        methods:{
            onSubmit(){
                this.$refs.refFormLevel1Observer.validate().then(success => {
                    if (success) {
                        this.isLoading = true;
                        axiosIns.post('/users/edit/settings/'+this.user.id,{
                            twofa: this.twofa,
                            password: this.password,
                            localization: this.locale,
                            notifications: this.Notifications,
                            level: this.user.level,
                            referralId: this.referralId,
                            accessDigitalMoney: this.accessDigitalMoney,
                            withdrawalLimit: this.withdrawalLimit,
                            withdrawalCrypto: this.withdrawalCrypto,
                            depositReceipt: this.depositReceipt,
                        })
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
                                this.$emit('getUser');
                                this.isLoading = false;
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
                                this.isLoading = false;
                            }
                        })
                        .catch((error) => {
                            this.errorFetching();
                        })
                    }
                })
            }
        },
        created() {
            Object.keys(this.data.locale).map(function(key, index) {
                this.localeOptions.push({'text':this.data.locale[key].name,'value':this.data.locale[key].symbol});
            },this)

            this.locale = (this.user.settings && this.user.settings.localization)? this.user.settings.localization : 'fa';

            if(this.user.twofa == null || this.user.twofa.status === false)
                this.twofa = false;
            else{
                this.twofa = this.user.twofa.type;
            }

            if(this.user.settings==null || this.user.settings.notifications==null){
                this.user.settings = this.user.settings ? this.user.settings : [];
                this.Notifications = this.NotificationsDefault;
            }else
                this.Notifications = this.user.settings.notifications;

            if(this.user.settings.withdrawal_limit == null){
                this.withdrawalLimit = true
            }else
                this.withdrawalLimit = this.user.settings.withdrawal_limit;

            if(this.user.settings.withdrawal_crypto == null){
                this.withdrawalCrypto = true
            }else
                this.withdrawalCrypto = this.user.settings.withdrawal_crypto;

            if(this.user.settings.deposit_receipt == null){
                this.depositReceipt = false
            }else
                this.depositReceipt = this.user.settings.deposit_receipt;

            this.referralId = this.user.referral ? this.user.referral.id : null;
            this.accessDigitalMoney = typeof this.user.settings.access_digital_money !== 'undefined'? this.user.settings.access_digital_money : false
        }
    }
</script>

<style scoped>

</style>
