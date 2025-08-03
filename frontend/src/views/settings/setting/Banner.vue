<template>
    <div v-if="formData" id="st-banner">
        <validation-observer
            v-if="accessUserLogin['setting']['banner'] || activeUserInfo.role === 'admin'"
            #default="{ handleSubmit }"
            ref="refFormGeneralObserver"
        >
            <!-- Form -->
            <b-form
                class="px-2"
                @submit.prevent="handleSubmit(onSubmit)"
                autocomplete="off"
            >

                <b-card title="تنظیمات بننر">
                    <p>
                        <strong>نکته 1:</strong>
                        بنر های اپلیکشین های موبایل با بنر های سایت متفاوت است.
                    </p>

                    <b-tabs justified v-model="activeTab">
                        <b-tab :title="item" active v-for="(item,key) in platform" :active="key===0">
                            <div class="d-flex">
                                <div class="demo-inline-spacing">
                                    <b-form-radio
                                        v-model="formData.banner[item].status"
                                        :name="'status'+item"
                                        value="false"
                                        class="custom-control-secondary"
                                    >
                                        غیر فعال
                                    </b-form-radio>
                                    <b-form-radio
                                        v-model="formData.banner[item].status"
                                        :name="'status'+item"
                                        value="true"
                                        class="custom-control-primary"
                                    >
                                        فعال
                                    </b-form-radio>
                                </div>
                                <div class="d-flex align-items-center ml-auto">
                                    <label>نمایش بنر در هر صفحه:</label>
                                    <b-form-select  size="sm"
                                                    v-model="formData.banner[item].perPage"
                                                    :options="perPageOptions"
                                    />
                                </div>
                            </div>
                            <hr class="w-100">
                            <b-row class="mb-2" v-for="(banner,key) in formData.banner[item].banner" v-if="renderComponent">
                                <b-col cols="12">
                                    <validation-provider #default="{ errors }" rules="required">
                                        <b-form-group label="آپلود عکس بننر">
                                            <file-pond dir="ltr"
                                                       v-model="imgUrlModel[key]"
                                                       :ref="'imgUrl'+key+item"
                                                       captureMethod="capture"
                                                       :label-idle="$t('Click to upload the file or drag and drop the file')"
                                                       accepted-file-types="image/jpeg, image/png"
                                                       :processfile="null"
                                                       @removefile="formData.banner[item].banner[key].imgUrl=null"
                                                       v-bind:server="myServer"
                                                       :files="formData.banner[item].banner[key].imgUrl ?[{source: formData.banner[item].banner[key].imgUrl,options: {type: 'local'}}]:null"
                                            />
                                            <small class="text-danger" v-if="errors.length > 0">تصویر بننر درج شود</small>
                                        </b-form-group>
                                    </validation-provider>
                                </b-col>
                                <b-col cols="4" class="mt-1">
                                    <validation-provider #default="{ errors }" rules="url">
                                        <b-form-group label="لینک شود به؟ (اختیاری)">
                                            <b-form-input
                                                type="url"
                                                v-model="formData.banner[item].banner[key].link"
                                                :state="errors.length > 0 ? false:null"
                                                placeholder="Url"
                                            />
                                        </b-form-group>
                                    </validation-provider>
                                </b-col>
                                <b-col cols="2" class="mt-1">
                                    <validation-provider #default="{ errors }" rules="required|integer">
                                        <b-form-group label="اولویت؟">
                                            <b-form-input
                                                v-model="formData.banner[item].banner[key].sort"
                                                :state="errors.length > 0 ? false:null"
                                                placeholder="Sort"
                                            />
                                        </b-form-group>
                                    </validation-provider>
                                </b-col>
                                <b-col cols="5" class="mt-1">
                                    <validation-provider #default="{ errors }" rules="required">
                                        <b-form-group label="برای چه زبان هایی نمایش داده شود؟">
                                            <v-select
                                                v-model="formData.banner[item].banner[key].locale"
                                                :dir="$store.state.appConfig.isRTL ? 'rtl' : 'ltr'"
                                                label="title"
                                                :options="optionLang"
                                                multiple
                                                :class="errors.length > 0 ? 'error':null"
                                            />
                                        </b-form-group>
                                    </validation-provider>
                                </b-col>
                                <b-col cols="1" class="mt-2">
                                    <feather-icon @click="removeBanner(item,key)" icon="XIcon" size="25" class="mt-2 text-danger cursor-pointer"/>
                                </b-col>
                            </b-row>
                            <b-button class="mt-3" type="button" variant="outline-success" @click="addBanner(item)">افزودن بننر</b-button>
                        </b-tab>
                    </b-tabs>


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
    BCard, BForm, BFormGroup, BFormInput,BFormSelect, BFormInvalidFeedback, BButton, BFormFile, BSpinner,BRow,BCol,BFormRadio,
    BTabs,BTab,BProgress
} from 'bootstrap-vue'
import {ValidationProvider, ValidationObserver} from 'vee-validate'
import {ref} from '@vue/composition-api'
import {required, integer, between,url} from '@validations'
import Ripple from 'vue-ripple-directive'
import {MODEL_EVENT_NAME} from "bootstrap-vue/src/mixins/form-radio-check";
import NotAccessed from "@/views/vuexy/pages/miscellaneous/NotAccessed";
import vSelect from 'vue-select'

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
            activeTab:0,
            renderComponent: true,
            isLoading: false,
            formData:{
                banner: null,
            },
            platform:['website','android','ios'],
            perPageOptions:[
                {text:'یک عدد',value:'1'},
                {text:'دو عدد',value:'2'},
                {text:'سه عدد',value:'3'},
            ],
            imgUrlModel:[],
            optionLang: [{ title: 'Square' }, { title: 'Rectangle' }, { title: 'Rombo' }, { title: 'Romboid' }],
            uploadPercentage:0,
            myServer: {
                load: (source, load) => {
                    axiosIns.get('settings/banner/image/'+source,{responseType: 'blob' })
                        .then((response) => {
                            const new_blob = new Blob( [ response.data ], { type: 'image/jpg' } );
                            load(new_blob)
                        })
                }
            },
        }
    },
    methods:{
        removeBanner(item,key){
            this.renderComponent = false;
            this.$nextTick(() => {
                this.formData.banner[item].banner.splice(key, 1)
                this.renderComponent = true;
            });
        },
        onSubmit(){
            this.$refs.refFormGeneralObserver.validate().then(success => {
                if (success) {
                    this.isLoading = true;
                    const bodyFormData = new FormData()
                    this.platform.map((item)=>{
                        for (let i = 0; i < this.formData.banner[item].banner.length; i++) {
                            var file = this.$refs['imgUrl'+i+''+item][0].getFiles();
                            if(file[0] && file[0].file && file[0].file.name !=='file' && this.formData.banner[item].banner[i].imgUrl === null)
                                bodyFormData.append('imgUrl'+i+item, file[0].file);
                        }
                    })
                    bodyFormData.append('banner', JSON.stringify(this.formData.banner));
                    axiosIns.post('/setting/settings/banner',bodyFormData,{
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
        addBanner(item){
            (this.formData.banner[item].banner).push({"imgUrl":null,"locale":this.optionLang,"link":""});
        }
    },
    created() {
        var optionLang = this.getGeneralInfo.locales.map((item)=>{  return {'title':item.name,'value':item.symbol};});
        this.optionLang = optionLang;

        Object.keys(this.formData).map(function(key, index) {
            this.formData[key] = this.settings[key];
        },this)

        Object.keys(this.formData.banner).map(item=>{
            Object.keys(this.formData.banner[item].banner).map(key=>{
                this.formData.banner[item].banner[key].locale.map((locle,k)=>{
                    this.formData.banner[item].banner[key].locale[k] = this.optionLang.find(x=>x.value === locle);
                })
            })
        })

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
