<template>
    <div>
        <b-modal
            v-model="modalStatus"
            id="card-modal" ref="card-modal"
            title="جزئیات کارت بانکی"
            ok-title="ذخیره تغییرات"
            cancel-title="بستن"
            cancel-variant="outline-secondary"
            @ok="handleOk"
            v-if="!isLoadingReject"
        >

            <b-card-text>
                <div class="text-center my-2" v-if="!card">
                    <b-spinner style="width: 3rem; height: 3rem;"/>
                </div>

                <validation-observer ref="simpleRules" v-else>
                    <b-form ref="form" @submit.stop.prevent="handleSubmit">
                        <b-row>
                            <b-col cols="12" class="text-center mb-1">
                                <img class="logo-bank" :src="require(`@/assets/images/banklogo/${ BankSelect.img }`)" width="20%" v-if="BankSelect">
                                <h5 class="mt-1">
                                    {{bankName}}
                                </h5>
                                <b-badge
                                    pill
                                    :variant="`light-${resolveStatusVariant(card.status)}`"
                                    class="text-capitalize"
                                >
                                    {{ $t(card.status) }}
                                </b-badge>
                            </b-col>

                            <b-alert class="w-100 my-1 mb-0" variant="warning" show v-if="card.status == 'reject' ">
                                <div class="alert-body">
                                    <strong>نکته!</strong> کارت بانکی به دلیل "{{JSON.parse(card.data).reject_reason}}" رد شده است.
                                </div>
                            </b-alert>

                            <b-col cols="12">
                                <b-form-group label="نام و فامیل" label-cols-md="3">
                                    <validation-provider #default="{ errors }" rules="required">
                                        <b-input-group class="input-group-merge">
                                            <b-form-input v-model="nameFamilyUser"  placeholder="شماره کارت" readonly
                                                          :state="errors.length > 0 ? false:null" class="text-center" dir="ltr"/>
                                        </b-input-group>
                                    </validation-provider>
                                </b-form-group>
                            </b-col>

                            <b-col cols="12">
                                <b-form-group label="شماره کارت" label-cols-md="3">
                                    <validation-provider #default="{ errors }" rules="required">
                                        <b-input-group class="input-group-merge">
                                            <b-form-input v-model="cardNumber"  placeholder="شماره کارت" required  v-mask="CardNumberMask"
                                                          :state="errors.length > 0 ? false:null" class="text-center" dir="ltr"/>
                                            <b-input-group-append is-text>
                                                <div v-clipboard:copy="cardNumber" v-clipboard:success="onCopy" v-clipboard:error="onError">
                                                    <feather-icon icon="CopyIcon" class="cursor-pointer" />
                                                </div>
                                            </b-input-group-append>
                                        </b-input-group>
                                    </validation-provider>
                                </b-form-group>
                            </b-col>

                            <b-col cols="12">
                                <b-form-group label="شماره حساب" label-cols-md="3">
                                    <validation-provider #default="{ errors }">
                                        <b-input-group class="input-group-merge">
                                            <b-form-input v-model="accountNumber"  placeholder="شماره حساب"
                                                          :state="errors.length > 0 ? false:null" class="text-center" dir="ltr"/>
                                            <b-input-group-append is-text>
                                                <div v-clipboard:copy="accountNumber" v-clipboard:success="onCopy" v-clipboard:error="onError">
                                                    <feather-icon icon="CopyIcon" class="cursor-pointer" />
                                                </div>
                                            </b-input-group-append>
                                        </b-input-group>
                                    </validation-provider>
                                </b-form-group>
                            </b-col>

                            <b-col cols="12">
                                <b-form-group label="شماره شبا" label-cols-md="3">
                                    <validation-provider #default="{ errors }" rules="required">
                                        <b-input-group class="input-group-merge">
                                            <b-form-input v-model="iban"  placeholder="شماره شبا" required v-mask="iBanMask"
                                                          :state="errors.length > 0 ? false:null" class="text-center" dir="ltr"/>
                                            <b-input-group-append is-text>
                                                <div v-clipboard:copy="iban" v-clipboard:success="onCopy" v-clipboard:error="onError">
                                                    <feather-icon icon="CopyIcon" class="cursor-pointer" />
                                                </div>
                                            </b-input-group-append>
                                        </b-input-group>
                                    </validation-provider>
                                </b-form-group>
                            </b-col>

                            <b-col cols="12">
                                <b-form-group label="نام صاحب حساب" label-cols-md="3">
                                    <validation-provider #default="{ errors }">
                                        <b-input-group class="input-group-merge">
                                            <b-form-input v-model="nameFamily"  placeholder="نام و فامیل صاحب حساب"
                                                          :state="errors.length > 0 ? false:null" class="text-center"/>
                                            <b-input-group-append is-text>
                                                <div v-clipboard:copy="nameFamily" v-clipboard:success="onCopy" v-clipboard:error="onError">
                                                    <feather-icon icon="CopyIcon" class="cursor-pointer" />
                                                </div>
                                            </b-input-group-append>
                                        </b-input-group>
                                    </validation-provider>
                                </b-form-group>
                            </b-col>


                            <b-col cols="12" md="9" offset-md="3" class="mb-1 d-flex">
                                <b-dropdown text="استعلام" variant="outline-warning"  class="mr-25 mt-0" dir="ltr">
                                    <b-dropdown-item @click="inquiryCard('finnotech')">استعلام کارت (فینوتک)</b-dropdown-item>
                                    <b-dropdown-item @click="inquiryIban('finnotech')">استعلام شبا (فینوتک)</b-dropdown-item>
                                    <b-dropdown-item @click="inquiryCard('cardinfo')">استعلام کارت (کارداینفو)</b-dropdown-item>
                                    <b-dropdown-item @click="inquiryIban('cardinfo')">استعلام شبا (کارداینفو)</b-dropdown-item>
                                    <b-dropdown-item @click="inquiryCard('zibal')">استعلام کارت (زیبال)</b-dropdown-item>
                                    <b-dropdown-item @click="inquiryIban('zibal')">استعلام شبا (زیبال)</b-dropdown-item>
                                </b-dropdown>

                                <b-button variant="outline-success" size="sm" block v-if="card.status == 'pending' || card.status == 'reject' " class="mr-25 mt-0" @click="confirmCard()">
                                    <feather-icon icon="CheckCircleIcon" class="mr-50"/>
                                    <span class="align-middle">تایید کارت</span>
                                </b-button>
                                <b-button variant="outline-danger" size="sm" block v-if="card.status == 'pending' || card.status == 'confirm' " class="mr-25 mt-0" @click="rejectCard()">
                                    <feather-icon icon="XCircleIcon" class="mr-50"/>
                                    <span class="align-middle">رَد کارت</span>
                                </b-button>

                            </b-col>
                        </b-row>

                    </b-form>
                </validation-observer>
            </b-card-text>
            <template #modal-footer>
                <div class="w-100">
                    <b-button variant="primary" class="float-right d-flex" @click="handleSubmit" :disabled="isLoadingEdit">
                        <div>ذخیره تغییرات</div>
                        <div class="line-height-0 ml-25"><b-spinner v-if="isLoadingEdit" small></b-spinner></div>
                    </b-button>
                    <b-button variant="outline-secondary" class="float-right mr-1" @click="modalStatus=false">
                        انصراف
                    </b-button>
                </div>
            </template>
        </b-modal>

        <b-modal
            v-model="modalInquiry"
            id="inquiry-modal" ref="inquiry-modal"
            title="نتیجه استعلام"
            ok-title="ذخیره تغییرات"

            cancel-title="بستن"
            cancel-variant="outline-secondary"
        >
            <b-card-text>
                <div class="text-center my-2" v-if="!inquiry">
                    <b-spinner style="width: 3rem; height: 3rem;"/>
                </div>
                <div v-else dir="ltr" class="text-right">
                    <pre v-html="JSON.stringify(inquiry, null, 6)"></pre>
                </div>
            </b-card-text>
            <template #modal-footer>
                <div class="w-100">
                    <b-button variant="outline-secondary" class="float-right" @click="modalInquiry=false">
                        بستن
                    </b-button>
                </div>
            </template>
        </b-modal>
    </div>
</template>

<script>
    import {
        BModal, BButton, VBModal, BAlert, BCardText, BFormGroup, BFormInput, BListGroup, BListGroupItem,BRow,BCol,BForm,
        BInputGroup,BInputGroupPrepend,BInputGroupAppend,BSpinner, BBadge, BDropdown, BDropdownItem
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
        components: {
            ValidationProvider, ValidationObserver,
            BCardCode,
            BButton,
            BModal,
            BAlert,
            BCardText,BFormGroup,BFormInput,BListGroup,BListGroupItem,BRow,BCol,BForm,
            BInputGroup,BInputGroupPrepend,BInputGroupAppend,BDropdown,BDropdownItem,
            BSpinner, BBadge
        },
        directives: {
            'b-modal': VBModal,
            Ripple,
        },
        name: "CardBank",
        props:['id','nameFamilyUser','modalShow'],
        data(){
            return {
                card: null,
                modalStatus: false,
                modalInquiry: false,
                inquiry: null,

                cardNumber:'',
                accountNumber: '',
                iban: '',
                nameFamily: '',
                bankName:'',
                BankSelect: { value: 0, img:'shetab.svg', selectedText: 'سایر', slug: ''},
                BankOptions: [
                    { value: 0, img:'shetab.svg', selectedText: 'سایر', slug: ''},
                    { value: 1, img:'melat-bank.svg', selectedText: 'بانک ملت', slug: '610433', slug2: '991975'},
                    { value: 2, img:'meli-bank.svg', selectedText: 'بانک ملی', slug: '603799'},
                    { value: 3, img:'tejarat-bank.svg', selectedText: 'بانک تجارت', slug: '627353', slug2: '585983'},
                    { value: 4, img:'sepah-bank.svg', selectedText: 'بانک سپه', slug: '589210'},
                    { value: 5, img:'tosee-saderat-bank.svg', selectedText: 'بانک توسعه صادرات', slug: '627648', slug2: '207177'},
                    { value: 6, img:'sanat-madan-bank.svg', selectedText: 'بانک صنعت و معدن', slug: '627961'},
                    { value: 7, img:'keshavarzi-bank.svg', selectedText: 'بانک کشاورزی', slug: '603770', slug2: '639217'},
                    { value: 8, img:'maskan-bank.svg', selectedText: 'بانک مسکن', slug: '628023'},
                    { value: 9, img:'post-bank.svg', selectedText: 'پست بانک', slug: '627760'},
                    { value: 10, img:'tosee-taavon-bank.svg', selectedText: 'بانک توسعه و تعاون', slug: '502908'},
                    { value: 11, img:'eghtesad-novin-bank.svg', selectedText: 'بانک اقتصاد نوین', slug: '627412'},
                    { value: 12, img:'parsian-bank.svg', selectedText: 'بانک پارسیان', slug: '622106', slug2: '639194', slug3: '627884'},
                    { value: 13, img:'pasargad-bank.svg', selectedText: 'بانک پاسارگاد', slug: '639347', slug2: '502229'},
                    { value: 14, img:'karafarin-bank.svg', selectedText: 'بانک کارآفرین', slug: '627488', slug2: '502910'},
                    { value: 15, img:'saman-bank.svg', selectedText: 'بانک سامان', slug: '62198610'},
                    { value: 16, img:'sina-bank.svg', selectedText: 'بانک سینا', slug: '639346'},
                    { value: 17, img:'sarmayeh-bank.svg', selectedText: 'بانک سرمایه', slug: '639607'},
                    { value: 18, img:'ayandeh-bank.svg', selectedText: 'بانک آینده', slug: '636214'},
                    { value: 19, img:'shahr-bank.svg', selectedText: 'بانک شهر', slug: '502806', slug2: '504706'},
                    { value: 20, img:'dey-bank.svg', selectedText: 'بانک دی', slug: '502938'},
                    { value: 21, img:'saderat-bank.svg', selectedText: 'بانک صادرات', slug: '603769'},
                    { value: 22, img:'refah-bank.svg', selectedText: 'بانک رفاه', slug: '589463'},
                    { value: 23, img:'ansar-bank.svg', selectedText: 'بانک انصار', slug: '627381'},
                    { value: 24, img:'iranzamin-bank.svg', selectedText: 'بانک ایران زمین', slug: '505785'},
                    { value: 25, img:'hekmat-iranian-bank.svg', selectedText: 'بانک حکمت ایرانیان', slug: '636949'},
                    { value: 26, img:'gardeshgari-bank.svg', selectedText: 'بانک گردشگری', slug: '505416'},
                    { value: 27, img:'mehr-iran-bank.svg', selectedText: 'بانک قرض الحسنه مهر ایران', slug: '606373'},
                    { value: 28, img:'kosar-bank.svg', selectedText: 'موسسه مالی کوثر', slug: '505801'},
                    { value: 29, img:'ghavamin-bank.svg', selectedText: 'موسسه قوامین', slug: '639599'},
                    { value: 30, img:'khavarmianeh-bank.svg', selectedText: 'بانک خاورمیانه', slug: '505809'},
                    { value: 31, img:'resalat-bank.svg', selectedText: 'بانک قرض الحسنه رسالت', slug: '504172'},
                    { value: 32, img:'noor-bank.svg', selectedText: 'موسسه نور', slug: '507677'},
                    { value: 33, img:'blu-bank.svg', selectedText: 'بلوبانک', slug: '62198619'},
                    { value: 34, img:'mehr-eghtesad-bank.svg', selectedText: 'بانک مهر اقتصاد', slug: '639370'},
                    { value: 35, img:'melal-bank.png', selectedText: 'موسسه ملل', slug: '606256'},
                    { value: 36, img:'markazi-bank.svg', selectedText: 'بانک مرکزی', slug: '636795'},
                ],

                CardNumberMask: {
                    mask: '#### #### #### ####',
                    tokens: {
                        '#': {
                            pattern: /[0-9٠١٢٣٤٥٦٧٨٩]/,
                            transform (v) {
                                return v.toLocaleUpperCase()
                            }
                        }
                    }
                },
                iBanMask: {
                    mask: 'IR ##-####-####-####-####-####-##',
                    tokens: {
                        '#': {
                            pattern: /[0-9٠١٢٣٤٥٦٧٨٩]/,
                            transform (v) {
                                return v.toLocaleUpperCase()
                            }
                        }
                    }
                },
                isLoadingEdit: false,
                isLoadingReject: false,
            }
        },
        methods:{
            handleOk(bvModalEvt) {
                // Prevent modal from closing
                bvModalEvt.preventDefault()
                // Trigger submit handler
                this.handleSubmit()
            },
            handleSubmit() {
                this.$refs.simpleRules.validate().then(success => {
                    if (success) {
                        this.isLoadingEdit = true;
                        axiosIns.post('card-bank/edit/'+this.id,{
                            cardNumber:this.cardNumber.replace(/\ /g, ''),
                            accountNumber:this.accountNumber,
                            iban:this.iban,
                            nameFamily:this.nameFamily,
                            bankName: this.bankName
                        }) .then(response => {
                            this.$emit('refetchData')
                            this.$toast({
                                component: ToastificationContent,
                                props: {
                                    title: 'انجام شد!',
                                    text: response.data.msg,
                                    icon: 'CheckCircleIcon',
                                    variant: 'success',
                                },
                            })
                            this.isLoadingEdit = false;
                        })
                        .catch(() => {
                            this.errorFetching();
                            this.isLoadingEdit = false;
                        })
                    }
                })
            },

            getCard(){
                axiosIns.post('card-bank/info/'+this.id) .then(response => {
                    this.card = response.data.card;
                    this.cardNumber =  this.card.card_number
                    this.accountNumber =  this.card.account_number ? this.card.account_number : ''
                    this.iban =  this.card.iban ? this.card.iban : ''
                    this.nameFamily =  this.card.name_family
                })
                .catch(() => {
                    this.errorFetching();
                })
            },
            confirmCard(){
                this.$swal({
                    title: 'از تایید کارت اطمینان دارید؟',
                    text: "با توجه به نام دارنده حساب با نام کاربری که ثبت کرده است تایید نمایید.",
                    icon: 'question',
                    showCancelButton: true,
                    showLoaderOnConfirm: true,
                    buttonsStyling: false,
                    preConfirm: () => {
                        return  axiosIns.post('card-bank/status/'+this.id,{type:'confirm'})
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
                            this.getCard();
                            this.getGeneralInfoApi();
                            this.$emit('refetchData');
                            this.$swal({icon: 'success',title: 'موفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }else{
                            this.$swal({icon: 'error',title: 'ناموفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }
                    }
                })
            },
            rejectCard(){
                this.isLoadingReject = true;
                this.$swal({
                    title: 'از رد کردن اطمینان دارید؟',
                    text: 'با رد کردن کارت مجددا امکان تایید آن وجود دارد',
                    input:'textarea',
                    inputPlaceholder: 'دلیلی جهت رد کردن و نمایش به کاربر درج کنید',
                    icon: 'question',
                    inputValidator: (value) => {
                        if (!value) {
                            return 'لطفا فیلدها را تکمیل نمایید!'
                        }
                    },
                    showCancelButton: true,
                    showLoaderOnConfirm: true,
                    buttonsStyling: false,
                    preConfirm: (value) => {
                        return  axiosIns.post('card-bank/status/'+this.id,{reason:value,type:'reject'})
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
                            this.getCard();
                            this.getGeneralInfoApi();
                            this.$emit('refetchData');
                            this.$swal({icon: 'success',title: 'موفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }else{
                            this.$swal({icon: 'error',title: 'ناموفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }
                    }
                    this.isLoadingReject = false;
                })
            },
            resolveStatusVariant(status){
                if (status === 'pending') return 'warning'
                if (status === 'confirm') return 'success'
                if (status === 'reject') return 'danger'
                return 'warning'
            },
            inquiryCard(company){
                this.inquiry = null;
                this.modalInquiry = true;
                axiosIns.post('card-bank/inquiry/'+this.card.id+'/card',{company:company}).then(response => {
                    this.inquiry = response.data;
                    if(company =='finnotech' && this.inquiry.status == 'DONE' && this.inquiry.result.name){
                        this.nameFamily = this.inquiry.result.name;
                    }else if(company == 'cardinfo' && this.inquiry.IBAN){
                        this.nameFamily = this.inquiry.depositOwners
                        this.iban = this.inquiry.IBAN
                        this.accountNumber = this.inquiry.deposit
                    }else if(company == 'zibal' && this.inquiry.data){
                        this.nameFamily = this.inquiry.data.name
                        this.iban = this.inquiry.data.IBAN
                    }
                }).catch((error) => {console.log(error);this.errorFetching();})
            },
            inquiryIban(company){
                this.inquiry = null;
                this.modalInquiry = true;
                axiosIns.post('card-bank/inquiry/'+this.card.id+'/iban',{company:company}).then(response => {
                    this.inquiry = response.data;
                    if(company =='finnotech' && this.inquiry.status == 'DONE' && this.inquiry.result.depositOwners){
                        this.accountNumber = this.inquiry.result.deposit;
                        this.nameFamily = this.inquiry.result.depositOwners[0].firstName+' '+this.inquiry.result.depositOwners[0].lastName;
                    }else if(company == 'cardinfo' && this.inquiry.sheba){
                        this.nameFamily = this.inquiry.owners[0].firstName+' '+this.inquiry.owners[0].lastName;
                    }
                    else if(company == 'zibal' && this.inquiry.data){
                        this.nameFamily = this.inquiry.data.name
                    }
                }).catch(() => {this.errorFetching();})
            },
        },
        watch:{
            modalShow(val){
               if(val){
                   this.getCard()
                   this.modalStatus = true;
               }else {
                   this.modalStatus = false;
               }
            },
            modalStatus(val){
                if(!val){
                    this.card = null;
                    this.$emit('modalUpdate', false)
                }
            },
            cardNumber (value) {
                const str = value.replace(/ /g, '')
                if (str.length >= 6) {
                    for (var i = 1; i <= 3; i++) {
                        var index = this.BankOptions.map(function (o) { return o[`slug${(i > 1) ? i : ''}`]; }).indexOf(str.substr(0, 6))
                        if(index < 0)
                            index = this.BankOptions.map(function (o) { return o[`slug${(i > 1) ? i : ''}`]; }).indexOf(str.substr(0, 8))
                        if (index >= 0) break
                    }

                    if (index >= 0) this.BankSelect = this.BankOptions[index]
                    else this.BankSelect = this.BankOptions[0]
                } else this.BankSelect = this.BankOptions[0]

                this.bankName = this.BankSelect.selectedText
                return this.cardNumber = this.parseNumberArabic(value)
            },
            iban (value) {
                return this.iban = this.parseNumberArabic(value)
            },
            accountNumber (value) {
                if(value && value != ''){
                    value = this.parseNumberArabic(value)
                    this.accountNumber = value.replace(/[^\d]/g, '')
                }
            }
        }
    }
</script>

<style lang="scss">
</style>
