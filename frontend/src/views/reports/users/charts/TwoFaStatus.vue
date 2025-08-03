<template>
    <b-card>
        <b-card-title class="mb-1">
           وضعیت ورود دو مرحله ای
        </b-card-title>
        <b-card-sub-title class="mb-2">
            چند درصد کاربران دارای چه نوع ورود دو مرحله ای هستند؟
        </b-card-sub-title>
        <e-charts
            ref="pie"
            autoresize
            :options="option"
            theme="theme-color"
            auto-resize
            :style="{height: '450px',width:'100%'}"
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
                tooltip: {
                    trigger: 'item'
                },
                legend: {
                    bottom: 'bottom',
                },
                series: [
                    {
                        name: 'وضعیت ورود دو مرحله ای کاربران',
                        type: 'pie',
                        selectedMode: 'single',
                        radius: [0, '30%'],
                        label: {
                            show: false,
                            position: 'inner',
                            fontSize: 10
                        },
                        labelLine: {
                            show: false
                        },
                        data: this.data.status,
                        color: ['#0c5bef','#e74349'],
                    },
                    {
                        name: 'طریقه',
                        type: 'pie',
                        radius: ['45%', '60%'],
                        labelLine: {
                            length: 30
                        },

                        data: this.data.active_via,
                        color: ['#aeb9cc','#e0cfb1','#000','#229ED9'],
                    }
                ]
            },
        }
    },
}
</script>
