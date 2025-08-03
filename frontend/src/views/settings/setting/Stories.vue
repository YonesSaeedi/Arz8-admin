<template>
    <div v-if="formData" id="st-stories">
        <validation-observer
            v-if="accessUserLogin['setting']['stories'] || activeUserInfo.role === 'admin'"
            #default="{ handleSubmit }"
            ref="refFormGeneralObserver"
        >
            <!-- Form -->
            <b-form
                class="px-2"
                @submit.prevent="handleSubmit(onSubmit)"
                autocomplete="off"
            >

                <b-card title="تنظیمات استوری">
                    <div class="d-flex">
                        <div class="demo-inline-spacing">
                            <b-form-radio
                                v-model="formData.stories.status"
                                :name="'status'"
                                value="false"
                                class="custom-control-secondary"
                            >
                                غیر فعال
                            </b-form-radio>
                            <b-form-radio
                                v-model="formData.stories.status"
                                :name="'status'"
                                value="true"
                                class="custom-control-primary"
                            >
                                فعال
                            </b-form-radio>
                        </div>
                    </div>
                    <hr class="w-100">
                    <b-row class="mb-2" v-for="(list,key) in formData.stories.list" v-if="renderComponent">
                        <b-col cols="12">
                            <validation-provider #default="{ errors }" :rules="formData.stories.list[key].imgUrl?'':'required'">
                                <b-form-group label="آپلود فایل بننر">
                                    <!-- نمایش نام فایل انتخابی -->
                                    <div v-if="formData.stories.list[key].imgUrl">
                                        <p>نام فایل: {{ formData.stories.list[key].imgUrl }}</p>
                                    </div>

                                    <b-form-file v-else
                                        :ref="'imgUrl'+key"
                                        v-model="imgUrlModel[key]"
                                        :state="Boolean(imgUrlModel[key])"
                                        placeholder="Choose a file..."
                                        drop-placeholder="Drop a file here..."
                                        :disabled="formData.stories.list[key].imgUrl"
                                    />
                                    <small class="text-danger" v-if="errors.length > 0">فایل استوری درج شود</small>


                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="5" class="mt-1">
                            <validation-provider #default="{ errors }" rules="url">
                                <b-form-group label="لینک شود به؟ (اختیاری)">
                                    <b-form-input
                                        type="url"
                                        v-model="formData.stories.list[key].link"
                                        :state="errors.length > 0 ? false:null"
                                        placeholder="Url"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <b-col cols="7" class="mt-1">
                            <b-row>
                                <b-col cols="5">
                                    <validation-provider #default="{ errors }" rules="required">
                                        <b-form-group label="شروع از تاریخ">
                                            <b-input-group>
                                                <b-form-input
                                                    placeholder="از تاریخ"
                                                    v-model="formData.stories.list[key].started_at"
                                                    :id="'StartedAt'+key" :name="'StartedAt'+key"
                                                />
                                                <b-input-group-append is-text>
                                                    <feather-icon icon="CalendarIcon" class="cursor-pointer" @click="showDatePickerStart[key]=true"/>
                                                    <date-picker v-model="formData.stories.list[key].started_at" :show="showDatePickerStart[key]" @close="showDatePickerStart[key]=false" :auto-submit="true" color="#7367f0"  type="datetime" :editable="true"
                                                                 :locale="localeHas" :locale-config="localeConfig" :format="(localeHas=='fa')?'jYYYY/jMM/jDD HH:mm' : 'YYYY-MM-DD HH:mm'" :element="'StartedAt'+key">
                                                    </date-picker>
                                                </b-input-group-append>
                                            </b-input-group>
                                        </b-form-group>
                                    </validation-provider>
                                </b-col>
                                <b-col cols="5">
                                    <validation-provider #default="{ errors }" rules="required">
                                        <b-form-group label="پایان تا تاریخ">
                                            <b-input-group>
                                                <b-form-input
                                                    placeholder="تا تاریخ"
                                                    v-model="formData.stories.list[key].expired_at"
                                                    :id="'ExpiredAt'+key" :name="'ExpiredAt'+key"
                                                />
                                                <b-input-group-append is-text>
                                                    <feather-icon icon="CalendarIcon" class="cursor-pointer" @click="showDatePickerStop[key]=true"/>
                                                    <date-picker v-model="formData.stories.list[key].expired_at" :show="showDatePickerStop[key]" @close="showDatePickerStop[key]=false" :auto-submit="true" color="#7367f0"  type="datetime" :editable="true"
                                                                 :locale="localeHas" :locale-config="localeConfig" :format="(localeHas=='fa')?'jYYYY/jMM/jDD HH:mm' : 'YYYY-MM-DD HH:mm'" :element="'ExpiredAt'+key">
                                                    </date-picker>
                                                </b-input-group-append>
                                            </b-input-group>
                                        </b-form-group>
                                    </validation-provider>
                                </b-col>
                                <b-col cols="2" class="mt-1">
                                    <feather-icon @click="removeBanner(item,key)" icon="XIcon" size="25" class="mt-2 text-danger cursor-pointer"/>
                                </b-col>
                            </b-row>
                        </b-col>
                    </b-row>
                    <b-button class="mt-3" type="button" variant="outline-success" @click="addBanner(item)">افزودن استوری</b-button>
                </b-card>



                <!-- Form Actions -->
                <b-progress :value="uploadPercentage" v-if="uploadPercentage > 0" :max="100" show-progress animated></b-progress>
                <div class="my-2 w-50 mx-auto">
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
                </div>

            </b-form>
        </validation-observer>
        <div v-else>
            <NotAccessed/>
        </div>
    </div>
</template>

<script>
import {
    BCard,
    BForm,
    BFormGroup,
    BFormInput,
    BFormSelect,
    BFormInvalidFeedback,
    BButton,
    BFormFile,
    BSpinner,
    BRow,
    BCol,
    BFormRadio,
    BTabs,
    BTab,
    BProgress,
    BInputGroupAppend, BInputGroup
} from 'bootstrap-vue'
import {ValidationProvider, ValidationObserver} from 'vee-validate'
import {ref} from '@vue/composition-api'
import {required, integer, between,url} from '@validations'
import Ripple from 'vue-ripple-directive'
import {MODEL_EVENT_NAME} from "bootstrap-vue/src/mixins/form-radio-check";
import NotAccessed from "@/views/vuexy/pages/miscellaneous/NotAccessed";
import vSelect from 'vue-select'
import VuePersianDatetimePicker from 'vue-persian-datetime-picker'
import moment from 'jalali-moment';

// Import Vue FilePond
import vueFilePond from 'vue-filepond'

// Import FilePond styles
import 'filepond/dist/filepond.min.css'
// Import image preview plugin styles
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css'

// Import image preview and file type validation plugins
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type'
import FilePondPluginImagePreview from 'filepond-plugin-image-preview'
import axiosIns from "@/libs/axios";
import ToastificationContent from "@core/components/toastification/ToastificationContent";
import {codeVueMultipleSize} from "@/views/vuexy/forms/form-element/vue-select/code";

// Create component
const FilePond = vueFilePond(
    FilePondPluginFileValidateType,
    FilePondPluginImagePreview,
)

export default {
    props:['settings'],
    components: {
        datePicker: VuePersianDatetimePicker,
        BInputGroup, BInputGroupAppend,
        BCard,
        BForm,
        BFormGroup,
        BFormInput,
        BFormFile,
        BFormInvalidFeedback,
        BButton,
        BFormSelect,
        BFormFile,
        BSpinner,
        BRow,BCol, BFormRadio,
        BTabs,BTab,
        NotAccessed,
        FilePond,
        vSelect,
        BProgress,
        // Form Validation
        ValidationProvider,
        ValidationObserver,
    },
    directives: {
        Ripple,
    },
    data() {
        return {
            showDatePickerStart: {},
            showDatePickerStop: {},
            localeConfig:{
                fa: {
                    dow: 6,     //  first day of week
                    dir: 'rtl',
                    displayFormat: 'jYYYY/jMM/jDD'
                },
                en: {
                    dow: 0,
                    dir: 'ltr',
                    displayFormat: 'YYYY-MM-DD'
                }
            },
            renderComponent: true,
            isLoading: false,
            formData:{
                stories: null,
            },
            imgUrlModel:[],
            optionLang: [{ title: 'Square' }, { title: 'Rectangle' }, { title: 'Rombo' }, { title: 'Romboid' }],
            uploadPercentage:0,

        }
    },
    watch:{
    },
    methods:{
        removeBanner(item,key){
            this.renderComponent = false;
            this.$nextTick(() => {
                this.formData.stories.list.splice(key, 1)
                this.renderComponent = true;
            });
        },
        onSubmit(){
            this.$refs.refFormGeneralObserver.validate().then(success => {
                if (success) {
                    this.isLoading = true;
                    const bodyFormData = new FormData()
                    for (let i = 0; i < this.formData.stories.list.length; i++) {
                        if (this.$refs['imgUrl' + i]) {
                            // دسترسی به فایل‌ها از طریق $refs و ویژگی files
                            const fileInput = this.$refs['imgUrl' + i];

                            // بررسی اینکه فایل‌ها موجود باشند
                            if (fileInput[0].files && fileInput[0].files.length > 0) {
                                const file = fileInput[0].files[0];  // دسترسی به اولین فایل انتخاب‌شده

                                // بررسی اینکه فایل معتبر است و مقدار imgUrl قبلاً مشخص نشده باشد
                                if (file && file.name !== 'file' && this.formData.stories.list[i].imgUrl === null) {
                                    bodyFormData.append('imgUrl' + i, file);  // اضافه کردن فایل به FormData به صورت باینری
                                }
                            }
                        } else {
                            // اگر فایلی انتخاب نشده باشد، از imgUrl قبلی استفاده می‌شود
                            bodyFormData.append('imgUrl' + i, this.formData.stories.list[i].imgUrl);
                        }
                    }

                    bodyFormData.append('stories', JSON.stringify(this.formData.stories));
                    axiosIns.post('/setting/settings/stories',bodyFormData,{
                        onUploadProgress: function( progressEvent ) {
                            this.uploadPercentage = parseInt( Math.round( ( progressEvent.loaded / progressEvent.total ) * 100 ));
                        }.bind(this)
                    })
                        .then(response => {
                            if(response.data.status == true){
                                this.$emit('getSettings')
                                this.$toast({
                                    component: ToastificationContent,
                                    props: {
                                        title: 'موفق!',
                                        text: response.data.msg,
                                        icon: 'CheckIcon',
                                        variant: 'success',
                                    },
                                })
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
                            this.uploadPercentage = 0;
                        })
                        .catch((error) => {
                            this.errorFetching();
                            this.isLoading = false;
                        })
                }
            })
        },
        addBanner(){
            this.formData.stories.list.push({"imgUrl":null,"link":"","started_at":null,"expired_at":null});
            this.formData.stories.list.forEach((item, index) => {
                this.$set(this.showDatePickerStart, index, false);
                this.$set(this.showDatePickerStop, index, false);
            });
        }
    },
    created() {
        this.formData['stories'] = this.settings['stories'];

        this.formData.stories.list.forEach((item, index) => {
            // مقدار اولیه `showDatePickerStart` برای مدیریت حالت نمایش
            this.$set(this.showDatePickerStart, index, false);
            this.$set(this.showDatePickerStop, index, false);

            // تبدیل تاریخ میلادی به شمسی
            if (item.started_at) {
                const jalaliDateStart = moment(item.started_at, 'YYYY-MM-DDTHH:mm:ss')
                    .locale('fa')
                    .format('jYYYY/jMM/jDD HH:mm');
                this.$set(this.formData.stories.list[index], 'started_at', jalaliDateStart);

                const jalaliDateExpire = moment(item.expired_at, 'YYYY-MM-DDTHH:mm:ss')
                    .locale('fa')
                    .format('jYYYY/jMM/jDD HH:mm');
                this.$set(this.formData.stories.list[index], 'expired_at', jalaliDateExpire);
            }
        });
    }
}
</script>

<style lang="scss">
#st-banner {
    .v-select.error div{
        border-color: red !important;
    }
    .filepond--root{
        margin-bottom: 0px;
    }
    .filepond--image-preview-wrapper div {
        direction: rtl;

    }

    .filepond--file .filepond--file-status {
        margin-right: auto;
        margin-left: 2.25em !important;
    }

    .filepond--file-info, .filepond--file-status {
        font-size: 18px !important;
    }

    .filepond--credits {
        display: none;
    }

    .filepond--file-action-button, .filepond--drop-label, .filepond--drop-label label {
        cursor: pointer;
    }

    /* the text color of the drop label*/
    .filepond--drop-label {
        color: #555;
        font-family: iranyekan, "Montserrat", Helvetica, Arial, sans-serif;
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
        background-color: red;
    }

    [data-filepond-item-state='processing-complete'] .filepond--item-panel {
        background-color: green;
    }

    /* bordered drop area */
    .filepond--panel-root {
        background-color: transparent;
        border: 1px solid #2c3340;
    }

    .filepond--file-status-sub, .filepond--file-info-sub {
        opacity: 0.8 !important;
    }

    .success .filepond--panel-root {
        border-color: rgba(var(--vs-success), 1) !important;
    }

    .warning .filepond--panel-root {
        border-color: rgba(var(--vs-warning), 1) !important;
    }
}
</style>
