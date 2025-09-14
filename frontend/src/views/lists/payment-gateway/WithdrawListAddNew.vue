<template>
    <b-sidebar
        id="add-new-withdraw-sidebar"
        :visible="isAddNewSidebarActive"
        bg-variant="white"
        sidebar-class="sidebar-lg"
        shadow
        backdrop
        no-header
        right
        @hidden="resetForm"
        @change="(val) => $emit('update:is-add-new-sidebar-active', val)"
    >
        <template #default="{ hide }">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center content-sidebar-header px-2 py-1">
                <h5 class="mb-0">
                    درخواست تسویه
                </h5>

                <feather-icon
                    class="ml-1 cursor-pointer"
                    icon="XIcon"
                    size="16"
                    @click="hide"
                />

            </div>

            <b-overlay :show="isAddNewSidebarActive && isLoadingInquiry" rounded="sm">

            <!-- BODY -->
            <validation-observer
                #default="{ handleSubmit }"
                ref="refFormObserver"
            >

                <!-- Form -->
                <b-form
                    class="p-2"
                    @submit.prevent="handleSubmit(onSubmit)"
                    autocomplete="off"
                    @reset.prevent="resetForm"
                >

                    <div class="text-center">
                        <img class="logo-bank" :src="require(`@/assets/images/banklogo/${ BankSelect.img }`)" width="20%" v-if="BankSelect">
                        <h5 class="mt-1" v-if="userInquiry">
                            {{userInquiry.name}} - بانک {{userInquiry.bankName}}
                        </h5>
                    </div>

                    <!-- Name -->
                    <validation-provider
                        #default="validationContext"
                        name="iban"
                        rules="required"
                    >
                        <b-form-group
                            label="شبا مقصد"
                            label-for="iban"
                        >
                            <b-form-input
                                id="iban"
                                v-model="userData.iban"
                                v-mask="iBanMask"
                                autofocus
                                dir="ltr"
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="شماره شبا"
                                class="text-center"
                            />

                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                        </b-form-group>
                    </validation-provider>

                    <!-- Amount -->
                    <validation-provider
                        #default="validationContext"
                        name="amount"
                        rules="required"
                    >
                        <b-form-group
                            label="مبلغ به تومان"
                            label-for="amount"
                        >
                            <b-form-input
                                class="text-center"
                                id="amount"
                                v-model="userData.amount"
                                autofocus
                                dir="ltr"
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="مبلغ به تومان"
                            />
                            <small v-show="num2persianAmount && num2persianAmount!='صفر'">{{num2persianAmount}} تومان</small>
                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                        </b-form-group>
                    </validation-provider>

                    <!-- Payment -->
                    <validation-provider
                        name="payment"
                        rules="required"
                        v-slot="validationContext"
                    >
                        <b-form-group
                            label="درگاه واریز"
                            label-for="payment"
                            :state="getValidationState(validationContext)"
                        >
                            <b-form-select
                                id="payment"
                                v-model="userData.payment"
                                :options="[
                                    { text: 'درگاه واریز رو انتخاب کنید', value: '', disabled: true },
                                    ...getGeneralInfo.gatewayslist
                                      .filter(gateway => gateway.withdraw === 1)
                                      .map(a => ({ text: $i18n.t(a.name), value: a.name }))
                                  ]"
                                :state="getValidationState(validationContext)"
                                autofocus
                                trim
                            />

                            <b-form-invalid-feedback v-if="validationContext.errors.length">
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                        </b-form-group>
                    </validation-provider>

                    <!-- Model -->
                    <validation-provider
                        v-if="userData.payment =='zibal' || userData.payment =='baje'"
                        #default="validationContext"
                        name="model"
                        rules="required"
                    >
                        <b-form-group
                            label="نحوه واریز"
                            label-for="model"
                        >
                            <b-form-select
                                id="model"
                                v-model="userData.model"
                                :options="[{'text':'برای تسویه در نزدیک ترین سیکل ممکن','value':'0'},{'text':'برای ارسال لحظه ای درخواست به بانک','value':'-1'}]"
                                autofocus
                                trim
                            />
                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                        </b-form-group>
                    </validation-provider>
                    <!-- Model -->
                    <validation-provider
                        v-if="userData.payment === 'baje'"
                        #default="validationContext"
                        name="bajeAccount"
                        rules="required"
                    >
                        <b-form-group
                            label="حساب واریز"
                            label-for="bajeAccount"
                        >
                            <b-form-select
                                id="bajeAccount"
                                v-model="userData.bajeAccount"
                                :state="getValidationState(validationContext)"
                                autofocus
                                trim
                            >
                                <!-- گزینه placeholder -->
                                <option value="" disabled hidden selected>انتخاب حساب باجه</option>

                                <!-- گزینه‌های داینامیک -->
                                <option
                                    v-for="acc in getGeneralInfo.bajeAccount"
                                    :key="acc.accountId"
                                    :value="acc.accountName"
                                >
                                    {{ acc.accountName }}
                                </option>
                            </b-form-select>

                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                        </b-form-group>
                    </validation-provider>


                    <!-- name family -->
                    <validation-provider
                        v-if="userData.payment =='paystar'"
                        #default="validationContext"
                        name="name"
                        rules="required"
                    >
                        <b-form-group
                            label="نام"
                            label-for="name"
                        >
                            <b-form-input
                                class="text-center"
                                id="name"
                                v-model="userData.name"
                                autofocus
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="نام"
                            />
                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                        </b-form-group>
                    </validation-provider>
                    <validation-provider
                        v-if="userData.payment =='paystar'"
                        #default="validationContext"
                        name="family"
                        rules="required"
                    >
                        <b-form-group
                            label="فامیل"
                            label-for="family"
                        >
                            <b-form-input
                                class="text-center"
                                id="family"
                                v-model="userData.family"
                                autofocus
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="فامیل"
                            />
                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                        </b-form-group>
                    </validation-provider>

                    <!-- description -->
                    <validation-provider
                        #default="validationContext"
                        name="description"
                        rules="required"
                    >
                        <b-form-group
                            label="توضیحات"
                            label-for="description"
                        >
                            <b-form-textarea
                                id="description"
                                v-model="userData.description"
                                trim
                            />
                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                        </b-form-group>
                    </validation-provider>


                    <!-- uniqueCode -->
                    <validation-provider
                        #default="validationContext"
                        name="uniqueCode"
                        rules="required"
                    >
                        <b-form-group
                            label="شناسه یکتا"
                            label-for="uniqueCode"
                        >
                            <b-form-input
                                class="text-center"
                                id="uniqueCode"
                                v-model="userData.uniqueCode"
                                autofocus
                                dir="ltr"
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="شناسه یکتا"
                            />
                            <small>جلوگیری از دوبار واریز!</small>
                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                        </b-form-group>
                    </validation-provider>


                    <!-- Form Actions -->
                    <div class="d-flex mt-2">
                        <b-button
                            block
                            v-ripple.400="'rgba(255, 255, 255, 0.15)'"
                            variant="primary"
                            class="mr-2 d-flex align-items-center justify-content-center"
                            :disabled="isLoading"
                            type="submit"
                        >
                            <div>ثبت واریز</div>
                            <div class="line-height-0 ml-25"><b-spinner v-if="isLoading" small></b-spinner></div>
                        </b-button>
                        <b-button
                            v-ripple.400="'rgba(186, 191, 199, 0.15)'"
                            type="button"
                            variant="outline-secondary"
                            @click="hide"
                        >
                            لغو
                        </b-button>
                    </div>

                </b-form>
            </validation-observer>
            </b-overlay>
        </template>
    </b-sidebar>
</template>

<script>
import {
    BSidebar, BForm, BFormGroup, BFormInput, BFormInvalidFeedback, BButton, BSpinner, BOverlay, BFormSelect,BFormTextarea
} from 'bootstrap-vue'
    import {ValidationProvider, ValidationObserver} from 'vee-validate'
    import {ref} from '@vue/composition-api'
    import {required, alphaNum, email} from '@validations'
    import formValidation from '@core/comp-functions/forms/form-validation'
    import Ripple from 'vue-ripple-directive'
    import vSelect from 'vue-select'
    import countries from '@/@fake-db/data/other/countries'
    import axios from "@/libs/axios";
    import ToastificationContent from "@core/components/toastification/ToastificationContent";
    import {useToast} from 'vue-toastification/composition'
    import Num2persian from 'num2persian';

    export default {
        components: {
            BFormSelect,
            BFormTextarea,
            BSidebar,
            BForm,
            BFormGroup,
            BFormInput,
            BFormInvalidFeedback,
            BButton,
            vSelect,
            BSpinner,BOverlay,

            // Form Validation
            ValidationProvider,
            ValidationObserver,
        },
        directives: {
            Ripple,
        },
        model: {
            prop: 'isAddNewSidebarActive',
            event: 'update:is-add-new-sidebar-active',
        },
        props: {
            isAddNewSidebarActive: {
                type: Boolean,
                required: true,
            }
        },

        data() {
            return {
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
                userInquiry:null,

                BankSelect: null,
                BankOptions: [
                    { value: 0, img:'shetab.svg', selectedText: 'سایر', slug: ''},
                    { value: 1, img:'melat-bank.svg', selectedText: 'بانک ملت', slug: '610433', slug2: '991975'},
                    { value: 2, img:'meli-bank.svg', selectedText: 'بانک ملی', slug: '603799'},
                    { value: 3, img:'tejarat-bank.svg', selectedText: 'بانک تجارت', slug: '627353', slug2: '585983'},
                    { value: 4, img:'sepah-bank.svg', selectedText: 'بانک سپه', slug: '589210'},
                    { value: 21, img:'saderat-bank.svg', selectedText: 'بانک صادرات', slug: '603769'},
                    { value: 6, img:'sanat-madan-bank.svg', selectedText: 'بانک صنعت و معدن صنعت معدن', slug: '627961'},
                    { value: 7, img:'keshavarzi-bank.svg', selectedText: 'بانک کشاورزی', slug: '603770', slug2: '639217'},
                    { value: 8, img:'maskan-bank.svg', selectedText: 'بانک مسکن', slug: '628023'},
                    { value: 9, img:'post-bank.svg', selectedText: 'پست بانک', slug: '627760'},
                    { value: 10, img:'tosee-taavon-bank.svg', selectedText: 'بانک توسعه و تعاون توسعه تعاون', slug: '502908'},
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
                    { value: 5, img:'tosee-saderat-bank.svg', selectedText: 'بانک توسعه صادرات', slug: '627648', slug2: '207177'},

                ],

                required,
                alphaNum,
                email,
                countries,

                debounceTimer: null,
                isLoadingInquiry: false,
                num2persianAmount:'',
            }
        },
        watch:{
            isAddNewSidebarActive(val){
                if(val){
                    this.userData.uniqueCode =  (Math.floor(Date.now() / 1000)).toString()
                    //this.getBalance()
                }
            },
            'userData.iban'(val){
                let cleanStr = val.replace(/[\s-]/g, "");
                if (cleanStr.length == 26) {
                    this.isLoadingInquiry = true;
                    if (this.debounceTimer) {
                        clearTimeout(this.debounceTimer);
                    }
                    this.debounceTimer = setTimeout(() => {
                        this.ibanInquiry()
                    }, 500);
                }
            },
            'userData.amount'(val){
                this.userData.amount = this.amountLocalFloat(val,0)
                this.num2persianAmount = Num2persian(val.replace(/[\s-]/g, ""));
            },
            userInquiry(val){
                if(val){
                    var bankName = val.bankName.replace(/ي/g, "ی")
                        .replace(/ك/g, "ک")
                        .replace(/ة/g, "ه")
                        .replace(/ء/g, "ئ");

                    this.BankSelect = this.BankOptions.find(option => option.selectedText.includes(bankName));
                }

            }
        },
        methods:{
            getBalance(){
                axios.post('withdraw/add/balance')
                .then((response) => {
                    //console.log(response.data)
                })
            },
            ibanInquiry(){
                this.BankSelect = null;
                this.userInquiry = null;
                axios.post('withdraw/add/ibanInquiry',{iban:this.userData.iban.replace(/[\s-]/g, "")})
                    .then((response) => {
                        if(response.data.msg)
                            this.$toast({
                                component: ToastificationContent,
                                props: {
                                    title: 'خطا!',
                                    text: response.data.msg,
                                    icon: 'AlertTriangleIcon',
                                    variant: 'danger',
                                },
                            })
                        else
                            this.userInquiry = response.data;

                        this.isLoadingInquiry = false;
                       // console.log(response.data)
                    }).catch((error) => {

                })
            }
        },
        setup(props, {emit}) {
            const isLoading = ref(false);
            const toast = useToast()
            const blankUserData = {
                iban: '',
                amount: '',
                payment: '',
                model: '0',
                name: '',
                family: '',
                description: 'تسویه همکار',
                uniqueCode: '',
                bajeAccount: '',
            }

            const userData = ref(JSON.parse(JSON.stringify(blankUserData)))
            const resetuserData = () => {
                userData.value = JSON.parse(JSON.stringify(blankUserData))
            }

            const onSubmit = () => {
                isLoading.value = true

                const submittedData = { ...userData.value };

                submittedData.amount = submittedData.amount.replace(/[\s,]/g, "");
                submittedData.iban =  'IR'+ submittedData.iban.replace(/\D/g, "");

                axios.post('withdraw/add', submittedData)
                .then((response) => {
                    emit('refetch-data')
                    emit('update:is-add-new-sidebar-active', false)
                    toast({
                        component: ToastificationContent,
                        props: {
                            title: 'موفق!',
                            text: response.data.msg,
                            icon: 'CheckIcon',
                            variant: 'success',
                        },
                    })
                    isLoading.value = false
                }).catch((error) => {
                    toast({
                        component: ToastificationContent,
                        props: {
                            title: 'خطا!',
                            text: error.response.data.msg,
                            icon: 'AlertTriangleIcon',
                            variant: 'danger',
                        },
                    })
                    isLoading.value = false
                })
            }

            const {
                refFormObserver,
                getValidationState,
                resetForm,
            } = formValidation(resetuserData)

            return {
                userData,
                onSubmit,

                refFormObserver,
                getValidationState,
                resetForm,
                isLoading
            }
        },
    }
</script>

<style lang="scss">
    @import '@core/scss/vue/libs/vue-select.scss';

    #add-new-user-sidebar {
        .vs__dropdown-menu {
            max-height: 200px !important;
        }
    }
</style>
