<template>
    <div class="auth-wrapper auth-v2">
        <b-row class="auth-inner m-0">

            <!-- Brand logo-->
            <b-link class="brand-logo">
                <vuexy-logo/>

                <h2 class="brand-text text-primary ml-1">
                    {{ appName }}
                </h2>
            </b-link>
            <!-- /Brand logo-->

            <!-- Left Text-->
            <b-col
                lg="8"
                class="d-none d-lg-flex align-items-center p-5"
            >
                <div class="w-100 d-lg-flex align-items-center justify-content-center px-5">
                    <b-img
                        fluid
                        :src="imgUrl"
                        alt="Forgot password V2"
                    />
                </div>
            </b-col>
            <!-- /Left Text-->

            <!-- Forgot password-->
            <b-col
                lg="4"
                class="d-flex align-items-center auth-bg px-2 p-lg-5"
            >
                <b-col
                    sm="8"
                    md="6"
                    lg="12"
                    class="px-xl-2 mx-auto"
                >
                    <b-card-title
                        title-tag="h2"
                        class="font-weight-bold mb-1"
                    >
                        🔒 ورود دو مرحله ای پیامکی
                    </b-card-title>
                    <b-card-text class="mb-2">
                        کدی  که برای شما پیامک شده است را درج کنید.
                    </b-card-text>

                    <!-- form -->
                    <validation-observer ref="simpleRules">
                        <b-form
                            class="auth-forgot-password-form mt-2"
                            @submit.prevent="validationForm"
                        >
                            <b-form-group
                                label="کد پیامک"
                            >
                                <validation-provider
                                    #default="{ errors }"
                                    name="کد پیامک"
                                    rules="required|min:4"
                                >
                                    <b-form-input
                                        size="lg"
                                        v-model="code"
                                        :state="errors.length > 0 ? false:null"
                                        placeholder="کد پیامک"
                                        class="text-center"
                                    />
                                </validation-provider>
                            </b-form-group>

                            <b-button variant="primary" block class="text-center d-flex align-items-center justify-content-center" type="submit" :disabled="isLoading">
                                <div>تایید</div>
                                <div class="line-height-0 ml-25"><b-spinner v-if="isLoading" small></b-spinner></div>
                            </b-button>

                        </b-form>
                    </validation-observer>

                    <p class="text-center mt-2">
                        <b-link :to="{name:'auth-login'}">
                            <feather-icon icon="ChevronLeftIcon"/>
                            بازگشت به صفحه لاگین
                        </b-link>
                    </p>
                </b-col>
            </b-col>
            <!-- /Forgot password-->
        </b-row>
    </div>
</template>

<script>
    /* eslint-disable global-require */
    import {ValidationProvider, ValidationObserver} from 'vee-validate'
    import VuexyLogo from '@core/layouts/components/Logo.vue'
    import {
        BRow, BCol, BLink, BCardTitle, BCardText, BImg, BForm, BFormGroup, BFormInput, BButton,BSpinner
    } from 'bootstrap-vue'
    import {required, min} from '@validations'
    import store from '@/store/index'
    import ToastificationContent from '@core/components/toastification/ToastificationContent.vue'
    import axiosIns from "@/libs/axios";
    import useJwt from "@/auth/jwt/useJwt";
    import {getHomeRouteForLoggedInUser} from "@/auth/utils";

    export default {
        components: {
            VuexyLogo,
            BRow,
            BCol,
            BLink,
            BImg,
            BForm,
            BButton,
            BFormGroup,
            BFormInput,
            BCardTitle,
            BCardText,
            BSpinner,
            ValidationProvider,
            ValidationObserver,
        },
        data() {
            return {
                code: '',
                sideImg: require('@/assets/images/pages/forgot-password-v2.svg'),
                // validation
                required,
                min,
                isLoading: false,
            }
        },
        computed: {
            imgUrl() {
                if (store.state.appConfig.layout.skin === 'dark') {
                    // eslint-disable-next-line vue/no-side-effects-in-computed-properties
                    this.sideImg = require('@/assets/images/pages/forgot-password-v2-dark.svg')
                    return this.sideImg
                }
                return this.sideImg
            },
        },
        methods: {
            validationForm() {
                this.$refs.simpleRules.validate().then(success => {
                    if (success) {
                        const TwoFaData = JSON.parse(localStorage.getItem('2faData'));
                        this.isLoading = true;
                        axiosIns.post('login/2fa-sms',{...TwoFaData,code:this.code})
                        .then(response => {
                            if(response.data.status == true){
                                const userData = response.data.user
                                userData.ability = [{action: 'manage', subject: 'all'}];
                                useJwt.setToken(response.data.jwtToken)
                                useJwt.setRefreshToken(response.data.jwtToken)
                                localStorage.setItem('userData', JSON.stringify(userData))
                                this.$ability.update(userData.ability)

                                // ? This is just for demo purpose as well.
                                // ? Because we are showing eCommerce app's cart items count in navbar
                                //this.$store.commit('app-ecommerce/UPDATE_CART_ITEMS_COUNT', userData.extras.eCommerceCartItemsCount)

                                // ? This is just for demo purpose. Don't think CASL is role based in this case, we used role in if condition just for ease
                                this.$router.replace(getHomeRouteForLoggedInUser(userData.role))
                                    .then(() => {
                                        this.isLoading = false;
                                        this.getGeneralInfoApi()
                                        this.$toast({
                                            component: ToastificationContent,
                                            position: 'top-right',
                                            props: {
                                                title: `${userData.name} عزیز خوش آمدید`,
                                                icon: 'CoffeeIcon',
                                                variant: 'success',
                                                text: `شما با موفقیت وارد پنل مدیریت شدید!`,
                                            },
                                        })
                                    })
                            }else{
                                this.$toast({
                                    component: ToastificationContent,
                                    props: {
                                        title: response.data.msg,
                                        icon: 'AlertTriangleIcon',
                                        variant: 'danger',
                                    },
                                })
                                this.isLoading = false;
                            }
                        })
                        .catch(() => {
                            this.$toast({
                                component: ToastificationContent,
                                props: {
                                    title: 'Error fetching data',
                                    icon: 'AlertTriangleIcon',
                                    variant: 'danger',
                                },
                            })
                        })
                    }
                })
            },
        },
    }
</script>

<style lang="scss">
    @import '@core/scss/vue/pages/page-auth.scss';
</style>
