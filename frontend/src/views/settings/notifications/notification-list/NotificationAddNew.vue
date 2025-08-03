<template>
    <b-sidebar
        id="add-new-notif-sidebar"
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
                    ارسال اطلاعیه
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
                    class="px-2"
                    @submit.prevent="handleSubmit(onSubmit)"
                    autocomplete="off"
                    @reset.prevent="resetForm"
                >
                    <!-- chart -->
                    <validation-provider #default="validationContext" rules="required">
                        <b-form-group class="mt-2" label="عنوان:">
                            <b-form-input
                                id="title"
                                v-model="userData.title"
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="عنوان"
                            />
                            <small>عنوان هایی را میتوانید انتخاب کنید که معادل ترجمه آن در اپلیکشن ها و وب سایت وجود داشته باشد.</small>
                        </b-form-group>
                    </validation-provider>

                    <b-tabs>
                        <b-tab :title="item.name" :active="item.symbol ==='fa'" v-for="(item,key) in getGeneralInfo.locales">
                            <validation-provider #default="validationContext" rules="required">
                                <b-form-group label="پیغام:">
                                    <b-form-textarea
                                        v-model="userData.message[item.symbol]"
                                        :state="getValidationState(validationContext)"
                                        trim
                                        :placeholder="'درج پیغام به زبان '+item.name"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-tab>
                    </b-tabs>

                    <div class="d-flex my-2">
                        <b-form-radio
                            v-model="userData.allUsers"
                            name="status"
                            :value="true"
                            :disabled="user"
                            class="custom-control-success mr-2"
                        >
                            ارسال برای همه کاربران
                        </b-form-radio>
                        <b-form-radio
                            v-model="userData.allUsers"
                            name="status"
                            :value="false"
                            class="custom-control-primary"
                        >
                            ارسال به کاربران خاص
                        </b-form-radio>
                    </div>

                    <!-- id Users -->
                    <validation-provider v-if="userData.allUsers !== true" #default="validationContext" rules="required">
                        <b-form-group label="شناسه کاربران ارسالی (اختیاری)">
                            <b-form-input
                                id="idUsers"
                                v-model="userData.idUsers"
                                :state="getValidationState(validationContext)"
                                trim
                                placeholder="شناسه کاربران ارسالی( , )"
                            />
                            <small>شناسه کاربرانی که فقط برای آنها این اطلاعیه برود را درج کنید و اگر بیش از یک نفر قصد درج دارد میتوانید با کاما(,) جدا کنید(مثلا 31,678)</small>
                        </b-form-group>
                    </validation-provider>


                    <div class="mb-2">
                        <p class="mb-0">طریقه ارسال:</p>
                        <small>نکته 1: اگر هیچ کدام از موارد زیر را انتخاب نکنید صرفا در قسمت اطلاعیه های پلتفرم به کاربران نمایش داده میشود.</small>
                        <br>
                        <small>نکته 2: اگر نوتیفکیشن و همه کاربران را انتخاب کرده باشید بصورت آنی برای کاربران نوتیفیکیشن میرود.</small>
                        <br>
                        <small>نکته 3: اگر چه ایمیل و چه پیامک را انتخاب کرده باشید باید زمانبندی ارسال مشخص کنید زیرا برای همه کاربران بصورت یکجا امکان ارسال نیست.</small>
                        <br>
                        <small>نکته 4: انتخاب پیامک فقط زمانی میسر است که شما یک الگو اعتبار سنجی در کاوه نگار به همه زبان های پلتفرم تعریف کرده باشید و نامش را بگذارید.</small>

                        <b-form-checkbox class="mt-1" v-model="userData.sendNotif" switch>
                            ارسال نوتیفیکیشن
                        </b-form-checkbox>
                        <b-form-checkbox v-model="userData.sendEmail" switch>
                            ارسال ایمیل
                        </b-form-checkbox>
                        <b-form-checkbox v-model="userData.sendSms" switch>
                            ارسال پیامک
                        </b-form-checkbox>
                        <b-form-checkbox v-model="userData.sendTelegram" switch>
                            ارسال در ربات تلگرام
                        </b-form-checkbox>
                    </div>
                    <b-tabs v-if="userData.sendSms === true">
                        <b-tab :title="item.name" :active="item.symbol ==='fa'" v-for="(item,key) in getGeneralInfo.locales">
                            <validation-provider #default="validationContext" rules="required">
                                <b-form-group label="نام الگو اعتبارسنجی کاوه نگار:">
                                    <b-form-input
                                        v-model="userData.sendSmsLocale[item.symbol]"
                                        :state="getValidationState(validationContext)"
                                        trim
                                        :placeholder="'نام الگو برای زبان '+item.name"
                                    />
                                    <small>بجای پارمتر %token در الگو اسم کاربر جاگزین میشود.</small>
                                </b-form-group>
                            </validation-provider>
                        </b-tab>
                    </b-tabs>


                    <validation-provider v-if="userData.sendEmail === true || userData.sendSms === true || userData.sendTelegram === true" #default="validationContext" rules="required">
                        <b-form-group label="در چند ثانیه؟">
                            <b-form-input
                                v-model="userData.numSeconds"
                                :state="getValidationState(validationContext)"
                                trim
                                type="number"
                                placeholder="هر چند ثانیه؟"
                            />
                        </b-form-group>
                    </validation-provider>

                    <validation-provider v-if="userData.sendEmail === true || userData.sendSms === true || userData.sendTelegram === true" #default="validationContext" rules="required">
                        <b-form-group label="در چند ثانیه؟">
                            <b-form-input
                                v-model="userData.numSeconds"
                                :state="getValidationState(validationContext)"
                                trim
                                type="number"
                                placeholder="هر چند ثانیه؟"
                            />
                        </b-form-group>
                    </validation-provider>

                    <validation-provider v-if="userData.sendNotif === true" #default="validationContext">
                        <b-form-group label="آدرس تصویر برای نوتیف(اختیاری)">
                            <b-form-input
                                v-model="userData.urlImageNotif"
                                :state="getValidationState(validationContext)"
                                trim
                                type="url"
                                placeholder="url تصویر نوتیف"
                            />
                        </b-form-group>
                    </validation-provider>
                    <validation-provider v-if="userData.sendNotif === true" #default="validationContext">
                        <b-form-group class="mt-2" label="صدای نوتیف">
                            <v-select
                                :dir="!$store.state.appConfig.isRTL ? 'rtl' : 'ltr'"
                                :options="[{label:'پیش فرض',value:null},{label:'صدای 1',value:'sound1.wav'},{label:'صدای 2',value:'sound2.wav'}]"
                                v-model="userData.soundNotif"
                                :reduce="val => val.value"
                                class="w-100"
                                placeholder="صدای نوتیف"
                                :state="getValidationState(validationContext)"
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
                            <div>ارسال اطلاعیه</div>
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
        BSidebar, BForm, BFormGroup, BFormInput,BFormSelect, BFormInvalidFeedback, BButton, BFormFile, BSpinner,
        BTabs,BTab,BFormTextarea, BFormRadio,BFormCheckbox,
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
            BTabs,BTab,
            BFormTextarea,
            BFormRadio,
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
            user: {
                type: BigInt,
                required: false,
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
            const NotifData = {
                isLoading: false,
                title: '',
                message: {},
                idUsers:  props.user ? props.user.id : '',
                allUsers: props.user ? false : true,
                sendSms: false,
                sendSmsLocale: {},
                sendEmail: false,
                sendNotif: false,
                sendTelegram: false,
                numSeconds: '1',
                numUsers: '5',
                urlImageNotif: null,
                soundNotif: null,
            }

            const userData = ref(JSON.parse(JSON.stringify(NotifData)))
            const resetuserData = () => {
                userData.value = JSON.parse(JSON.stringify(NotifData))
            }


            const onSubmit = () => {
                userData.value.isLoading = true;
                axios.post('setting/notification/send',  userData.value)
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
                    userData.value.isLoading = false
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

    #add-new-notif-sidebar {
        .vs__dropdown-menu {
            max-height: 200px !important;
        }

        .nav-tabs .nav-item a{
            font-size: 12px !important;
        }
    }
</style>
