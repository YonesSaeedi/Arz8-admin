<template>
    <div id="level1-form">
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
                        اطلاعات سطح یک در این بخش قابل ثبت و ویرایش میباشد! اطلاعات این سطح بصورت اتوماتیک تایید میشود.
                    </p>

                    <b-row>
                        <b-col md="6">
                            <validation-provider #default="{ errors }" rules="integer|min:11">
                                <b-form-group label="موبایل">
                                    <b-input-group class="input-group-merge">
                                        <b-form-input dir="ltr" class="text-center"
                                                      v-model="user.mobile"
                                                      :state="errors.length > 0 ? false:null"
                                                      placeholder="موبایل" maxlength="11"
                                        />
                                        <b-input-group-append is-text>
                                            <div @click="inquiryMobile()" v-b-tooltip.hover title="استعلام موبایل با کد ملی">
                                                <feather-icon icon="InfoIcon" class="cursor-pointer" />
                                            </div>
                                        </b-input-group-append>
                                    </b-input-group>
                                </b-form-group>
                            </validation-provider>
                        </b-col>

                        <b-col md="6">
                            <validation-provider #default="{ errors }" rules="required|integer|min:10">
                                <b-form-group :label="'کد ملی'">
                                    <b-form-input class="text-center" dir="ltr"
                                                  v-model="user.national_code"
                                                  :state="errors.length > 0 ? false:null"
                                                  :placeholder="'کد ملی'" maxlength="10"
                                    />
                                    <small v-if="user.info.birthplace" class="text-primary">
                                        <feather-icon icon="MapPinIcon"/>
                                        استان: {{user.info.birthplace.province}} | شهر: {{user.info.birthplace.city}}
                                    </small>

                                </b-form-group>
                            </validation-provider>
                        </b-col>

                        <hr class="w-100">

                        <b-col md="6">
                            <b-form-group label="نام">
                                <b-form-input class="text-center"
                                              v-model="user.name"
                                              placeholder="نام"
                                />
                            </b-form-group>
                        </b-col>
                        <b-col md="6">
                            <b-form-group label="فامیل">
                                <b-form-input class="text-center"
                                              v-model="user.family"
                                              placeholder="فامیل"
                                />
                            </b-form-group>
                        </b-col>
                        <b-col md="6">
                            <b-form-group label="نام پدر">
                                <b-form-input class="text-center"
                                              v-model="user.father"
                                              placeholder="نام پدر"
                                />
                            </b-form-group>
                        </b-col>
                        <b-col md="6">
                            <b-form-group label="تاریخ تولد">
                                <b-input-group>
                                    <b-form-input
                                        placeholder="تاریخ تولد"
                                        v-model="user.date_birth" class="vazir text-center"
                                        id="registeryDateStop" name="registeryDateStop"
                                    />
                                    <b-input-group-append is-text>
                                        <feather-icon icon="CalendarIcon" class="cursor-pointer" @click="showDatePickerStop=true"/>
                                        <date-picker v-model="user.date_birth" :show="showDatePickerStop" @close="showDatePickerStop=false" :auto-submit="true" color="#7367f0" view="year" :editable="true"
                                                     :locale="localeHas" :locale-config="localeConfig" :format="(localeHas=='fa')?'jYYYY/jMM/jDD' : 'YYYY-MM-DD'" element="registeryDateStop">
                                        </date-picker>
                                    </b-input-group-append>
                                </b-input-group>
                            </b-form-group>
                        </b-col>
                        <b-col md="6" >
                            <validation-provider #default="{ errors }" rules="email">
                                <b-form-group label="ایمیل">
                                    <b-input-group class="input-group-merge">
                                        <b-form-input dir="ltr" class="text-center"
                                                      v-model="user.email"
                                                      :state="errors.length > 0 ? false:null"
                                                      placeholder="Email"
                                        />
                                    </b-input-group>
                                </b-form-group>
                            </validation-provider>
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
    },
    directives: {
        Ripple,
        'b-tooltip': VBTooltip,
    },
    data() {
        return {
            emailVerified:null,
            isLoading:false,
            showDatePickerStart: false,
            showDatePickerStop: false,
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
                    bodyFormData.append('name',  this.user.name?.trim() || null)
                    bodyFormData.append('family',  this.user.family?.trim() || null)
                    bodyFormData.append('nationalCode', this.user.national_code?.trim() || null)
                    bodyFormData.append('dateBirth', this.user.date_birth)
                    bodyFormData.append('mobile', this.user.mobile?.trim() || null)
                    bodyFormData.append('father', this.user.father?.trim() || null)

                    axiosIns.post('/users/edit/kyc/basic/'+this.user.id,bodyFormData,{
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
        convertDate(date){
            return jalaliMmoment(date, 'YYYY/MM/DD HH:mm:ss').format('jYYYY/jMM/jDD HH:mm:ss')
        },
        setDefault(){
            if(this.user.settings==null || this.user.settings.localization==null){
                this.user.settings = this.user.settings ? this.user.settings : [];
                this.user.settings.localization = 'fa'
            }
        },
        inquiryMobile(){
            this.$swal({
                title: 'استعلام دریافت شود؟',
                text: 'وضعیت تطابق کدملی با کد ملی صاحب شماره موبایل بررسی میشود',
                icon: 'question',
                showCancelButton: true,
                showLoaderOnConfirm: true,
                buttonsStyling: false,
                preConfirm: () => {
                    return  axiosIns.post('/users/edit/level1/'+this.user.id +'/inquiry')
                        .then(response => {
                            return response;
                        }).catch(() => {this.errorFetching();})
                },
                allowOutsideClick: () => false
            }).then((result) => {
                if (result.isConfirmed) {
                    if (result.value.data.status == true){
                        this.$swal({icon: 'success',title: 'تطابق دارد!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                    }else{
                        this.$swal({icon: 'warning',title: 'ناموفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                    }
                }
            })
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
#level1-form{

}
</style>
