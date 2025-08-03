<template>
    <div>
        <b-row>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="ListIcon"
                    :statistic="statistic && statistic.orders? statistic.orders.toLocaleString() :'0'"
                    statistic-title="تعداد سفارشات"
                />
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="ChevronsDownIcon"
                    color="success"
                    :statistic="statistic && statistic.orders_buy_count? statistic.orders_buy_count.toLocaleString() :'0'"
                    statistic-title="تعداد خرید ها"
                />
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="ChevronsUpIcon"
                    color="danger"
                    :statistic="statistic && statistic.orders_sell_count? statistic.orders_sell_count.toLocaleString() :'0'"
                    statistic-title="تعداد فروش ها"
                />
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="ActivityIcon"
                    color="success"
                    :statistic="statistic && statistic.orders_buy_amount? statistic.orders_buy_amount.toLocaleString() :'0'"
                    statistic-title="مبلغ کل خرید ها(تومان)"
                />
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="ActivityIcon"
                    color="danger"
                    :statistic="statistic && statistic.orders_sell_amount? statistic.orders_sell_amount.toLocaleString() :'0'"
                    statistic-title="مبلغ کل فروش ها(تومان)"
                />
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="CommandIcon"
                    color="light"
                    :statistic="statistic && statistic.orders_max_amount? statistic.orders_max_amount.toLocaleString() :'0'"
                    statistic-title="مبلغ بیشترین سفارش(تومان)"
                />
            </b-col>

            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="ActivityIcon"
                    :statistic="statistic && statistic.trades? statistic.trades.toLocaleString() :'0'"
                    statistic-title="تعداد معاملات"
                />
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="ActivityIcon"
                    color="info"
                    :statistic="statistic && statistic.trades_amount? '$'+statistic.trades_amount.toLocaleString() :'0'"
                    statistic-title="ارزش معاملات به دلار"
                />
            </b-col>

            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="PackageIcon"
                    color="primary"
                    :statistic="statistic && statistic.amount_30days_orders? statistic.amount_30days_orders.toLocaleString() :'0'"
                    statistic-title="سفارشات 30 روز گذشته(تومان)"
                />
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="RepeatIcon"
                    color="primary"
                    :statistic="statistic && statistic.amount_30days_trades? statistic.amount_30days_trades.toLocaleString() :'0'"
                    statistic-title="معاملات 30 روز گذشته(تومان)"
                />
            </b-col>
        </b-row>
    </div>
</template>

<script>
import axiosIns from "@/libs/axios";
import ToastificationContent from "@core/components/toastification/ToastificationContent";
import StatisticCardHorizontal from '@core/components/statistics-cards/StatisticCardHorizontal.vue'
import {
    BRow, BCol
} from 'bootstrap-vue'

export default {
    props:['idUser'],
    data () {
        return {
            statistic: '',
        }
    },
    components: {
        StatisticCardHorizontal,
        BRow,BCol
    },
    methods:{
        getStatistic(){
            axiosIns.post('users/portfolio/' + this.idUser+'/statistic',{id:this.idUser})
                .then(response => {
                    this.statistic = response.data
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
    },
    mounted() {
        this.getStatistic()
    }
}
</script>
