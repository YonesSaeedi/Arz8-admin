<template>
    <b-card>
        <b-card-title class="mb-1">
            طریقه ثبت نام
        </b-card-title>
        <b-card-sub-title class="mb-2">
            کاربران از چه طریقی در پلتفرم ثبت نام شده اند؟
        </b-card-sub-title>

        <vue-apex-charts v-if="data"
            type="donut"
            height="350"
            :options="donutChart.chartOptions"
            :series="donutChart.series"
        />
    </b-card>
</template>

<script>
import {
    BCard, BCardTitle, BCardSubTitle,
} from 'bootstrap-vue'
import VueApexCharts from 'vue-apexcharts'

export default {
    components: {
        VueApexCharts,
        BCard,
        BCardTitle,
        BCardSubTitle,
    },
    props:['data'],
    data() {
        return {
            donutChart: {
                series: this.data.map(({sum}) => sum),
                chartOptions: {
                    legend: {
                        show: true,
                        position: 'bottom',
                        fontSize: '14px',
                        fontFamily: 'Montserrat',
                    },
                    colors: ['#a4c639','#fdb901','#826bf8','#2b9bf4','#FFA1A1'],
                    dataLabels: {
                        enabled: true,
                        formatter(val) {
                            // eslint-disable-next-line radix
                            return `${parseInt(val)}%`
                        },
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                labels: {
                                    show: true,
                                    name: {
                                        fontSize: '2rem',
                                        fontFamily: 'Montserrat',
                                    },
                                    value: {
                                        fontSize: '1rem',
                                        fontFamily: 'Montserrat',
                                        formatter(val) {
                                            // eslint-disable-next-line radix
                                            return `${parseInt(val).toLocaleString('en-US')} `
                                        },
                                    },
                                    total: {
                                        show: true,
                                        fontSize: '1.5rem',
                                        fontFamily: 'tohoma',
                                        formatter(val) {
                                            var sumTotal = 0;
                                            val.globals.series.map((item)=>{
                                                sumTotal = sumTotal +item
                                            })
                                            return sumTotal.toLocaleString('en-US')
                                        },
                                    },
                                },
                            },
                        },
                    },
                    labels:  this.data.map(({via}) => via),
                    responsive: [
                        {
                            breakpoint: 992,
                            options: {
                                chart: {
                                    height: 380,
                                },
                                legend: {
                                    position: 'bottom',
                                },
                            },
                        },
                        {
                            breakpoint: 576,
                            options: {
                                chart: {
                                    height: 320,
                                },
                                plotOptions: {
                                    pie: {
                                        donut: {
                                            labels: {
                                                show: true,
                                                name: {
                                                    fontSize: '1.5rem',
                                                },
                                                value: {
                                                    fontSize: '1rem',
                                                },
                                                total: {
                                                    fontSize: '1.5rem',
                                                },
                                            },
                                        },
                                    },
                                },
                                legend: {
                                    show: true,
                                },
                            },
                        },
                    ],
                },
            },
        }
    },
}
</script>
<style lang="scss">
@import '@core/scss/vue/libs/vue-flatpicker.scss';
@import '@core/scss/vue/libs/chart-apex.scss';
</style>
