<template>
    <b-row v-if="user" class="match-height">
        <b-col
            cols="12"
            xl="9"
            lg="8"
            md="7"
        >
            <b-card  :class="`border-${resolveStatus(user.access)}`" style="border-width: 3px !important;">
                <b-row >
                    <!-- User Info: Left col -->
                    <b-col
                        cols="21"
                        xl="6"
                        class="d-flex justify-content-between flex-column"
                    >
                        <!-- User Avatar & Action Buttons -->
                        <div class="d-flex justify-content-start">
                            <b-avatar
                                :src="user.info && user.info.account_profile_img ? user.info.account_profile_img : null"
                                :text="avatarText(user.name_display)"
                                :variant="`light-primary`"
                                :size="isSmallerScreen?'70px':'104px'"
                                rounded
                            />
                            <div class="d-flex flex-column ml-1">
                                <div class="mb-md-1">
                                    <h4 class="mb-0">
                                        {{ user.name_display }}
                                    </h4>
                                    <span class="card-text">{{ user.email }}</span>
                                </div>
                                <div class="d-flex flex-wrap">
                                    <b-button :variant="user.access === 'blocked'?'success':'dark'" @click="block()" >
                                        {{user.access === 'blocked'?'آنبلاک':'بلاک'}}
                                    </b-button>
                                    <b-button variant="outline-danger" class="mx-md-1 mx-25" @click="remove()">
                                        حذف
                                    </b-button>
                                    <b-button variant="outline-warning" v-b-modal.note-modal size="sm" v-b-tooltip.hover :title="'یادداشت گذاری برای کاربر'">
                                        <feather-icon icon="FileIcon" size="18"/>
                                    </b-button>
                                </div>
                            </div>
                        </div>

                        <!-- User Stats -->
                        <div class="d-flex align-items-center mt-2">
                            <div class="d-flex align-items-center mr-2">
                                <b-avatar
                                    variant="light-primary"
                                    rounded
                                >
                                    <feather-icon
                                        icon="TrendingUpIcon"
                                        size="18"
                                    />
                                </b-avatar>
                                <div class="ml-1">
                                    <h5 class="mb-0">
                                        {{data.internal_balance.balance_available.toLocaleString()}} تومان
                                    </h5>
                                    <small>موجودی ریالی</small>
                                </div>
                            </div>

                            <div class="d-flex align-items-center">
                                <b-avatar
                                    variant="light-success"
                                    rounded
                                >
                                    <feather-icon
                                        icon="DollarSignIcon"
                                        size="18"
                                    />
                                </b-avatar>
                                <div class="ml-1">
                                    <h5 class="mb-0">
                                        {{data.balance_crypto_usdt.toLocaleString()}} دلار
                                    </h5>
                                    <small>موجودی رمز ارز به دلار</small>
                                </div>
                            </div>
                        </div>
                    </b-col>

                    <!-- Right Col: Table -->
                    <b-col
                        cols="12"
                        xl="6"
                    >
                        <table class="mt-2 mt-xl-0 w-100">
                            <tr>
                                <th class="pb-50">
                                    <feather-icon
                                        icon="UserIcon"
                                        class="mr-75"
                                    />
                                    <span class="font-weight-bold">نام و نام خانوادگی</span>
                                </th>
                                <td class="pb-50">
                                    {{ user.name? user.name +' '+ user.family:'----' }}
                                </td>
                            </tr>
                            <tr>
                                <th class="pb-50">
                                    <feather-icon
                                        icon="CheckIcon"
                                        class="mr-75"
                                    />
                                    <span class="font-weight-bold">موبایل</span>
                                </th>
                                <td class="pb-50 text-capitalize">
                                    {{ user.mobile?user.mobile:'----' }}
                                </td>
                            </tr>
                            <tr>
                                <th class="pb-50">
                                    <feather-icon
                                        icon="StarIcon"
                                        class="mr-75"
                                    />
                                    <span class="font-weight-bold">تاریخ ثبت نام</span>
                                </th>
                                <td class="pb-50 text-capitalize">
                                    {{user.date_register}}
                                </td>
                            </tr>
                            <tr>
                                <th class="pb-50">
                                    <feather-icon
                                        icon="FlagIcon"
                                        class="mr-75"
                                    />
                                    <span class="font-weight-bold">نحوه ثبت نام</span>
                                </th>
                                <td class="pb-50">
                                    {{(user.info && user.info.register_via)?user.info.register_via:'website'}}
                                </td>
                            </tr>
                            <tr>
                                <th class="pb-50">
                                    <feather-icon
                                        icon="KeyIcon"
                                        class="mr-75"
                                    />
                                    <span class="font-weight-bold">ورود دو مرحله ای</span>
                                </th>
                                <td class="pb-50">
                                    {{
                                    this.user.twofa == null || this.user.twofa.status === false ?
                                    ' غیر فعال':
                                    'فعال از طریق '+this.user.twofa.type
                                    }}
                                </td>
                            </tr>

                            <tr v-if="user.info.birthplace">
                                <th>
                                    <feather-icon
                                        icon="MapPinIcon"
                                        class="mr-75"
                                    />
                                    <span class="font-weight-bold">استان و شهر</span>
                                </th>
                                <td>
                                    {{user.info.birthplace.province}} - {{user.info.birthplace.city}}
                                </td>
                            </tr>
                        </table>
                    </b-col>
                </b-row>
            </b-card>
        </b-col>
        <b-col
            cols="12"
            md="5"
            xl="3"
            lg="4"
        >
            <b-card no-body class="p-1">
                <div class="text-center">
                    <img :src="require('@/assets/images/logo/logo-'+ levelAccount(user.level_account) +'.png')" width="105"
                         v-b-tooltip.hover :title="'سطح کاربری '+ $t('l-'+user.level_account)">
                </div>

                <div class="d-flex align-items-center justify-content-between">
                    <div>سطج احراز هویت:</div>
                    <div class="d-flex">
                        <feather-icon v-if="user.level>0" icon="StarIcon" class="text-warning" size="20" v-for="(star) of user.level"/>
                        <feather-icon v-if="user.level ==0" icon="StarIcon" class="" size="20"/>
                    </div>
                </div>

                <hr class="w-100 my-1">
                <div class="d-flex align-items-center justify-content-center">
                    <feather-icon icon="FileIcon" class="text-warning mx-50" size="30" v-if="note"
                                  v-b-modal.note-modal v-b-tooltip.hover :title="'دارای توضیحات '"/>

                    <feather-icon icon="UserPlusIcon" class="text-primary mx-50" size="30" v-if="user.referral"
                                  v-b-tooltip.hover :title="'معرفی شده توسط '+user.referral.email"/>

                    <feather-icon icon="KeyIcon" class="text-warning mx-50" size="27" v-if="this.user.twofa && this.user.twofa.status !== false"
                                  v-b-tooltip.hover :title="'ورود دو مرحله ای از طریق '+this.user.twofa.type"/>

                    <img src="@/assets/images/icons/website.png" class="mx-50" width="30" v-if="!user.info || !user.info.register_via ||user.info.register_via=='website'"
                                  v-b-tooltip.hover :title="'ثبت نام از طریق وب سایت'"/>
                    <img src="@/assets/images/icons/android.png" class="mx-50" width="30" v-if="user.info && user.info.register_via=='android'"
                                  v-b-tooltip.hover :title="'ثبت نام از طریق اندروید'"/>
                    <img src="@/assets/images/icons/ios.png" class="mx-50" width="30" v-if="user.info && user.info.register_via=='ios'"
                         v-b-tooltip.hover :title="'ثبت نام از طریق آی او اس'"/>

                    <img src="@/assets/images/icons/github.png" class="mx-50" width="30" v-if="user.info && user.info.register_account=='github'"
                         v-b-tooltip.hover :title="'ثبت نام با گیت هاب'"/>
                    <img src="@/assets/images/icons/yahoo.png" class="mx-50" width="30" v-if="user.info && user.info.register_account=='yahoo'"
                         v-b-tooltip.hover :title="'ثبت نام با یاهو'"/>
                    <img src="@/assets/images/icons/twitter.png" class="mx-50" width="30" v-if="user.info && user.info.register_account=='twitter'"
                         v-b-tooltip.hover :title="'ثبت نام با توییتر'"/>
                    <img src="@/assets/images/icons/google.png" class="mx-50" width="30" v-if="user.info && user.info.register_account=='google'"
                         v-b-tooltip.hover :title="'ثبت نام با گوگل'"/>
                </div>
            </b-card>
        </b-col>

        <b-modal id="note-modal" title="یادداشت های کاربر">
            <b-card-text>
                <b-form ref="form" @submit.stop.prevent="noteSubmit">
                    <b-form-group label="یادداشت یا توضیحات کاربر">
                        <b-input-group class="input-group-merge">
                            <b-form-textarea rows="6" v-model="note"  placeholder="توضیحات کاربر ..."/>
                        </b-input-group>
                    </b-form-group>
                </b-form>
            </b-card-text>
            <template #modal-footer>
                <div class="w-100">
                    <b-button variant="primary" class="float-right d-flex" @click="noteSubmit" :disabled="isLoadingNote">
                        <div>ذخیره تغییرات</div>
                        <div class="line-height-0 ml-25"><b-spinner v-if="isLoadingNote" small></b-spinner></div>
                    </b-button>
                    <b-button variant="outline-secondary" class="float-right mr-1" @click="$bvModal.hide('note-modal')">
                        انصراف
                    </b-button>
                </div>
            </template>
        </b-modal>
    </b-row>
</template>

<script>
    import BCardActions from '@core/components/b-card-actions/BCardActions.vue'
    import {
        BCard, BCardHeader, BBadge, BCollapse, BLink, BCardBody, BRow, BCol, BInputGroup, BInputGroupAppend, BFormGroup, BFormInput, BTable, BButton,
        BMedia, BAvatar,BTooltip,VBTooltip,BCardText,BForm,BFormTextarea,BSpinner
    } from 'bootstrap-vue'
    import Table from "@/views/vuexy/table/bs-table/Table";
    import Ripple from 'vue-ripple-directive'
    import axiosIns from "@/libs/axios";
    import ToastificationContent from "@core/components/toastification/ToastificationContent";

    export default {
        props:['user','data'],
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
            BInputGroup,BFormGroup, BInputGroupAppend,BFormInput,BSpinner,
            BMedia, BAvatar, BTooltip,VBTooltip,BCardText,BForm,BFormTextarea
        },
        directives: {
            'b-tooltip': VBTooltip,
            Ripple,
        },
        data () {
            return {
                note: '',
                isLoadingNote: false,
            }
        },
        created() {
            this.note = (this.user.info && this.user.info.note) ? this.user.info.note:''
        },
        methods:{
            noteSubmit(){
                this.isLoadingNote = true;
                axiosIns.post('/users/note/'+this.user.id ,{note: this.note?.trim() || null})
                    .then(response => {
                        this.$toast({
                            component: ToastificationContent,
                            props: {
                                title: response.data.msg,
                                icon: 'CheckCircleIcon',
                                variant: 'success',
                            },
                        })
                        this.isLoadingNote = false;
                    })
                    .catch(() => {
                        this.errorFetching();
                        this.isLoadingNote = false;
                    })
            },
            resolveStatus(status){
                if (status === 'accessible') return 'success'
                if (status === 'blocked') return 'secondary'
                return 'warning'
            },
            avatarText(value){
                if (!value) return ''
                const nameArray = value.split(' ')
                return nameArray.map(word => word.charAt(0).toUpperCase()).join('')
            },
            block(){
                var status = (this.user.access == 'accessible')?'blocked':'accessible';

                if(status == 'blocked') {
                    var msg = 'از بلاک کردن اطمینان دارید؟';
                    var text = 'با بلاک کردن این کاربر دیگر امکان دسترسی به پنل را ندارد.';
                    var classBtn = 'btn-warning';
                    var textBtn = 'بلاک';
                }
                else{
                    var msg = 'از آنبلاک کردن اطمینان دارید؟';
                    var text = 'با آن بلاک کردن این کاربر امکان دسترسی به پنل را دارد.';
                    var classBtn = 'btn-success';
                    var textBtn = 'آنبلاک';
                }

                this.$swal({
                    title: msg,
                    text: text,
                    icon: 'question',
                    showCancelButton: true,
                    showLoaderOnConfirm: true,
                    confirmButtonText: textBtn,
                    customClass: {
                        confirmButton: 'btn px-2 '+classBtn,
                        cancelButton: 'btn btn-outline-dark ml-1',
                    },
                    buttonsStyling: false,
                    preConfirm: () => {
                        return  axiosIns.put('/users/block/'+this.user.id ,{access:status})
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
                            this.$swal({icon: 'success',title: 'موفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }else{
                            this.$swal({icon: 'error',title: 'ناموفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }
                    }
                })
            },
            remove(){
                this.$swal({
                    title: 'از حذف اطمینان دارید؟',
                    text: 'کاربر در صورتی حذف میشود که داده ای نداشده باشد.',
                    icon: 'question',
                    showCancelButton: true,
                    showLoaderOnConfirm: true,
                    confirmButtonText: 'حذف شود',
                    customClass: {
                        confirmButton: 'btn px-2 btn-danger',
                        cancelButton: 'btn btn-outline-dark ml-1',
                    },
                    buttonsStyling: false,
                    preConfirm: () => {
                        return  axiosIns.delete('/users/remove/'+this.user.id )
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
                            this.$router.push('/users')
                            this.$swal({icon: 'success',title: 'موفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }else{
                            this.$swal({icon: 'error',title: 'ناموفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }
                    }
                })
            },
        }
    }
</script>

<style scoped>

</style>
