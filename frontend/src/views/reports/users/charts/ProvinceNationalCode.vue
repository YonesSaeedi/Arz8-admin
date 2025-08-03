<template>
    <b-card>
        <b-card-title class="mb-1">
            استان کاربران بر اساس کد ملی
        </b-card-title>
        <b-card-sub-title class="mb-2">
            تعداد کاربران در استان های مختلف بر اساس کد ملی و زادگاه
        </b-card-sub-title>
        <e-charts
            ref="pie"
            autoresize
            :options="option"
            theme="theme-color"
            auto-resize
            :style="{height: '400px',width:'100%'}"
        />
    </b-card>
</template>

<script>
import {
    BCard, BCardTitle, BCardSubTitle,
} from 'bootstrap-vue'
import ECharts from 'vue-echarts'
import 'echarts/lib/component/tooltip'
import 'echarts/lib/component/legend'
import 'echarts/lib/chart/bar'


export default {
    components: {
        ECharts,
        BCard, BCardTitle, BCardSubTitle
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
                        data: this.data.map((i)=> i.name),
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
                        data: this.data.map((i)=> i.value)
                    },
                ]
            },
        }
    },
}
</script>
