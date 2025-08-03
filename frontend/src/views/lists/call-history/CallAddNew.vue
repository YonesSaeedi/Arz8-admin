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
                    افزودن تماس
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
                        name="mobile"
                        rules="required"
                    >
                        <b-form-group
                            label="موبایل کاربر"
                            label-for="mobile"
                        >
                            <b-form-input
                                :disabled="mobile!==null"
                                id="mobile"
                                v-model="userData.mobile"
                                autofocus
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="موبایل کاربر"
                            />

                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                        </b-form-group>
                    </validation-provider>


                    <!-- sellDiscount -->
                    <validation-provider
                        #default="validationContext"
                        name="text"
                        rules="required"
                    >
                        <b-form-group
                            label="توضیحات"
                            label-for="text"
                        >
                            <b-form-textarea
                                id="text"
                                v-model="userData.text"
                                autofocus
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="توضیحات"
                            />
                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                        </b-form-group>
                    </validation-provider>

                    <!-- started -->
                    <validation-provider
                        #default="validationContext"
                        name="date"
                        rules="required"
                    >
                        <b-form-group
                            label="تاریخ فعال شدن این کد تخفیف"
                            label-for="date"
                        >
                            <b-input-group>
                                <b-form-input
                                    placeholder="زمان تماس"
                                    v-model="userData.date"
                                    id="date" name="date"
                                    :state="getValidationState(validationContext)"
                                />
                                <b-input-group-append is-text>
                                    <feather-icon icon="CalendarIcon" class="cursor-pointer" @click="showDatePickerStart=true"/>
                                    <date-picker v-model="userData.date" :show="showDatePickerStart" @close="showDatePickerStart=false" :auto-submit="true" color="#7367f0"  type="datetime"
                                                 :locale="localeHas" :locale-config="localeConfig" :format="(localeHas=='fa')?'jYYYY/jMM/jDD HH:mm' : 'YYYY-MM-DD HH:mm'" element="date">
                                    </date-picker>
                                </b-input-group-append>
                            </b-input-group>
                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                            <small>تاریخ و زمانی که تماس با کاربر بر قرار شده است.</small>
                        </b-form-group>
                    </validation-provider>

                    <!-- Form Actions -->
                    <div class="d-flex mt-2">
                        <b-button
                            v-ripple.400="'rgba(255, 255, 255, 0.15)'"
                            variant="primary"
                            class="mr-2 d-flex align-items-center justify-content-center"
                            type="submit" block
                            :disabled="userData.isLoading"

                        >
                            <div>افزودن</div>
                            <div class="line-height-0 ml-25"><b-spinner v-if="userData.isLoading" small></b-spinner></div>
                        </b-button>
                        <b-button
                            v-ripple.400="'rgba(186, 191, 199, 0.15)'"
                            type="button"
                            variant="outline-secondary"
                            @click="hide"
                            :disabled="userData.isLoading"
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
    BSidebar,
    BForm,
    BFormGroup,
    BFormInput,
    BFormTextarea,
    BFormInvalidFeedback,
    BButton,
    BTabs,
    BTab,
    BInputGroup,
    BInputGroupAppend,
    BSpinner
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
            BSpinner,
            BTabs,BTab,
            BSidebar,
            BForm,
            BFormGroup,
            BFormInput,
            BFormTextarea,
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
            mobile: {
                type: String,
                required: false,
            },
            isAddNewSidebarActive: {
                type: Boolean,
                required: true,
            }
        },
        created() {
            if(this.mobile!==null){
                this.userData.mobile = this.mobile;
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
            const CallData = {
                mobile: '',
                text: '',
                date: '',
                isLoading: false,
            }

            const userData = ref(JSON.parse(JSON.stringify(CallData)))
            const resetuserData = () => {
                userData.value = JSON.parse(JSON.stringify(CallData))
            }

            const onSubmit = () => {
                userData.value.isLoading = true;
                axios.post('call-history/add', userData.value)
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
                        userData.value.isLoading = false;
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
                        userData.value.isLoading = false;
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
                    userData.value.isLoading = false;
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
                resetForm
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
