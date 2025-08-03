<template>
    <b-card title="حجم و تعداد ثبت تراکنش رفرال در بازه های زمانی">
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
                        name: 'تعداد تراکنش',
                        type: 'line',
                        stack: 'Total',
                        areaStyle: {},
                        showSymbol: false,
                        lineStyle: {
                            width: 0,
                        },
                        data: this.data.data.count,
                    },
                    {
                        name: 'حجم تراکنش',
                        type: 'line',
                        showSymbol: false,
                        areaStyle: {},
                        lineStyle: {
                            width: 0,
                        },
                        data: this.data.data.amount,
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
