<template>
    <div>
        <validation-observer
            #default="{ handleSubmit }"
            ref="refFormLevel1Observer"
        >
            <b-form
                class="px-md-2"
                @submit.prevent="handleSubmit(onSubmit)"
                autocomplete="off"
            >
            <b-col md="8" class="mx-auto">
                <b-row>
                    <b-col md="6">
                        <b-form-group label="شناسه کاربر">
                            <b-form-input dir="ltr" class="text-center" v-model="user.id" disabled=""/>
                        </b-form-group>
                    </b-col>

                    <b-col md="6">
                        <validation-provider #default="{ errors }" rules="required|email">
                            <b-form-group label="ایمیل">
                                <b-form-input dir="ltr" class="text-center"
                                              v-model="user.email"
                                              :state="errors.length > 0 ? false:null"
                                              placeholder="ایمیل"
                                />
                            </b-form-group>
                        </validation-provider>
                    </b-col>
                    <b-col md="6">
                        <validation-provider #default="{ errors }" rules="required">
                            <b-form-group label="نام نمایشی">
                                <b-form-input class="text-center"
                                              v-model="user.name_display"
                                              :state="errors.length > 0 ? false:null"
                                              placeholder="نام نمایشی"
                                />
                            </b-form-group>
                        </validation-provider>
                    </b-col>
                    <b-col md="6">
                        <validation-provider #default="{ errors }" rules="required">
                            <b-form-group label="وضعیت وریفای ایمیل">
                                <b-form-select
                                    v-model="emailVerified"
                                    :options="[{'text':'تایید شده','value':true},{'text':'تایید نشده','value':false}]"
                                    :state="errors.length > 0 ? false:null"
                                />
                            </b-form-group>
                        </validation-provider>
                    </b-col>
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

                    <hr class="w-100">
                    <div v-if="user.info">
                        <p class="mb-0" v-if="user.info.register_account">ثبت نام و وریفای ایمیل از طریق شبکه اجتماعی {{user.info.register_account}} انجام شده است.</p>
                        <p class="text-primary cursor-pointer" v-b-modal.modal-info-social v-if="user.info.info_social">نمایش اطلاعاتی که از شبکه اجتماعی گرفته شده است.</p>
                    </div>
                </b-row>
            </b-col>

        </b-form>
        </validation-observer>

        <!-- basic modal -->
        <b-modal id="modal-info-social" scrollable size="lg" title="اطلاعات شبکه اجتماعی" ok-only ok-title="باشه">
            <b-card-text>
                <div dir="ltr" class="text-right" v-if="user.info">
                    <pre v-html="user.info.info_social"></pre>
                </div>
            </b-card-text>
        </b-modal>
    </div>
</template>

<script>
    import {
        BCard, BCardHeader, BBadge, BCollapse, BLink, BCardBody, BRow, BCol, BInputGroup, BInputGroupAppend, BFormGroup, BFormInput, BTable, BButton,
        BMedia, BAvatar,BTab,BTabs,BForm,BFormSelect,BSpinner
    } from 'bootstrap-vue';
    import Table from "@/views/vuexy/table/bs-table/Table";
    import BCardActions from "@core/components/b-card-actions/BCardActions";
    import {ValidationProvider, ValidationObserver} from 'vee-validate'
    import {required, alphaNum, between,email} from '@validations'
    import Ripple from "vue-ripple-directive";
    import axiosIns from "@/libs/axios";
    import ToastificationContent from "@core/components/toastification/ToastificationContent";

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
            BButton,
            BInputGroup,BFormGroup, BInputGroupAppend,BFormInput,
            BMedia, BAvatar,BTab,BTabs,BForm,BFormSelect,BSpinner,
            ValidationProvider,
            ValidationObserver,
        },
        directives: {
            Ripple,
        },
        data() {
            return {
                emailVerified:null,
                isLoading:false
            }
        },
        methods:{
            onSubmit(){
                this.$refs.refFormLevel1Observer.validate().then(success => {
                    if (success) {
                        this.isLoading = true;
                        axiosIns.post('/users/edit/level1/'+this.user.id,{
                            emailVerified:this.emailVerified,
                            email:this.user.email,
                            name_display:this.user.name_display,
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
            }
        },
        created() {
            if(this.user.email_verified_at){
                this.emailVerified = true;
            }else
                this.emailVerified = false;
        }
    }
</script>

<style scoped>

</style>
