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
                    افزودن هزینه
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
                            label="نوع هزینه"
                            label-for="type"
                        >
                            <b-form-select
                                id="type"
                                v-model="userData.type"
                                :options="[{'text':'شارژ BNB','value':'BNB'},{'text':'شارژ CET','value':'CET'},{'text':'هزینه تومانی','value':'decrement'}]"
                                autofocus
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="نوع هزینه"
                            />
                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                        </b-form-group>
                    </validation-provider>

                    <!-- count -->
                    <validation-provider v-if="userData.type === 'BNB' || userData.type === 'CET'"
                        #default="validationContext"
                        name="fee"
                        rules="required"
                    >
                        <b-form-group
                            :label="'قیمت هر یک '+userData.type+' به تومان'"
                            label-for="fee"
                        >
                            <b-form-input
                                id="fee"
                                v-model="userData.fee"
                                autofocus
                                :state="getValidationState(validationContext)"
                                trim
                                :placeholder="'قیمت هر یک '+userData.type+' به تومان'"
                            />
                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                        </b-form-group>
                    </validation-provider>



                    <!-- amount -->
                    <validation-provider
                        #default="validationContext"
                        name="amount"
                        rules="required"
                    >
                        <b-form-group
                            label="مبلغ"
                            label-for="amount"
                        >
                            <b-form-input
                                id="amount"
                                v-model="userData.amount"
                                autofocus
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="مبلغ یا مقدار"
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
                                row="2"
                                id="description"
                                v-model="userData.description"
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

                    <!-- file -->
                    <validation-provider #default="validationContext" name="file">
                        <b-form-group label="فایل اختیاری (فرمت مجاز png و jpg و pdf)" label-for="file">
                            <b-form-file id="file"
                                         v-model="userData.file"
                                         placeholder="فایل را انتخاب کنید"
                                         drop-placeholder="Drop file here..."
                                         :state="getValidationState(validationContext)"
                            />
                        </b-form-group>
                    </validation-provider>

                    <!-- Tax -->
                    <validation-provider
                        #default="validationContext"
                        name="tax"
                        rules="required"
                    >
                        <b-form-group
                            label="در هزینه های مالیاتی درج شود؟"
                            label-for="tax"
                        >
                            <b-form-select
                                id="tax"
                                v-model="userData.tax"
                                :options="[{'text':'بله','value':'1'},{'text':'خیر','value':'0'}]"
                                autofocus
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="مالیات"
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
                                  :disabled="userData.isLoading"
                        >
                            <div>افزودن هزینه</div>
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
        BSidebar, BForm, BFormGroup, BFormInput,BFormSelect, BFormInvalidFeedback, BButton,BTabs,BTab,BInputGroup,BInputGroupAppend,
        BFormTextarea,BFormFile,BSpinner
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
            BButton,BFormTextarea,
            vSelect,BFormFile,BSpinner,
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
                isLoading: false,
                type: 'decrement',
                description: '',
                amount: '',
                fee: '',
                date: '',
                file: null,
                tax: null,
            }

            const userData = ref(JSON.parse(JSON.stringify(blankUserData)))
            const resetuserData = () => {
                userData.value = JSON.parse(JSON.stringify(blankUserData))
            }

            const onSubmit = () => {
                userData.value.isLoading = true;

                var formData = new FormData();
                var file = document.querySelector('#file');
                if(file.files[0])
                    formData.append("file", file.files[0]);
                formData.append("type", userData.value.type);
                formData.append("description", userData.value.description);
                formData.append("amount", userData.value.amount);
                formData.append("fee", userData.value.fee);
                formData.append("date", userData.value.date);
                formData.append("tax", userData.value.tax);

                axios.post('audit/cust/add-new', formData)
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
