<template>
    <b-card
        v-if="data"
        class="earnings-card"
    >
        <b-row>
            <b-col cols="6">
                <b-card-title class="mb-1">
                    طریقه ثبت نام
                </b-card-title>
                <div class="font-small-2">
                    در سی روز گذشته
                </div>
                <h5 class="mb-1 mt-25">
                    {{data.users_last_days}}
                </h5>
                <b-card-text class="text-muted font-small-2">
                    <span class="font-weight-bolder">{{changePercent*1}}%</span><span> {{changePercent>0?'افزایش':'کاهش'}} نسبت به سی روز دوره مشابه ماقبل</span>
                </b-card-text>
            </b-col>
            <b-col cols="6">
                <!-- chart -->
                <vue-apex-charts
                    height="120"
                    :options="earningsChart.chartOptions"
                    :series="series"
                />
            </b-col>
        </b-row>
    </b-card>
</template>

<script>
    import {
        BCard, BRow, BCol, BCardTitle, BCardText,
    } from 'bootstrap-vue'
    import VueApexCharts from 'vue-apexcharts'
    import {$themeColors} from '@themeConfig'

    const $earningsStrokeColor2 = '#28c76f66'
    const $earningsStrokeColor3 = '#28c76f33'
    export default {
        components: {
            BCard,
            BRow,
            BCol,
            BCardTitle,
            BCardText,
            VueApexCharts,
        },
        props: {
            data: {
                type: Object,
                default: () => {
                },
            },
        },
        computed:{
            changePercent(){
                return ((this.data.users_last_days - this.data.users_ago_days) / this.data.users_ago_days * 100).toFixed(2);
            },
            series(){
                var series = [];
                Object.keys(this.data.data).map(function(key, index) {
                    series.push(parseInt(this.data.data[key].percent));
                },this);
                return series;
            },
        },
        watch:{
            series(){
                this.setLable();
            }
        },
        methods:{
          setLable(){
              var lable = [];
              Object.keys(this.data.data).map(function(key, index) {
                  lable.push(this.data.data[key].via);
              },this);
              this.earningsChart.chartOptions = {...this.earningsChart.chartOptions,labels: lable};
          }
        },
        created() {
            this.setLable();
        },
        data() {
            return {
                earningsChart: {
                    chartOptions: {
                        chart: {
                            type: 'donut',
                            toolbar: {
                                show: false,
                            },
                        },
                        dataLabels: {
                            enabled: false,
                        },
                        legend: {show: false},
                        comparedResult: [2, -3, 8],
                        labels: [],
                        stroke: {width: 0},
                        colors: [$earningsStrokeColor2, $earningsStrokeColor3, $themeColors.success],
                        grid: {
                            padding: {
                                right: -20,
                                bottom: -8,
                                left: -20,
                            },
                        },
                        plotOptions: {
                            pie: {
                                startAngle: -10,
                                donut: {
                                    labels: {
                                        show: true,
                                        name: {
                                            offsetY: 15,
                                        },
                                        value: {
                                            offsetY: -15,
                                            formatter(val) {
                                                // eslint-disable-next-line radix
                                                return `${parseInt(val)}%`
                                            },
                                        },
                                        total: {
                                            show: true,
                                            offsetY: 15,
                                            label: 'users',
                                            formatter() {
                                                return '100%'
                                            },
                                        },
                                    },
                                },
                            },
                        },
                        responsive: [
                            {
                                breakpoint: 1325,
                                options: {
                                    chart: {
                                        height: 100,
                                    },
                                },
                            },
                            {
                                breakpoint: 1200,
                                options: {
                                    chart: {
                                        height: 120,
                                    },
                                },
                            },
                            {
                                breakpoint: 1045,
                                options: {
                                    chart: {
                                        height: 100,
                                    },
                                },
                            },
                            {
                                breakpoint: 992,
                                options: {
                                    chart: {
                                        height: 120,
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
