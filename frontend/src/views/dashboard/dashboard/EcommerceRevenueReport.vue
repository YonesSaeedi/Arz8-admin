<template>
    <b-card id="chart-type-dashboard"
        v-if="data"
        no-body
        class="card-revenue-budget"
    >
        <b-row class="mx-0">
            <b-col
                md="8"
                class="revenue-report-wrapper"
            >
                <div class="d-sm-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-50 mb-sm-0">
                        گزارشات خرید و فروش
                    </h4>
                    <div class="d-flex align-items-center">
                        <div class="d-flex align-items-center mr-2">
                            <span class="bullet bullet-success svg-font-small-3 mr-50 cursor-pointer"/>
                            <span>خرید</span>
                        </div>
                        <div class="d-flex align-items-center ml-75">
                            <span class="bullet bullet-warning svg-font-small-3 mr-50 cursor-pointer"/>
                            <span>فروش</span>
                        </div>
                    </div>
                </div>

                <!-- chart -->
                <vue-apex-charts
                    id="revenue-report-chart"
                    type="bar"
                    height="250"
                    :options="revenueReport.chartOptions"
                    :series="series"
                />
            </b-col>

            <b-col
                md="4"
                class="budget-wrapper"
            >
                <b-dropdown
                    :text="model=='orders'?'سفارشات':'معاملات'"
                    size="sm"
                    class="budget-dropdown"
                    variant="outline-primary"
                >
                    <b-dropdown-item @click="model='orders'">سفارشات</b-dropdown-item>
                    <b-dropdown-item @click="model='trades'">معاملات</b-dropdown-item>
                </b-dropdown>

                <h2 class="mb-25">
                    {{ parseInt(data[model+'_tomans_today']).toLocaleString() }} <small>{{model=='orders'?'تومان':'دلار'}}</small>
                </h2>
                <div class="d-flex justify-content-center">
                    <span class="font-weight-bolder mr-25">ارزش {{model=='orders'?'سفارشات':'معاملات'}} امروز</span>
                </div>
                <vue-apex-charts
                    id="budget-chart"
                    type="line"
                    height="80"
                    :options="budgetChart.options"
                    :series="seriesBudget"
                />

                <b-button
                    v-ripple.400="'rgba(255, 255, 255, 0.15)'"
                    variant="primary" @click="timeFrame=='months'?timeFrame='days':timeFrame='months'"
                >
                    {{timeFrame=='months'?'نمایش بر اساس روز':'نمایش بر اساس ماه'}}
                </b-button>
            </b-col>
        </b-row>
    </b-card>
</template>

<script>
    import {
        BCard, BRow, BCol, BDropdown, BDropdownItem, BButton,
    } from 'bootstrap-vue'
    import VueApexCharts from 'vue-apexcharts'
    import {$themeColors} from '@themeConfig'
    import Ripple from 'vue-ripple-directive'

    export default {
        components: {
            VueApexCharts,
            BDropdown,
            BDropdownItem,
            BCard,
            BButton,
            BRow,
            BCol,
        },
        directives: {
            Ripple,
        },
        props: {
            data2: {
                type: Object,
                default: () => {
                },
            },
            data: {
                type: Object,
                default: () => {
                },
            },
        },
        computed:{
            series(){
                return  [
                    {
                    name: 'خرید',
                    data: this.data[this.model][this.timeFrame].buy,
                    },
                    {
                        name: 'فروش',
                        data: this.data[this.model][this.timeFrame].sell,
                    },
                ]
            },
            seriesBudget(){
                return [
                    {
                        data: this.data[this.model]['days'].buy,
                    },
                    {
                        data: this.data[this.model]['days'].sell,
                    },
                ];
            },
        },
        data() {
            return {
                timeFrame: 'days',
                model: 'orders',

                revenueReport: {
                    chartOptions: {
                        labels: [],
                        chart: {
                            stacked: true,
                            type: 'bar',
                            toolbar: {show: false},
                        },
                        grid: {
                            show: true,
                            xaxis: {
                                lines: {show: true }
                            },
                            padding: {
                                top: -20,
                                bottom: -10,
                            },
                            yaxis: {
                                lines: {show: false},
                            },
                        },
                        xaxis: {
                            categories: [],
                            labels: {
                                style: {
                                    colors: '#6E6B7B',
                                    fontSize: '0.86rem',
                                    fontFamily: 'vazir',
                                },
                            },
                            axisTicks: {
                                show: false,
                            },
                            axisBorder: {
                                show: false,
                            },
                        },
                        legend: {
                            show: false,
                        },
                        dataLabels: {
                            enabled: false,
                        },
                        colors: [$themeColors.success, $themeColors.warning],
                        plotOptions: {
                            bar: {
                                columnWidth: '17%',
                                endingShape: 'rounded',
                            },
                            distributed: true,
                        },
                        yaxis: {
                            labels: {
                                style: {
                                    colors: '#6E6B7B',
                                    fontSize: '0.86rem',
                                    fontFamily: 'vazir',
                                },
                            },
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
                                    formatter: (seriesName) => seriesName+': ',
                                },
                            }
                        },
                    },
                },
                // budget chart
                budgetChart: {
                    options: {
                        chart: {
                            height: 80,
                            toolbar: {show: false},
                            zoom: {enabled: false},
                            type: 'line',
                            sparkline: {enabled: true},
                        },
                        stroke: {
                            curve: 'smooth',
                            dashArray: [0, 5],
                            width: [2],
                        },
                        colors: [$themeColors.primary, '#dcdae3'],
                        tooltip: {
                            enabled: false,
                        },
                    },
                },
            }
        },
        watch:{
            timeFrame(value){
                this.revenueReport.chartOptions = {...this.revenueReport.chartOptions,labels: this.data.lable[this.timeFrame]};
            }
        },
        created() {
            this.revenueReport.chartOptions.labels = this.data.lable[this.timeFrame];
        }
    }
</script>
<style lang="scss">
    #chart-type-dashboard{
        .apexcharts-xaxis-label{
            letter-spacing:normal;
        }
        .apexcharts-canvas .apexcharts-text, .apexcharts-canvas .apexcharts-tooltip-text,
        .apexcharts-canvas .apexcharts-datalabel-label, .apexcharts-canvas .apexcharts-datalabel {
            font-family: "vazir", Helvetica, Arial, serif !important;
        }
        .apexcharts-grid{
            display: none;
        }
    }

</style>
