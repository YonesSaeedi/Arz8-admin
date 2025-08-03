<template>
    <b-card
        v-if="data"
        class="card-tiny-line-stats"
        body-class="pb-50"
    >
        <h6>معاملات</h6>
        <h2 class="font-weight-bolder mb-1">
            {{toThousand(data.trades)}}
        </h2>
        <!-- chart -->
        <vue-apex-charts
            height="100"
            :options="statisticsTrade.chartOptions"
            :series="statisticsTrade.series"
        />
    </b-card>
</template>

<script>
    import {BCard} from 'bootstrap-vue'
    import VueApexCharts from 'vue-apexcharts'
    import {$themeColors} from '@themeConfig'

    const $trackBgColor = '#EBEBEB'

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
                statisticsTrade: {
                    series: [
                        {
                            name: '1400',
                            data: this.data.data,
                        },
                    ],
                    chartOptions: {
                        chart: {

                            type: 'line',
                            toolbar: {
                                show: false,
                            },
                            zoom: {
                                enabled: false,
                            },
                        },
                        grid: {
                            borderColor: $trackBgColor,
                            strokeDashArray: 5,
                            xaxis: {
                                lines: {
                                    show: true,
                                },
                            },
                            yaxis: {
                                lines: {
                                    show: false,
                                },
                            },
                            padding: {
                                top: -30,
                                bottom: -10,
                            },
                        },
                        stroke: {
                            width: 3,
                        },
                        colors: [$themeColors.info],
                        markers: {
                            size: 2,
                            colors: $themeColors.info,
                            strokeColors: $themeColors.info,
                            strokeWidth: 2,
                            strokeOpacity: 1,
                            strokeDashArray: 0,
                            fillOpacity: 1,
                            discrete: [
                                {
                                    seriesIndex: 0,
                                    dataPointIndex: 5,
                                    fillColor: '#ffffff',
                                    strokeColor: $themeColors.info,
                                    size: 5,
                                },
                            ],
                            shape: 'circle',
                            radius: 2,
                            hover: {
                                size: 3,
                            },
                        },
                        xaxis: {
                            categories: this.data.lable,
                            labels: {
                                show: true,
                                style: {
                                    fontSize: '0px',
                                },
                            },
                            axisBorder: {
                                show: false,
                            },
                            axisTicks: {
                                show: false,
                            },

                            tooltip: {
                                enabled: true,
                                formatter: undefined,
                                offsetY: 0,
                                style: {
                                    fontSize: '12px',
                                    fontFamily: 'vazir'
                                },
                            },

                        },
                        yaxis: {
                            show: false,
                        },
                        tooltip: {
                            style: {
                                fontSize: '12px',
                                fontFamily: 'vazir'
                            },
                            x: {
                                show: false,
                            },
                            y: {
                                formatter: (value) => { return this.toThousand(value) },
                                title: {
                                    formatter: (seriesName) => null,
                                },
                            },
                            marker: {
                                show: false,
                            },
                        },
                    },
                },
            }
        },
    }
</script>
