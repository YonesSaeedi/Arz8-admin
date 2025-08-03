<template>
    <div>
        <b-card title="ورود دو مرحله ای">
            <b-row class="col-lg-6 px-0 mx-auto">
                همواره به صورت پیش فرض ورود دو مرحله ای پیامکی برای همه ادمین ها فعال است و در صورت تمایل میتوانید در این قسمت ورود دو مرحله گوگل را جایگزین پیامک کنید.
                <hr class="w-100">
                <div v-if="!status">
                <p class="mb-2">
                    جهت فعال سازی این قابلیت، مراحل زیر را دنبال کنید:
                </p>
                <p class="mb-2">
                    1. آخرین نسخه Google Authenticator را از
                    <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2">گوگل پلی</a>
                    یا
                    <a href="https://itunes.apple.com/us/app/google-authenticator/id388497605?mt=8">اپل استور</a>
                    دریافت نمایید.
                </p>
                <p>
                    2. پس از نصب و اجرای برنامه Google Authenticator از طریق یکی از روش های زیر، کلید را به برنامه اضافه نمایید.
                    <br>
                    - Scan a barcode (اسکن بارکد): این گزینه را انتخاب کرده و بارکد زیر را اسکن نمایید.
                </p>
                    <div class="text-center w-100">
                        <img v-if="inlineUrl" :src="inlineUrl" width="150px">
                    </div>
                <p>
                    - Enter a provided key (با استفاده از کلید): این گزینه را انتخاب کرده و کد زیر را به دقت وارد نمایید.
                </p>

                <dif class="w-100 text-center h4 font-bold text-primary" v-if="GoogleHash"
                      v-clipboard:copy="GoogleHash"
                      v-clipboard:success="onCopy"
                      v-clipboard:error="onError">
                    {{GoogleHash}}
                </dif>

                <p class="mb-2">
                    3. کد دریافتی (عدد 6 رقمی) را در کادر زیر وارد نموده و دکمه فعال سازی را کلیک نمایید.
                </p>
                </div>
                <div v-else>
                    <p>جهت غیرفعال سازی این قابلیت، بایستی کد درج شده در اپلیکیشن را در فیلد زیر درج کنید و دکمه غیر فعالسازی رو بزنید.</p>
                </div>
                <hr class="w-100">
                <validation-observer ref="simpleRules" class="w-100" >
                    <b-form ref="form" @submit.stop.prevent="handleSubmit">
                        <b-col cols="12">
                            <b-form-group label="کد شش رقمی" label-cols-md="3" >
                                <validation-provider #default="{ errors }" rules="required|min:6">
                                    <b-form-input v-model="code" placeholder="کد شش رقمی" :state="errors.length > 0 ? false:null"
                                                  class="text-center"/>
                                </validation-provider>
                            </b-form-group>
                        </b-col>
                        <b-col cols="9" offset-md="3">
                            <b-button variant="primary" block class="text-center d-flex align-items-center justify-content-center" type="submit" :disabled="isLoading">
                                <div>{{!status?'فعال سازی':'غیرفعال سازی'}}</div>
                                <div class="line-height-0 ml-25"><b-spinner v-if="isLoading" small></b-spinner></div>
                            </b-button>
                        </b-col>
                    </b-form>
                </validation-observer>
            </b-row>
        </b-card>
    </div>
</template>

<script>
    import BCardActions from '@core/components/b-card-actions/BCardActions.vue'
    import {
        BCard,
        BCardHeader,
        BBadge,
        BCollapse,
        BLink,
        BCardBody,
        BRow,
        BCol,
        BInputGroup,
        BInputGroupAppend,
        BFormGroup,
        BFormInput,
        BTable,
        BButton,
        BAvatar,
        BInputGroupPrepend, BForm, BFormSelect,BSpinner
    } from 'bootstrap-vue'
    import Table from "@/views/vuexy/table/bs-table/Table";
    import vSelect from "vue-select";
    import { ValidationProvider, ValidationObserver } from 'vee-validate'
    import {
        required,min
    } from '@validations'
    import axiosIns from "@/libs/axios";
    import ToastificationContent from "@core/components/toastification/ToastificationContent";

    export default {
        data () {
            return {
                userData: JSON.parse(localStorage.getItem('userData')),
                code:null,
                GoogleHash:null,
                inlineUrl:null,
                isLoading:false,
                status: false,
            }
        },

        components: {
            Table,
            BTable,
            BLink,
            BCard,
            BBadge,
            BRow,
            BCol,
            BCardActions,
            BCardHeader,
            BCardBody,
            BCollapse,
            BButton,
            BInputGroup,BFormGroup, BInputGroupAppend,BFormInput,BAvatar,BSpinner,
            vSelect, ValidationProvider, ValidationObserver,BInputGroupPrepend,BForm, BFormSelect,
        },
        methods:{
            handleSubmit() {
                this.$refs.simpleRules.validate().then(success => {
                    if (success) {
                        this.isLoading = true;
                        axiosIns.post('/profile/2fa',{code:this.code})
                            .then(response => {
                                if(response.data.status == true){
                                    this.$toast({
                                        component: ToastificationContent,
                                        props: {
                                            title: 'انجام شد!',
                                            text: response.data.msg,
                                            icon: 'CheckCircleIcon',
                                            variant: 'success',
                                        },
                                    })
                                    document.getElementById("logout").click();
                                }else {
                                    this.$swal({icon: 'warning',title: 'نکته!',text: response.data.msg, confirmButtonText: 'باشه'})
                                }
                                this.isLoading = false;
                            })
                            .catch((error) => { console.log(error); this.errorFetching(); })
                    }
                });
            },
            getInfo(){
                axiosIns.post('/profile/2fa/info')
                .then(response => {
                    this.GoogleHash = response.data.secret
                    this.inlineUrl = response.data.inlineUrl
                    this.status = response.data.status
                })
                .catch((error) => { console.log(error); this.errorFetching(); })
            }
        },
        created() {
            this.status = this.userData.google2fa != null ? true : false
            this.getInfo();
        }
    }
</script>

<style scoped>

</style>
