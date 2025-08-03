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
                    افزودن رمز ارز
                </h5>
                <feather-icon
                    class="ml-1 cursor-pointer"
                    icon="XIcon"
                    size="16"
                    @click="hide"
                />
            </div>
            <p class="px-2 mt-1">
                لیست تمامی مارکت های باینسس در آدرس
                <a href="https://api.binance.com/api/v1/ticker/allPrices" target="_blank">https://api.binance.com/api/v1/ticker/allPrices</a>
                وجود دارد. و تمامی مارکت های این لیست را میتوانید درج کنید و حتما باید مارکتی که درج میکنید در این لیست باشد. فرمت خروجی این آدرس json است که برای نمای بهتر میتوانید کل خروجی را کپی کنید و در آدرس https://jsonformatter.org/json-viewer پیست و مشاهده کنید.
            </p>
            <p class="px-2 mt-1">
                مثلا در لیست بازار های باینسس نمادی مانند ETHBTC وجود دارد و ارز دوم این نماد که در این مثال BTC میباشد باید در بعنوان "ارز quoteAsset" درج کنید و ارز دیگر این نماد که در این مثال ETH به عنوان "ارز baseAsset" باید در فرم زیر انتخاب کنید.
            </p>
            <p class="px-2 mt-1">
                لیست چارت ها در سایت تردینگ ویو و در آدرس
                <a href="https://www.tradingview.com/widget/advanced-chart" target="_blank">https://www.tradingview.com/widget/advanced-chart</a>
                میباشد که میتوانید در قسمت "Default symbol" مقدار را کپی کرده(حتی چارت های غیر باینسس) و در فیلد چارت کپی کنید و یا بصورت دستی BINANCE:ETHBTC را درج کنید که البته بجای BTCUSDT باید مارکتی که در لیست باینسس قصد اضافه کردن را دارید درج کنید.

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

                    <div class="mt-1">
                        <!-- baseAsset -->
                        <label>ارز baseAsset</label>
                        <v-select
                            :dir="!$store.state.appConfig.isRTL ? 'rtl' : 'ltr'"
                            :options="cryptoOptions"
                            v-model="userData.baseAsset"
                            :reduce="val => val.value"
                            class="w-100"
                            placeholder="ارز baseAsset را انتخاب کنید"
                        />
                    </div>

                    <div class="my-1">
                        <!-- quoteAsset -->
                        <label>ارز quoteAsset</label>
                        <v-select
                            :dir="!$store.state.appConfig.isRTL ? 'rtl' : 'ltr'"
                            :options="cryptoOptions"
                            v-model="userData.quoteAsset"
                            :reduce="val => val.value"
                            class="w-100"
                            placeholder="ارز quoteAsset را انتخاب کنید"
                        />
                    </div>

                    <!-- chart -->
                    <validation-provider #default="validationContext" rules="required">
                        <b-form-group label="چارت">
                            <b-form-input
                                v-model="userData.chart"
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="چارت مانند: BINANCE:BTCUSDT"
                            />
                        </b-form-group>
                    </validation-provider>


                    <!-- Form Actions -->
                    <div class="d-flex mt-2 mb-4">
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
    import {ref, watch} from '@vue/composition-api'
    import {required, alphaNum, between} from '@validations'
    import formValidation from '@core/comp-functions/forms/form-validation'
    import Ripple from 'vue-ripple-directive'
    import vSelect from 'vue-select'
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
            event: 'update:is-add-new-user-sidebar-active',
        },
        props: {
            isAddNewSidebarActive: {
                type: Boolean,
                required: true,
            },
            cryptoOptions: {
                type: Array,
                required: true,
            },
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
                baseAsset: null,
                quoteAsset: null,
                chart: null,
            }

            const userData = ref(JSON.parse(JSON.stringify(blankUserData)))
            const resetuserData = () => {
                userData.value = JSON.parse(JSON.stringify(blankUserData))
            }


            watch(() => [userData.value.baseAsset,userData.value.quoteAsset], (first, second) => {
                if(userData.value.baseAsset != null && userData.value.quoteAsset != null){
                    userData.value.chart = 'BINANCE:'+userData.value.baseAsset+userData.value.quoteAsset;
                }
            });

            const onSubmit = () => {
                if(userData.value.baseAsset == null || userData.value.quoteAsset == null){
                    toast({
                        component: ToastificationContent,
                        props: {
                            title: 'خطا!',
                            text: 'فرم را تکمیل نمایید',
                            icon: 'AlertTriangleIcon',
                            variant: 'danger',
                        },
                    })
                    return;
                }


                userData.value.isLoading = true;
                axios.post('setting/markets/new',  userData.value)
                .then((response) => {
                    if(response.data.status == true){
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

    #add-new-user-sidebar {
        .vs__dropdown-menu {
            max-height: 200px !important;
        }
    }
</style>
