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
                    افزودن شبکه
                </h5>
                <feather-icon
                    class="ml-1 cursor-pointer"
                    icon="XIcon"
                    size="16"
                    @click="hide"
                />
            </div>
            <p class="px-2 mt-1">
                سیمبل مهمترین پارامتری است که شما درج میکنید و به صورت اتوماتیک صحت سیمبل چک میشود.
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
                        <b-form-group label="نام به انگلیسی" label-for="name">
                            <b-form-input
                                id="name"
                                v-model="userData.name"
                                autofocus
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="نام مانند TRC20"
                            />
                        </b-form-group>
                    </validation-provider>

                    <!-- Name -->
                    <validation-provider #default="validationContext" name="nameFa" rules="required">
                        <b-form-group label="نام به فارسی" label-for="nameFa">
                            <b-form-input
                                id="nameFa"
                                v-model="userData.nameFa"
                                autofocus
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="نام مانند ترون"
                            />
                        </b-form-group>
                    </validation-provider>

                    <!-- symbol -->
                    <validation-provider #default="validationContext" name="symbol" rules="required">
                        <b-form-group label="نماد یا سیمبل(مهمترین)" label-for="symbol">
                            <b-form-input
                                id="symbol" class="text-uppercase"
                                v-model="userData.symbol"
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="نماد مانند TRX"
                            />
                        </b-form-group>
                    </validation-provider>

                    <!-- symbol Coin -->
                    <validation-provider #default="validationContext" name="symbolCoin" rules="required">
                        <b-form-group label="نماد یک ارز" label-for="symbolCoin">
                            <b-form-input
                                id="symbolCoin" class="text-uppercase"
                                v-model="userData.symbolCoin"
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="نماد مانند TRX"
                            />
                            <small>نماد یک ارز که از این شبکه استفاده میکند و مهم نیست که ارز قبلا در سیستم تعریف شده باشد یا خیر! صرفا جهت بررسی صحت شبکه در بایننس میباشد.</small>
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
                            <div>افزودن ارز</div>
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
        BSidebar, BForm, BFormGroup, BFormInput,BFormSelect, BFormInvalidFeedback, BButton, BFormFile, BSpinner
    } from 'bootstrap-vue'
    import {ValidationProvider, ValidationObserver} from 'vee-validate'
    import {ref} from '@vue/composition-api'
    import {required, alphaNum, between} from '@validations'
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
            }
        },
        data() {
            return {
                required,
                alphaNum,
                between,
            }
        },
        setup(props, {emit}) {
            const toast = useToast()
            const blankUserData = {
                isLoading: false,
                name: '',
                nameFa: '',
                symbol: '',
                symbolCoin: '',
            }

            const userData = ref(JSON.parse(JSON.stringify(blankUserData)))
            const resetuserData = () => {
                userData.value = JSON.parse(JSON.stringify(blankUserData))
            }


            const onSubmit = () => {
                userData.value.isLoading = true;

                axios.post('setting/network/new', userData.value)
                    .then((response) => {
                        if(response.data.status == true){
                            emit('refetch-data')
                            emit('update:is-add-new-sidebar-active', false)
                            emit('general-info')
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
                            userData.value.isLoading = false
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
        mounted() {
        }
    }
</script>

<style lang="scss">
    @import '@core/scss/vue/libs/vue-select.scss';
</style>
