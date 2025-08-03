<template>

    <div>
        <!--
        <b-row v-if="accessUserLogin['admins']['single'] || activeUserInfo.role === 'admin'">
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="UsersIcon"
                    color="info"
                    :statistic="statistic && statistic.userBalance ? statistic.userBalance.toLocaleString() :'0'"
                    statistic-title="کاربران موجودی"
                    id="userBalance"
                />
                <b-tooltip target="userBalance" variant="info">
                    کاربرانی که موجودی بیشتر از صفر دارند
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="ActivityIcon"
                    color="danger"
                    :statistic="statistic && statistic.allBalance? toFixFloat(parseFloat(statistic.allBalance.toFixed(crypto.percent))) :'0'"
                    statistic-title="موجودی کل"
                    id="allBalance"
                />
                <b-tooltip target="allBalance" variant="danger">
                    موجودی کل کاربران در پلتفرم
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="DiscIcon"
                    color="success"
                    :statistic="statistic && statistic.allBalanceAvailable? toFixFloat(parseFloat(statistic.allBalanceAvailable.toFixed(crypto.percent))) :'0'"
                    statistic-title="موجودی دسترس کل"
                    id="allBalanceAvailable"
                />
                <b-tooltip target="allBalanceAvailable" variant="success">
                    موجودی در دسترس کل کاربران در پلتفرم
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="HexagonIcon"
                    color="warning"
                    :statistic="statistic && statistic.allBalanceBinance? toFixFloat(parseFloat(statistic.allBalanceBinance.toFixed(crypto.percent))) :'0'"
                    statistic-title="موجودی بایننس"
                    id="avg"
                />
                <b-tooltip target="avg" variant="warning">
                   موجودی این ارز در بایننس در حال حاضر
                </b-tooltip>
            </b-col>
        </b-row>
        -->
        <b-card id="st-admin">
            <div class="" v-if="!admin">
                <vue-skeleton-loader
                    type="circle"
                    :width="120"
                    :height="120"
                    class="mx-auto my-2"
                />
                <vue-skeleton-loader v-for="i in 5"
                    type="rect"
                    :width="400"
                    :height="30"
                    class="mx-auto mt-2"
                />
            </div>

            <b-card-body v-if="admin" class="px-0 px-md-1">
                <div class="text-center">
                    <b-avatar size="120" :variant="`light-primary`" />
                </div>
                <b-row class="col-lg-6 px-0 mx-auto">
                    <hr class="w-100 my-2">
                    <validation-observer ref="simpleRules" class="w-100" >
                        <b-form ref="form" @submit.stop.prevent="handleSubmit">
                            <b-tabs>
                                <b-tab active>
                                    <template #title>
                                        <feather-icon icon="SlidersIcon" />
                                        <span>اطلاعات پایه</span>
                                    </template>

                                    <b-col cols="12">
                                        <b-form-group label="نام و نام خانوادگی" label-cols-md="4">
                                            <validation-provider #default="{ errors }" rules="required">
                                                <b-form-input v-model="admin.name" placeholder="نام و فامیل" :state="errors.length > 0 ? false:null"
                                                              class="text-center text-uppercase"/>
                                            </validation-provider>
                                        </b-form-group>
                                    </b-col>

                                    <b-col cols="12">
                                        <b-form-group label="ایمیل" label-cols-md="4">
                                            <validation-provider #default="{ errors }" rules="required|email">
                                                <b-form-input v-model="admin.email" placeholder="ایمیل" dir="ltr"
                                                              :state="errors.length > 0 ? false:null" class="text-center"/>
                                            </validation-provider>
                                        </b-form-group>
                                    </b-col>

                                    <b-col cols="12">
                                        <b-form-group label="موبایل" label-cols-md="4">
                                            <validation-provider #default="{ errors }" rules="required|min:11|alpha-num">
                                                <b-form-input v-model="admin.mobile" placeholder="موبایل" dir="ltr" maxlength="11"
                                                              :state="errors.length > 0 ? false:null" class="text-center"/>
                                            </validation-provider>
                                        </b-form-group>
                                    </b-col>

                                    <b-col cols="12">
                                        <b-form-group label="سطح" label-cols-md="4">
                                            <validation-provider #default="{ errors }" rules="required">
                                                <b-form-select
                                                    v-model="admin.role"
                                                    :options="[{'text':'ادمین کل','value':'admin'},{'text':'ادمین عادی','value':'member'}]"
                                                    :state="errors.length > 0 ? false:null"
                                                />
                                            </validation-provider>
                                        </b-form-group>
                                    </b-col>

                                    <b-col cols="12">
                                        <b-form-group label="رمز عبور (اختیاری)" label-cols-md="4">
                                            <validation-provider #default="{ errors }">
                                                <b-form-input v-model="admin.password" placeholder="رمز عبور اختیاری" :state="errors.length > 0 ? false:null"
                                                              class="text-center text-uppercase"/>
                                            </validation-provider>
                                            <small>در صورتی که نیاز به تغییر رمز عبور را دارید این فید پر شود.</small>
                                        </b-form-group>
                                    </b-col>

                                    <b-col cols="12">
                                        <b-form-group label="ورود دو مرحله ای" label-cols-md="4">
                                            <validation-provider #default="{ errors }" rules="required">
                                                <b-form-select
                                                    v-model="admin.twofa"
                                                    :options="[{'text':'پیامک','value':'sms'},{'text':'گوگل','value':'google'}]"
                                                    :state="errors.length > 0 ? false:null"
                                                />
                                            </validation-provider>
                                            <small>ورود دو مرحله ای الزامیست و پیش فرض پیامک تنظیم میباشد.</small>
                                        </b-form-group>
                                    </b-col>
                                </b-tab>
                                <b-tab>
                                    <template #title>
                                        <feather-icon icon="ZapIcon" />
                                        <span>دسترسی ها</span>
                                    </template>
                                    <b-alert class="w-100 mt-1 mb-0" variant="warning" show v-if="admin.role == 'admin' ">
                                        <div class="alert-body">
                                            <span>سطح این ادمین "مدیر کل" تنظیم شده است و هیچ محدودیتی در دسترسی ها برای این ادمین وجود ندارد!</span>
                                        </div>
                                    </b-alert>

                                    <div class="mt-2" :class="admin.role == 'admin' ? 'disable-block':''">
                                        <p>
                                            <strong>نکته:</strong> در بخش هایی که گزینه "لیست" دارند و شما تیک لیست را نزنید بقیه دسترسی های اون بخش مانند جزئیات و ویرایش معانایی ندارد چون لیستی به کاربر نمایش داده نمیشود.
                                        </p>

                                        <b-form-group v-for="(item,key) in access.section_access" class="mb-2">
                                            <h5>{{$t(key)}}:</h5>
                                            <div>
                                                <b-form-checkbox-group
                                                    :id="key"
                                                    v-model="selected[key]"
                                                    :options="Object.keys(item).map(x=>{ return {'text':$t(x),'value':x }})"
                                                    :name="key"
                                                    class="ml-4"
                                                    @change="changeSelected($event,key)"
                                                    stacked
                                                ></b-form-checkbox-group>
                                            </div>
                                            <hr width="50%">
                                            <hr width="30%">
                                        </b-form-group>
                                    </div>

                                </b-tab>
                                <b-tab>
                                    <template #title>
                                        <feather-icon icon="ShieldIcon" />
                                        <span>حساب ها</span>
                                    </template>
                                    <b-col cols="12">
                                        <b-row v-for="(item,key) in admin.hesab">
                                            <b-col cols="6">
                                                <b-form-group :label="'نام حساب '+(key+1)" label-cols-md="12">
                                                        <validation-provider #default="{ errors }" rules="required">
                                                            <b-form-input v-model="admin.hesab[key].name" placeholder="نام حساب"
                                                                          :state="errors.length > 0 ? false:null" class="text-center"/>
                                                        </validation-provider>
                                                </b-form-group>
                                            </b-col>
                                            <b-col cols="6">
                                                <b-form-group cols="2" :label="'موجودی حساب '+(key+1)" label-cols-md="12">
                                                    <validation-provider #default="{ errors }" rules="required">
                                                        <b-form-input v-model="admin.hesab[key].stock" placeholder="موجودی حساب" v-on:keyup="addCammas(key)"
                                                                      :state="errors.length > 0 ? false:null" class="text-center" dir="ltr"/>
                                                    </validation-provider>
                                                </b-form-group>
                                            </b-col>
                                            <b-col cols="11">
                                                <b-form-group cols="2" :label="'توضیحات حساب '+(key+1)" label-cols-md="12">
                                                    <validation-provider #default="{ errors }" rules="required">
                                                        <b-form-input v-model="admin.hesab[key].description" placeholder="توضیحات حساب"
                                                                      :state="errors.length > 0 ? false:null" class="text-center"/>
                                                    </validation-provider>
                                                </b-form-group>
                                            </b-col>
                                            <b-col cols="1">
                                                <div class="text-center mx-auto mt-3">
                                                    <feather-icon @click="removeHesab(key)" icon="XIcon" size="25" class="text-danger mx-auto cursor-pointer"/>
                                                </div>
                                            </b-col>
                                            <hr width="100%"/>
                                        </b-row>
                                        <b-col cols="12" class="px-0">
                                            <b-button variant="outline-success" block class="text-center" @click="addHesab()">
                                                <div>افزودن حساب</div>
                                            </b-button>
                                        </b-col>
                                    </b-col>
                                </b-tab>
                            </b-tabs>

                            <hr class="w-100">

                            <b-col cols="12">
                                <b-button variant="primary" block class="text-center d-flex align-items-center justify-content-center" type="submit" :disabled="isLoading">
                                    <div>ذخیره تغییرات</div>
                                    <div class="line-height-0 ml-25"><b-spinner v-if="isLoading" small></b-spinner></div>
                                </b-button>
                            </b-col>

                        </b-form>
                    </validation-observer>
                </b-row>
            </b-card-body>
        </b-card>
        <!--<balance-list :crypto="crypto" v-if="crypto" />-->

        <div v-if="!accessUserLogin['admins']['single'] && activeUserInfo.role !== 'admin'">
            <NotAccessed/>
        </div>

    </div>
</template>
<i18n locale="fa">
{
    "list": "لیست"
}
</i18n>
<script>
    import BCardActions from '@core/components/b-card-actions/BCardActions.vue'
    import {
        BCard, BCardHeader, BBadge, BCollapse, BLink, BCardBody, BRow,BForm, BCol, BInputGroup, BInputGroupAppend, BFormGroup, BFormInput, BTable, BButton,BAlert,
        BInputGroupPrepend, BFormSelect,BFormFile, BFormRadioGroup, BTab,BTabs ,BSpinner,BTooltip, BOverlay,BAvatar,BFormCheckbox,BFormCheckboxGroup

    } from 'bootstrap-vue'
    import vSelect from 'vue-select'
    import axiosIns from "@/libs/axios";
    import ToastificationContent from "@core/components/toastification/ToastificationContent";
    import StatisticCardHorizontal from '@core/components/statistics-cards/StatisticCardHorizontal.vue'

    import Table from "@/views/vuexy/table/bs-table/Table";
    import { ValidationProvider, ValidationObserver } from 'vee-validate'
    import {required,between,email,min,alphaNum } from '@validations'
    import BalanceList from "./balance-list/BalanceList";
    import VueSkeletonLoader from 'skeleton-loader-vue';
    import NotAccessed from "@/views/vuexy/pages/miscellaneous/NotAccessed";

    export default {
        data () {
            return {
                required,
                alphaNum,
                between,
                email,min,

                isLoading: false,
                admin: null,
                access: null,
                statistic: [],

                selected: [],
                allSelected: [],
                indeterminate: [],

                allSelected_user:false
            }
        },
        components: {
            BalanceList,
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
            BAlert,
            BInputGroup,BFormGroup, BInputGroupAppend,BFormInput,BFormFile,BAvatar,BFormCheckbox,BFormCheckboxGroup,
            vSelect, ValidationProvider, ValidationObserver,BInputGroupPrepend,BForm, BFormSelect,
            BFormRadioGroup,BTab,BTabs,BSpinner,BTooltip,BOverlay,
            StatisticCardHorizontal,NotAccessed,
            VueSkeletonLoader
        },
        methods:{
            getAdmin(id){
                axiosIns.post('admins/admin/'+id)
                .then(response => {
                    this.access = JSON.parse(response.data.access);
                    Object.keys(this.access.section_access).map((item,key)=>{
                        this.indeterminate[item] = false
                        this.allSelected[item] = false
                        this.selected[item] = [];
                        Object.keys(this.access.section_access[item]).map((item2)=>{
                            if(this.access.section_access[item][item2]){
                                this.selected[item].push(item2);
                                this.changeSelected(this.access.section_access[item][item2],item);
                            }
                        })
                    })
                    this.admin = response.data;
                    if(this.admin.google2fa)
                        this.admin.twofa = 'google'
                    else
                        this.admin.twofa = 'sms'
                    document.title = this.$t('admin')+': '+this.admin.email;

                    Object.keys(this.admin.hesab).map((item,key)=>{
                        this.admin.hesab[key].stock = parseInt(this.admin.hesab[key].stock).toLocaleString()
                    })
                })
                .catch((error) => { console.log(error); this.errorFetching(); })
            },
            handleSubmit() {
                this.$refs.simpleRules.validate().then(success => {
                    if (success) {

                        this.isLoading = true;
                        Object.keys(this.access.section_access).map((item,key)=>{
                            Object.keys(this.access.section_access[item]).map((x)=>{
                                if(this.selected[item].includes(x))
                                    this.access.section_access[item][x] = true;
                                else
                                    this.access.section_access[item][x] = false;
                            })
                        })
                        this.admin.access = this.access;

                        axiosIns.post('admins/admin/edit/'+this.admin.id+'',this.admin) .then(response => {
                            if(response.data.status == true){
                                this.getAdmin(this.$router.currentRoute.params.id)
                                this.$toast({
                                    component: ToastificationContent,
                                    props: {
                                        title: 'انجام شد!',
                                        text: response.data.msg,
                                        icon: 'CheckCircleIcon',
                                        variant: 'success',
                                    },
                                })
                            }else {
                                this.$swal({icon: 'warning',title: 'نکته!',text: response.data.msg, confirmButtonText: 'باشه'})
                            }

                            this.isLoading = false;
                        })
                        .catch(() => {
                            this.errorFetching();
                            this.isLoading = false;
                        })
                    }else{
                        this.$swal({icon: 'warning',title: 'نکته!',text: 'تمامی فیلد ها رو بررسی کنید!',confirmButtonText: 'باشه'})
                    }
                })
            },
            toggleAll(checked,key) {
                return;
                //console.log(checked,Object.keys(this.access.section_access[key]), this.selected[key]);
                this.selected[key] = checked ? Object.keys(this.access.section_access[key]).slice() : [];
                //console.log(this.selected[key])
                //console.log(key,this.selected[key]);
                console.log(this.allSelected);

            },
            changeSelected(checked,key){
                return;
                if (checked.length === 0) {
                    this.indeterminate[key] = false
                    this.allSelected[key] = false
                } else if (checked.length === Object.keys(this.access.section_access[key]).length) {
                    this.indeterminate[key] = true
                    this.allSelected[key] = true
                } else {
                    this.indeterminate[key] = true
                    this.allSelected[key] = true
                }
            },
            addCammas(key){
                this.admin.hesab[key].stock =  this.amountLocalFloat(this.admin.hesab[key].stock)
            },
            addHesab(){
                var obj = {name:null, stock:null, description:null};
                this.admin.hesab.push(obj);
            },
            removeHesab(key){
                var id = this.admin.hesab[key].id;
                if(!id){
                    this.admin.hesab.splice(key, 1);
                }else{
                    this.$swal({
                        title: 'از حذف اطمینان دارید؟',
                        text: 'حساب در صورتی حدف میشود که هیچ تراکنشی با آن انجام  نشده باشد.',
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
                            return  axiosIns.delete('/admins/admin/hesab/'+ id )
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
                                this.admin.hesab.splice(key, 1);
                                this.$swal({icon: 'success',title: 'موفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                            }else{
                                this.$swal({icon: 'error',title: 'ناموفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                            }
                        }
                    })
                }
            }
        },
        created() {
            if(this.accessUserLogin['admins']['single']  || this.activeUserInfo.role === 'admin')
                this.getAdmin(this.$router.currentRoute.params.id)
        }
    }
</script>

<style lang="scss">
#st-admin{
    .demo-inline-spacing.mt-0 .custom-radio{
        margin-top: 0px !important;
    }

    .ml-4.bv-no-focus-ring{
        display: flex;
        flex-wrap: wrap;
    }

    .ml-4.bv-no-focus-ring .custom-checkbox{
        width: 33.3%;
    }
}
</style>
