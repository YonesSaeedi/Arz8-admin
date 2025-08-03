<template>
    <div id="factor" class="p-4" style="width: 100%;height: 100%; background-color: white;position: absolute" v-if="factor">

        <b-row class="align-items-center">
            <b-col cols="12" md="4" >
                <b-img :src="require('@/assets/images/logo/logo-dark.png')" width="200px"/>
                <p class="bold">صرافی ارز دیجیتال ارز هشت Exchange Crypto Curency</p>
            </b-col>
            <b-col cols="12" md="4" class="text-center">
                <h2 class="bold mb-0">صورت حساب فروش کالا و خدمات</h2>
            </b-col>
            <b-col cols="12" md="4" class="text-right">
                <table class="ml-auto">
                    <tr>
                        <td class="bold">
                            شماره  فاکتور:
                        </td>
                        <td class="bold">
                            <span class="ml-2">{{factor.id_factor}}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="bold">
                            تاریخ:
                        </td>
                        <td class="bold">
                            <span class="ml-2">{{factor.date}}</span>
                        </td>
                    </tr>
                </table>
            </b-col>
            <b-col cols="12" md="12" >
                <p style="font-size: 16px;text-align: center;background-color: #c8c8c8;padding: 10px">مشخصات فروشنده</p>
                <div class="border p-2">
                    <b-row class="align-items-center">
                        <b-col cols="12" md="4" class="">
                            <div class="d-flex align-items-center">
                                <p class="bold">نام شخص حقوقی:</p>
                                <h3 class="bold ml-2">امیر رستمی سرجزه</h3>
                            </div>
                            <div class="d-flex align-items-center mt-2">
                                <p class="bold">نشانی کامل:</p>
                                <p class="bold ml-2">استان: آذربایجان شرقی</p>
                                <p class="bold ml-4">شهرستان: مراغه</p>
                            </div>
                            <div class="d-flex align-items-center mt-2">
                                <p class="bold">آدرس:</p>
                                <p class="bold ml-2">جام جم ، مجتمع سهند طبقه ۵ واحد ۳</p>
                            </div>
                        </b-col>
                        <b-col cols="12" md="4" >
                            <div class="d-flex align-items-center">
                                <p class="bold">شماره اقتصادی:</p>
                                <table class="table b-table table-bordered ml-auto mb-0" style="width: 60%;">
                                    <tr>
                                        <td>1</td>
                                        <td>2</td>
                                        <td>6</td>
                                        <td>9</td>
                                        <td>0</td>
                                        <td>5</td>
                                        <td>0</td>
                                        <td>4</td>
                                        <td>5</td>
                                        <td>1</td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="d-flex align-items-center mt-1">
                                <p class="bold">شناسه ملی:</p>
                                <table class="table b-table table-bordered ml-auto mb-0" style="width: 60%;">
                                    <tr>
                                        <td>1</td>
                                        <td>2</td>
                                        <td>6</td>
                                        <td>9</td>
                                        <td>0</td>
                                        <td>5</td>
                                        <td>0</td>
                                        <td>4</td>
                                        <td>5</td>
                                        <td>1</td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="d-flex align-items-center mt-1">
                                <p class="bold">کد پستی:</p>
                                <table class="table b-table table-bordered ml-auto mb-0" style="width: 60%;">
                                    <tr>
                                        <td>1</td>
                                        <td>0</td>
                                        <td>3</td>
                                        <td>5</td>
                                        <td>1</td>
                                        <td>8</td>
                                        <td>4</td>
                                        <td>1</td>
                                        <td>5</td>
                                        <td>5</td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                </table>
                            </div>
                        </b-col>
                        <b-col cols="12" md="4" >
                            <div class="d-flex align-items-center">
                                <p class="bold ml-4">شماره ثبت:</p>
                                <table class="table b-table table-bordered ml-auto mb-0" style="width: 60%;">
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="d-flex align-items-center mt-2">
                                <p class="bold ml-4">شماره تلفن:</p>
                                <p class="bold ml-2"></p>
                            </div>
                            <div class="d-flex align-items-center mt-2">
                                <p class="bold ml-4">شماره نمابر:</p>
                                <p class="bold ml-2"></p>
                            </div>
                        </b-col>
                    </b-row>
                </div>
            </b-col>

            <b-col cols="12" md="12" >
                <p style="font-size: 16px;text-align: center;background-color: #c8c8c8;padding: 10px">مشخصات فروشنده</p>
                <div class="border p-2">
                    <b-row class="align-items-center">
                        <b-col cols="12" md="4" class="">
                            <div class="d-flex align-items-center">
                                <p class="bold">نام شخص حقیقی/حقوقی:</p>
                                <p class="bold ml-2">{{factor.user.name}} {{factor.user.family}}</p>
                            </div>
                            <div class="d-flex align-items-center mt-2">
                                <p class="bold">نشانی کامل:</p>
                                <p class="bold ml-2">استان:
                                    <span v-if="(JSON.parse(factor.user.info)) && (JSON.parse(factor.user.info)).birthplace">{{(JSON.parse(factor.user.info)).birthplace.province}}</span>
                                    <span v-else></span>
                                </p>
                                <p class="bold ml-4">شهرستان:
                                    <span v-if="(JSON.parse(factor.user.info)) && (JSON.parse(factor.user.info)).birthplace">{{(JSON.parse(factor.user.info)).birthplace.city}}</span>
                                    <span v-else></span>
                                </p>
                            </div>
                            <div class="d-flex align-items-center mt-2">
                                <p class="bold">آدرس:</p>
                                <p class="bold ml-2">
                                    <span v-if="(JSON.parse(factor.user.address))">{{(JSON.parse(factor.user.address)).address}}</span>
                                    <span v-else></span>
                                </p>
                            </div>
                        </b-col>
                        <b-col cols="12" md="4" >
                            <div class="d-flex align-items-center">
                                <p class="bold">شماره اقتصادی:</p>
                                <table class="table b-table table-bordered ml-auto mb-0" style="width: 60%;">
                                    <tr>
                                        <td> </td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="d-flex align-items-center mt-1">
                                <p class="bold">شماره ملی/شماره ثبت:</p>
                                <table class="table b-table table-bordered ml-auto mb-0" style="width: 60%;">
                                    <tr>
                                        <td>{{factor.user.national_code.split("")[9]}}</td>
                                        <td>{{factor.user.national_code.split("")[8]}}</td>
                                        <td>{{factor.user.national_code.split("")[7]}}</td>
                                        <td>{{factor.user.national_code.split("")[6]}}</td>
                                        <td>{{factor.user.national_code.split("")[5]}}</td>
                                        <td>{{factor.user.national_code.split("")[4]}}</td>
                                        <td>{{factor.user.national_code.split("")[3]}}</td>
                                        <td>{{factor.user.national_code.split("")[2]}}</td>
                                        <td>{{factor.user.national_code.split("")[1]}}</td>
                                        <td>{{factor.user.national_code.split("")[0]}}</td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="d-flex align-items-center mt-1">
                                <p class="bold">کد پستی:</p>
                                <table class="table b-table table-bordered ml-auto mb-0" style="width: 60%;">
                                    <tr v-if="(JSON.parse(factor.user.address))">
                                        <td>{{(JSON.parse(factor.user.address)).zipcode.split("")[9]}}</td>
                                        <td>{{(JSON.parse(factor.user.address)).zipcode.split("")[8]}}</td>
                                        <td>{{(JSON.parse(factor.user.address)).zipcode.split("")[7]}}</td>
                                        <td>{{(JSON.parse(factor.user.address)).zipcode.split("")[6]}}</td>
                                        <td>{{(JSON.parse(factor.user.address)).zipcode.split("")[5]}}</td>
                                        <td>{{(JSON.parse(factor.user.address)).zipcode.split("")[4]}}</td>
                                        <td>{{(JSON.parse(factor.user.address)).zipcode.split("")[3]}}</td>
                                        <td>{{(JSON.parse(factor.user.address)).zipcode.split("")[2]}}</td>
                                        <td>{{(JSON.parse(factor.user.address)).zipcode.split("")[1]}}</td>
                                        <td>{{(JSON.parse(factor.user.address)).zipcode.split("")[0]}}</td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr v-else>
                                        <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                                    </tr>
                                </table>
                            </div>
                        </b-col>
                        <b-col cols="12" md="4" >
                            <div class="d-flex align-items-center">
                                <p class="bold ml-4">شماره موبایل:</p>
                                <p class="bold ml-2">{{factor.user.mobile}}</p>
                            </div>
                            <div class="d-flex align-items-center mt-2">
                                <p class="bold ml-4">شماره تلفن:</p>
                                <p class="bold ml-2">
                                    <span v-if="factor.user.phone">{{factor.user.phone}}</span>
                                </p>
                            </div>
                            <div class="d-flex align-items-center mt-2">
                                <p class="bold ml-4">شماره نمابر:</p>
                                <p class="bold ml-2"></p>
                            </div>
                        </b-col>
                    </b-row>
                </div>
            </b-col>

            <b-col cols="12" md="12" >
                <p style="font-size: 16px;text-align: center;background-color: #c8c8c8;padding: 10px">مشخصات کالا یا خدمات مورد معامله</p>
                <table class="table b-table table-bordered service">
                    <tr>
                        <th>ردیف</th>
                        <th>شرح کالا یا خدمات</th>
                        <th>تعداد/مقدار</th>
                        <th>واحد اندازه گیری</th>
                        <th>مبلغ واحد(ریال)</th>
                        <th>مبلغ کل(ریال)</th>
                        <th>درصد کارمزد</th>
                        <th>مبلغ کارمزد(ریال)</th>
                    </tr>
                    <tr>
                        <td>1</td>
                        <td>{{factor.type==='deposit'?'خرید':'فروش'}} ارز دیجیتال USDT</td>
                        <td>{{(((factor.amount-(factor.amount*0.01))*10)/fee).toFixed(2).toLocaleString()}}</td>
                        <td>عدد</td>
                        <td>
                            {{fee.toLocaleString()}}
                        </td>
                        <td>{{((factor.amount-(factor.amount*0.01))*10).toLocaleString()}}</td>
                        <td>
                            <span>%{{(getFeePercentage(factor.amount,factor.type)*100)}}</span>
                        </td>
                        <td>
                            <span>{{(factor.wage*10).toLocaleString()}}</span>
                        </td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td class="text-center" style="font-size: 18px;font-weight: bolder" colspan="5">جمع کل</td>
                        <td>{{(factor.amount*10).toLocaleString()}} ریال</td>
                        <td></td>
                        <td></td>
                    </tr>
                </table>
            </b-col>
        </b-row>

    </div>
</template>

<script>
/* eslint-disable global-require */
import {BLink, BButton, BImg, BRow, BCol} from 'bootstrap-vue'
import VuexyLogo from '@core/layouts/components/Logo.vue'
import store from '@/store/index'
import axiosIns from "@/libs/axios";

export default {
    components: {
        BCol,
        BRow,
        VuexyLogo,
        BLink,
        BButton,
        BImg,
    },
    data() {
        return {
            factor: null
        }
    },
    methods:{
        getFactor(id){
            axiosIns.post('audit/factor/'+id)
            .then(response => {
                this.factor = response.data.factor
            })
            .catch(() => { this.errorFetching(); })
        },
        getFeePercentage(amount, type) {
            // تعریف نرخ‌های کارمزد
            const buyFeeRate1 = 0.004;  // 0.4% برای خرید زیر ۵ میلیون
            const buyFeeRate2 = 0.003;  // 0.3% برای خرید بین ۵ تا ۱۰ میلیون
            const buyFeeRate3 = 0.0025; // 0.25% برای خرید بالای ۱۰ میلیون

            const sellFeeRate = 0.001; // 0.1% برای فروش (ثابت برای همه مبالغ)

            // بررسی نوع عملیات و مبلغ
            if (type === 'buy' || type ==='deposit') {
                if (amount < 5000000) {
                    return buyFeeRate1;
                } else if (amount >= 5000000 && amount <= 10000000) {
                    return buyFeeRate2;
                } else {
                    return buyFeeRate3;
                }
            } else if (type === 'sell' || type === 'withdraw' ) {
                return sellFeeRate;
            } else {
                throw new Error("نوع عملیات نامعتبر است. فقط 'buy' یا 'sell' مجاز است.");
            }
        }
    },
    created() {
        if(this.accessUserLogin['audit-factor']['single'] || this.activeUserInfo.role === 'admin')
            this.getFactor(this.$router.currentRoute.params.id)
    },
    computed: {
        fee(){
            if(this.factor){
                var data = JSON.parse(this.factor.data)
                if(data && data.fee_usdt)
                    //var fee = this.factor.type ==='deposit'?data.fee_usdt['buy']:data.fee_usdt['sell']
                    var fee = data.fee_usdt['buy']
                else
                    var fee = 51558

                return fee*10;
            }else
                return 0;

        }
    },
}
</script>

<style lang="scss">
@import '@core/scss/vue/pages/page-misc.scss';
#factor{
    .bold{
        font-weight: bolder;
    }
    color: black !important;
    p ,h3 ,h2{
        color: black !important;
        margin-bottom:0 !important;
    }
    .table td{
        background-color: white !important;
        padding: 0.35rem 0.5rem;
        color: black !important;
        width: 25px;
        height: 30px;
    }
    .table.service td{
        padding: 0.75rem 0.8rem;
    }
    .table th{
        background-color: #f8f8f8 !important;
        color: black !important;
        font-size: 16px;
        font-weight: bolder;
    }
}
</style>
