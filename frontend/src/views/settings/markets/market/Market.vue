<template>
<div>
    <div v-if="accessUserLogin['setting-markets']['single'] || activeUserInfo.role === 'admin'">
        <b-row>
            <b-col lg="4" sm="6">
                <statistic-card-horizontal
                    icon="DiscIcon"
                    color="success"
                    :statistic="statistic && statistic.amountTradeBase?
                      toFixFloat(parseFloat(statistic.amountTradeBase.toFixed(market.baseAsset.percent)))+' '+market.baseAsset.symbol :'0'"
                    statistic-title="حجم معاملات"
                    id="amountTradeBase"
                />
                <b-tooltip target="amountTradeBase" variant="success">
                    حجم معاملات موفق در این بازار
                </b-tooltip>
            </b-col>

            <b-col lg="4" sm="6">
                <statistic-card-horizontal
                    icon="ActivityIcon"
                    color="danger"
                    :statistic="statistic && statistic.amountTradeQuote?
                        toFixFloat(parseFloat(statistic.amountTradeQuote.toFixed(market.quoteAsset.percent)))+' '+market.quoteAsset.symbol :'0'"
                    statistic-title="حجم معاملات هدف"
                    id="amountTradeQuote"
                />
                <b-tooltip target="amountTradeQuote" variant="success">
                    حجم معاملات موفق در این بازار بر اساس ارز هدف
                </b-tooltip>
            </b-col>

            <b-col lg="4" sm="6">
                <statistic-card-horizontal
                    icon="MonitorIcon"
                    color="info"
                    :statistic="statistic && statistic.allTradeSuccess ? statistic.allTradeSuccess.toLocaleString() :'0'"
                    statistic-title="کل معاملات موفق"
                    id="allTradeSuccess"
                />
                <b-tooltip target="allTradeSuccess" variant="info">
                    کل معاملات انجام شده در این بازار
                </b-tooltip>
            </b-col>

            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="CpuIcon"
                    color="warning"
                    :statistic="statistic && statistic.allTradeOpen ? statistic.allTradeOpen.toLocaleString() :'0'"
                    statistic-title="معاملات باز"
                    id="allBalance"
                />
                <b-tooltip target="allBalance" variant="warning">
                    تعداد کل معاملات باز در این مارکت
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="TrendingDownIcon"
                    color="success"
                    :statistic="statistic && statistic.allTradeBuy ? statistic.allTradeBuy.toLocaleString() :'0'"
                    statistic-title="تعداد خرید ها"
                    id="allTradeBuy"
                />
                <b-tooltip target="allTradeBuy" variant="success">
                    تعداد کل معاملات خرید موفق
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="TrendingUpIcon"
                    color="danger"
                    :statistic="statistic && statistic.allTradeSell ? statistic.allTradeSell.toLocaleString() :'0'"
                    statistic-title="تعداد فروش ها"
                    id="allTradeSell"
                />
                <b-tooltip target="allTradeSell" variant="danger">
                    تعداد کل معاملات فروش موفق
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="ShieldOffIcon"
                    color="dark"
                    :statistic="statistic && statistic.allTradeCancel ? statistic.allTradeCancel.toLocaleString() :'0'"
                    statistic-title="لغو شده"
                    id="allTradeCancel"
                />
                <b-tooltip target="allTradeCancel" variant="dark">
                    تعداد کل معاملات لغو شده
                </b-tooltip>
            </b-col>

            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="ZapIcon"
                    color="success"
                    :statistic="statistic && statistic.wageTradeBuy?
                        toFixFloat(parseFloat(statistic.wageTradeBuy.toFixed(market.baseAsset.percent)))+' '+market.baseAsset.symbol :'0'"
                    statistic-title="کارمزد خرید ها"
                    id="wageTradeBuy"
                />
                <b-tooltip target="wageTradeBuy" variant="success">
                   کل کارمزد خرید های موفق و تعلق گرفته به صرافی
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="ZapIcon"
                    color="danger"
                    :statistic="statistic && statistic.wageTradeSell ?
                        toFixFloat(parseFloat(statistic.wageTradeSell.toFixed(market.quoteAsset.percent)))+' '+market.quoteAsset.symbol :'0'"
                    statistic-title="کارمزد فروش ها"
                    id="wageTradeSell"
                />
                <b-tooltip target="wageTradeSell" variant="danger">
                    کل کارمزد فروش های موفق و تعلق گرفته به صرافی
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="WindIcon"
                    color="info"
                    :statistic="statistic && statistic.avgTradeWagePercent ? '%'+toFixFloat(parseFloat(statistic.avgTradeWagePercent.toFixed(5))) :'0'"
                    statistic-title="میانگین کارمزد"
                    id="avgTradeWagePercent"
                />
                <b-tooltip target="avgTradeWagePercent" variant="info">
                    میانگین درصد کارمزد دریافتی
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="StarIcon"
                    color="warning"
                    :statistic="statistic && statistic.maxTradeAmount ?
                      toFixFloat(parseFloat(statistic.maxTradeAmount.toFixed(market.baseAsset.percent)))+' '+market.baseAsset.symbol :'0'"
                    statistic-title="بیشترین حجم"
                    id="maxTradeAmount"
                />
                <b-tooltip target="maxTradeAmount" variant="warning">
                    بیشترین حجمی که در یک معامله موفق انجام شده است.
                </b-tooltip>
            </b-col>
        </b-row>

        <b-card v-if="market" id="st-market">
            <b-card-body class="px-0 px-md-1">
                <div class="text-center">
                    <div class="d-flex">
                        <div class="quoteAsset ml-auto">
                            <i class="cf" style="font-size: 110px" v-if="isFontExist(market.quoteAsset.symbol)" :class="'cf-'+ market.quoteAsset.symbol.toLowerCase()" :style="{color:colorSymbol(market.quoteAsset.symbol)}"></i>
                            <img :src="baseURL+'images/currency/' + iconSymbol(market.quoteAsset.symbol)" width="110px" height="110px" v-else/>
                        </div>
                        <div class="baseAsset mr-auto">
                            <i class="cf" style="font-size: 110px" v-if="isFontExist(market.baseAsset.symbol)" :class="'cf-'+ market.baseAsset.symbol.toLowerCase()" :style="{color:colorSymbol(market.baseAsset.symbol)}"></i>
                            <img :src="baseURL+'images/currency/' + iconSymbol(market.baseAsset.symbol)" width="110px" height="110px" v-else/>
                        </div>
                    </div>
                    <div class="text-capitalize mt-1">{{market.data.name.fa}} ({{market.symbol}})</div>
                </div>

                <b-row class="col-lg-6 px-0 mx-auto">
                    <hr class="w-100 my-2">
                    <validation-observer ref="simpleRules" class="w-100" >
                        <b-form ref="form" @submit.stop.prevent="handleSubmit">
                            <b-col cols="12">
                                <b-form-group label="چارت" label-cols-md="4">
                                    <validation-provider #default="{ errors }" rules="required">
                                        <b-form-input v-model="market.chart" placeholder="چارت" :state="errors.length > 0 ? false:null" class="text-center"/>
                                    </validation-provider>
                                </b-form-group>
                            </b-col>

                            <b-col cols="12">
                                <b-form-group label="وضعیت" label-cols-md="4">
                                    <validation-provider #default="{ errors }" rules="required">
                                        <b-form-select
                                            v-model="market.status"
                                            :options="[{'text':'فعال','value':'active'},{'text':'غیرفعال','value':'inactive'}]"
                                            :state="errors.length > 0 ? false:null"
                                        />
                                    </validation-provider>
                                </b-form-group>
                            </b-col>

                            <b-col cols="8" offset-md="4">
                                <b-row>
                                    <b-col cols="8">
                                        <b-button variant="primary" block class="text-center d-flex align-items-center justify-content-center" type="submit" :disabled="isLoading">
                                            <div>ذخیره تغییرات</div>
                                            <div class="line-height-0 ml-25"><b-spinner v-if="isLoading" small></b-spinner></div>
                                        </b-button>
                                    </b-col>
                                    <b-col cols="4">
                                        <b-button variant="outline-danger" block class="text-center d-flex align-items-center justify-content-center" @click="remove()">
                                            حذف
                                        </b-button>
                                    </b-col>
                                </b-row>
                            </b-col>

                        </b-form>
                    </validation-observer>
                </b-row>

            </b-card-body>
        </b-card>
        <div class="w-full mt-2" dir="ltr" ref="chart" :style="this.expandChart ?{height: 'calc(100%)'}:''" id="div-chart-loading">
            <VueTradingView :options="optionsChart" :style="this.expandChart ?{height: 'calc(100%)'}:{height: '450px'}" v-if="isLoadChart"/>
        </div>
    </div>
    <div v-else>
        <NotAccessed/>
    </div>
</div>
</template>

<script>
    import BCardActions from '@core/components/b-card-actions/BCardActions.vue'
    import {
        BCard, BCardHeader, BBadge, BCollapse, BLink, BCardBody, BRow,BForm, BCol, BInputGroup, BInputGroupAppend, BFormGroup, BFormInput, BTable, BButton,BAlert,
        BInputGroupPrepend, BFormSelect,BFormFile, BFormRadioGroup, BTab,BTabs ,BSpinner,BTooltip, BOverlay

    } from 'bootstrap-vue'
    import vSelect from 'vue-select'
    import axiosIns from "@/libs/axios";
    import ToastificationContent from "@core/components/toastification/ToastificationContent";

    import Table from "@/views/vuexy/table/bs-table/Table";
    import { ValidationProvider, ValidationObserver } from 'vee-validate'
    import {
        required,between
    } from '@validations'
    import VueTradingView from 'vue-trading-view/src/vue-trading-view';
    import StatisticCardHorizontal from '@core/components/statistics-cards/StatisticCardHorizontal.vue'
    import NotAccessed from "@/views/vuexy/pages/miscellaneous/NotAccessed";

    export default {
        data () {
            return {
                isLoading: false,
                market: null,

                expandChart: false,
                invisible: true,
                width: window.innerWidth,
                isLoadChart: false,
                height:  500,
                themeChartIsDark: null,
                optionsChart:{
                    symbol: null,
                    autosize: true,
                    enable_publishing:false,
                    interval: "60",
                    style: "1",
                    hide_side_toolbar:false,
                    allow_symbol_change:false,
                    theme: null,
                    timezone: null,
                    studies: [
                        "CRSI@tv-basicstudies"
                    ],
                },
                statistic: [],
            }
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
            BAlert,
            BInputGroup,BFormGroup, BInputGroupAppend,BFormInput,BFormFile,
            vSelect, ValidationProvider, ValidationObserver,BInputGroupPrepend,BForm, BFormSelect,
            BFormRadioGroup,BTab,BTabs,BSpinner,BTooltip, BOverlay,
            VueTradingView, StatisticCardHorizontal, NotAccessed
        },
        methods:{
            getMarket(id){
                axiosIns.post('/setting/markets/info/'+id)
                .then(response => {
                    this.market = response.data.market;
                    this.statistic = response.data.statistic;
                    this.isLoadChart = true;
                    this.optionsChart.symbol = this.market.symbol.replace("-", "");
                })
                .catch((error) => { console.log(error); this.errorFetching(); })
            },
            handleSubmit() {
                this.$refs.simpleRules.validate().then(success => {
                    if (success) {
                        this.isLoading = true;
                        axiosIns.post('setting/markets/edit/'+this.market.id+'',{
                            status: this.market.status,
                            chart: this.market.chart,
                        }) .then(response => {
                            if(response.data.status == true){
                                this.getMarket(this.$router.currentRoute.params.id)
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
            remove(){
                this.$swal({
                    title: 'از حذف اطمینان دارید؟',
                    text: 'بازار در صورتی حذف میشود که داده ای نداشده باشد.',
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
                        return  axiosIns.delete('setting/markets/remove/'+this.market.id )
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
                            this.$router.push('/markets')
                            this.$swal({icon: 'success',title: 'موفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }else{
                            this.$swal({icon: 'error',title: 'ناموفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }
                    }
                })
            }
        },
        watch:{
            networks(val){
                var networkOptions = [];
                val.map(function(item) {
                    var obj = {text:(item.name), value:item.id };
                    networkOptions.push(obj);
                });
                var obj = {text:'شبکه را انتخاب کنید', value:null, disabled:true, hidden:true};
                networkOptions.push(obj);
                this.networkOptions = networkOptions;
            }
        },
        created() {
            if(this.accessUserLogin['setting-markets']['single'] || this.activeUserInfo.role === 'admin')
                this.getMarket(this.$router.currentRoute.params.id)
        }
    }
</script>

<style lang="scss">
#st-market{
    .demo-inline-spacing.mt-0 .custom-radio{
        margin-top: 0px !important;
    }

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
