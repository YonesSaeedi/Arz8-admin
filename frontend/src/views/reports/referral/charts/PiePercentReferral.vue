<template>
    <b-card>
        <b-card-title class="mb-1">
           درصدی که معرفی شونده ها دارند
        </b-card-title>
        <b-card-sub-title class="mb-2">
            کسی که توسط کسی معرفی میشود چند درصد برایش در نظر گرفته شده است؟
        </b-card-sub-title>
        <e-charts
            ref="pie"
            autoresize
            :options="option"
            theme="theme-color"
            auto-resize
            :style="{height: '315px',width:'100%'}"
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
import 'echarts/lib/chart/pie'


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
                title: {
                    text: 'Stacked area chart',
                },
                tooltip: {
                    backgroundColor: 'rgba(50,50,50,0.7)',
                    trigger: 'item',
                    textStyle: {
                        fontFamily: 'iranyekan,"Montserrat", Helvetica, Arial, sans-serif',
                        color: '#fff'
                    },
                    formatter: function (params) {
                        return ` ${params.name}: <br> درصد: ${params.percent}%<br> تعداد: ${params.data.value.toLocaleString('en-US')} `;
                    }
                },
                legend: {
                    bottom: 'bottom',
                },
                grid: {
                    width: '95%',
                    left: '40px',
                    right: '4%',
                    containLabel: false,
                },
                series: [
                    {
                        name: 'Amount Via',
                        type: 'pie',
                        radius: '65%',
                        center: ['50%', '50%'],
                        data: this.data,
                        emphasis: {
                            itemStyle: {
                                shadowBlur: 10,
                                shadowOffsetX: 0,
                                shadowColor: 'rgba(0, 0, 0, 0.5)'
                            }
                        }
                    }
                ]
            },
        }
    },
}
</script>
