<template>
    <b-sidebar
        id="add-new-sidebar"
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
                    افزودن سفارش
                </h5>

                <feather-icon
                    class="ml-1 cursor-pointer"
                    icon="XIcon"
                    size="16"
                    @click="hide"
                />

            </div>

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

                    <!-- Name -->
                    <validation-provider
                        #default="validationContext"
                        name="type"
                        rules="required"
                    >
                        <b-form-group
                            label="نوع سفارش یا تراکنش"
                            label-for="type"
                        >
                            <b-form-select
                                id="type"
                                v-model="userData.type"
                                :options="[{'text':'واریز','value':'deposit'},{'text':'برداشت','value':'withdraw'}]"
                                autofocus
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="نوع سفارش"
                            />
                            <small v-if="isCrypto">اگر واریز انتخاب شود یک تراکنش رمزارز واریزی همراه با سفارش فروش در قسمت سفارشات تتر ثبت میشود و اگر برداشت انتخاب شود یک تراکنش برداشت و یک سفارش خرید ثبت میشود.</small>
                            <small v-else>اگر واریز انتخاب شود سفارش فروش پرفکت مانی در قسمت سفارشات ثبت میشود و اگر برداشت انتخاب شود یک سفارش خرید پرفکت مانی ثبت میشود.</small>

                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                        </b-form-group>
                    </validation-provider>

                    <!-- count -->
                    <validation-provider v-if="userData.type === 'deposit' && isCrypto"
                        #default="validationContext"
                        name="txid"
                        rules="required"
                    >
                        <b-form-group
                            label="شناسه تراکنش Txid"
                            label-for="txid"
                        >
                            <b-form-input
                                id="txid"
                                v-model="userData.txid"
                                autofocus
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="شناسه عمومی تراکنش txid"
                            />
                            <small>هیچ استعلامی از بایننس گرفته نمیشود و فقط ثبت میشود.</small>
                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                        </b-form-group>
                    </validation-provider>

                    <!-- period -->
                    <validation-provider
                        #default="validationContext"
                        name="amount"
                        rules="required"
                    >
                        <b-form-group
                            label="مقدار دلاری"
                            label-for="amount"
                        >
                            <b-form-input
                                id="amount"
                                v-model="userData.amount"
                                autofocus
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="مبلغ یا مقدار به دلار"
                            />
                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                        </b-form-group>
                    </validation-provider>

                    <validation-provider
                        #default="validationContext"
                        name="fee"
                        rules="required"
                    >
                        <b-form-group
                            label="قیمت"
                            label-for="fee"
                        >
                            <b-form-input
                                id="fee"
                                v-model="userData.fee"
                                autofocus
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="قیمت هر تتر"
                            />
                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                        </b-form-group>
                    </validation-provider>


                    <!-- started -->
                    <validation-provider
                        #default="validationContext"
                        name="started"
                        rules="required"
                    >
                        <b-form-group
                            label="تاریخ ثبت"
                            label-for="started"
                        >
                            <b-input-group>
                                <b-form-input
                                    placeholder="تاریخ"
                                    v-model="userData.date"
                                    id="started" name="started"
                                    :state="getValidationState(validationContext)"
                                />
                                <b-input-group-append is-text>
                                    <feather-icon icon="CalendarIcon" class="cursor-pointer" @click="showDatePickerStart=true"/>
                                    <date-picker v-model="userData.date" :show="showDatePickerStart" @close="showDatePickerStart=false" :auto-submit="true" color="#7367f0"
                                                 :locale="localeHas" :locale-config="localeConfig" :format="(localeHas=='fa')?'jYYYY/jMM/jDD' : 'YYYY-MM-DD'" element="started">
                                    </date-picker>
                                </b-input-group-append>
                            </b-input-group>
                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                            <small>از این تاریخ به بعد این کد قابلیت درج شدن توسط کاربر را دارد.</small>
                        </b-form-group>
                    </validation-provider>



                    <!-- Form Actions -->
                    <div class="d-flex mt-2">
                        <b-button
                            v-ripple.400="'rgba(255, 255, 255, 0.15)'"
                            variant="primary"
                            class="mr-2"
                            type="submit"
                        >
                            افزودن
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
        </template>
    </b-sidebar>
</template>

<script>
    import {
        BSidebar, BForm, BFormGroup, BFormInput,BFormSelect, BFormInvalidFeedback, BButton,BTabs,BTab,BInputGroup,BInputGroupAppend
    } from 'bootstrap-vue'
    import {ValidationProvider, ValidationObserver} from 'vee-validate'
    import {ref} from '@vue/composition-api'
    import {required, alphaNum, email,between} from '@validations'
    import formValidation from '@core/comp-functions/forms/form-validation'
    import Ripple from 'vue-ripple-directive'
    import vSelect from 'vue-select'
    import countries from '@/@fake-db/data/other/countries'
    import axios from "@/libs/axios";
    import ToastificationContent from "@core/components/toastification/ToastificationContent";
    import {useToast} from 'vue-toastification/composition'
    import VuePersianDatetimePicker from 'vue-persian-datetime-picker'

    export default {
        components: {
            BTabs,BTab,
            BSidebar,
            BForm,
            BFormGroup,
            BFormInput,BFormSelect,
            BFormInvalidFeedback,
            BButton,
            vSelect,
            BInputGroup,BInputGroupAppend,

            // Form Validation
            ValidationProvider,
            ValidationObserver,

            datePicker: VuePersianDatetimePicker,
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
            },
            isCrypto: {
                type: Boolean,
                required: true,
            }
        },
        data() {
            return {
                required,
                alphaNum,
                email,
                countries,
                between,

                showDatePickerStart: false,
                showDatePickerStop: false,
                localeConfig:{
                    fa: {
                        dow: 6,     //  first day of week
                        dir: 'rtl',
                        displayFormat: 'jYYYY/jMM/jDD'
                    },
                    en: {
                        dow: 0,
                        dir: 'ltr',
                        displayFormat: 'YYYY-MM-DD'
                    }
                },
            }
        },
        setup(props, {emit}) {
            const toast = useToast()
            const blankUserData = {
                type: 'deposit',
                txid: '',
                amount: '',
                fee: '',
                date: '',
                isCrypto:props.isCrypto
            }

            const userData = ref(JSON.parse(JSON.stringify(blankUserData)))
            const resetuserData = () => {
                userData.value = JSON.parse(JSON.stringify(blankUserData))
            }

            const onSubmit = () => {
                axios.post('audit/daily/add-new', userData.value)
                .then((response) => {
                    if(response.data.status){
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
                    }else{
                        toast({
                            component: ToastificationContent,
                            props: {
                                title: 'خطا!',
                                text: response.data.msg,
                                icon: 'AlertTriangleIcon',
                                variant: 'danger',
                            },
                        })
                    }

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
            }
        },
    }
</script>

<style lang="scss">
    @import '@core/scss/vue/libs/vue-select.scss';

    #add-new-sidebar {
        .vs__dropdown-menu {
            max-height: 200px !important;
        }

        .nav-tabs .nav-item a{
            font-size: 12px !important;
        }
    }


</style>
