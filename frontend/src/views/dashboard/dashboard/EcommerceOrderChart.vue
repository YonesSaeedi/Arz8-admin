<template>
    <b-card id="dashboard-order-chart-min"
        v-if="data"
        body-class="pb-50"
    >
        <h6>سفارشات</h6>
        <h2 class="font-weight-bolder mb-1">
            {{toThousand(data.orders)}}
        </h2>

        <!-- chart -->
        <vue-apex-charts dir="ltr"
            height="100"
            :options="statisticsOrder.chartOptions"
            :series="statisticsOrder.series"
        />
    </b-card>
</template>

<script>
    import {BCard} from 'bootstrap-vue'
    import VueApexCharts from 'vue-apexcharts'
    import {$themeColors} from '@themeConfig'

    const $barColor = '#f3f3f3'

    export default {
        components: {
            BCard,
            VueApexCharts,
        },
        props: {
            data: {
                type: Object,
                default: () => {
                },
            },
        },
        data() {
            return {
                statisticsOrder: {
                    series: [
                        {
                            name: '1400',
                            data: this.data.data,
                        },
                    ],
                    chartOptions: {
                        chart: {
                            type: 'bar',
                            stacked: true,
                            toolbar: {
                                show: false,
                            },
                        },
                        grid: {
                            show: true,
                            padding: {
                                left: 0,
                                right: 0,
                                top: -30,
                                bottom: -15,
                            },
                            xaxis: {
                                lines: {
                                    show: true
                                }
                            },
                            yaxis: {
                                lines: {
                                    show: false
                                }
                            },

                        },
                        plotOptions: {
                            bar: {
                                horizontal: false,
                                columnWidth: '20%',
                                startingShape: 'rounded',
                                colors: {
                                    backgroundBarColors: [$barColor, $barColor, $barColor, $barColor, $barColor],
                                    backgroundBarRadius: 5,
                                },
                            },
                        },
                        legend: {
                            show: false,
                        },
                        dataLabels: {
                            enabled: false,
                        },
                        colors: [$themeColors.warning],

                        xaxis: {
                            categories: this.data.lable,
                            labels: {
                                show: false,
                            },
                            axisBorder: {
                                show: false,
                            },
                            axisTicks: {
                                show: false,
                            },
                        },
                        yaxis: {
                            show: false,
                        },
                        tooltip: {
                            enabled: true,
                            style: {
                                fontSize: '12px',
                                fontFamily: 'vazir'
                            },
                            x: {
                                show: true,
                            },
                            y: {
                                formatter: (value) => { return this.toThousand(value) },
                                title: {
                                    formatter: (seriesName) => null,
                                },
                            }
                        },
                    },
                },
            }
        },
        created() {
            //this.statisticsOrder.series[0].data = this.data.data;
        }
    }
</script>
<style lang="scss">
    #dashboard-order-chart-min{
        .apexcharts-grid{
            display: none;
        }
    }
</style>
