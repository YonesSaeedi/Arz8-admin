<template>
    <div id="settings">
        <section >
            <b-tabs
                v-model="tabActive"
                vertical
                content-class="col-12 col-md-8 col-lg-9"
                pills
                nav-wrapper-class="faq-navigation col-md-4 col-lg-3 col-12"
                nav-class="nav-left"
            >
                <b-tab>
                    <template #title>
                        <feather-icon
                            icon="SettingsIcon"
                            size="18"
                            class="mr-1"
                        />
                        <span class="font-weight-bold">تنظیمات عمومی</span>
                    </template>
                    <general @onSubmit="onSubmit" :settings="settings" v-if="settings"/>

                </b-tab>

                <b-tab>
                    <template #title>
                        <feather-icon
                            icon="PocketIcon"
                            size="18"
                            class="mr-1"
                        />
                        <span class="font-weight-bold">درگاه پرداخت</span>
                    </template>

                    <payment-gateway @onSubmit="onSubmit" :settings="settings" v-if="settings" :paymentGatewayList="paymentGatewayList"/>
                </b-tab>


                <b-tab>
                    <template #title>
                        <feather-icon
                            icon="ActivityIcon"
                            size="18"
                            class="mr-1"
                        />
                        <span class="font-weight-bold">واریز اتوماتیک</span>
                    </template>
                    <auto-deposit @onSubmit="onSubmit" :settings="settings" v-if="settings" :paymentGatewayList="paymentGatewayList"/>
                </b-tab>

                <b-tab>
                    <template #title>
                        <feather-icon
                            icon="CommandIcon"
                            size="18"
                            class="mr-1"
                        />
                        <span class="font-weight-bold">پروکسی</span>
                    </template>
                    <proxy @onSubmit="onSubmit" :settings="settings" v-if="settings"/>
                </b-tab>

                <b-tab>
                    <template #title>
                        <feather-icon
                            icon="UserCheckIcon"
                            size="18"
                            class="mr-1"
                        />
                        <span class="font-weight-bold">سطوح احراز هویت</span>
                    </template>
                    <levels @onSubmit="onSubmit" :settings="settings" v-if="settings"/>
                </b-tab>
                <b-tab>
                    <template #title>
                        <feather-icon
                            icon="UsersIcon"
                            size="18"
                            class="mr-1"
                        />
                        <span class="font-weight-bold">سطوح کاربری</span>
                    </template>
                    <LevelsAccount @onSubmit="onSubmit" :settings="settings" v-if="settings"/>
                </b-tab>

                <b-tab>
                    <template #title>
                        <feather-icon
                            icon="CreditCardIcon"
                            size="18"
                            class="mr-1"
                        />
                        <span class="font-weight-bold">واریز با فیش</span>
                    </template>
                    <receipt @onSubmit="onSubmit" :settings="settings" v-if="settings"/>
                </b-tab>

                <b-tab>
                    <template #title>
                        <feather-icon
                            icon="ShieldIcon"
                            size="18"
                            class="mr-1"
                        />
                        <span class="font-weight-bold">تتر</span>
                    </template>
                    <Tether @onSubmit="onSubmit" :settings="settings" v-if="settings"/>
                </b-tab>

                <b-tab>
                    <template #title>
                        <feather-icon
                            icon="ShieldIcon"
                            size="18"
                            class="mr-1"
                        />
                        <span class="font-weight-bold">بایننس</span>
                    </template>
                    <binance @onSubmit="onSubmit" :settings="settings" v-if="settings"/>
                </b-tab>

                <b-tab>
                    <template #title>
                        <feather-icon
                            icon="ShieldIcon"
                            size="18"
                            class="mr-1"
                        />
                        <span class="font-weight-bold">کوینکس</span>
                    </template>
                    <coinex @onSubmit="onSubmit" :settings="settings" v-if="settings"/>
                </b-tab>

                <b-tab>
                    <template #title>
                        <feather-icon
                            icon="ShieldIcon"
                            size="18"
                            class="mr-1"
                        />
                        <span class="font-weight-bold">کوکوین</span>
                    </template>
                    <kucoin @onSubmit="onSubmit" :settings="settings" v-if="settings"/>
                </b-tab>

                <b-tab>
                    <template #title>
                        <feather-icon
                            icon="InfoIcon"
                            size="18"
                            class="mr-1"
                        />
                        <span class="font-weight-bold">فینوتک و کارداینفو</span>
                    </template>
                    <finnotech @onSubmit="onSubmit" :settings="settings" v-if="settings"/>
                </b-tab>

                <b-tab>
                    <template #title>
                        <feather-icon
                            icon="SmartphoneIcon"
                            size="18"
                            class="mr-1"
                        />
                        <span class="font-weight-bold">اپلیکیشن</span>
                    </template>
                    <Application @onSubmit="onSubmit" :settings="settings" v-if="settings"/>
                </b-tab>

                <b-tab>
                    <template #title>
                        <feather-icon
                            icon="ImageIcon"
                            size="18"
                            class="mr-1"
                        />
                        <span class="font-weight-bold">بننر</span>
                    </template>
                    <Banner @onSubmit="onSubmit" @getSettings="getSettings" :settings="settings" v-if="settings && tabActive==13"/>
                </b-tab>

                <b-tab>
                    <template #title>
                        <img :src="baseURL+'images/currency/' + iconSymbol('PM')" width="20px" />
                        <span class="font-weight-bold ml-1">پرفکت مانی</span>
                    </template>
                    <PerfectMoney @onSubmit="onSubmit" :settings="settings" v-if="settings && tabActive==14"/>
                </b-tab>
                <b-tab>
                    <template #title>
                        <img :src="baseURL+'images/currency/' + iconSymbol('PMV')" width="20px" />
                        <span class="font-weight-bold ml-1">ووچر پرفکت مانی</span>
                    </template>
                    <PMvoucher @onSubmit="onSubmit" :settings="settings" v-if="settings && tabActive==15"/>
                </b-tab>
                <b-tab>
                    <template #title>
                        <img :src="baseURL+'images/currency/' + iconSymbol('PSV')" width="20px" />
                        <span class="font-weight-bold ml-1">پی اس ووچرز</span>
                    </template>
                    <PSVouchers @onSubmit="onSubmit" :settings="settings" v-if="settings && tabActive==16"/>
                </b-tab>
                <b-tab>
                    <template #title>
                        <img :src="baseURL+'images/currency/' + iconSymbol('UUSD')" width="20px" />
                        <span class="font-weight-bold ml-1">یوتوپیا</span>
                    </template>
                    <Utopia @onSubmit="onSubmit" :settings="settings" v-if="settings && tabActive==17"/>
                </b-tab>
                <b-tab>
                    <template #title>
                        <feather-icon
                            icon="ImageIcon"
                            size="18"
                            class="mr-1"
                        />
                        <span class="font-weight-bold">استوری</span>
                    </template>
                    <Stories @onSubmit="onSubmit" :settings="settings" v-if="settings && tabActive==18"/>
                </b-tab>
            </b-tabs>
        </section>
    </div>
</template>

<script>
    import {
        BCard, BCardBody, BForm, BInputGroup, BFormInput, BCardText, BInputGroupPrepend, BTabs, BTab, BImg, BRow, BCol, BAvatar,
    } from 'bootstrap-vue'

    import General from "./General";
    import paymentGateway from "./paymentGateway";
    import autoDeposit from "./autoDeposit";
    import proxy from "./Proxy";
    import levels from "./Levels";
    import LevelsAccount from "./LevelsAccount";
    import Receipt from "./Receipt";
    //import PopUp from "./PopUp";
    //import Alert from "./Alert";
    import Binance from "./Binance";
    import Tether from "./Tether";
    import Coinex from "./Coinex";
    import Kucoin from "./Kucoin";
    import Finnotech from "./Finnotech";
    import Application from "./Application";
    import Banner from "./Banner";
    import Stories from "./Stories";
    import PerfectMoney from "./digital-currency/PerfectMoney";
    import PMvoucher from "./digital-currency/PMvoucher";
    import PSVouchers from "./digital-currency/PSVouchers";
    import Utopia from "./digital-currency/Utopia";
    import axiosIns from "@/libs/axios";
    import ToastificationContent from "@core/components/toastification/ToastificationContent";

    export default {
        components: {
            autoDeposit,
            General,
            paymentGateway,
            proxy,
            levels,
            LevelsAccount,
            Receipt,
            //PopUp,
            //Alert,
            Binance,
            Tether,
            Coinex,
            Kucoin,
            Finnotech,
            Application,
            Banner,
            Stories,
            PerfectMoney,
            PMvoucher,
            PSVouchers,
            Utopia,


            BForm,
            BCard,
            BRow,
            BCol,
            BAvatar,
            BCardBody,
            BInputGroup,
            BFormInput,
            BCardText,
            BInputGroupPrepend,
            BTabs,
            BTab,
            BImg,
        },
        data() {
            return {
                settings: null,
                paymentGatewayList: null,
                tabActive:null
            }
        },
        methods:{
            getSettings(){
                axiosIns.post('/setting/settings')
                .then(response => {
                    this.settings = response.data.settings;
                    this.paymentGatewayList = response.data.paymentGateway;
                })
                .catch((error) => {
                    this.errorFetching();
                })
            },
            onSubmit(formData){
                axiosIns.post('/setting/settings/edit',formData)
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
                        this.getSettings();
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
                    }
                })
                .catch((error) => {
                    this.errorFetching();
                })
            }
        },
        created() {
            this.getSettings()
        }
    }
</script>

<style lang="scss">
#settings{
    .form-group{
        margin-bottom: 0px;
    }
}
</style>
