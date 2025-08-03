<template>
    <div>
        <!-- basic modal -->
        <b-modal
            v-model="status"
            id="change-balance-crypto-modal" ref="change-balance-crypto-modal"
            title="تغییر موجودی"
            ok-title="ثبت تراکنش"
            cancel-title="بستن"
            cancel-variant="outline-secondary"
            @ok="handleSubmit"
        >
            <b-card-text>
                <div class="text-center my-2" v-if="!wallet">
                    <b-spinner style="width: 3rem; height: 3rem;"/>
                </div>

                <validation-observer ref="simpleRules" v-else>
                    <div class="text-center">
                        <i class="cf" style="font-size: 60px" v-if="isFontExist(wallet.symbol)" :class="'cf-'+wallet.symbol.toLowerCase()" :style="{color:colorSymbol(wallet.symbol)}"></i>
                        <img :src="baseURL+'images/currency/' + iconSymbol(wallet.symbol)" width="60px" v-else />
                        <h5 class="mt-1 text-capitalize"> {{localeNameSymbol(wallet.symbol)[localeHas]}} -  {{localeNameSymbol(wallet.symbol)['en']}}</h5>
                    </div>
                    <hr class="w-100">

                    <b-tabs v-model="activeTab">
                        <b-tab title="درج تراکنش"></b-tab>
                        <b-tab title="یکسان کردن موجود ها"></b-tab>
                    </b-tabs>

                    <b-form ref="form" @submit.stop.prevent="handleSubmit" v-if="activeTab===0">
                        <b-form-group label="موجودی فعلی" label-cols-md="4">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-input-group class="input-group-merge">
                                    <b-form-input v-model="wallet.balance" dir="ltr" class="text-center" disabled/>
                                    <b-input-group-append is-text>
                                        <div>
                                            {{wallet.symbol}}
                                        </div>
                                    </b-input-group-append>
                                </b-input-group>
                            </validation-provider>
                        </b-form-group>

                        <b-form-group label="موجودی بعد تراکنش" label-cols-md="4">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-input-group class="input-group-merge">
                                    <b-form-input v-model="balanceAfter" dir="ltr" class="text-center" disabled/>
                                    <b-input-group-append is-text>
                                        <div>
                                            {{wallet.symbol}}
                                        </div>
                                    </b-input-group-append>
                                </b-input-group>
                            </validation-provider>
                        </b-form-group>

                        <b-form-group label="نوع تراکنش" label-cols-md="4">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-form-select
                                    v-model="type"
                                    :options="[{'text':'واریز','value':'deposit'},{'text':'برداشت','value':'withdraw'}]"
                                    :state="errors.length > 0 ? false:null"
                                />
                            </validation-provider>
                        </b-form-group>

                        <b-form-group label="مقدار" label-cols-md="4">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-input-group class="input-group-merge">
                                    <b-form-input v-model="amount" dir="ltr" class="text-center"/>
                                    <b-input-group-append is-text>
                                        <div>
                                            {{wallet.symbol}}
                                        </div>
                                    </b-input-group-append>
                                </b-input-group>
                            </validation-provider>
                        </b-form-group>

                        <b-form-group label="شرح تراکنش" label-cols-md="4">
                            <validation-provider #default="{ errors }" rules="required">
                                <b-form-input
                                    v-model="description"
                                    :state="errors.length > 0 ? false:null"
                                    :placeholder="'شرح تراکنش به زبان '+ $t(user.settings.localization)"
                                />
                            </validation-provider>
                        </b-form-group>

                    </b-form>

                    <div v-if="activeTab===1">
                        <p>نوع یکسان کردن موجودی را انتخاب کنید:</p>
                        <b-form-radio-group
                            id="fixation-balance"
                            v-model="fixationBalanceType"
                            stacked
                        >
                            <b-form-radio name="fixation-balance" value="balanceAvailable">موجودی با "موجودی در دسترس" یکسان شود</b-form-radio>
                            <b-form-radio name="fixation-balance" value="balance" class="mt-50">موجودی در دسترس با "موجودی" یکسان شود</b-form-radio>
                        </b-form-radio-group>
                        <hr width="40%">
                        <b-form-checkbox v-model="fixationTransaction" name="check-button" switch>
                            تراکنش در لیست تراکنش های کاربر ثبت <b> {{ fixationTransaction?'شود':'نشود' }}</b>
                        </b-form-checkbox>
                    </div>
                </validation-observer>
            </b-card-text>

            <template #modal-footer>
                <div class="w-100">
                    <b-button variant="primary" class="float-right d-flex" @click="submitForm" :disabled="isLoadingEdit">
                        <div>ذخیره تغییرات</div>
                        <div class="line-height-0 ml-25"><b-spinner v-if="isLoadingEdit" small></b-spinner></div>
                    </b-button>
                    <b-button variant="outline-secondary" class="float-right mr-1" @click="status = false">
                        انصراف
                    </b-button>
                </div>
            </template>
        </b-modal>

    </div>
</template>

<script>
    import {
        BModal, BButton, VBModal, BAlert, BCardText, BFormGroup, BFormInput, BListGroup, BListGroupItem,BRow,BCol,BForm,
        BInputGroup,BInputGroupPrepend,BInputGroupAppend,BSpinner, BBadge, BFormSelect, BTabs,BTab,BFormRadioGroup,BFormRadio,
        BFormCheckbox
    } from 'bootstrap-vue';
    import Ripple from "vue-ripple-directive";
    import BCardCode from "@core/components/b-card-code";
    import axiosIns from "@/libs/axios";
    import { ValidationProvider, ValidationObserver } from 'vee-validate'
    import {
        required, email, confirmed, password,
    } from '@validations'
    import ToastificationContent from "@core/components/toastification/ToastificationContent";

    export default {
        props:['user','modalBalanceCryptoStatus','symbol'],
        data() {
            return {
                isLoadingEdit:false,
                activeTab:0,
                wallet: null,
                status: false,
                balanceAfter: null,
                type:null,
                amount:'',
                description:null,

                fixationBalanceType:'balance',
                fixationTransaction:false
            }
        },
        components: {
            ValidationProvider, ValidationObserver,
            BCardCode,
            BButton,
            BModal,
            BAlert,
            BCardText,BFormGroup,BFormInput,BListGroup,BListGroupItem,BRow,BCol,BForm,
            BInputGroup,BInputGroupPrepend,BInputGroupAppend,
            BSpinner, BBadge,BFormSelect,BTabs,BTab,BFormRadioGroup,BFormRadio,BFormCheckbox
        },
        directives: {
            'b-modal': VBModal,
            Ripple,
        },
        watch:{
            symbol(value){
                if(value != null)
                    this.getWallet();
            },
            status(value){
                if(value === false){
                    this.$emit('change-status-balacne-crypto',null);
                }
            },
            modalBalanceCryptoStatus(){
                this.status = this.modalBalanceCryptoStatus;
            },
            type(){
                this.calculate()
            },
            amount(value){
                var amount = this.amountLocalFloat(value, this.wallet.percent)
                this.amount = this.subStrFloat(amount, this.wallet.percent);
                this.calculate()
            },
            'wallet.balance'(value){
                if(value)
                    this.wallet.balance = this.toFixFloat(value)
            }
        },
        methods: {
            getWallet(){
                this.wallet = null
                this.amount = ''
                this.type = null
                this.description = null
                axiosIns.post('users/wallets/'+ this.symbol +'/crypto-single',{id_user:this.user.id}) .then(response => {
                    this.wallet = response.data.wallet;
                    this.balanceAfter = this.toFixFloat(this.wallet.balance)
                })
                .catch(() => {
                    this.errorFetching();
                })
            },
            calculate(){
                  if(this.type !== null){
                      if(this.type === 'deposit'){
                          this.balanceAfter = parseFloat(this.removeLocalString(this.wallet.balance)) + parseFloat(this.amount?this.removeLocalString(this.amount) :0);
                      }else{
                          this.balanceAfter = parseFloat(this.removeLocalString(this.wallet.balance)) - parseFloat(this.amount?this.removeLocalString(this.amount) :0);
                      }
                      this.balanceAfter = this.toFixFloat(this.balanceAfter)
                  }
            },
            handleSubmit(){
                this.$refs.simpleRules.validate().then(success => {
                    if (success) {
                        this.isLoadingEdit = true;
                        axiosIns.post('users/wallets/'+this.wallet.id+'/crypto-transaction',{
                            amount: this.removeLocalString(this.amount),
                            type:this.type,
                            description:this.description?.trim() || null,
                        }) .then(response => {
                            if (response.data.status == true){
                                this.$emit('refetch-data')
                                this.status = false;
                                this.$toast({component: ToastificationContent, props: {title: 'انجام شد!', text: response.data.msg, icon: 'CheckCircleIcon', variant: 'success'}})
                            }else{
                                this.$toast({component: ToastificationContent, props: {title: 'خطا!', text: response.data.msg, icon: 'AlertTriangleIcon', variant: 'danger'}})
                            }
                            this.isLoadingEdit = false;
                        })
                        .catch(() => {
                            this.errorFetching();
                            this.isLoadingEdit = false;
                        })
                    }
                })
            },
            fixationBalance(){
                this.isLoadingEdit = true;
                axiosIns.post('users/wallets/'+this.wallet.id+'/crypto-balance-fixation',{
                    fixationBalanceType:this.fixationBalanceType,
                    fixationTransaction:this.fixationTransaction,
                }) .then(response => {
                    if (response.data.status == true){
                        this.$emit('refetch-data')
                        this.status = false;
                        this.$toast({component: ToastificationContent, props: {title: 'انجام شد!', text: response.data.msg, icon: 'CheckCircleIcon', variant: 'success'}})
                    }else{
                        this.$toast({component: ToastificationContent, props: {title: 'خطا!', text: response.data.msg, icon: 'AlertTriangleIcon', variant: 'danger'}})
                    }
                    this.isLoadingEdit = false;
                })
                    .catch(() => {
                        this.errorFetching();
                        this.isLoadingEdit = false;
                    })
            },
            submitForm(){
                if(this.activeTab === 0)
                    this.handleSubmit()
                else if(this.activeTab === 1)
                    this.fixationBalance();
            }
        },
    }
</script>

<style scoped>

</style>
