<template>
<div>
    <b-card v-if="trade" id="trade-single" class="border" style="border-width: 5px !important;"
            :class="trade.status!='partial'?`border-${resolveStatusVariant(trade.status)}` :'border-success'">
        <b-card-body class="px-0 px-md-1">
            <div class="text-center">
                <div class="d-flex">
                    <div class="quoteAsset ml-auto">
                        <i class="cf" style="font-size: 110px" v-if="isFontExist(trade.quoteSymbol)" :class="'cf-'+ trade.quoteSymbol.toLowerCase()" :style="{color:colorSymbol(trade.quoteSymbol)}"></i>
                        <img :src="baseURL+'images/currency/' + iconSymbol(trade.quoteSymbol)" width="110px" height="110px" v-else/>
                    </div>
                    <div class="baseAsset mr-auto">
                        <i class="cf" style="font-size: 110px" v-if="isFontExist(trade.baseSymbol)" :class="'cf-'+ trade.baseSymbol.toLowerCase()" :style="{color:colorSymbol(trade.baseSymbol)}"></i>
                        <img :src="baseURL+'images/currency/' + iconSymbol(trade.baseSymbol)" width="110px" height="110px" v-else/>
                    </div>
                </div>
                <div class="text-capitalize mt-1">{{trade.marketName}} ({{trade.marketSymbol}})</div>
            </div>

            <b-row class="mt-4 col-lg-7 px-0 mx-auto">
                <b-alert class="w-100 mt-1 mb-3" variant="warning" show  v-if="trade.status =='partial'">
                    <div class="alert-body">
                        مقدار اولیه این سفارش {{trade.first_amount_base+trade.baseSymbol}} بوده است که
                        {{Math.abs(((trade.first_amount_base - trade.amount_base) / trade.first_amount_base *100)-100).toFixed(2)+'%'}}
                        آن معادل {{toFixFloat(trade.amount_base)+trade.baseSymbol}} آن انجام شده است.
                    </div>
                </b-alert>

                <b-col cols="12" md="6" class="border p-1 pt-2 mb-1">
                    <span class="font-weight-bolder">شناسه:</span> <span>#{{trade.id}}</span>
                    <b-badge
                        pill
                        :variant="`${resolveStatusVariant(trade.status)}`"
                        class="text-capitalize ml-1"
                    >
                        {{$t(trade.status)}}
                    </b-badge>
                </b-col>
                <b-col cols="12" md="6" class="border px-1 py-75 mb-1">
                    <span class="font-weight-bolder">ثبت:</span> <span>{{trade.date}}</span>
                    <br>
                    <span class="font-weight-bolder">بروزرسانی:</span> <span>{{trade.update}}</span>
                </b-col>

                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">کاربر:</span>
                    <b-link :to="{ name: 'user-single', params: { id: trade.user.id } }">
                        <span>{{trade.user.name+' '+trade.user.family}}</span>
                    </b-link>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">ایمیل:</span> <span>{{trade.user.email}}</span>
                </b-col>

                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">نوع:</span>
                    <span class="ml-50 bullet bullet-sm" :class="trade.type =='buy'?'bullet-success':'bullet-danger'"/>
                    <span>{{$t(trade.type)}}</span>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">مدل:</span>
                    <span>{{$t(trade.model)}}</span>
                </b-col>

                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">مقدار:</span> <span>{{toFixFloat(trade.amount_base)}} {{trade.baseSymbol}}</span>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">مقدار معادل:</span> <span>{{toFixFloat(trade.amount_quote)}} {{trade.quoteSymbol}}</span>
                </b-col>


                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">پرداختی:</span>
                    <span>
                        {{trade.type != 'buy' ? toFixFloat(trade.amount_base) : toFixFloat(trade.amount_quote)}}
                        {{trade.type != 'buy' ? trade.baseSymbol : trade.quoteSymbol}}
                    </span>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">دریافتی:</span>
                    <span>
                         {{trade.type == 'buy' ? toFixFloat(trade.amount_base) : toFixFloat(trade.amount_quote)}}
                         {{trade.type == 'buy' ? trade.baseSymbol : trade.quoteSymbol}}
                    </span>
                </b-col>

                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">کارمزد:</span>
                    <span>
                        {{'%' + trade.wage}} {{$t('equivalent')}} {{toFixFloat(trade.wage_amount)}}
                        {{trade.type == 'buy' ? (trade.baseSymbol) : (trade.quoteSymbol)}}
                    </span>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">خالص دریافتی:</span>
                    <span>
                        {{trade.type == 'buy' ? toFixFloat(trade.amount_base - trade.wage_amount) : toFixFloat(trade.amount_quote - trade.wage_amount)}}
                        {{trade.type == 'buy' ? (trade.baseSymbol) : (trade.quoteSymbol)}}
                    </span>
                </b-col>

                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">قیمت هر واحد:</span> <span>{{toFixFloat(trade.fee)}} <small>{{trade.quoteSymbol}}</small></span>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">توقف (stop):</span> <span>{{trade.stop ? toFixFloat(trade.stop) : '---'}} <small>{{trade.quoteSymbol}}</small></span>
                </b-col>


                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">طریقه:</span> <span class="text-capitalize">{{trade.via}}</span>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">آی پی (ip):</span> <span>{{trade.ip}}</span>
                </b-col>

                <b-col cols="12" md="6" class="border p-1 mb-1" v-if="trade.referral_amount">
                    <span class="font-weight-bolder">مبلغ رفرال:</span>
                    <span>{{trade.referral_amount.toLocaleString()}} <small>تومان</small></span>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1" v-if="trade.referral">
                    <span class="font-weight-bolder">درصد معرفی کننده / معرفی شونده:</span>
                    <span>{{trade.referral.percent_caller}} / {{trade.referral.percent_referral}} </span>
                </b-col>

                <b-col cols="12" md="6" class="border p-1 mb-1" v-if="trade.admin">
                    <span class="font-weight-bolder">آخرین تغییر توسط ادمین:</span>
                    <b-link :to="{ name: 'admin-view', params: { id: trade.admin.id } }">
                        <span>{{trade.admin.name}}</span>
                    </b-link>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">شناسه سفارش در بایننس:</span> <span>{{trade.binance_orderId}}</span>
                </b-col>

                <b-col cols="12" md="12" class="border p-1 mb-1">
                    <span class="font-weight-bolder cursor-pointer" :class="visible ? null : 'collapsed'"
                          :aria-expanded="visible ? 'true' : 'false'"
                          aria-controls="collapse-4"
                          @click="visible = !visible"> + داده های ذخیره شده:</span>
                    <b-collapse id="collapse-4" v-model="visible">
                        <div dir="ltr" class="text-right">
                            <pre v-html="JSON.stringify(JSON.parse(trade.data), null, 4)"></pre>
                        </div>
                    </b-collapse>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <b-link :to="{ name: 'tr-internal', query: { id_trade: trade.id } }">
                        تراکنش های داخلی این معامله
                    </b-link>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <b-link :to="{ name: 'tr-crypto', query: { id_trade: trade.id } }">
                        تراکنش های رمز ارز این معامله
                    </b-link>
                </b-col>

                <hr>
                <h5 class="mt-2">
                    معاملات این سفارش:
                    <br>
                    <small>
                        هر سفارش ممکن است طی چند معامله انجام شود که در جدول زیر قابل مشاهده است.
                    </small>
                </h5>
                <div class="table-responsive mb-1">
                    <table class="table b-table table-striped">
                        <thead>
                            <tr>
                                <td>شناسه</td>
                                <td>زمان</td>
                                <td>مقدار</td>
                                <td>قیمت واحد</td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, indextr) in trade.fills">
                                <td class="text-nowrap">{{item.id}}</td>
                                <td class="text-nowrap">{{item.dateTime}}</td>
                                <td class="text-nowrap"> {{ toFixFloat(item.amount) }} {{trade.baseSymbol}}</td>
                                <td class="text-nowrap"> {{ toFixFloat(item.fee) }} {{trade.quoteSymbol}}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <hr class="w-100" v-if="trade.status == 'open'">
                <b-col cols="12" md="12" class="mb-1 d-flex p-0" v-if="trade.status == 'open'">
                    <b-button variant="info" block class="float-right d-flex" @click="statusTrade()" :disabled="isLoadingStatus">
                        <feather-icon icon="CheckCircleIcon" class="ml-auto mr-50"/>
                        <span class="align-middle">استعلام وضعیت فعلی معامله</span>
                        <div class="line-height-0 ml-25 mr-auto"><b-spinner v-if="isLoadingStatus" small></b-spinner></div>
                    </b-button>
                </b-col>

            </b-row>
        </b-card-body>
    </b-card>
    <div v-if="!accessUserLogin['trades']['single'] && activeUserInfo.role !== 'admin'">
        <NotAccessed/>
    </div>
</div>
</template>

<script>
    import BCardActions from '@core/components/b-card-actions/BCardActions.vue'
    import {
        BCard, BCardHeader, BBadge, BCollapse, BLink, BCardBody, BRow, BCol, BInputGroup, BInputGroupAppend, BFormGroup,
        BAlert,BFormInput, BTable, BButton,BSpinner
    } from 'bootstrap-vue'
    import vSelect from 'vue-select'
    import axiosIns from "@/libs/axios";
    import axios from "@/libs/axios";
    import ToastificationContent from "@core/components/toastification/ToastificationContent";
    import Table from "@/views/vuexy/table/bs-table/Table";
    import NotAccessed from "@/views/vuexy/pages/miscellaneous/NotAccessed";

    export default {
        name: "Order",
        data () {
            return {
                trade: null,
                visible: false,
                isLoadingStatus: false,
            }
        },
        components: {
            Table,
            BTable,
            BLink,
            BCard,
            BAlert,
            BBadge,
            BRow,
            BCol,
            BCardActions,
            BCardHeader,
            BCardBody,
            BCollapse,
            BButton,
            BInputGroup,BFormGroup, BInputGroupAppend,BFormInput,BSpinner,
            vSelect,NotAccessed
        },
        methods:{
            getOrder(id){
                axiosIns.post('trades/info/'+id)
                    .then(response => {
                        this.trade = response.data.trade
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
            },
            resolveStatusVariant(status){
                if (status === 'open') return 'warning'
                if (status === 'success') return 'success'
                if (status === 'expired') return 'secondary'
                if (status === 'canceled') return 'dark'
                if (status === 'revoke') return 'danger'
                if (status === 'partial') return 'warning'
                return 'warning'
            },
            statusTrade(){
                this.isLoadingStatus = true
                axiosIns.post('trades/status/'+this.trade.id)
                    .then(response => {
                        if (response.data.status == true){
                            this.$swal({icon: 'success',title: 'موفق!',text: response.data.msg,confirmButtonText: 'باشه'})
                        }else{
                            this.$swal({icon: 'warning',title: 'ناموفق!',text: response.data.msg,confirmButtonText: 'باشه'})
                        }
                        this.isLoadingStatus = false
                        this.getOrder();
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
                        this.isLoadingStatus = false
                    })
            }
        },
        created() {
            if(this.accessUserLogin['trades']['single'] || this.activeUserInfo.role === 'admin')
                this.getOrder(this.$router.currentRoute.params.id)
        }
    }
</script>

<style lang="scss">
    #trade-single {
        .quoteAsset{
            opacity: 0.5;
            margin-right: -50px;
            z-index: 0;
        }
        .baseAsset{
            z-index: 1;
        }
    }
</style>
