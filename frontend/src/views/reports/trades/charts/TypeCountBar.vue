<template>
    <b-card title="تعداد خرید و فروش در بازه های زمانی">
        <e-charts
            ref="line"
            autoresize
            :options="option"
            theme="theme-color"
            auto-resize
            :style="{height: '515px',width:'100%'}"
        />
    </b-card>
</template>

<script>
import { BCard } from 'bootstrap-vue'
import ECharts from 'vue-echarts'
import 'echarts/lib/component/tooltip'
import 'echarts/lib/component/legend'
import 'echarts/lib/chart/bar'

export default {
    components: {
        BCard,
        ECharts,
    },
    props:['data'],
    data() {
        return {
            option: {
                textStyle:{
                    fontFamily: 'iranyekan,"Montserrat", Helvetica, Arial, sans-serif',
                },
                color:['#51d50f','#b92d2d'],
                grid: {
                    width: '95%',
                    left: '40px',
                    right: '4%',
                    containLabel: true,
                },
                tooltip: {
                    trigger: 'axis',
                    axisPointer: {
                        type: 'cross',
                        label: {
                            backgroundColor: '#6a7985',
                        },
                    },
                },
                legend: {
                    data: ['خرید', 'فروش']
                },
                xAxis: [
                    {
                        type: 'category',
                        data: this.data.lable,
                    }
                ],
                yAxis: [
                    {
                        type: 'value',
                        splitLine: { show: false },
                        axisLabel: {
                            formatter(val) {
                                return `${ (parseInt(val/1000000000)+'B') }`
                            },
                        }
                    }
                ],
                series: [
                    {
                        name: 'خرید',
                        type: 'bar',
                        tooltip: {
                            valueFormatter: function (value) {
                                return value + ' ml';
                            }
                        },
                        data: this.data.data.buy
                    },
                    {
                        name: 'فروش',
                        type: 'bar',
                        tooltip: {
                            valueFormatter: function (value) {
                                return value + ' ml';
                            }
                        },
                        data: this.data.data.sell
                    },
                ]
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
