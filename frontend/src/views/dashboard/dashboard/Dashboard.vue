<template>
    <section id="dashboard-ecommerce">
        <b-row class="match-height">
            <b-col lg="4">
                <b-row class="match-height">
                    <!-- Bar Chart - Orders -->
                    <b-col
                        lg="6"
                        md="3"
                        cols="6"
                    >
                        <ecommerce-order-chart v-if="data2.chart_min_order" :data="data2.chart_min_order"/>
                    </b-col>
                    <!--/ Bar Chart - Orders -->
                    <b-col
                        lg="6"
                        md="3"
                        cols="6"
                    >
                        <ecommerce-trade-chart v-if="data2.chart_min_trade" :data="data2.chart_min_trade"/>
                    </b-col>
                </b-row>
            </b-col>
            <b-col
                xl="8"
                md="6"
            >
                <ecommerce-statistics :data="data2.statistics" @getData="getData" :isLoading="isLoading"/>
            </b-col>
        </b-row>

        <b-row class="match-height">
            <b-col
                xl="4"
                md="6"
            >
                <ecommerce-medal :data="data.congratulations"/>


                <ecommerce-earnings-chart v-if="data2.chart_user_via" :data="data2.chart_user_via"/>
            </b-col>


            <!-- Revenue Report Card -->
            <b-col lg="8">
                <ecommerce-revenue-report :data2="data.revenue" :data="data2.chart_type" v-if="data2.chart_type"/>
            </b-col>
            <!--/ Revenue Report Card -->

            <!-- Price Statistics Card -->
            <b-col lg="12" v-if="activeUserInfo.role === 'admin'">
                <price-statistics :table-data="data2.report_table"/>
            </b-col>
            <!--/ Price Statistics Card -->

            <!-- Report Table Card -->
            <b-col lg="12" v-if="activeUserInfo.role === 'admin'">
                <reports-table :table-data="data2.report_table"/>
            </b-col>
            <!--/ Report Table Card -->

        </b-row>




        <b-row class="match-height disable-block" v-if="1!=1">
            <!-- Company Table Card -->
            <b-col lg="8">
                <ecommerce-company-table :table-data="data.companyTable"/>
            </b-col>
            <!--/ Company Table Card -->

            <!-- Developer Meetup Card -->
            <b-col
                lg="4"
                md="6"
            >
                <ecommerce-meetup :data="data.meetup"/>
            </b-col>
            <!--/ Developer Meetup Card -->

            <!-- Browser States Card -->
            <b-col
                lg="4"
                md="6"
            >
                <ecommerce-browser-states/>
            </b-col>
            <!--/ Browser States Card -->

            <!-- Goal Overview Card -->
            <b-col
                lg="4"
                md="6"
            >
                <ecommerce-goal-overview :data="data.goalOverview"/>
            </b-col>
            <!--/ Goal Overview Card -->

            <!-- Transaction Card -->
            <b-col
                lg="4"
                md="6"
            >
                <ecommerce-transactions :data="data.transactionData"/>
            </b-col>
            <!--/ Transaction Card -->
        </b-row>
    </section>
</template>

<script>
    import {BRow, BCol} from 'bootstrap-vue'

    import {getUserData} from '@/auth/utils'
    import EcommerceMedal from './EcommerceMedal.vue'
    import EcommerceStatistics from './EcommerceStatistics.vue'
    import EcommerceRevenueReport from './EcommerceRevenueReport.vue'
    import EcommerceOrderChart from './EcommerceOrderChart.vue'
    import EcommerceTradeChart from './EcommerceTradeChart.vue'
    import EcommerceEarningsChart from './EcommerceEarningsChart.vue'
    import EcommerceCompanyTable from './EcommerceCompanyTable.vue'
    import ReportsTable from './ReportsTable.vue'
    import PriceStatistics from './PriceStatistics.vue'
    import EcommerceMeetup from './EcommerceMeetup.vue'
    import EcommerceBrowserStates from './EcommerceBrowserStates.vue'
    import EcommerceGoalOverview from './EcommerceGoalOverview.vue'
    import EcommerceTransactions from './EcommerceTransactions.vue'
    import axiosIns from "@/libs/axios";

    export default {
        components: {
            BRow,
            BCol,

            EcommerceMedal,
            EcommerceStatistics,
            EcommerceRevenueReport,
            EcommerceOrderChart,
            EcommerceTradeChart,
            EcommerceEarningsChart,
            ReportsTable,
            PriceStatistics,
            EcommerceMeetup,
            EcommerceBrowserStates,
            EcommerceGoalOverview,
            EcommerceTransactions,
        },
        data() {
            return {
                data: {},
                data2: {},
                isLoading: false
            }
        },
        methods:{
            getData(fast = false){
                this.isLoading = true;
                axiosIns.post('/dashboard',{fast:fast})
                    .then(response => {
                        this.data2 = response.data
                        this.isLoading = false;
                        localStorage.setItem('dashboard', JSON.stringify(response.data))
                    })
                    .catch((error) => { console.log(error); this.errorFetching(); })
            }
        },
        created() {
            // data
            this.$http.get('/ecommerce/data')
                .then(response => {
                    this.data = response.data;
                    // ? Your API will return name of logged in user or you might just directly get name of logged in user
                    // ? This is just for demo purpose
                    const userData = getUserData()
                    this.data.congratulations.name = userData.name.split(' ')[0] || userData.name
                    this.data.congratulations.role = userData.role
                })

            if(localStorage.getItem('dashboard') !== null)
                this.data2 = JSON.parse(localStorage.getItem('dashboard'))
            this.getData();
        },
    }
</script>

<style lang="scss">
    @import '@core/scss/vue/pages/dashboard-ecommerce.scss';
    @import '@core/scss/vue/libs/chart-apex.scss';
</style>
