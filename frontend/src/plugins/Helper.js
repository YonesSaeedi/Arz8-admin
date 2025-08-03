import axiosIns from "@/libs/axios";
import store from "../store/app/index";

const coins = require('./coins.json');
import {$themeConfig} from '@themeConfig'
import ToastificationContent from "@core/components/toastification/ToastificationContent";

const getGeneralInfo = () => {
    const generalInfoLocalStorage = JSON.parse(localStorage.getItem('generalInfo')) || {}
    const generalInfo = {}
    Object.keys(generalInfoLocalStorage).forEach((key) => {
        if (generalInfo[key] === undefined && generalInfoLocalStorage[key] !== null) generalInfo[key] = generalInfoLocalStorage[key]
    })
    return generalInfo
}

const Helper = {
    data () {
        return {
           // baseURL: 'http://127.0.0.1:8000/api/',
            baseAdminURL: 'https://admin.arz8x.com/api/',
            //baseURL: 'https://app.farachange.ir/api/',
            baseURL: 'https://app.arz8.com/api/',
            homeURL: 'https://arz8.com/',
            access:{
                'action-transaction-crypto':'tr-crypto',
                'action-transaction-internal':'tr-internal',
                'admins':'admins',
                'admins-log':'admins',
                'action-users':'users',
                'action-cardbank':'cardbank',
                'action-tickets':'tickets',
                'users':'users',
                'gift':'gift',
                'gift-wheel':'gift-wheel',
                'gift-card':'gift-card',
                'orders':'orders',
                'trades':'trades',
                'tickets':'tickets',
                'tr-internal':'tr-internal',
                'tr-crypto':'tr-crypto',
                'card-bank':'cardbank',
                'referral-users':'referral',
                'referral-transaction':'referral',
                'payment-gateway':'payment-gateway',
                'cryptos':'setting-crypto',
                'cryptos-balance':'setting-cryptos-balance',
                'cryptos-little':'setting-cryptos-little',
                'cryptos-wallets':'setting-cryptos-wallets',
                'cryptos-auto-trade':'setting-cryptos-auto-trade',
                'networks':'setting-markets',
                'markets':'setting-networks',
                'notification':'setting-notification',
                'settings':'setting',
                'audit':'audit',
                'audit-wage':'audit-wage',
                'audit-digital':'audit-digital',
                'audit-factor':'audit-factor',
                'login-history':'login-history',
                'call-history':'call-history',
                'wallets-users':'wallets-users',
                'deposit-id':'deposit-id',
                'reports-users':'reports-users',
                'reports-orders':'reports-orders',
                'reports-trades':'reports-trades',
                'reports-referral':'reports-referral',
                'reports-shaparak':'reports-shaparak',
                'reports-payment-gateway':'reports-payment-gateway',
            },
        }
    },
    methods: {
        getGeneralInfoApi(){
            axiosIns.post('get-general-info', null)
                .then(response => {
                    if (response.data) {
                        // Update General Info
                        localStorage.setItem('generalInfo', JSON.stringify(response.data))
                        getGeneralInfo();
                        this.$store.commit('verticalMenu/UPDATE_PENDING_NOTIFICATION', response.data.pending);
                    }
                })
        },
        updateUser() {
            axiosIns.post('get-user', null)
                .then(response => {
                    if (response.data.status == true) {
                        response.data.user = Object.assign({}, response.data.user, {providerId: 'jwt'})
                        // Update user details
                        this.$store.commit('UPDATE_USER_INFO', response.data.user, {root: true})
                        return true
                    } else {
                        this.$vs.notify({
                            title: 'Error',
                            text: error.message,
                            iconPack: 'feather',
                            icon: 'icon-alert-circle',
                            color: 'danger'
                        })
                    }
                })
                .catch(error => {
                    this.$vs.notify({
                        title: 'Error',
                        text: error.message,
                        iconPack: 'feather',
                        icon: 'icon-alert-circle',
                        color: 'danger'
                    })
                })
        },
        levelAccount(level){
            if(level === 1)
                return 'bronze'
            else if(level === 2)
                return 'silver'
            else if(level === 3)
                return 'golden'
            else if(level === 4)
                return 'crystal'
        },
        onCopy() {
            this.$toast({
                component: ToastificationContent,
                props: {
                    title: this.$t('Success'),
                    text: this.$t('Text copied successfully'),
                    icon: 'CheckCircleIcon',
                    variant: 'success',
                },
            })
        },
        onError() {
            this.$toast({
                component: ToastificationContent,
                props: {
                    title: this.$t('Failed'),
                    text: this.$t('Error in copying text'),
                    icon: 'AlertTriangleIcon',
                    variant: 'danger',
                },
            })
        },
        toFixFloat(value) {
            return value.toLocaleString('en-US', {'maximumFractionDigits': 12})
        },
        getSymbolCoin(name) {
            const coin = coins.find(coin => coin.id.toUpperCase() === name.toUpperCase());
            const value = coin ? coin['symbol'] : null;
            return value;
        },
        parseNumberArabic(value) {
            return (value.replace(/[٠١٢٣٤٥٦٧٨٩]/g, function (d) {
                    return d.charCodeAt(0) - 1632
                }).replace(/[۰۱۲۳۴۵۶۷۸۹]/g, function (d) {
                    return d.charCodeAt(0) - 1776
                })
            )
        },
        handelerErrorApi(error) {
            if (error.response && error.response.status == 429)
                this.$vs.notify({
                    title: this.$t('Error'),
                    text: this.$t('Try a minute later.'),
                    iconPack: 'feather',
                    icon: 'icon-alert-circle',
                    color: 'warning'
                })
            else if (error.response && error.response.status == 404)
                this.$router.push('/error-404')
            return;
        },
        getFile(file, param) {
            const self = this;
            axiosIns.get('/image/' + file, {responseType: 'blob'})
            .then((response) => {
                let reader = new FileReader();
                reader.readAsDataURL(response.data);
                reader.onload = () => {
                    self[param] = reader.result;
                }
            })
        },
        amountLocalFloat(value, floatNumber = 5) {
            value = this.parseNumberArabic(value)
            value = value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1')
            var split = value.split(".");
            var value1 = parseInt(split[0]).toLocaleString('en-US');
            var value2 = (split[1] == null) ? '' : (split[1] == '' ? '.' : '.' + split[1]);
            value = value1 + value2.slice(0, floatNumber > 0 ? floatNumber + 1 : floatNumber)
            value = value == 'NaN' ? '' : value
            return value;
        },
        getRandomInt(min, max) {
            min = Math.ceil(min);
            max = Math.floor(max);
            return Math.floor(Math.random() * (max - min + 1)) + min;
        },
        isFontExist(symbol) {
            if (this.getGeneralInfo.crypto.findIndex(item => item.symbol === symbol && item.has_font === 1) >= 0)
                return true
            else
                return false
        },
        numberCheck(value, float = true) {
            value = this.parseNumberArabic(value)
            value = value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1')
            for (let i = 0; i <= 9; i++) {
                if (value == `0${i}`) value = i.toString()
            }
            if (float) {
                if (value == '.') value = '0.'
            } else if (value != '') {
                value = parseInt(value).toLocaleString('en-US')
            }
            return value
        },
        subStrFloat(amount, percent) {
            if (amount.includes(".")) {
                var split = amount.split(".")
                return split[0] + '.' + (split[1] ? split[1].substr(0, percent) : '');
            } else
                return amount;
        },
        removeLocalString(amountStr) {
            return amountStr.replace(/[^\d.-]/g, '')
        },
        isCryptoCurrency(symbol) {
            var crypto = this.getGeneralInfo.crypto.find(item => item.symbol === symbol);
            if (crypto)
                return true;
            else
                return false;
        },
        iconSymbol(symbol) {
            var crypto = this.getGeneralInfo.crypto.find(item => item.symbol === symbol);
            if (crypto)
                return crypto.icon;
            else
                return this.getGeneralInfo.digital_icon[symbol];
        },
        colorSymbol(symbol) {
            return this.getGeneralInfo.crypto.find(item => item.symbol === symbol).color;
        },
        localeNameSymbol(symbol) {
            var item = this.getGeneralInfo.crypto.find(item => item.symbol === symbol);
            return item ? JSON.parse(item.name_locale) : {fa:'تومان',en:'Toman'};
        },
        iconPackSymbol(symbol) {
            return {
                'pack': this.isFontExist(symbol) ? 'cf' : 'feather',
                'icon': !this.isFontExist(symbol) ? 'icon-hash' : 'cf-' + symbol.toLowerCase()
            };
        },
        errorFetching(){
            this.$toast({
                component: ToastificationContent,
                props: {
                    title: 'Error fetching data',
                    icon: 'AlertTriangleIcon',
                    variant: 'danger',
                },
            })
        },
        toThousand(count){
            return count <1000 ? count.toLocaleString('en-US') : (count/1000).toFixed(count>10000?1:2).toLocaleString('en-US')+'K'
        }
    },
    computed: {
        getGeneralInfo(){
            return getGeneralInfo();
        },
        activeUserInfo() {
            return JSON.parse(localStorage.getItem('userData'))
        },
        accessUserLogin() {
            return JSON.parse(this.activeUserInfo.access)['section_access']
        },
        generalInfo() {
            return this.$store.state.AppGeneralInfo
        },
        isSmallerScreen() {
            return this.$store.getters['app/currentBreakPoint'] === 'xs' ||this.$store.getters['app/currentBreakPoint'] === 'md'
        },
        localeHas() {
            return this.$i18n.locale
        },
        globalProperty() {
            return this.$store.state.globalProperty
        },
        refreshTableTrades() {
            return this.$store.state.refreshTableTrades;
        },
    },
    setup() {
        // App Name
        const {appName, appLogoImage} = $themeConfig.app
        return {
            appName,
            appLogoImage,
        }
    },
};
export default Helper;
