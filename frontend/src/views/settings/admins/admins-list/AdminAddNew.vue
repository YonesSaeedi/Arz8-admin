<template>
    <b-sidebar
        id="add-new-user-sidebar"
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
                    افزودن ادمین
                </h5>
                <feather-icon
                    class="ml-1 cursor-pointer"
                    icon="XIcon"
                    size="16"
                    @click="hide"
                />
            </div>
            <p class="px-2 mt-1">
                در این بخش فقط اطلاعات اولیه ثبت میشود و بقیه تنظیمات در صفحه ادمین باید انجام شود.
            </p>

            <!-- BODY -->
            <validation-observer
                #default="{ handleSubmit }"
                ref="refFormObserver"
            >
                <!-- Form -->
                <b-form
                    class="px-2"
                    @submit.prevent="handleSubmit(onSubmit)"
                    autocomplete="off"
                    @reset.prevent="resetForm"
                >

                    <!-- Name -->
                    <validation-provider #default="validationContext" name="name" rules="required">
                        <b-form-group label="نام و فامیل" label-for="name">
                            <b-form-input
                                id="name"
                                v-model="userData.name"
                                autofocus
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="نام و نام خانوادگی"
                            />
                        </b-form-group>
                    </validation-provider>


                    <!-- email -->
                    <validation-provider #default="validationContext" name="email" rules="required|email">
                        <b-form-group label="ایمیل" label-for="email">
                            <b-form-input
                                id="symbol"
                                v-model="userData.email"
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="ایمیل"
                            />
                        </b-form-group>
                    </validation-provider>

                    <!-- mobile -->
                    <validation-provider #default="validationContext" name="mobile" rules="required|min:11">
                        <b-form-group label="موبایل" label-for="mobile">
                            <b-form-input
                                id="mobile"
                                v-model="userData.mobile"
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="موبایل یازده رقمی"
                            />
                        </b-form-group>
                    </validation-provider>

                    <!-- password -->
                    <validation-provider #default="validationContext" name="password" rules="required|min:6">
                        <b-form-group label="کلمه عبور" label-for="password">
                            <b-form-input
                                id="password"
                                v-model="userData.password"
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="کلمه عبور"
                            />
                        </b-form-group>
                    </validation-provider>

                    <!-- role -->
                    <validation-provider #default="validationContext" name="role" rules="required">
                        <b-form-group label="سطح یا نقش" label-for="role">
                            <b-form-select
                                id="role"
                                v-model="userData.role"
                                :options="[{text:'سطح را انتخاب کنید', value:null, disabled:true, hidden:true},{text:'ادمین کل بدون محدودیت', value:'admin' },{text:'ادمین عادی با محدودیت', value:'member' }]"
                                :state="getValidationState(validationContext)"
                            />
                        </b-form-group>
                    </validation-provider>

                    <!-- Form Actions -->
                    <div class="d-flex my-2">
                        <b-button block
                            v-ripple.400="'rgba(255, 255, 255, 0.15)'"
                            variant="primary"
                            class="mr-2 d-flex align-items-center justify-content-center"
                            type="submit"
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
        BSidebar, BForm, BFormGroup, BFormInput,BFormSelect, BFormInvalidFeedback, BButton, BFormFile, BSpinner,BFormCheckbox
    } from 'bootstrap-vue'
    import {ValidationProvider, ValidationObserver} from 'vee-validate'
    import {ref} from '@vue/composition-api'
    import {required, alphaNum, between,email,min} from '@validations'
    import formValidation from '@core/comp-functions/forms/form-validation'
    import Ripple from 'vue-ripple-directive'
    import vSelect from 'vue-select'
    import countries from '@/@fake-db/data/other/countries'
    import axios from "@/libs/axios";
    import ToastificationContent from "@core/components/toastification/ToastificationContent";
    import {useToast} from 'vue-toastification/composition'

    export default {
        components: {
            BSidebar,
            BForm,
            BFormGroup,
            BFormInput,
            BFormInvalidFeedback,
            BButton,
            BFormSelect,
            BFormFile,
            BSpinner,
            BFormCheckbox,

            // Form Validation
            ValidationProvider,
            ValidationObserver,
        },
        directives: {
            Ripple,
        },
        model: {
            prop: 'isAddNewSidebarActive',
            event: 'update:is-add-new-user-sidebar-active',
        },
        props: {
            isAddNewSidebarActive: {
                type: Boolean,
                required: true,
            },
        },
        data() {
            return {
                required,
                alphaNum,
                between,
                email,min
            }
        },
        setup(props, {emit}) {
            const toast = useToast()
            const blankUserData = {
                isLoading: false,
                name: '',
                mobile: '',
                email: '',
                password: '',
                role: null,
            }

            const userData = ref(JSON.parse(JSON.stringify(blankUserData)))
            const resetuserData = () => {
                userData.value = JSON.parse(JSON.stringify(blankUserData))
            }

            const onSubmit = () => {
                userData.value.isLoading = true;
                axios.post('admins/add-new', userData.value)
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
                        userData.value.isLoading = false;
                    }).catch((error) => {
                    userData.value.isLoading = false;
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
        mounted() {
        }
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
