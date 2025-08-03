<template>
    <div class="auth-wrapper auth-v2">
        <b-row class="auth-inner m-0">

            <!-- Brand logo-->
            <b-link class="brand-logo">
                <b-img
                    :src="require('@/assets/images/logo/logo-'+ (skin === 'semi-dark'||skin ==='dark' ? 'light' : 'dark') +'.png')"
                    alt="logo"
                />
                <!--
                <h2 class="brand-text text-primary ml-1">
                    {{ appName }}
                </h2>
                -->
            </b-link>
            <!-- /Brand logo-->

            <!-- Login-->
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
                        class="mb-1 font-weight-bold"
                        title-tag="h3"
                    >
                        خوش آمدید به پنل مدریت
                        {{$t(appName)}}
                    </b-card-title>
                    <b-card-text class="mb-2">
                        برای درسترسی به پتل لطفا اطلاعات حساب کاربری خود را درج نمایید.
                    </b-card-text>

                    <b-alert
                        variant="danger"
                        show
                        v-if="errorMsg"
                    >
                        <div class="alert-body font-small-2">
                            {{errorMsg}}
                        </div>
                        <feather-icon
                            icon="AlertTriangleIcon"
                            size="18"
                            class="position-absolute"
                            style="top: 10; left: 10;"
                        />
                    </b-alert>

                    <!-- form -->
                    <validation-observer
                        ref="loginForm"
                        #default="{invalid}"
                    >
                        <b-form
                            class="auth-login-form mt-2"
                            @submit.prevent="submitForm"
                        >
                            <!-- email -->
                            <b-form-group
                                label="ایمیل"
                                label-for="login-email"
                            >
                                <validation-provider
                                    #default="{ errors }"
                                    name="Email"
                                    vid="email"
                                    rules="required|email"
                                >
                                    <b-form-input
                                        id="login-email"
                                        v-model="userEmail"
                                        :state="errors.length > 0 ? false:null"
                                        name="login-email"
                                        placeholder="john@example.com"
                                    />
                                    <small class="text-danger">{{ errors[0] }}</small>
                                </validation-provider>
                            </b-form-group>

                            <!-- forgot password -->
                            <b-form-group>
                                <div class="d-flex justify-content-between">
                                    <label for="login-password">پسورد</label>
                                    <b-link :to="{name:'auth-forgot-password'}">
                                        <small>فراموشی رمز عبور؟</small>
                                    </b-link>
                                </div>
                                <validation-provider
                                    #default="{ errors }"
                                    name="Password"
                                    vid="password"
                                    rules="required"
                                >
                                    <b-input-group
                                        class="input-group-merge"
                                        :class="errors.length > 0 ? 'is-invalid':null"
                                    >
                                        <b-form-input
                                            id="login-password"
                                            v-model="password"
                                            :state="errors.length > 0 ? false:null"
                                            class="form-control-merge"
                                            :type="passwordFieldType"
                                            name="login-password"
                                            placeholder="Password"
                                        />
                                        <b-input-group-append is-text>
                                            <feather-icon
                                                class="cursor-pointer"
                                                :icon="passwordToggleIcon"
                                                @click="togglePasswordVisibility"
                                            />
                                        </b-input-group-append>
                                    </b-input-group>
                                    <small class="text-danger">{{ errors[0] }}</small>
                                </validation-provider>
                            </b-form-group>

                            <!-- checkbox -->
                            <b-form-group>
                                <b-form-checkbox
                                    id="remember-me"
                                    v-model="status"
                                    name="checkbox-1"
                                >
                                    مرا بخاطر بسپار
                                </b-form-checkbox>
                            </b-form-group>

                            <!-- submit buttons -->
                            <b-button
                                type="submit"
                                variant="primary"
                                block
                                :disabled="invalid || isLoading"
                            >
                                <b-spinner v-if="isLoading" small></b-spinner>
                                <span v-else>ورود</span>
                            </b-button>
                        </b-form>
                    </validation-observer>



                </b-col>
            </b-col>
            <!-- /Login-->

            <!-- Left Text-->
            <b-col
                lg="8"
                class="d-none d-lg-flex align-items-center p-5"
            >
                <div class="w-100 d-lg-flex align-items-center justify-content-center px-5">
                    <b-img
                        fluid
                        :src="imgUrl"
                        alt="Login V2"
                    />
                </div>
            </b-col>
            <!-- /Left Text-->

        </b-row>
    </div>
</template>

<script>
    /* eslint-disable global-require */
    import {ValidationProvider, ValidationObserver} from 'vee-validate'
    import VuexyLogo from '@core/layouts/components/Logo.vue'
    import {
        BRow,
        BCol,
        BLink,
        BFormGroup,
        BFormInput,
        BInputGroupAppend,
        BInputGroup,
        BFormCheckbox,
        BCardText,
        BCardTitle,
        BImg,
        BForm,
        BButton,
        BSpinner,
        BAlert,
        VBTooltip,
    } from 'bootstrap-vue'
    import useJwt from '@/auth/jwt/useJwt'
    import {required, email} from '@validations'
    import {togglePasswordVisibility} from '@core/mixins/ui/forms'
    import store from '@/store'
    import {getHomeRouteForLoggedInUser} from '@/auth/utils'

    import ToastificationContent from '@core/components/toastification/ToastificationContent.vue'
    import { $themeConfig } from '@themeConfig'

    export default {
        directives: {
            'b-tooltip': VBTooltip,
        },
        components: {
            BRow,
            BCol,
            BLink,
            BFormGroup,
            BFormInput,
            BInputGroupAppend,
            BInputGroup,
            BFormCheckbox,
            BCardText,
            BCardTitle,
            BImg,
            BForm,
            BButton,
            BAlert,
            BSpinner,
            VuexyLogo,
            ValidationProvider,
            ValidationObserver,
        },

        mixins: [togglePasswordVisibility],
        data() {
            return {
                isLoading: false,
                sitekey: '6LeWpVEeAAAAAMYFW2tJHofb4Sdft7BqPZQn4at_',
                recaptcha: null,
                errorMsg: null,
                status: '',
                password: '',
                userEmail: '',
                sideImg: require('@/assets/images/pages/login-v2.svg'),

                // validation rules
                required,
                email,
            }
        },
        computed: {
            passwordToggleIcon() {
                return this.passwordFieldType === 'password' ? 'EyeIcon' : 'EyeOffIcon'
            },
            imgUrl() {
                if (store.state.appConfig.layout.skin === 'dark') {
                    // eslint-disable-next-line vue/no-side-effects-in-computed-properties
                    this.sideImg = require('@/assets/images/pages/login-v2-dark.svg')
                    return this.sideImg
                }
                return this.sideImg
            },
        },
        mounted() {
            const plugin = document.createElement('script')
            plugin.setAttribute(
                'src',
                `https://www.google.com/recaptcha/api.js?render=${this.sitekey}`
            )
            plugin.async = true
            document.head.appendChild(plugin)
        },
        methods: {
            submitForm(){
                this.isLoading = true
                const self = this
                grecaptcha.execute(this.sitekey, {action:'validate_captcha'})
                .then(function (token) {
                    self.recaptcha = token
                    self.login()
                })
            },
            login() {
                this.$refs.loginForm.validate().then(success => {
                    if (success) {
                        const app = this
                        grecaptcha.execute(this.sitekey, {action:'validate_captcha'})
                            .then(function (token) {
                                app.recaptcha = token

                        })
                        useJwt.login({
                            email: this.userEmail,
                            password: this.password,
                            recaptcha: this.recaptcha,
                        })
                            .then(response => {
                                if(response.data.data && response.data.data.twofa == true){
                                    localStorage.setItem('2faData', JSON.stringify(response.data.data))
                                    this.$router.push('/login/2fa-'+response.data.data.type)
                                    return;
                                }
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
                            })
                            .catch(error => {
                                this.errorMsg = error.response.data.msg;
                                this.$toast({
                                    component: ToastificationContent,
                                    position: 'top-right',
                                    props: {
                                        title: 'خطا',
                                        icon: 'AlertOctagonIcon',
                                        variant: 'danger',
                                        text: error.response.data.msg,
                                    },
                                })
                                this.isLoading = false
                                //this.$refs.loginForm.setErrors({email: [error.response.data.msg]});
                            })
                    }
                })
            },
        },
        setup() {
            // App Name
            const { appName, appLogoImage } = $themeConfig.app
            return {
                appName,
                appLogoImage,
            }
        },
    }
</script>

<style lang="scss">
    @import '@core/scss/vue/pages/page-auth.scss';
</style>
