<template>
    <div>
        <!-- basic modal -->
        <b-modal
            v-model="status"
            id="change-balance-internal-modal" ref="change-balance-internal-modal"
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
                        <img :src="baseURL+'images/currency/iran.svg'" width="60px" />
                    </div>
                    <hr class="w-100">

                    <b-form ref="form" @submit.stop.prevent="handleSubmit">
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
                </validation-observer>
            </b-card-text>

            <template #modal-footer>
                <div class="w-100">
                    <b-button variant="primary" class="float-right d-flex" @click="handleSubmit" :disabled="isLoadingEdit">
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
        BInputGroup,BInputGroupPrepend,BInputGroupAppend,BSpinner, BBadge, BFormSelect
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
        props:['user','modalBalanceInternalStatus','idWalletChangeBalanceInternal'],
        data() {
            return {
                isLoadingEdit:false,
                wallet: null,
                status: false,
                balanceAfter: null,
                type:null,
                amount:'',
                description:null
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
            BSpinner, BBadge,BFormSelect
        },
        directives: {
            'b-modal': VBModal,
            Ripple,
        },
        watch:{
            idWalletChangeBalanceInternal(value){
                if(value != null)
                    this.getWallet();
            },
            status(value){
                if(value === false){
                    this.$emit('change-status-balacne-internal',null);
                }
            },
            modalBalanceInternalStatus(){
                this.status = this.modalBalanceInternalStatus;
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
                axiosIns.post('users/wallets/'+ this.idWalletChangeBalanceInternal +'/internal-single') .then(response => {
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
                        axiosIns.post('users/wallets/'+this.wallet.id+'/internal-transaction',{
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
            }
        },
    }
</script>

<style scoped>

</style>
