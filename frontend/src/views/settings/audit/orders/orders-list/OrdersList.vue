<template>

    <div>
        <!-- Filters -->
        <orders-list-filters
            :type-filter.sync="typeFilter" :type-options="typeOptions"
            :status-filter.sync="statusFilter" :status-options="statusOptions"
            :date-start-filter.sync="dateStartFilter" :date-stop-filter.sync="dateStopFilter"
            :amount-start-filter.sync="amountStartFilter" :amount-stop-filter.sync="amountStopFilter"
            :via-filter.sync="viaFilter" :via-options="viaOptions"
            :id-filter.sync="idFilter"
            :dateStartPriod="dateStartPriod" :dateStopPriod="dateStopPriod"
            @refetchData="refetchData" :isLoading="isLoading"
        />

        <!-- Table Container Card -->
        <b-card no-body class="mb-0">
            <div class="m-2">
                <b-row>
                    <!-- balance -->
                    <b-col class="border py-1" cols="12" md="6" :offset-md="isCrypto?'0':'3'">
                        <b-row class="align-items-center">
                            <b-col cols="6" md="6">
                                موجودی:
                            </b-col>
                            <b-col cols="6" md="6">
                                <b-form-input
                                    v-model="balance"
                                    class="d-inline-block mr-1 text-center" dir="ltr"
                                    placeholder="موجودی"
                                />
                            </b-col>
                        </b-row>
                        <hr>
                        <b-row class="align-items-center">
                            <b-col cols="6" md="6">
                                مقدار فروش به ما:
                            </b-col>
                            <b-col class="text-center" cols="6" md="6">
                                <span v-if="calculationTable">
                                    {{(calculationTable&&calculationTable.sell.sum_amount_usdt)?toFixFloat(calculationTable.sell.sum_amount_usdt):0}} دلار
                                </span>
                                <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                            </b-col>
                        </b-row>
                        <hr>
                        <b-row class="align-items-center">
                            <b-col cols="6" md="6">
                                نرخ میانگین فروش به ما:
                            </b-col>
                            <b-col class="text-center" cols="6" md="6">
                                <span v-if="calculationTable">
                                    {{(calculationTable&&calculationTable.sell.avg_fee_usdt)?toFixFloat(calculationTable.sell.avg_fee_usdt):0}} تومان
                                </span>
                                <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                            </b-col>
                        </b-row>
                        <hr>
                        <b-row class="align-items-center">
                            <b-col cols="6" md="6">
                                مقدار خرید ما:
                            </b-col>
                            <b-col class="text-center" cols="6" md="6">
                                <span v-if="calculationTable">
                                    {{(calculationTable&&calculationTable.buy.sum_amount_usdt)?toFixFloat(calculationTable.buy.sum_amount_usdt):0}} دلار
                                </span>
                                <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                            </b-col>
                        </b-row>
                        <hr>
                        <b-row class="align-items-center">
                            <b-col cols="6" md="6">
                                نرخ میانگین خرید از ما:
                            </b-col>
                            <b-col class="text-center" cols="6" md="6">
                                <span v-if="calculationTable">
                                    {{(calculationTable&&calculationTable.buy.avg_fee_usdt)?toFixFloat(calculationTable.buy.avg_fee_usdt):0}} تومان
                                </span>
                                <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                            </b-col>
                        </b-row>
                        <hr v-if="!isCrypto">
                        <b-row class="align-items-center" v-if="!isCrypto">
                            <b-col cols="6" md="6">
                                کارمزد خرید ها:
                            </b-col>
                            <b-col class="text-center text-danger" cols="6" md="6">
                                <span v-if="calculationTable">
                                    <span dir="ltr">{{(calculationTable&&calculationTable.cust.wage_buy_digital)?toFixFloat(calculationTable.cust.wage_buy_digital):0}}</span>
                                    تومان
                                </span>
                                <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                            </b-col>
                        </b-row>
                        <hr>
                        <b-row class="align-items-center">
                            <b-col cols="6" md="6">
                                سود:
                            </b-col>
                            <b-col class="text-center" :class="(profit && parseInt(profit)> 0)? 'text-success':'text-danger'" cols="6" md="6">
                                <span v-if="calculationTable">
                                    <span dir="ltr">{{profit?parseInt(profit).toLocaleString():0}}</span>
                                    تومان
                                </span>
                                <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                            </b-col>
                        </b-row>
                    </b-col>

                    <b-col class="border pb-1 mt-2 mt-md-0" cols="12" md="6" v-if="isCrypto">
                        <b-row>
                            <b-col class="border py-1" cols="12" md="6">
                                <b-row class="align-items-center">
                                    <b-col cols="6" md="4">
                                        کارمزد سفارشات:
                                    </b-col>
                                    <b-col class="text-center" cols="6" md="8">
                                        <span v-if="calculationTable">
                                            {{(calculationTable&&calculationTable.cust.order_wage_toman)?toFixFloat(calculationTable.cust.order_wage_toman):0}} تومان
                                            <br>
                                            <small>
                                                {{(calculationTable&&calculationTable.cust.order_wage_bnb)?toFixFloat(calculationTable.cust.order_wage_bnb):0}}BNB
                                            </small>
                                            <small>
                                                | {{(calculationTable&&calculationTable.cust.order_wage_cet)?toFixFloat(calculationTable.cust.order_wage_cet):0}}CET
                                            </small>
                                        </span>
                                        <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                                    </b-col>
                                </b-row>
                                <hr class="mt-75">
                                <b-row class="align-items-center">
                                    <b-col cols="6" md="6">
                                        رفررال سفارشات:
                                    </b-col>
                                    <b-col class="text-center" cols="6" md="6">
                                        <span v-if="calculationTable">
                                            {{(calculationTable&&calculationTable.cust.referral.orders)?toFixFloat(calculationTable.cust.referral.orders):0}} تومان
                                        </span>
                                        <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                                    </b-col>
                                </b-row>
                            </b-col>
                            <b-col class="border py-1" cols="12" md="6">
                                <b-row class="align-items-center">
                                    <b-col cols="6" md="6">
                                        کارمزد تریدها:
                                    </b-col>
                                    <b-col class="text-center" cols="6" md="6">
                                        <span v-if="calculationTable">
                                            {{(calculationTable&&calculationTable.cust.trade_wage_toman)?toFixFloat(calculationTable.cust.trade_wage_toman):0}} تومان
                                            <br>
                                            <small>
                                                {{(calculationTable&&calculationTable.cust.trade_wage_bnb)?toFixFloat(calculationTable.cust.trade_wage_bnb):0}} BNB
                                            </small>
                                        </span>
                                        <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                                    </b-col>
                                </b-row>
                                <hr class="mt-75">
                                <b-row class="align-items-center">
                                    <b-col cols="6" md="6">
                                        رفررال تریدها:
                                    </b-col>
                                    <b-col class="text-center" cols="6" md="6">
                                        <span v-if="calculationTable">
                                            {{(calculationTable&&calculationTable.cust.referral.trades)?toFixFloat(calculationTable.cust.referral.trades):0}} تومان
                                        </span>
                                        <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                                    </b-col>
                                </b-row>
                            </b-col>
                        </b-row>
                        <b-col class="p-0 m-0 pt-1 mt-md-0" cols="12">
                            <b-row class="align-items-center">
                                <b-col cols="6" md="6">
                                    سایر هزینه ها:
                                </b-col>
                                <b-col class="text-center" cols="6" md="6">
                                        <span v-if="calculationTable">
                                            {{(calculationTable&&calculationTable.cust.cust_toman)?toFixFloat(calculationTable.cust.cust_toman):0}} تومان
                                        </span>
                                    <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                                </b-col>
                            </b-row>
                            <hr>
                            <b-row class="align-items-center">
                                <b-col cols="6" md="4">
                                    مانده BNB و CET:
                                </b-col>
                                <b-col class="text-center" cols="6" md="8">
                                    <span v-if="calculationTable" :class="(calculationTable&&calculationTable.cust.balance_bnb<0)&&'text-danger'">
                                        <small>{{(calculationTable&&calculationTable.cust.balance_bnb)?toFixFloat(calculationTable.cust.balance_bnb):0}}BNB</small>
                                    </span>
                                    <span v-if="calculationTable" :class="(calculationTable&&calculationTable.cust.balance_cet<0)&&'text-danger'">
                                        | <small>{{(calculationTable&&calculationTable.cust.balance_cet)?toFixFloat(calculationTable.cust.balance_cet):0}}CET</small>
                                    </span>
                                    <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                                </b-col>
                            </b-row>
                            <hr>
                            <b-row class="align-items-center">
                                <b-col cols="6" md="4">
                                    فی BNB و CET:
                                </b-col>
                                <b-col class="text-center" cols="6" md="8">
                                    <span v-if="calculationTable">
                                        BNB:{{(calculationTable&&calculationTable.cust.bnb.fee)?toFixFloat(calculationTable.cust.bnb.fee):0}} تومان
                                    </span>
                                    <span v-if="calculationTable">
                                        | CET:{{(calculationTable&&calculationTable.cust.cet.fee)?toFixFloat(calculationTable.cust.cet.fee):0}} تومان
                                    </span>
                                    <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                                </b-col>
                            </b-row>
                            <hr>
                            <b-row class="align-items-center">
                                <b-col cols="6" md="6">
                                    جمع کل هزینه ها:
                                </b-col>
                                <b-col class="text-center" :class="'text-danger'" cols="6" md="6">
                                    <span v-if="calculationTable">
                                        <span dir="ltr">{{custs?parseInt(custs).toLocaleString():0}}</span>
                                        تومان
                                    </span>
                                    <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                                </b-col>
                            </b-row>
                        </b-col>
                    </b-col>

                    <b-col class="border py-1 mt-2 mt-md-0 text-center" cols="12" md="6" offset-md="3" >
                        جمع نهایی:
                        <span class="mx-50 font-weight-bolder" :class="(profit-custs > 0)? 'text-success':'text-danger'" dir="ltr">
                            {{(profit-custs).toLocaleString()}}
                        </span>
                        تومان
                    </b-col>
                    <b-col class="px-0 mt-1 d-flex justify-content-between" cols="12" md="8" offset-md="2">
                        <b-button @click="subtract=subtract+1" size="sm" variant="outline-primary">
                            <feather-icon icon="ChevronsRightIcon"/>  روز قبلی
                        </b-button>
                        <b-button @click="subtract=subtract-1" size="sm" variant="outline-primary">
                            روز بعدی <feather-icon icon="ChevronsLeftIcon"/>
                        </b-button>
                    </b-col>
                </b-row>
            </div>

            <div class="m-2">
                <!-- Table Top -->
                <b-row>
                    <!-- Search -->
                    <b-col cols="12" md="3">
                        <div class="d-flex align-items-center justify-content-end">
                            <b-form-input
                                v-model="searchQuery"
                                class="d-inline-block mr-1"
                                placeholder="جستجو ..."
                            />
                        </div>
                    </b-col>

                    <!-- Per Page -->
                    <b-col cols="12" md="9" class="mb-1 mb-md-0 text-right">
                        <v-select
                            v-model="perPage"
                            :dir="$store.state.appConfig.isRTL ? 'rtl' : 'ltr'"
                            :options="perPageOptions"
                            :clearable="false"
                            class="per-page-selector d-inline-block mx-50"
                        />
                    </b-col>
                </b-row>
            </div>

            <b-table
                ref="refOrdersListTable"
                class="position-relative"
                :items="fetchOrders"
                responsive
                :fields="tableColumns"
                primary-key="id"
                :sort-by.sync="sortBy"
                show-empty
                striped hover
                empty-text="داده ای برای نمایش وجود ندارد"
                :sort-desc.sync="isSortDirDesc"
            >

                <!-- Column: id -->
                <template #cell(id)="data">
                    <div class="text-nowrap">
                        <b-link
                            :to="{ name: 'order-view', params: { id: data.item.id } }"
                            class="font-weight-bold d-block text-nowrap"
                        >
                            #{{ data.item.id }}
                        </b-link>
                    </div>
                </template>

                <!-- Column: coin -->
                <template #cell(coin)="data">
                    <Currency :item="data.item"/>
                </template>

                <!-- Column: Name Family -->
                <template #cell(nameFamily)="data">
                    <User :item="data.item"/>
                </template>


                <!-- Column: type -->
                <template #cell(type)="data">
                    <b-avatar
                        :id="`orders-row-${data.item.id}`"
                        size="32"
                        :variant="data.item.type == 'buy'? `light-success` : `light-danger`"
                    >
                        <feather-icon
                            :icon="data.item.type == 'buy' ? 'TrendingUpIcon' : 'TrendingDownIcon'"
                        />
                    </b-avatar>
                    <span>{{data.item.type == 'buy' ? 'خرید' :'فروش'}}</span>
                </template>

                <!-- Column: amount usdt -->
                <template #cell(amount_usdt)="data">
                    <div class="text-nowrap">
                        <span>{{data.item.amount_usdt? toFixFloat(data.item.amount_usdt):0}} <small>دلار</small></span>
                    </div>
                </template>

                <!-- Column: fee usdt -->
                <template #cell(fee_usdt)="data">
                    <div class="text-nowrap vazir">
                        <span>{{data.item.fee_usdt? parseInt(data.item.fee_usdt).toLocaleString():0}} <small>تومان</small></span>
                    </div>
                </template>

                <!-- Column: amount -->
                <template #cell(amount)="data">
                    <div class="text-nowrap vazir">
                        <span>{{data.item.amount.toLocaleString()}} <small>تومان</small></span>
                    </div>
                </template>

                <!-- Column: amount_coin -->
                <template #cell(amount_coin)="data">
                    <div class="text-nowrap">
                        <span>{{toFixFloat(data.item.amount_coin)}} <small>{{data.item.crypto ? data.item.crypto.symbol : data.item.symbol}}</small></span>
                    </div>
                </template>

                <!-- Column: date -->
                <template #cell(date)="data">
                    <div class="text-nowrap vazir">
                        {{ $t(data.item.date) }}
                    </div>
                </template>



            </b-table>
            <div class="mx-2 mb-2">
                <b-row>
                    <b-col
                        cols="12"
                        sm="6"
                        class="d-flex align-items-center justify-content-center justify-content-sm-start"
                    >
                        <span class="text-muted">Showing {{ dataMeta.from }} to {{ dataMeta.to }} of {{ dataMeta.of }} entries</span>
                    </b-col>
                    <!-- Pagination -->
                    <b-col
                        cols="12"
                        sm="6"
                        class="d-flex align-items-center justify-content-center justify-content-sm-end"
                    >

                        <b-pagination
                            v-model="currentPage"
                            :total-rows="totalRows"
                            :per-page="perPage"
                            first-number
                            last-number
                            class="mb-0 mt-1 mt-sm-0"
                            prev-class="prev-item"
                            next-class="next-item"
                        >
                            <template #prev-text>
                                <feather-icon
                                    icon="ChevronLeftIcon"
                                    size="18"
                                />
                            </template>
                            <template #next-text>
                                <feather-icon
                                    icon="ChevronRightIcon"
                                    size="18"
                                />
                            </template>
                        </b-pagination>

                    </b-col>

                </b-row>
            </div>
        </b-card>
    </div>
</template>
<script>
    import {
        BCard, BRow, BCol, BFormInput, BButton, BTable, BMedia, BAvatar, BLink,
        BBadge, BDropdown, BDropdownItem, BPagination,BTooltip,BFormGroup,BSkeleton
    } from 'bootstrap-vue'
    import vSelect from 'vue-select'
    import {ref, onUnmounted} from '@vue/composition-api'
    import {avatarText} from '@core/utils/filter'
    import OrdersListFilters from './OrdersListFilters.vue'
    import useOrdersList from './useOrdersList'
    import StatisticCardHorizontal from '@core/components/statistics-cards/StatisticCardHorizontal.vue'
    import jalaliMmoment from "jalali-moment";
    import Currency from '@/views/lists/Currency.vue'
    import User from '@/views/lists/User.vue'

    export default {
        data () {
            return {
                dateStartPriod:'',
                dateStopPriod:'',
                subtract:0,
            }
        },
        props: ['date','isCrypto'],
        components: {
            OrdersListFilters,
            StatisticCardHorizontal,
            Currency,User,
            BCard,
            BRow,
            BCol,
            BFormInput,BFormGroup,BSkeleton,
            BButton,
            BTable,
            BMedia,
            BAvatar,
            BLink,
            BBadge,
            BDropdown,
            BDropdownItem,
            BPagination,
            BTooltip,
            vSelect,
        },
        setup(props) {

            const typeOptions = [
                {label: 'خرید', value: 'buy'},
                {label: 'فروش', value: 'sell'}
            ]

            const viaOptions = [
                {label: 'وبسایت', value: 'website'},
                {label: 'اندروید', value: 'android'},
                {label: 'آی او اس', value: 'ios'}
            ]


            const statusOptions = [
            ]

            const {
                fetchOrders,
                tableColumns,
                perPage,
                currentPage,
                totalRows,
                dataMeta,
                perPageOptions,
                searchQuery,
                sortBy,
                isSortDirDesc,
                refOrdersListTable,
                refetchData,

                // UI
                resolveStatusVariant,

                // Extra Filters
                typeFilter,
                statusFilter,
                dateStartFilter,dateStopFilter,
                amountStartFilter,amountStopFilter,
                viaFilter,
                idFilter,
                calculationTable,fetchCalculation,
                balance,
                isLoading
            } = useOrdersList(props.isCrypto)

            return {
                fetchOrders,
                tableColumns,
                perPage,
                currentPage,
                totalRows,
                dataMeta,
                perPageOptions,
                searchQuery,
                sortBy,
                isSortDirDesc,
                refOrdersListTable,
                refetchData,

                // Filter
                avatarText,

                // UI
                resolveStatusVariant,

                typeOptions,
                statusOptions,
                viaOptions,

                // Extra Filters
                typeFilter,
                statusFilter,
                dateStartFilter, dateStopFilter,
                amountStartFilter, amountStopFilter,
                idFilter,
                viaFilter,
                calculationTable,fetchCalculation,
                balance,
                isLoading
            }
        },
        methods:{
          setDatePeriod(){
              const NowDate = jalaliMmoment()
              var day = NowDate.subtract(this.subtract, 'jDay');
              this.dateStartPriod = day.format('jYYYY/jMM/jDD 00:00');
              this.dateStopPriod = day.add(1,'jDay').format('jYYYY/jMM/jDD 00:00');

          }
        },
        watch:{
            subtract(){
                this.setDatePeriod()
            },
            period(){
                this.subtract = 0;
                this.setDatePeriod()
            },
            date(date){
                const day = jalaliMmoment(date,'jYYYY/jMM/jDD 00:00');
                this.dateStartPriod = day.format('jYYYY/jMM/jDD 00:00');
                this.dateStopPriod = day.add(1,'jDay').format('jYYYY/jMM/jDD 00:00');
            }
        },
        mounted() {
            //this.setDatePeriod();
            this.fetchCalculation();
            this.statusOptions = [
                {label: this.$i18n.t('pending'), value: 'pending'},
                {label: this.$i18n.t('success'), value: 'success'},
                {label: this.$i18n.t('return'), value: 'return'},
                {label: this.$i18n.t('suspend'), value: 'suspend'},
                {label: this.$i18n.t('unsuccessful'), value: 'unsuccessful'},
                {label: this.$i18n.t('canceled'), value: 'canceled'},
            ]
        },
        computed:{
            profit(){
                if(this.calculationTable && this.calculationTable.sell.sum_amount_usdt) {
                    var a = this.calculationTable.buy.sum_amount_usdt * this.calculationTable.buy.avg_fee_usdt
                    var b = this.calculationTable.buy.sum_amount_usdt * this.calculationTable.sell.avg_fee_usdt
                    return (a - b).toFixed();
                }else
                    return 0;
            },
            custs(){
                if(this.calculationTable) {
                    if(this.isCrypto){
                        var cust_toman = this.calculationTable.cust.cust_toman;
                        var wage_toman = this.calculationTable.cust.order_wage_toman + this.calculationTable.cust.trade_wage_toman;
                        var referral = this.calculationTable.cust.referral.trades + this.calculationTable.cust.referral.orders;
                        return (cust_toman + wage_toman + referral).toFixed();
                    }else{
                        return ( this.calculationTable.cust.wage_buy_digital).toFixed();
                    }

                }else
                    return 0;
            }
        }
    }
</script>

<style lang="scss" scoped>
    .per-page-selector {
        width: 90px;
    }
</style>

<style lang="scss">
    @import '@core/scss/vue/libs/vue-select.scss';
</style>
