<template>
    <div id="level2-form">
        <validation-observer
            #default="{ handleSubmit }"
            ref="refFormLevel3Observer"
        >
            <b-form
                class="px-md-2"
                @submit.prevent="handleSubmit(onSubmit)"
                autocomplete="off"
            >
                <b-col md="8" class="mx-auto">
                    <p>
                        اطلاعات سطح دو در این بخش قابل ثبت و ویرایش میباشد! اطلاعات این سطح باید به تایید شما برسد و
                        اگر بعد از تایید سطح آن کمتر از سه باشد، به سطه سه ارتقاء میباید.
                    </p>
                    <div  class="mb-2">
                        <b-button size="sm" v-ripple.400="'rgba(255, 255, 255, 0.15)'" variant="info" class="mr-md-2 mt-md-0 mt-25"
                                  v-b-modal.modal-level2-list-file>
                            <feather-icon icon="ListIcon" class="mr-50"/>
                            لیست تصاویر آپلودی
                        </b-button>

                        <b-button v-if="user.kyc_advanced.status === 'reject' || user.kyc_advanced.status === 'pending'"
                                  @click="confirm()" size="sm" v-ripple.400="'rgba(255, 255, 255, 0.15)'" variant="success" class="mr-md-2 mt-md-0 mt-25">
                            <feather-icon icon="CheckSquareIcon" class="mr-50"/>
                            تایید اطلاعات
                        </b-button>

                        <b-button v-if="user.kyc_advanced.status === 'confirm' || user.kyc_advanced.status === 'pending'"
                                  @click="reject()" size="sm" v-ripple.400="'rgba(255, 255, 255, 0.15)'" variant="danger" class="mr-md-2 mt-md-0 mt-25">
                            <feather-icon icon="CheckSquareIcon" class="mr-50"/>
                            رد کردن اطلاعات
                        </b-button>
                    </div>
                    <hr class="w-100">

                    <b-alert variant="warning" show v-if="user.kyc_advanced && user.kyc_advanced.status === 'pending'">
                        <div class="alert-body">
                            <span><strong>توجه!</strong> اطلاعات این بخش در درست بررسی قرار دارد و لطفا نسبت به رد یا تایید آن اقدام کنید.</span>
                        </div>
                    </b-alert>
                    <b-alert variant="danger" show v-else-if="user.kyc_advanced && user.kyc_advanced.status === 'reject'">
                        <div class="alert-body">
                        <span><strong>توجه!</strong>
                            اطلاعات این بخش به دلیل
                            "{{user.kyc_advanced.reason_reject}}"
                            تایید نشده است.</span>
                        </div>
                    </b-alert>
                    <b-alert variant="success" show v-else-if="user.kyc_advanced && user.kyc_advanced.status === 'confirm'">
                        <div class="alert-body">
                            <span><strong>توجه!</strong> این بخش قبلا تایید شده است.</span>
                        </div>
                    </b-alert>

                    <b-row>
                        <b-col md="6" class=" mx-auto">
                            <b-form-group label="آپلود فایل 1">
                                <validation-provider #default="{ errors }" rules="required">
                                    <b-form-file
                                        v-model="selectedFile1"
                                        :state="errors.length > 0 ? false : null"
                                        placeholder="فایل را انتخاب کنید یا اینجا رها کنید"
                                        drop-placeholder="فایل را اینجا رها کنید"
                                        accept="image/jpeg, image/png"
                                    />
                                </validation-provider>
                            </b-form-group>
                            <b-button v-if="user.kyc_advanced && user.kyc_advanced.file"
                                      size="sm" v-ripple.400="'rgba(255, 255, 255, 0.15)'" variant="primary" class="mr-md-1"
                                      v-b-modal.modal-advanced-file1>
                                <feather-icon icon="DownloadIcon" class="mr-50"/>
                                مشاهده تصویر 1
                            </b-button>
                        </b-col>
                        <b-col md="6" class=" mx-auto">
                            <b-form-group label="آپلود فایل 2">
                                <validation-provider #default="{ errors }" rules="required">
                                    <b-form-file
                                        v-model="selectedFile2"
                                        :state="errors.length > 0 ? false : null"
                                        placeholder="فایل را انتخاب کنید یا اینجا رها کنید"
                                        drop-placeholder="فایل را اینجا رها کنید"
                                        accept="image/jpeg, image/png"
                                    />
                                </validation-provider>
                            </b-form-group>
                            <b-button v-if="user.kyc_advanced && user.kyc_advanced.file"
                                      size="sm" v-ripple.400="'rgba(255, 255, 255, 0.15)'" variant="primary" class="mr-md-2"
                                      v-b-modal.modal-advanced-file1>
                                <feather-icon icon="DownloadIcon" class="mr-50"/>
                                مشاهده تصویر 2
                            </b-button>
                        </b-col>
                        <hr class="w-100">
                        <b-col md="6" class=" mx-auto">
                            <b-button block
                                      v-ripple.400="'rgba(255, 255, 255, 0.15)'"
                                      variant="primary"
                                      class="mr-2 d-flex align-items-center justify-content-center"
                                      type="submit"
                                      :disabled="isLoading"
                            >
                                <div>ثبت تغییرات</div>
                                <div class="line-height-0 ml-25"><b-spinner v-if="isLoading" small></b-spinner><span v-if="selectedFile2 > 0">{{selectedFile2}}</span></div>
                            </b-button>
                        </b-col>

                    </b-row>
                </b-col>

            </b-form>
        </validation-observer>

        <!-- basic modal -->
        <b-modal id="modal-advanced-file1" scrollable size="lg" title="تصویر 1" ok-only ok-title="باشه">
            <a :href="baseAdminURL+'image2/'+ user.kyc_advanced.file1_hash +'/image.jpg'" target="_blank">
                <img :src="baseAdminURL+'image2/'+ user.kyc_advanced.file1_hash +'/image.jpg'"  width="100%" class="box-shadow-1 border">
            </a>
        </b-modal>
        <!-- basic modal -->
        <b-modal id="modal-advanced-file2" scrollable size="lg" title="تصویر 2" ok-only ok-title="باشه">
            <a :href="baseAdminURL+'image2/'+ user.kyc_advanced.file2_hash +'/image.jpg'" target="_blank">
                <img :src="baseAdminURL+'image2/'+ user.kyc_advanced.file2_hash +'/image.jpg'"  width="100%" class="box-shadow-1 border">
            </a>
        </b-modal>

        <!-- basic modal -->
        <b-modal id="modal-level2-list-file" scrollable size="xl" title="لیست تصاویر آپلود شده" ok-only ok-title="باشه"
                 v-if="user.kyc_advanced && user.kyc_advanced.file">
            <div class="table-responsive mb-1">
                <table class="table b-table table-striped">
                    <thead>
                    <tr>
                        <td>زمان ثبت</td>
                        <td>تصویر 1</td>
                        <td>تصویر 2</td>
                        <td>وضعیت</td>
                        <td>تغییر توسط</td>
                        <td>زمان تغییر</td>
                        <td>توضیحات</td>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(item, indextr) in user.kyc_advanced.file">
                        <td class="text-nowrap vazir">{{convertDate(item.date)}}</td>
                        <td>
                            <a :href="baseAdminURL+'image2/'+ item.file1 +'/image.jpg'" target="_blank">
                                <img :src="baseAdminURL+'image2/'+ item.file1 +'/image.jpg'" width="50px">
                            </a>
                        </td>
                        <td>
                            <a :href="baseAdminURL+'image2/'+ item.file2 +'/image.jpg'" target="_blank">
                                <img :src="baseAdminURL+'image2/'+ item.file2 +'/image.jpg'" width="50px">
                            </a>
                        </td>
                        <td class="text-nowrap">
                            <b-badge pill v-if="item.status"
                                     :variant="`light-${item.status=='confirm'?'success':'danger'}`"
                                     class="text-capitalize">
                                {{ $t(item.status) }}
                            </b-badge>
                        </td>
                        <td class="text-nowrap">
                            {{item.admin?item.admin:'----'}}
                            <p v-if="item.admin && item.admin_email">
                                {{item.admin_email}}
                            </p>
                        </td>
                        <td class="text-nowrap vazir">{{item.date_admin? convertDate(item.date_admin) :'----'}} </td>
                        <td class="text-nowrap">{{item.upload_admin? 'درج تصویر توسط ادمین '+item.upload_admin :(item.reason?item.reason:'----')}}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </b-modal>
    </div>
</template>

<script>
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
    BFormFile,
    BTable,
    BButton,
    BMedia,
    BAvatar,
    BTab,
    BTabs,
    BForm,
    BFormSelect,
    BSpinner,
    BAlert,
    BFormRadio,
    VBTooltip,
    BFormInvalidFeedback
} from 'bootstrap-vue';
import Table from "@/views/vuexy/table/bs-table/Table";
import BCardActions from "@core/components/b-card-actions/BCardActions";
import {ValidationProvider, ValidationObserver} from 'vee-validate'
import {required, alphaNum, between,integer,min} from '@validations'
import Ripple from "vue-ripple-directive";
import axiosIns from "@/libs/axios";
import ToastificationContent from "@core/components/toastification/ToastificationContent";
import VuePersianDatetimePicker from 'vue-persian-datetime-picker'
import jalaliMmoment from "jalali-moment";





export default {
    props:['user'],
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
        BButton,BAlert,BFormRadio,
        BInputGroup,BFormGroup, BInputGroupAppend,BFormInput,BFormFile,
        BMedia, BAvatar,BTab,BTabs,BForm,BFormSelect,BSpinner,BFormInvalidFeedback,
        ValidationProvider,
        ValidationObserver,
        datePicker: VuePersianDatetimePicker,
    },
    directives: {
        Ripple,
        'b-tooltip': VBTooltip,
    },
    data() {
        return {
            selectedFile1: null,
            selectedFile2: null,
            emailVerified:null,
            isLoading:false,
            uploadPercentage:0,
            srcFile: null,
        }
    },
    methods:{
        onSubmit(){
            this.$refs.refFormLevel3Observer.validate().then(success => {
                if (success) {
                    this.isLoading = true;

                    const bodyFormData = new FormData()
                    if (this.selectedFile1) {
                        bodyFormData.append('file1', this.selectedFile1);
                    }
                    if (this.selectedFile2) {
                        bodyFormData.append('file2', this.selectedFile2);
                    }

                    axiosIns.post('/users/edit/kyc/advanced/'+this.user.id,bodyFormData,{
                        onUploadProgress: function( progressEvent ) {
                            this.uploadPercentage = parseInt( Math.round( ( progressEvent.loaded / progressEvent.total ) * 100 ));
                        }.bind(this)
                    })
                        .then(response => {
                            if(response.data.status == true){
                                this.$toast({
                                    component: ToastificationContent,
                                    props: {
                                        title: 'موفق!',
                                        text: response.data.msg,
                                        icon: 'CheckIcon',
                                        variant: 'success',
                                    },
                                })
                                this.$emit('getUser');
                                this.isLoading = false;
                            }else{
                                this.$toast({
                                    component: ToastificationContent,
                                    props: {
                                        title: 'خطا!',
                                        text: response.data.msg,
                                        icon: 'AlertTriangleIcon',
                                        variant: 'danger',
                                    },
                                })
                                this.isLoading = false;
                            }
                        })
                        .catch((error) => {
                            this.errorFetching();
                        })
                }
            })
        },
        confirm(){
            this.$swal({
                title: 'از تایید اطمینان دارید؟',
                text: 'با تایید این بخش برای کاربر اعلان تاییده ارسال میشود.',
                icon: 'question',
                showCancelButton: true,
                showLoaderOnConfirm: true,
                buttonsStyling: false,
                preConfirm: () => {
                    return  axiosIns.put('/users/edit/kyc/advanced/'+this.user.id +'/status',{status:'confirm'})
                        .then(response => {
                            return response;
                        })
                        .catch(() => {
                            this.errorFetching();
                        })
                },
                allowOutsideClick: () => false
            }).then((result) => {
                if (result.isConfirmed) {
                    if (result.value.data.status == true){
                        this.$emit('getUser');
                        this.getGeneralInfoApi();
                        this.$swal({icon: 'success',title: 'موفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                    }else{
                        this.$swal({icon: 'error',title: 'ناموفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                    }
                }
            })
        },
        reject(){
            this.$swal({
                title: 'از رد کردن اطمینان دارید؟',
                text: 'با رد کردن این بخش اعلانی برای کاربر ارسال میشود.',
                input:'textarea',
                inputPlaceholder: 'دلیلی جهت رد کردن و نمایش به کاربر با زبان '+ this.$i18n.t(this.user.settings.localization) +' درج کنید',
                icon: 'question',
                inputValidator: (value) => {
                    if (!value) {
                        return 'لطفا فیلدها را تکمیل نمایید!'
                    }
                },
                showCancelButton: true,
                showLoaderOnConfirm: true,
                buttonsStyling: false,
                preConfirm: (value) => {
                    return  axiosIns.put('/users/edit/kyc/advanced/'+this.user.id +'/status',{status:'reject',reason:value})
                        .then(response => {
                            return response;
                        })
                        .catch(() => {
                            this.errorFetching();
                        })
                },
                allowOutsideClick: () => false
            }).then((result) => {
                if (result.isConfirmed) {
                    if (result.value.data.status == true){
                        this.$emit('getUser');
                        this.getGeneralInfoApi();
                        this.$swal({icon: 'success',title: 'موفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                    }else{
                        this.$swal({icon: 'error',title: 'ناموفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                    }
                }
            })
        },
        convertDate(date){
            return jalaliMmoment(date, 'YYYY/MM/DD HH:mm:ss').format('jYYYY/jMM/jDD HH:mm:ss')
        },
        setDefault(){
            if(this.user.settings==null || this.user.settings.localization==null){
                this.user.settings = this.user.settings ? this.user.settings : [];
                this.user.settings.localization = 'fa'
            }

            if(this.user.kyc_advanced==null){
                this.user.kyc_advanced = this.user.kyc_advanced? this.user.kyc_advanced:[];
            }
        },
    },
    watch:{
        user(){
            this.setDefault();
        }
    },
    created() {
        this.setDefault();
    }
}
</script>

<style lang="scss">
</style>
