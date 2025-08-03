<template>
    <div>
        <b-modal
            v-model="modalStatus"
            id="card-modal" ref="card-modal"
            title="جزئیات اطلاعیه"
            ok-title="ذخیره تغییرات"
            cancel-title="بستن"
            cancel-variant="outline-secondary"
            @ok="handleOk"
            v-if="!isLoadingRemove"
        >

            <b-card-text>
                <div class="text-center my-2" v-if="!notification">
                    <b-spinner style="width: 3rem; height: 3rem;"/>
                </div>

                <validation-observer ref="simpleRules" v-else>
                    <b-form ref="form" @submit.stop.prevent="handleSubmit">
                        <b-row>
                            <b-col cols="12">
                                <b-form-group label="عنوان" label-cols-md="3">
                                    <validation-provider #default="{ errors }" rules="required">
                                        <b-input-group class="input-group-merge">
                                            <v-select
                                                disabled
                                                :dir="!$store.state.appConfig.isRTL ? 'rtl' : 'ltr'"
                                                :options="optionsTitle"
                                                v-model="notification.title"
                                                :reduce="val => val.value"
                                                class="w-100"
                                                placeholder="عنوان را انتخاب کنید"
                                                :state="errors.length > 0 ? false:null"
                                            />
                                            <small>عنوان هایی را میتوانید انتخاب کنید که معادل ترجمه آن در اپلیکشن ها و وب سایت وجود داشته باشد.</small>
                                        </b-input-group>
                                    </validation-provider>
                                </b-form-group>
                            </b-col>

                            <b-col cols="12">
                                <b-form-group label="پیغام" label-cols-md="3">
                                    <b-tabs>
                                        <b-tab :title="item.name" :active="item.symbol ==='fa'" v-for="(item,key) in getGeneralInfo.locales">
                                            <validation-provider #default="{ errors }" rules="required">
                                                <b-form-textarea
                                                    :disabled="notification.keyword !== 'message'"
                                                    v-model="notification.message[item.symbol]"
                                                    :state="errors.length > 0 ? false:null"
                                                    trim rows="4"
                                                    :placeholder="'درج پیغام به زبان '+item.name"
                                                />
                                            </validation-provider>
                                        </b-tab>
                                    </b-tabs>
                                </b-form-group>
                            </b-col>

                            <b-col cols="12">
                                <b-form-group label="کاربر" label-cols-md="3">
                                    <b-form-input
                                        readonly
                                        :value="notification.id_user?notification.user.name+' '+notification.user.family:'همه کاربران'"
                                        trim
                                    />
                                    <b-link
                                        :to="{ name: 'user-single', params: { id: notification.user.id } }"
                                        class="font-weight-bold d-block text-nowrap" v-if="notification.id_user"
                                    >
                                        {{ notification.user.email ? notification.user.email : notification.user.mobile }}
                                    </b-link>
                                </b-form-group>
                            </b-col>

                            <b-col cols="12">
                                <b-form-group label="وضعیت" label-cols-md="3">
                                    <validation-provider #default="{ errors }" rules="required">
                                        <b-input-group class="input-group-merge">
                                            <b-form-select
                                                :dir="!$store.state.appConfig.isRTL ? 'rtl' : 'ltr'"
                                                :options="[{text:'دیده شده',value:'seen'},{text:'دیده نشده',value:'unseen'}]"
                                                v-model="notification.seen"
                                                :reduce="val => val.value"
                                                class="w-100"
                                                placeholder="وضعیت را انتخاب کنید"
                                                :state="errors.length > 0 ? false:null"
                                            />
                                        </b-input-group>
                                    </validation-provider>
                                </b-form-group>
                            </b-col>
                            <b-col cols="12" v-if="!notification.id_user">
                                <b-form-group label="نحوه ارسال" label-cols-md="3">
                                    <b-form-checkbox class="mt-1" v-model="notification.data.sendNotif" value="1" disabled>
                                        ارسال نوتیفیکیشن
                                    </b-form-checkbox>
                                    <b-form-checkbox v-model="notification.data.sendEmail" value="1" disabled>
                                        ارسال ایمیل
                                    </b-form-checkbox>
                                    <b-form-checkbox v-model="notification.data.sendSms" value="1" disabled>
                                        ارسال پیامک
                                    </b-form-checkbox>
                                    <b-form-checkbox v-model="notification.data.sendTelegram" value="1" disabled>
                                        ارسال در ربات تلگرام
                                    </b-form-checkbox>
                                </b-form-group>
                            </b-col>

                            <b-col cols="12" v-if="notification.data && notification.data.sendSmsLocale.fa">
                                <b-form-group label="نام الگو اعتبارسنجی" label-cols-md="3">
                                    <b-tabs>
                                        <b-tab :title="item.name" :active="item.symbol ==='fa'" v-for="(item,key) in getGeneralInfo.locales">
                                            <validation-provider #default="{ errors }" rules="required">
                                                <b-form-input
                                                    v-model="notification.data.sendSmsLocale[item.symbol]"
                                                    :state="errors.length > 0 ? false:null"
                                                    trim
                                                    :placeholder="'نام الگو برای زبان '+item.name"
                                                />
                                            </validation-provider>
                                        </b-tab>
                                    </b-tabs>
                                </b-form-group>
                            </b-col>
                        </b-row>
                    </b-form>
                </validation-observer>
            </b-card-text>
            <template #modal-footer>
                <div class="w-100">
                    <b-button variant="primary" class="float-right d-flex" @click="handleSubmit" :disabled="isLoadingEdit">
                        <div>ذخیره تغییرات</div>
                        <div class="line-height-0 ml-25"><b-spinner v-if="isLoadingEdit" small></b-spinner></div>
                    </b-button>
                    <b-button variant="danger" class="text-center float-right d-flex mr-1" @click="removeNotif" :disabled="isLoadingRemove">
                        <div>حذف اعلان</div>
                        <div class="line-height-0 ml-25"><b-spinner v-if="isLoadingRemove" small></b-spinner></div>
                    </b-button>
                    <b-button variant="outline-secondary" class="float-right mr-1" @click="modalStatus=false">
                        انصراف
                    </b-button>
                </div>
            </template>
        </b-modal>

    </div>
</template>

<script>
    import {
        BModal, BButton, VBModal, BAlert, BCardText, BFormGroup, BFormInput, BListGroup, BListGroupItem,BRow,BCol,BForm,BLink,BFormSelect,
        BInputGroup,BInputGroupPrepend,BInputGroupAppend,BSpinner, BBadge, BDropdown, BDropdownItem,BTabs,BTab,BFormTextarea,BFormCheckbox
    } from 'bootstrap-vue';

    import Ripple from "vue-ripple-directive";
    import BCardCode from "@core/components/b-card-code";
    import axiosIns from "@/libs/axios";
    import { ValidationProvider, ValidationObserver } from 'vee-validate'
    import {
        required, email,
    } from '@validations'
    import ToastificationContent from "@core/components/toastification/ToastificationContent";
    import vSelect from 'vue-select'

    export default {
        components: {
            ValidationProvider, ValidationObserver,
            BCardCode,
            BButton,
            BModal,
            BAlert,
            BCardText,BFormGroup,BFormInput,BListGroup,BListGroupItem,BRow,BCol,BForm,
            BInputGroup,BInputGroupPrepend,BInputGroupAppend,BDropdown,BDropdownItem,
            BSpinner, BBadge,BTabs,BTab,BFormTextarea,BLink,
            vSelect,BFormSelect,BFormCheckbox
        },
        directives: {
            'b-modal': VBModal,
            Ripple,
        },
        props:['id','modalShow'],
        data(){
            return {
                modalStatus: false,
                notification: null,
                isLoadingEdit: false,
                isLoadingRemove: false,
                optionsTitle:[
                    {label:this.$t('Warning'),value:'Warning'},
                    {label:this.$t('Bank card confirm'),value:'Bank card confirm'},
                    {label:this.$t('successful trade'),value:'successful trade'},
                    {label:this.$t('Bank card rejected'),value:'Bank card rejected'},
                    {label:this.$t('Transaction confirm'),value:'Transaction confirm'},
                    {label:this.$t('Transaction rejected'),value:'Transaction rejected'},
                    {label:this.$t('User referral'),value:'User referral'},
                    {label:this.$t('Commission'),value:'Commission'},
                    {label:this.$t('Level account confirm'),value:'Level account confirm'},
                    {label:this.$t('Level account rejected'),value:'Level account rejected'},
                    {label:this.$t('New answer on the ticket'),value:'New answer on the ticket'},
                ]
            }
        },
        methods:{
            handleOk(bvModalEvt) {
                // Prevent modal from closing
                bvModalEvt.preventDefault()
                // Trigger submit handler
                this.handleSubmit()
            },
            handleSubmit() {
                this.$refs.simpleRules.validate().then(success => {
                    if (success) {
                        this.isLoadingEdit = true;
                        axiosIns.post('setting/notification/edit/'+this.id,{...this.notification}) .then(response => {
                            this.$emit('refetchData')
                            this.$toast({
                                component: ToastificationContent,
                                props: {
                                    title: 'انجام شد!',
                                    text: response.data.msg,
                                    icon: 'CheckCircleIcon',
                                    variant: 'success',
                                },
                            })
                            this.isLoadingEdit = false;
                        })
                        .catch(() => {
                            this.errorFetching();
                            this.isLoadingEdit = false;
                        })
                    }
                })
            },

            getNotif(){
                axiosIns.post('setting/notification/info/'+this.id) .then(response => {
                    this.notification = response.data.notification;
                })
                .catch(() => {
                    this.errorFetching();
                })
            },
            removeNotif(){
                this.$swal({
                    title: 'از حذف اطلاعیه اطمینان دارید؟',
                    text: "اگر زمانبندی تعیین کرده باشید امکان حذف تا اتمام آن وجود ندارد.",
                    icon: 'warning',
                    showCancelButton: true,
                    showLoaderOnConfirm: true,
                    buttonsStyling: false,
                    preConfirm: () => {
                        return  axiosIns.post('setting/notification/remove/'+this.id,{type:'confirm'})
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
                            this.modalStatus = false;
                            this.$emit('refetchData');
                            this.$swal({icon: 'success',title: 'موفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }else{
                            this.$swal({icon: 'error',title: 'ناموفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }
                    }
                })
            },
        },
        watch:{
            modalShow(val){
               if(val){
                   this.getNotif()
                   this.modalStatus = true;
               }else {
                   this.modalStatus = false;
               }
            },
            modalStatus(val){
                if(!val){
                    setTimeout(() => {
                        this.notification = null;
                    }, 500)
                    this.$emit('modalUpdate', false)
                }
            },
        }
    }
</script>

<style lang="scss">
</style>
