<template>
    <b-card title="تعداد معرفی ها در بازه های زمانی">
        <e-charts
            ref="line"
            autoresize
            :options="option"
            theme="theme-color"
            auto-resize
            :style="{height: '600px',width:'100%'}"
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
                color:['#fdb901'],
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
                                return `${ (val+' نفر') }`
                            },
                        }
                    }
                ],
                series: [
                    {
                        name: 'کاربران',
                        type: 'bar',
                        tooltip: {
                            valueFormatter: function (value) {
                                return value + ' ml';
                            }
                        },
                        data: this.data.data
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
