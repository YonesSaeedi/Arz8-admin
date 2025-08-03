<template>
    <b-card title="تعداد ثبت نام در بازه های زمانی">
        <app-echart-stacked-area
            :option-data="option"
        />
    </b-card>
</template>

<script>
import { BCard } from 'bootstrap-vue'
import AppEchartStackedArea from '@core/components/charts/echart/AppEchartStackedArea.vue'

export default {
    components: {
        BCard,
        AppEchartStackedArea,
    },
    props:['data'],
    data() {
        return {
            option: {
                xAxisData: this.data.lable,
                series: [
                    {
                        name: 'همه کاربران',
                        type: 'line',
                        stack: 'Total',
                        areaStyle: {},
                        showSymbol: false,
                        lineStyle: {
                            width: 0,
                        },
                        data: this.data.data.all,
                    },
                    {
                        name: 'از طریق اندروید',
                        type: 'line',
                        stack: 'Total',
                        showSymbol: false,
                        areaStyle: {},
                        lineStyle: {
                            width: 0,
                        },
                        data: this.data.data.android,
                    },
                    {
                        name: 'از طریق سایت',
                        type: 'line',
                        stack: 'Total',
                        showSymbol: false,
                        areaStyle: {},
                        lineStyle: {
                            width: 0,
                        },
                        data: this.data.data.website,
                    },
                    {
                        name: 'از طریق ios',
                        type: 'line',
                        stack: 'Total',
                        showSymbol: false,
                        areaStyle: {},
                        lineStyle: {
                            width: 0,
                        },
                        data: this.data.data.ios,
                    },
                ],
            },
        }
    },
}
</script>
<style>
.echarts{
    width: 100%;
    margin: 0
}
</style>
