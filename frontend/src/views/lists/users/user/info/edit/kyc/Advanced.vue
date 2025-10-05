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
                        <b-col md="12" class=" mx-auto">
                            <file-pond
                                name="test"
                                ref="pond"
                                captureMethod="capture"
                                :label-idle="$t('Click to upload the file or drag and drop the file')"
                                accepted-file-types="image/jpeg, image/png"
                                v-bind:server="/*myServer*/null"
                                :files="user.kyc_advanced && user.kyc_advanced.file1 ? myFiles: null"
                                :processfile="null"
                            />
                        </b-col>
                        <b-col md="12" class=" mx-auto">
                            <file-pond
                                name="test"
                                ref="pond"
                                captureMethod="capture"
                                :label-idle="$t('Click to upload the file or drag and drop the file')"
                                accepted-file-types="image/jpeg, image/png"
                                v-bind:server="/*myServer*/null"
                                :files="user.kyc_advanced && user.kyc_advanced.file2 ? myFiles: null"
                                :processfile="null"
                            />
                        </b-col>
                        <b-col md="12">
                            <b-button v-if="user.kyc_advanced && user.kyc_advanced.file"
                                      size="sm" v-ripple.400="'rgba(255, 255, 255, 0.15)'" variant="primary" class="mr-md-2"
                                      v-b-modal.modal-level2-file>
                                <feather-icon icon="DownloadIcon" class="mr-50"/>
                                مشاهده تصویر
                            </b-button>

                            <b-button size="sm" v-ripple.400="'rgba(255, 255, 255, 0.15)'" variant="primary" class="mr-md-2 mt-md-0 mt-25"
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
                                <div class="line-height-0 ml-25"><b-spinner v-if="isLoading" small></b-spinner></div>
                            </b-button>
                        </b-col>
                    </b-row>
                </b-col>

            </b-form>
        </validation-observer>

        <!-- basic modal -->
        <b-modal id="modal-level2-file" scrollable size="lg" title="تصویر" ok-only ok-title="باشه">
            <a :href="baseAdminURL+'image/'+ user.kyc_advanced.file_hash +'/image.jpg'" target="_blank">
                <img :src="baseAdminURL+'image/'+ user.kyc_advanced.file_hash +'/image.jpg'"  width="100%" class="box-shadow-1 border">
            </a>
            <!--<img :src="srcFile" width="100%" class="box-shadow-1 border">-->
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
                            <a :href="baseAdminURL+'image/'+ item.url +'/image.jpg'" target="_blank">
                                <img :src="baseAdminURL+'image/'+ item.file1 +'/image.jpg'" width="50px">
                            </a>
                        </td>
                        <td>
                            <a :href="baseAdminURL+'image/'+ item.url +'/image.jpg'" target="_blank">
                                <img :src="baseAdminURL+'image/'+ item.file2 +'/image.jpg'" width="50px">
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
    VBTooltip
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


// Import Vue FilePond
import vueFilePond from 'vue-filepond'

// Import FilePond styles
import 'filepond/dist/filepond.min.css'
// Import image preview plugin styles
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css'

// Import image preview and file type validation plugins
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type'
import FilePondPluginImagePreview from 'filepond-plugin-image-preview'

// Create component
const FilePond = vueFilePond(
    FilePondPluginFileValidateType,
    FilePondPluginImagePreview,
)


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
        BInputGroup,BFormGroup, BInputGroupAppend,BFormInput,
        BMedia, BAvatar,BTab,BTabs,BForm,BFormSelect,BSpinner,
        ValidationProvider,
        ValidationObserver,
        datePicker: VuePersianDatetimePicker,
        FilePond
    },
    directives: {
        Ripple,
        'b-tooltip': VBTooltip,
    },
    data() {
        return {
            emailVerified:null,
            isLoading:false,
            myFiles: [
                {
                    source:
                        "users/edit/kyc/advanced/"+ this.user.id +"/file",
                    options: {
                        type: "local"
                    }
                }
            ],
            myServer: {
                process: null,
                load: (source, load) => {
                    axiosIns.get(source, {responseType: 'blob' })
                        .then((response) => {
                            const new_blob = new Blob( [ response.data ], { type: 'image/jpg' } );
                            load(new_blob)
                        })
                }
            },
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
                    if(this.$refs.pond.getFiles()[0] && this.$refs.pond.getFiles()[0].file && this.$refs.pond.getFiles()[0].file.name !=='file')
                        bodyFormData.append('file', this.$refs.pond.getFiles()[0].file);

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
#level2-form{
    .filepond--image-preview-wrapper div{
        direction: rtl;

    }

    .filepond--file .filepond--file-status{
        margin-right: auto;
        margin-left: 2.25em !important;
    }

    .filepond--file-info,.filepond--file-status{
        font-size: 18px !important;
    }
    .filepond--credits{
        display: none;
    }

    .filepond--file-action-button,.filepond--drop-label, .filepond--drop-label label{
        cursor: pointer;
    }

    /* the text color of the drop label*/
    .filepond--drop-label {
        color: #555;
        font-family: iranyekan,"Montserrat", Helvetica, Arial, sans-serif;
    }

    /* underline color for "Browse" button */
    .filepond--label-action {
        text-decoration-color: #aaa;
    }

    /* the background color of the filepond drop area */
    .filepond--panel-root {
        background-color: #eee;
    }

    /* the border radius of the drop area */
    .filepond--panel-root {
        border-radius: 0.5em;
    }

    /* the border radius of the file item */
    .filepond--item-panel {
        border-radius: 0.5em;
    }

    /* the background color of the file and file panel (used when dropping an image) */
    .filepond--item-panel {
        background-color: #555;
    }

    /* the background color of the drop circle */
    .filepond--drip-blob {
        background-color: #999;
    }

    /* the background color of the black action buttons */
    .filepond--file-action-button {
        background-color: rgba(0, 0, 0, 0.5);
    }

    /* the icon color of the black action buttons */
    .filepond--file-action-button {
        color: white;
    }

    /* the color of the focus ring */
    .filepond--file-action-button:hover,
    .filepond--file-action-button:focus {
        box-shadow: 0 0 0 0.125em rgba(255, 255, 255, 0.9);
    }

    /* the text color of the file status and info labels */
    .filepond--file {
        color: white;
    }

    /* error state color */
    [data-filepond-item-state*='error'] .filepond--item-panel,
    [data-filepond-item-state*='invalid'] .filepond--item-panel {
        //background-color: red;
    }

    [data-filepond-item-state='processing-complete'] .filepond--item-panel {
        background-color: green;
    }

    /* bordered drop area */
    .filepond--panel-root {
        background-color: transparent;
        border: 2px solid #2c3340;
        .dark-layout & {
            border: 2px solid white;
        }
    }


    .filepond--file-status-sub,.filepond--file-info-sub{
        opacity: 0.8 !important;
    }

    .success .filepond--panel-root{
        border-color: rgba(var(--vs-success), 1) !important;
    }
    .warning .filepond--panel-root{
        border-color: rgba(var(--vs-warning), 1) !important;
    }
}
</style>
