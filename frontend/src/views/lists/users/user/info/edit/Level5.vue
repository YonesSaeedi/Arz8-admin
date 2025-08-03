<template>
    <div id="level3-form">
        <validation-observer
            #default="{ handleSubmit }"
            ref="refFormLevel5Observer"
        >
            <b-form
                class="px-md-2"
                @submit.prevent="handleSubmit(onSubmit)"
                autocomplete="off"
            >
            <b-col md="8" class="mx-auto">
                <h5 class="mt-2 text-center">برای سطح پنج نیاز به عقد قرارداد و نگهداری فیزیکی قرارداد میباشد. و بعد از دریافت قرارداد میتوانید سطح کاربر را به صورت دستی بر روی 5 بگذارید.</h5>
                <h5 class="mt-2 text-center">سطح شش یا سطح vip فقط برای کاربرانی که این سطح را داشته باشند قابل مشاهده است و میتوانید کاربر را بصورت دستی روی سطح 6 قرار دهید.</h5>
            </b-col>

        </b-form>
        </validation-observer>

    </div>
</template>

<script>
    import {
        BCard, BCardHeader, BBadge, BCollapse, BLink, BCardBody, BRow, BCol, BInputGroup, BInputGroupAppend, BFormGroup, BFormInput, BTable, BButton,
        BMedia, BAvatar,BTab,BTabs,BForm,BFormSelect,BSpinner,BAlert,BFormRadio
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
        },
        data() {
            return {
                emailVerified:null,
                isLoading:false,
                address:null,
                myFiles: [
                    {
                        source:
                            "users/edit/level5/"+ this.user.id +"/file",
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
                this.$refs.refFormLevel5Observer.validate().then(success => {
                    if (success) {
                        this.isLoading = true;

                        const bodyFormData = new FormData()
                        bodyFormData.append('address', this.address)
                        bodyFormData.append('phone',  this.user.phone)

                        if(this.$refs.pond.getFiles()[0] && this.$refs.pond.getFiles()[0].file && this.$refs.pond.getFiles()[0].file.name !=='file')
                            bodyFormData.append('file', this.$refs.pond.getFiles()[0].file);

                        axiosIns.post('/users/edit/level5/'+this.user.id,bodyFormData,{
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
                    title: 'از تایید برداشت اطمینان دارید؟',
                    text: 'با تایید این بخش برای کاربر اعلان تاییده ارسال میشود.',
                    icon: 'question',
                    showCancelButton: true,
                    showLoaderOnConfirm: true,
                    buttonsStyling: false,
                    preConfirm: () => {
                        return  axiosIns.put('/users/edit/level5/'+this.user.id +'/status',{status:'confirm'})
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
                        return  axiosIns.put('/users/edit/level5/'+this.user.id +'/status',{status:'reject',reason:value})
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

                if(this.user.address && this.user.address.file_hash)
                    this.getFile(this.user.address.file_hash,'srcFile')
                if(this.user.address && this.user.address.address)
                    this.address = this.user.address.address;
            }
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
    #level3-form{
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
            border: 1px solid #2c3340;
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
