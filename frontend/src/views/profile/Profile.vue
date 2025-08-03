<template>
    <div>
        <b-card title="پروفایل">
            <div class="text-center mb-2">
                <b-avatar
                    size="100px"
                    variant="light-primary"
                    icon="UserIcon"
                >
                    <feather-icon
                        icon="UserIcon"
                        size="60"
                    />
                </b-avatar>
            </div>

            <b-row class="col-lg-5 px-0 mx-auto">
                <hr class="w-100">
                <validation-observer ref="simpleRules" class="w-100" >
                    <b-form ref="form" @submit.stop.prevent="handleSubmit">
                        <b-col cols="12">
                            <b-form-group label="ایمیل" label-cols-md="3">
                                <validation-provider #default="{ errors }">
                                    <b-form-input v-model="userData.email" placeholder="ایمیل" :state="errors.length > 0 ? false:null"
                                                  class="text-center" disabled=""/>
                                </validation-provider>
                            </b-form-group>
                        </b-col>
                        <b-col cols="12">
                            <b-form-group label="نام" label-cols-md="3">
                                <validation-provider #default="{ errors }" rules="required">
                                    <b-form-input v-model="userData.name" placeholder="نام" :state="errors.length > 0 ? false:null"
                                                  class="text-center"/>
                                </validation-provider>
                            </b-form-group>
                        </b-col>
                        <b-col cols="12">
                            <b-form-group label="موبایل" label-cols-md="3">
                                <validation-provider #default="{ errors }" rules="required|min:11">
                                    <b-form-input v-model="userData.mobile" placeholder="موبایل" :state="errors.length > 0 ? false:null"
                                                  class="text-center" maxlength="11"/>
                                </validation-provider>
                            </b-form-group>
                        </b-col>

                        <b-col cols="12">
                            <b-form-group label="رمز عبور جدید" label-cols-md="3">
                                <validation-provider #default="{ errors }" rules="min:10">
                                    <b-input-group>
                                        <b-form-input :type="passwordFieldType" v-model="password" placeholder="رمز عبور جدید(اختیاری)"
                                                  :state="errors.length > 0 ? false:null" class="text-center" minlength="10"/>
                                        <b-input-group-append is-text>
                                            <feather-icon
                                                :icon="passwordToggleIcon"
                                                class="cursor-pointer"
                                                @click="togglePasswordVisibility"
                                            />
                                        </b-input-group-append>
                                    </b-input-group>
                                </validation-provider>
                            </b-form-group>
                        </b-col>
                        <b-col cols="12">
                            <b-form-group label="تکرار رمز عبور" label-cols-md="3">
                                <validation-provider #default="{ errors }" rules="min:10">
                                    <b-input-group>
                                        <b-form-input :type="passwordFieldType" v-model="passwordConfirmation" placeholder="تکرار رمز عبور جدید(اختیاری)"
                                                  :state="errors.length > 0 ? false:null" class="text-center" minlength="10"/>
                                        <b-input-group-append is-text>
                                            <feather-icon
                                                :icon="passwordToggleIcon"
                                                class="cursor-pointer"
                                                @click="togglePasswordVisibility"
                                            />
                                        </b-input-group-append>
                                    </b-input-group>
                                </validation-provider>
                            </b-form-group>
                        </b-col>

                        <b-col cols="9" offset-md="3">
                            <b-button variant="primary" block class="text-center d-flex align-items-center justify-content-center" type="submit" :disabled="isLoading">
                                <div>ذخیره تغییرات</div>
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
        name: "Profile",
        data () {
            return {
                userData: JSON.parse(localStorage.getItem('userData')),
                password:null,
                passwordConfirmation:null,
                isLoading:false,
                passwordFieldType:'password',
            }
        },
        computed: {
            passwordToggleIcon() {
                return this.passwordFieldType === 'password' ? 'EyeIcon' : 'EyeOffIcon'
            },
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
                        axiosIns.post('/profile/edit',{...this.userData,password:this.password,password_confirmation:this.passwordConfirmation})
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
            togglePasswordVisibility(){
                this.passwordFieldType =  this.passwordFieldType === 'password' ? 'text' : 'password'
            }
        }
    }
</script>

<style scoped>

</style>
