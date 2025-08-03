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
                    افزودن تراکنش جدید
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
                            label="نوع تراکنش"
                            label-for="type"
                        >
                            <b-form-select
                                v-model="userData.type"
                                :options="[{'text':'واریز','value':'deposit'},{'text':'برداشت','value':'withdraw'}]"
                                :state="getValidationState(validationContext)"
                            />
                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                        </b-form-group>
                    </validation-provider>

                    <!-- Family -->
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
                                v-model="amount"
                                autofocus
                                :state="getValidationState(validationContext)"
                                trim
                                dir="ltr"
                                placeholder="مبلغ"
                            />

                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                        </b-form-group>
                    </validation-provider>

                    <!-- Email -->
                    <validation-provider
                        #default="validationContext"
                        name="dis"
                        rules="required"
                    >
                        <b-form-group
                            label="توضیحات تراکنش"
                            label-for="dis"
                        >
                            <b-form-input
                                id="dis"
                                v-model="userData.dis"
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="توضیحات"
                            />

                            <b-form-invalid-feedback>
                                {{ validationContext.errors[0] }}
                            </b-form-invalid-feedback>
                        </b-form-group>
                    </validation-provider>

                    <!-- Name -->
                    <validation-provider
                        #default="validationContext"
                        name="payment_gateway"
                        rules="required"
                    >
                        <b-form-group
                            label="پرداخت یاری"
                            label-for="payment_gateway"
                        >
                            <b-form-select
                                v-model="userData.payment_gateway"
                                :options="gatewayOptions.map((op)=>{return {'text':op.label,'value':op.value}})"
                                :state="getValidationState(validationContext)"
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

                            <div>افزودن</div>
                            <div class="line-height-0 ml-25"><b-spinner v-if="userData.isLoading" small></b-spinner></div>
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
        BSidebar, BForm, BFormGroup, BFormInput, BFormInvalidFeedback, BButton,BFormSelect,BSpinner
    } from 'bootstrap-vue'
    import {ValidationProvider, ValidationObserver} from 'vee-validate'
    import {ref,watch} from '@vue/composition-api'
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
            BButton,BFormSelect,BSpinner,
            vSelect,

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
            },
            gatewayOptions:{
                type: Array,
                required: false,
            }
        },
        data() {
            return {
                required,
                alphaNum,
                email,
                countries,
                amount:'',
            }
        },
        setup(props, {emit}) {
            const toast = useToast()
            const blankUserData = {
                type: 'deposit',
                amount: '',
                dis: '',
                payment_gateway: '',
                isLoading: false
            }

            const userData = ref(JSON.parse(JSON.stringify(blankUserData)))


            const resetuserData = () => {
                userData.value = JSON.parse(JSON.stringify(blankUserData))
            }

            const onSubmit = () => {
                userData.value.isLoading = true;
                userData.value.amount = amount.value.replace(/[^\d.-]/g, '');
                axios.post('reports/payment-gateway/add-tr', userData.value)
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
                resetForm,
            }
        },
        watch: {
            amount(value) {
                this.$nextTick(() => {
                    var amount = this.amountLocalFloat(value, 0)
                    this.amount = this.subStrFloat(amount, 0);
                })
            },
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
