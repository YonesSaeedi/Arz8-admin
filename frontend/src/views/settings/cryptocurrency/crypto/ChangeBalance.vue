<template>
    <b-row class="col-lg-8 offset-md-4 px-0">
        <b-col cols="6">
            <b-button variant="info" block class="text-center d-flex align-items-center justify-content-center" @click="modalWithdraw=true" :disabled="isLoading">
                <div>انتقال به کیف پول</div>
            </b-button>
        </b-col>
        <b-col cols="6">
            <b-button variant="success" block class="text-center d-flex align-items-center justify-content-center" @click="modalTrade=true" :disabled="isLoading">
                <div>ترید به تتر</div>
            </b-button>
        </b-col>
        <b-col cols="6" class="mt-1">
            <b-button variant="warning" block class="text-center d-flex align-items-center justify-content-center" @click="blalnceFit" :disabled="isLoading">
                <div>بالانس موجودی</div>
            </b-button>
        </b-col>
        <b-col cols="6" class="mt-1">
            <b-button variant="light" block class="text-center d-flex align-items-center justify-content-center" @click="modalAllBalance=true" :disabled="isLoading">
                <div>تبدیل موجودی کاربران</div>
            </b-button>
        </b-col>


        <b-modal
            v-model="modalWithdraw"
            id="modalWithdraw"
            :title="'انتقال '+localeNameSymbol(crypto.symbol)[localeHas]+' به کیف پول دیگر'"
            cancel-variant="outline-secondary"
            centered
        >
            <b-card-text>
                <validation-observer ref="withdrawRules" class="w-100" #default="{ handleSubmit }">
                    <b-form ref="form" @submit.prevent="handleSubmit(onSubmit)" autocomplete="off">
                        <b-col cols="12">
                            <b-form-group :label="'مقدار '+localeNameSymbol(crypto.symbol)[localeHas]" label-cols-md="5">
                                <validation-provider #default="{ errors }" rules="required">
                                    <b-form-input v-model="amount" placeholder="مقدار" dir="ltr" :state="errors.length > 0 ? false:null" class="text-center"/>
                                </validation-provider>
                                <small>
                                    موجودی صرافی مربوطه:
                                    <span class="text-primary cursor-pointer" @click="amount=amountLocalFloat(balanceBinance.toString(), 8)">{{balanceBinance}}#</span>
                                </small>
                            </b-form-group>
                        </b-col>
                        <b-col cols="12">
                            <b-form-group label="شبکه انتقال" label-cols-md="5">
                                <validation-provider #default="{ errors }" rules="required">
                                    <b-form-select
                                        v-model="network"
                                        :options="networkOptions"
                                        :state="errors.length > 0 ? false:null"
                                    />
                                </validation-provider>
                            </b-form-group>
                        </b-col>
                        <hr width="30%">
                        <b-col cols="12">
                            <b-form-group label="آدرس کیف پول گیرنده" label-cols-md="12">
                                <validation-provider #default="{ errors }" rules="required">
                                    <b-form-input v-model="addressWallet" placeholder="آدرس کیف پول" dir="ltr" :state="errors.length > 0 ? false:null" class="text-center"/>
                                </validation-provider>
                            </b-form-group>
                        </b-col>
                        <b-col cols="12">
                            <b-form-group label="تگ آدرس کیف پول گیرنده(اختیاری)" label-cols-md="12">
                                <validation-provider #default="{ errors }" rules="">
                                    <b-form-input v-model="addressTagWallet" placeholder="تگ آدرس (اختیاری)" dir="ltr" :state="errors.length > 0 ? false:null" class="text-center"/>
                                </validation-provider>
                            </b-form-group>
                        </b-col>

                        <button type="submit" class="d-none" ref="submit"></button>
                        <button type="reset" class="d-none" ref="resetForm"></button>
                    </b-form>
                </validation-observer>

            </b-card-text>
            <template #modal-footer>
                <div class="w-100 d-flex">
                    <b-button variant="info" class="text-center d-flex align-items-center justify-content-center ml-auto" @click="$refs.submit.click()" :disabled="isLoading">
                        <div v-if="!isLoading">
                            <feather-icon icon="CheckIcon" size="15"/> انتقال
                        </div>
                        <div v-else class="line-height-0 ml-25"><b-spinner small></b-spinner></div>
                    </b-button>
                    <b-button variant="outline-secondary" class="float-right ml-1" @click="modalWithdraw=false">
                        بستن
                    </b-button>
                </div>
            </template>
        </b-modal>

        <b-modal
            v-model="modalTrade"
            id="modalTrade"
            :title="'ترید '+localeNameSymbol(crypto.symbol)[localeHas]+' به تتر'"
            cancel-variant="outline-secondary"
            centered
        >
            <b-card-text>
                <validation-observer ref="tradeRules" class="w-100" #default="{ handleSubmit }">
                    <b-form ref="form" @submit.prevent="handleSubmit(onSubmitTrade)" autocomplete="off">
                        <b-col cols="12">
                            <b-form-group :label="'مقدار '+localeNameSymbol(crypto.symbol)[localeHas]" label-cols-md="5">
                                <validation-provider #default="{ errors }" rules="required">
                                    <b-form-input v-model="amount" placeholder="مقدار" dir="ltr" :state="errors.length > 0 ? false:null" class="text-center"/>
                                </validation-provider>
                                <small>
                                    موجودی بایننس:
                                    <span class="text-primary cursor-pointer" @click="amount=amountLocalFloat(balanceBinance.toString(), 8)">{{balanceBinance}}#</span>
                                </small>
                            </b-form-group>
                            <hr width="40%">
                            <b-form-checkbox v-model="reverse" name="check-button" switch>
                                ترید معکوس انجام<b> {{ reverse?'شود':'نشود' }}.</b>
                                (تبدیل {{!reverse?localeNameSymbol(crypto.symbol)[localeHas]+' به تتر':'تتر به '+localeNameSymbol(crypto.symbol)[localeHas]}})
                            </b-form-checkbox>
                        </b-col>

                        <button type="submit" class="d-none" ref="submitTrade"></button>
                        <button type="reset" class="d-none" ref="resetFormTrade"></button>
                    </b-form>
                </validation-observer>

            </b-card-text>
            <template #modal-footer>
                <div class="w-100 d-flex">
                    <b-button variant="success" class="text-center d-flex align-items-center justify-content-center ml-auto" @click="$refs.submitTrade.click()" :disabled="isLoading">
                        <div v-if="!isLoading">
                            <feather-icon icon="CheckIcon" size="15"/>
                            تبدیل {{!reverse?localeNameSymbol(crypto.symbol)[localeHas]+' به تتر':'تتر به '+localeNameSymbol(crypto.symbol)[localeHas]}}
                        </div>
                        <div v-else class="line-height-0 ml-25"><b-spinner small></b-spinner></div>
                    </b-button>
                    <b-button variant="outline-secondary" class="float-right ml-1" @click="modalTrade=false">
                        بستن
                    </b-button>
                </div>
            </template>
        </b-modal>

        <b-modal
            v-model="modalAllBalance"
            id="modalAllBalance"
            :title="'تبدیل همه موجودی کاربران از '+localeNameSymbol(crypto.symbol)[localeHas]+' به تومان'"
            cancel-variant="outline-secondary"
            centered
        >
            <b-card-text>
                <validation-observer ref="allBalanceRules" class="w-100" #default="{ handleSubmit }">
                    <b-form ref="form" @submit.prevent="handleSubmit(onSubmitAllBalance)" autocomplete="off">
                        <b-col cols="12">
                            <b-form-group :label="'قیمت تتری ارز '+localeNameSymbol(crypto.symbol)[localeHas]" label-cols-md="5">
                                <validation-provider #default="{ errors }" rules="required">
                                    <b-form-input v-model="feePerTheter" placeholder="قیمت" dir="ltr" :state="errors.length > 0 ? false:null" class="text-center"/>
                                </validation-provider>
                            </b-form-group>
                        </b-col>
                        <button type="submit" class="d-none" ref="submitAllBalance"></button>
                        <button type="reset" class="d-none" ref="resetFormAllBalance"></button>
                    </b-form>
                </validation-observer>

            </b-card-text>
            <template #modal-footer>
                <div class="w-100 d-flex">
                    <b-button variant="warning" class="text-center d-flex align-items-center justify-content-center ml-auto" @click="$refs.submitAllBalance.click()" :disabled="isLoading">
                        <div v-if="!isLoading">
                            <feather-icon icon="CheckIcon" size="15"/> تبدیل همه
                        </div>
                        <div v-else class="line-height-0 ml-25"><b-spinner small></b-spinner></div>
                    </b-button>
                    <b-button variant="outline-secondary" class="float-right ml-1" @click="modalAllBalance=false">
                        بستن
                    </b-button>
                </div>
            </template>
        </b-modal>

    </b-row>
</template>

<script>
import {
    BRow,BForm, BCol, BInputGroup, BInputGroupAppend, BFormGroup, BFormInput, BTable, BButton,BAlert,BSpinner,BCardText,
    BModal,BFormSelect,BFormCheckbox
} from 'bootstrap-vue'
import { ValidationProvider, ValidationObserver } from 'vee-validate'
import {
    required,between
} from '@validations'
import axiosIns from "@/libs/axios";

export default {
    components: {
        BRow,BForm, BCol, BInputGroup, BInputGroupAppend, BFormGroup, BFormInput, BTable, BButton,BAlert,BSpinner,BCardText,
        BModal,ValidationProvider, ValidationObserver,BFormSelect,BFormCheckbox
    },
    props:['balanceBinance','crypto','networks','cryptoNetworks'],
    data () {
        return {
            isLoading: false,
            modalWithdraw: false,
            modalTrade: false,
            amount: '',
            network: null,
            addressWallet: '',
            addressTagWallet: '',
            codeOtp: '',
            networkOptions:[],
            reverse:false,

            modalAllBalance: false,
            feePerTheter:''
        }
    },
    methods:{
        onSubmit() {
            this.$refs.withdrawRules.validate().then(success => {
                if (success) {
                    var network = this.networks.find((el)=> el.id === this.network);
                    var networkCrypto = this.cryptoNetworks.find((el)=> el.id_network === this.network);
                    var withdrawAmount = this.amount.replace(/[^\d.-]/g, '');

                    if(! this.addressWallet.match(network.addressRegex) ){
                        this.$swal(this.$i18n.t('Warning'), 'آدرس کیف پول اشتباه است!' ,'warning')
                        return;
                    }

                    if(network.tag && !this.addressTagWallet.match(this.network.memoRegex) ){
                        this.$swal(this.$i18n.t('Warning'), 'تگ آدرس ولت اشتباه است!' ,'warning')
                        return;
                    }

                    if(withdrawAmount > this.balanceBinance){
                        this.$swal(this.$i18n.t('Warning'), 'مقدار مورد نظر بیشتر از موجودی فعلی در بایننس است!' ,'warning')
                        return;
                    }

                    if(withdrawAmount < parseFloat(networkCrypto.withdraw_min)){
                        this.$swal(this.$i18n.t('Warning'), 'مقدار از حداقل برداشت کمتر است! حداقل مجاز: '+networkCrypto.withdraw_min+''+this.crypto.symbol ,'warning')
                        return;
                    }

                    this.isLoading = true;
                    axiosIns.post('setting/crypto/withdraw/'+this.crypto.id+'',{
                        amount:withdrawAmount,
                        network:this.network,
                        addressWallet:this.addressWallet,
                        addressTagWallet:this.addressTagWallet,
                        codeOtp:this.codeOtp,
                    }) .then(response => {
                        if (response.data.status == true) {
                            this.network = null;
                            this.addressWallet = '';
                            this.addressTagWallet = '';
                            this.amount = '';
                            this.modalWithdraw = false;
                            this.$refs.resetForm.click();
                            this.$emit('getCrypto',this.crypto.id)
                            this.$swal({icon: 'success', title:'موفق', text: response.data.msg})
                        }else if(response.data.otp == true) {
                            this.otp(response,'modalWithdraw');
                        }else
                            this.$swal({icon: 'danger', title:'خطا', text: response.data.msg})

                        this.isLoading = false;
                    }).catch((error) => { console.log(error); this.errorFetching(); })

                }else{
                    this.$swal({icon: 'warning',title: 'نکته!',text: 'تمامی فیلد ها رو بررسی کنید!',confirmButtonText: 'باشه'})
                }
            })
        },
        onSubmitTrade() {
            this.$refs.tradeRules.validate().then(success => {
                if (success) {
                    var withdrawAmount = this.amount.replace(/[^\d.-]/g, '');

                    this.isLoading = true;
                    axiosIns.post('setting/crypto/trade/'+this.crypto.id+'',{
                        amount:withdrawAmount,
                        codeOtp:this.codeOtp,
                        reverse:this.reverse,
                    }) .then(response => {
                        if (response.data.status == true) {
                            this.amount = '';
                            this.$refs.resetFormTrade.click();
                            this.modalTrade = false;
                            this.$emit('getCrypto',this.crypto.id)
                            this.$swal({icon: 'success', title:'موفق', text: response.data.msg})
                        }else if(response.data.otp == true) {
                            this.otp(response,'modalTrade');
                        }else
                            this.$swal({icon: 'error', title:'خطا', text: response.data.msg})
                        this.isLoading = false;
                    }).catch((error) => { console.log(error); this.errorFetching(); })

                }else{
                    this.$swal({icon: 'warning',title: 'نکته!',text: 'تمامی فیلد ها رو بررسی کنید!',confirmButtonText: 'باشه'})
                }
            })
        },
        onSubmitAllBalance() {
            this.$refs.allBalanceRules.validate().then(success => {
                if (success) {
                    var feePerTheter = this.feePerTheter.replace(/[^\d.-]/g, '');
                    this.isLoading = true;
                    axiosIns.post('setting/crypto/all-balance/'+this.crypto.id+'',{
                        fee: feePerTheter,
                        codeOtp:this.codeOtp,
                    }) .then(response => {
                        if (response.data.status == true) {
                            this.amount = '';
                            this.$refs.resetFormAllBalance.click();
                            this.modalAllBalance = false;
                            this.feePerTheter = '';
                            this.$emit('getCrypto',this.crypto.id)
                            this.$emit('getList')
                            this.$swal({icon: 'success', title:'موفق', text: response.data.msg})
                        }else if(response.data.otp == true) {
                            this.otp(response,'modalAllBalance');
                        }else
                            this.$swal({icon: 'error', title:'خطا', text: response.data.msg})
                        this.isLoading = false;
                    }).catch((error) => { console.log(error); this.errorFetching(); })

                }else{
                    this.$swal({icon: 'warning',title: 'نکته!',text: 'تمامی فیلد ها رو بررسی کنید!',confirmButtonText: 'باشه'})
                }
            })
        },
        otp(response,idModal){
            var element = document.getElementById(idModal);
            element.classList.add('invisible');
            this.$swal({
                icon: 'question',
                title: 'کد تایید برای انجام تغییرات',
                input: 'text',
                inputAttributes: {
                    autocomplete: 'off'
                },
                confirmButtonText: 'تایید کد',
                cancelButtonText: 'انصراف',
                inputLabel: (response.data.msgOtp?response.data.msgOtp:response.data.msg),
                showCancelButton: true,
                inputValidator: (value) => {
                    if (!value) {
                        return 'کد را درج کنید!'
                    }
                },
                preConfirm: (value) => {
                    element.classList.remove('invisible');
                    this.codeOtp = value;
                    if(idModal ==='modalWithdraw')
                        this.$refs.submit.click()
                    else if(idModal ==='modalAllBalance')
                        this.$refs.submitAllBalance.click()
                    else
                        this.$refs.submitTrade.click()
                },
            }).then((result) => {
                if (result.dismiss === 'cancel' || result.dismiss === 'backdrop') {
                    this.codeOtp = null;
                    element.classList.remove('invisible');
                }
            })
        },
        blalnceFit(){
            this.$swal({
                title: 'از تنظیم کردن موجودی اطمینان دارید؟',
                text: 'به مقدار موجودی سایر معامله ای خرید یا فروش انجام میشود.',
                icon: 'question',
                showCancelButton: true,
                showLoaderOnConfirm: true,
                buttonsStyling: false,
                preConfirm: (value) => {
                    return axiosIns.post('setting/crypto/fit-balance/'+this.crypto.id)
                        .then(response => {
                            return response;
                        })
                        .catch(() => {
                            this.errorFetching();
                        })
                },
                allowOutsideClick: () => false
            }).then((result) => {
                if (result.isConfirmed) {
                    if (result.value.data.status == true){
                        this.$emit('getCrypto',this.crypto.id)
                        this.getGeneralInfoApi();
                        this.$swal({icon: 'success',title: 'موفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                    }else{
                        this.$swal({icon: 'error',title: 'ناموفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                    }
                }
            })
        }
    },
    watch:{
        amount(value) {
            this.$nextTick(() => {
                value = this.subStrFloat(value, this.crypto.percent)
                this.amount = this.amountLocalFloat(value, 8)
            })
        },
    },
    mounted(){
        var networkOptions = [];
        this.cryptoNetworks.map((item)=> {
            var network = this.networks.find((el)=> el.id === item.id_network);
            var obj = {text:(network.name), value:network.id };
            networkOptions.push(obj);
        });
        var obj = {text:'شبکه را انتخاب کنید', value:null, disabled:true, hidden:true};
        networkOptions.push(obj);
        this.networkOptions = networkOptions;
    }
}
</script>

<style scoped>

</style>
