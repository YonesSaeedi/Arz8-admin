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
                    افزودن کد تخفیف
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
                        name="name"
                        rules="required"
                    >
                        <b-form-group
                            label="نام یا کلید کد تخفیف"
                            label-for="name"
                        >
                            <b-form-input
                                id="name"
                                v-model="userData.name"
                                autofocus
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="نام کد تخفیف"
                            />

                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                        </b-form-group>
                    </validation-provider>

                    <!-- count -->
                    <validation-provider
                        #default="validationContext"
                        name="count"
                        rules="required|integer"
                    >
                        <b-form-group
                            label="حداکثر تعداد مجاز برای استفاده"
                            label-for="count"
                        >
                            <b-form-input
                                id="count"
                                v-model="userData.count"
                                autofocus
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="تعداد"
                            />

                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                        </b-form-group>
                    </validation-provider>

                    <!-- period -->
                    <validation-provider
                        #default="validationContext"
                        name="period"
                        rules="required|integer"
                    >
                        <b-form-group
                            label="چند ساعت اعتبار داشته باشد"
                            label-for="period"
                        >
                            <b-form-input
                                id="period"
                                v-model="userData.period"
                                autofocus
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="اعتبار به ساعت"
                            />
                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                            <small>بعد از فعال شدن برای کاربر تا چه مدت اعتبار داشته باشد و به ساعت مشخص کنید(مثلا برای سه روز 72 ساعت)</small>
                        </b-form-group>
                    </validation-provider>

                    <!-- buyDiscount -->
                    <validation-provider
                        #default="validationContext"
                        name="buyDiscount"
                        rules="required|integer|between:1,100"
                    >
                        <b-form-group
                            label=" میزان درصد تخفیف برای خرید"
                            label-for="period"
                        >
                            <b-form-input
                                id="buyDiscount"
                                v-model="userData.buyDiscount"
                                autofocus
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="تخفیف خرید ها بین 1 تا 100"
                            />
                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                            <small>میزان تخفیف از کارمزد خرید ها به درصد</small>
                        </b-form-group>
                    </validation-provider>

                    <!-- sellDiscount -->
                    <validation-provider
                        #default="validationContext"
                        name="sellDiscount"
                        rules="required|integer|between:1,100"
                    >
                        <b-form-group
                            label=" میزان درصد تخفیف برای فروش"
                            label-for="period"
                        >
                            <b-form-input
                                id="sellDiscount"
                                v-model="userData.sellDiscount"
                                autofocus
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="تخفیف فروش ها بین 1 تا 100"
                            />
                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                            <small>میزان تخفیف از کارمزد فروش ها به درصد</small>
                        </b-form-group>
                    </validation-provider>

                    <!-- started -->
                    <validation-provider
                        #default="validationContext"
                        name="started"
                        rules="required"
                    >
                        <b-form-group
                            label="تاریخ فعال شدن این کد تخفیف"
                            label-for="started"
                        >
                            <b-input-group>
                                <b-form-input
                                    placeholder="از تاریخ"
                                    v-model="userData.started"
                                    id="started" name="started"
                                    :state="getValidationState(validationContext)"
                                />
                                <b-input-group-append is-text>
                                    <feather-icon icon="CalendarIcon" class="cursor-pointer" @click="showDatePickerStart=true"/>
                                    <date-picker v-model="userData.started" :show="showDatePickerStart" @close="showDatePickerStart=false" :auto-submit="true" color="#7367f0"  type="datetime"
                                                 :locale="localeHas" :locale-config="localeConfig" :format="(localeHas=='fa')?'jYYYY/jMM/jDD HH:mm' : 'YYYY-MM-DD HH:mm'" element="started">
                                    </date-picker>
                                </b-input-group-append>
                            </b-input-group>
                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                            <small>از این تاریخ به بعد این کد قابلیت درج شدن توسط کاربر را دارد.</small>
                        </b-form-group>
                    </validation-provider>

                    <!-- started -->
                    <validation-provider
                        #default="validationContext"
                        name="expired"
                        rules="required"
                    >
                        <b-form-group
                            label="تاریخ منقضی شدن این کد تخفیف"
                            label-for="expired"
                        >
                            <b-input-group>
                                <b-form-input
                                    placeholder="تا تاریخ"
                                    v-model="userData.expired"
                                    id="expired" name="expired"
                                    :state="getValidationState(validationContext)"
                                />
                                <b-input-group-append is-text>
                                    <feather-icon icon="CalendarIcon" class="cursor-pointer" @click="showDatePickerStop=true"/>
                                    <date-picker v-model="userData.expired" :show="showDatePickerStop" @close="showDatePickerStop=false" :auto-submit="true" color="#7367f0"  type="datetime"
                                                 :locale="localeHas" :locale-config="localeConfig" :format="(localeHas=='fa')?'jYYYY/jMM/jDD HH:mm' : 'YYYY-MM-DD HH:mm'" element="expired">
                                    </date-picker>
                                </b-input-group-append>
                            </b-input-group>
                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                            <small>تا این تاریخ این کد قابلیت درج شدن توسط کاربر را دارد.</small>
                        </b-form-group>
                    </validation-provider>

                    <b-tabs>
                        <b-tab v-for="(item,key) in getGeneralInfo.locales" :title="item.name" :active="key===0">
                            <validation-provider
                                #default="validationContext"
                                :name="'locale'+item.symbol"
                                rules="required"
                            >
                                <b-form-group
                                    :label="'توضیحات کوتاه به زبان '+item.name"
                                    label-for="period"
                                >
                                    <b-form-input
                                        :id="'locale'+item.symbol"
                                        v-model="userData.description[item.symbol]"
                                        autofocus
                                        :state="getValidationState(validationContext)"
                                        trim
                                        placeholder="توضیح کوتاه"
                                    />
                                    <b-form-invalid-feedback>
                                        {{ validationContext.errors[0] }}
                                    </b-form-invalid-feedback>
                                </b-form-group>
                            </validation-provider>
                        </b-tab>
                    </b-tabs>

                    <!-- id Users -->
                    <validation-provider
                        #default="validationContext"
                        name="idUsers"
                        rules=""
                    >
                        <b-form-group
                            label="شناسه کاربران مجاز (اختیاری)"
                            label-for="period"
                        >
                            <b-form-input
                                id="idUsers"
                                v-model="userData.idUsers"
                                autofocus
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="شناسه کاربران مجاز( , )"
                            />
                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                            <small>شناسه کاربرانی که آنها فقط مجاز به استفاده هستند را درج کنید و اگر بیش از یک نفر قصد درج دارد میتوانید با کاما(,) جدا کنید(مثلا 31,678)</small>
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
        BSidebar, BForm, BFormGroup, BFormInput, BFormInvalidFeedback, BButton,BTabs,BTab,BInputGroup,BInputGroupAppend
    } from 'bootstrap-vue'
    import {ValidationProvider, ValidationObserver} from 'vee-validate'
    import {ref} from '@vue/composition-api'
    import {required, alphaNum, email,integer,between} from '@validations'
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
            BFormInput,
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
            }
        },
        data() {
            return {
                required,
                alphaNum,
                email,
                countries,
                integer,
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
                name: '',
                count: '',
                period: '',
                buyDiscount: '',
                sellDiscount: '',
                description: {},
                idUsers: '',
                started: '',
                expired: '',
            }

            const userData = ref(JSON.parse(JSON.stringify(blankUserData)))
            const resetuserData = () => {
                userData.value = JSON.parse(JSON.stringify(blankUserData))
            }

            const onSubmit = () => {
                axios.post('gift/add-new', userData.value)
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
