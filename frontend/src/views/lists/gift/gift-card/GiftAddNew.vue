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
        @change="(val) => {$emit('update:is-add-new-sidebar-active', val);$emit('update:id-edit', null)}"
    >
        <template #default="{ hide }">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center content-sidebar-header px-2 py-1">
                <h5 class="mb-0">
                    {{giftData.id!==null?'ویرایش کارت هدیه':'افزودن کارت هدیه'}}
                </h5>

                <feather-icon
                    class="ml-1 cursor-pointer"
                    icon="XIcon"
                    size="16"
                    @click="hide"
                />

            </div>

            <b-overlay :show="isAddNewSidebarActive && !giftData.name && giftData.id!==null" rounded="sm">

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
                            label="کد هدیه را درج کنید"
                            label-for="name"
                        >
                            <b-form-input
                                id="name"
                                v-model="giftData.name"
                                autofocus
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="نام کد هدیه"
                            />

                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                        </b-form-group>
                    </validation-provider>

                    <validation-provider
                        #default="validationContext"
                        name="symbol"
                        rules="required"
                    >
                        <b-form-group
                            label="کوین یا ارزی که قصد هدیه دادن دارید"
                            label-for="period"
                        >
                            <b-form-input
                                id="symbol"
                                v-model="giftData.symbol"
                                autofocus
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="نماد ارز"
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
                            label="مقدار هدیه یا ارز"
                            label-for="period"
                        >
                            <b-form-input
                                id="amount"
                                v-model="giftData.amount"
                                autofocus
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="مقدار ارز یا هدیه"
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
                            label="تاریخ فعال شدن این کد تخفیف"
                            label-for="started"
                        >
                            <b-input-group>
                                <b-form-input
                                    placeholder="از تاریخ"
                                    v-model="giftData.started"
                                    id="started" name="started"
                                    :state="getValidationState(validationContext)"
                                />
                                <b-input-group-append is-text>
                                    <feather-icon icon="CalendarIcon" class="cursor-pointer" @click="showDatePickerStart=true"/>
                                    <date-picker v-model="giftData.started" :show="showDatePickerStart" @close="showDatePickerStart=false" :auto-submit="true" color="#7367f0"  type="datetime"
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
                                    v-model="giftData.expired"
                                    id="expired" name="expired"
                                    :state="getValidationState(validationContext)"
                                />
                                <b-input-group-append is-text>
                                    <feather-icon icon="CalendarIcon" class="cursor-pointer" @click="showDatePickerStop=true"/>
                                    <date-picker v-model="giftData.expired" :show="showDatePickerStop" @close="showDatePickerStop=false" :auto-submit="true" color="#7367f0"  type="datetime"
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
                                v-model="giftData.count"
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


                    <!-- Form Actions -->
                    <div class="d-flex mt-2">
                        <b-button block
                          v-ripple.400="'rgba(255, 255, 255, 0.15)'"
                          variant="primary"
                          class="mr-2 d-flex align-items-center justify-content-center"
                          type="submit"
                          :disabled="giftData.isLoading"
                        >
                            <div>{{giftData.id!==null?'ویرایش کارت هدیه':'افزودن کارت هدیه'}}</div>
                            <div class="line-height-0 ml-25"><b-spinner v-if="giftData.isLoading" small></b-spinner></div>
                        </b-button>
                        <b-button
                            v-if="giftData.id!==null"
                            v-ripple.400="'rgba(255, 255, 255, 0.15)'"
                            variant="outline-danger"
                            class="mr-1"
                            type="button"
                            @click="$emit('remove',giftData.id)"
                        >حذف</b-button>
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
        BSidebar, BForm, BFormGroup, BFormInput, BFormInvalidFeedback, BButton,BTabs,BTab,BInputGroup,BInputGroupAppend,
        BSpinner,BOverlay
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
    import axiosIns from "@/libs/axios";

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
            BInputGroup,BInputGroupAppend,BSpinner,BOverlay,

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
            idEdit: {
                type: Number,
                required: false,
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
                }
            }
        },
        watch:{
            idEdit(value){
                if(value){
                    this.giftData.id = value
                    axiosIns.post('gift/card/info/'+value).then((response) => {
                        this.giftData.name = response.data.card_namber
                        this.giftData.amount = response.data.amount
                        this.giftData.symbol = response.data.symbol
                        this.giftData.count = response.data.count
                        this.giftData.started = response.data.started
                        this.giftData.expired = response.data.expired
                    })
                }
            }
        },
        setup(props, {emit}) {
            const toast = useToast()
            const blankGiftData = {
                name: '',
                count: '',
                amount: '',
                symbol: '',
                started: '',
                expired: '',
                isLoading: false,
                id:null,
            }

            const giftData = ref(JSON.parse(JSON.stringify(blankGiftData)))
            const resetuserData = () => {
                giftData.value = JSON.parse(JSON.stringify(blankGiftData))
            }

            const onSubmit = () => {
                giftData.value.isLoading = true;
                axios.post('gift/card/add-new', giftData.value)
                .then((response) => {
                    if(response.data.status){
                        emit('refetch-data')
                        emit('update:is-add-new-sidebar-active', false)
                        emit('update:id-edit', null)
                        toast({
                            component: ToastificationContent,
                            props: {
                                title: 'موفق!',
                                text: response.data.msg,
                                icon: 'CheckIcon',
                                variant: 'success',
                            },
                        })
                        giftData.value.isLoading = false;
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
                        giftData.value.isLoading = false;
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
                    giftData.value.isLoading = false;
                })
            }

            const {
                refFormObserver,
                getValidationState,
                resetForm,
            } = formValidation(resetuserData)

            return {
                giftData,
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
