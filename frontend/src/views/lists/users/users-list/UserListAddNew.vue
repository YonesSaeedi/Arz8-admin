<template>
    <b-sidebar
        id="add-new-user-sidebar"
        :visible="isAddNewUserSidebarActive"
        bg-variant="white"
        sidebar-class="sidebar-lg"
        shadow
        backdrop
        no-header
        right
        @hidden="resetForm"
        @change="(val) => $emit('update:is-add-new-user-sidebar-active', val)"
    >
        <template #default="{ hide }">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center content-sidebar-header px-2 py-1">
                <h5 class="mb-0">
                    افزودن کاربر جدید
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
                            label="نام"
                            label-for="name"
                        >
                            <b-form-input
                                id="name"
                                v-model="userData.name"
                                autofocus
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="اسم کوچک"
                            />

                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                        </b-form-group>
                    </validation-provider>

                    <!-- Family -->
                    <validation-provider
                        #default="validationContext"
                        name="family"
                        rules="required"
                    >
                        <b-form-group
                            label="نام خانوادگی"
                            label-for="name"
                        >
                            <b-form-input
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

                    <!-- Email -->
                    <validation-provider
                        #default="validationContext"
                        name="email"
                        rules="required|email"
                    >
                        <b-form-group
                            label="ایمیل"
                            label-for="email"
                        >
                            <b-form-input
                                id="email"
                                v-model="userData.email"
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="Email"
                            />

                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                        </b-form-group>
                    </validation-provider>

                    <!-- Password -->
                    <validation-provider
                        #default="validationContext"
                        name="password"
                        rules="required|min:6"
                    >
                        <b-form-group
                            label="کلمه عبور"
                            label-for="password"
                        >
                            <b-form-input
                                id="password"
                                v-model="userData.password"
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="حداقل 6 کاراکتر"
                            />

                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
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
        BSidebar, BForm, BFormGroup, BFormInput, BFormInvalidFeedback, BButton,
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

    export default {
        components: {
            BSidebar,
            BForm,
            BFormGroup,
            BFormInput,
            BFormInvalidFeedback,
            BButton,
            vSelect,

            // Form Validation
            ValidationProvider,
            ValidationObserver,
        },
        directives: {
            Ripple,
        },
        model: {
            prop: 'isAddNewUserSidebarActive',
            event: 'update:is-add-new-user-sidebar-active',
        },
        props: {
            isAddNewUserSidebarActive: {
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
            }
        },
        setup(props, {emit}) {
            const toast = useToast()
            const blankUserData = {
                name: '',
                family: '',
                email: '',
                password: '',
            }

            const userData = ref(JSON.parse(JSON.stringify(blankUserData)))
            const resetuserData = () => {
                userData.value = JSON.parse(JSON.stringify(blankUserData))
            }

            const onSubmit = () => {
                axios.post('users/add-new', userData.value)
                .then((response) => {
                    emit('refetch-data')
                    emit('update:is-add-new-user-sidebar-active', false)
                    toast({
                        component: ToastificationContent,
                        props: {
                            title: 'موفق!',
                            text: response.data.msg,
                            icon: 'CheckIcon',
                            variant: 'success',
                        },
                    })
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

    #add-new-user-sidebar {
        .vs__dropdown-menu {
            max-height: 200px !important;
        }
    }
</style>
