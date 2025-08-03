<template>
    <b-card body-class="p-1">
        <b-col md="8" class="mx-auto">
            <b-row>
                <b-col md="4">
                    <div class="text-center">
                        <img :src="require('@/assets/images/logo/logo-'+ levelAccount(user.level_account) +'.png')" width="100">
                        <p>سطح {{$t('l-'+user.level_account)}}</p>
                    </div>
                </b-col>
                <b-col md="8">
                    <div class="d-flex align-items-center mt-2">
                        <div class="d-flex align-items-center mr-4">
                            <b-avatar
                                variant="light-success"
                                rounded
                            >
                                <feather-icon
                                    icon="TrendingUpIcon"
                                    size="18"
                                />
                            </b-avatar>
                            <div class="ml-1">
                                <h5 class="mb-0">
                                    %{{data.levels_account[user.level_account-1].feeBuyOrder}}
                                </h5>
                                <small>کارمزد سفارشات خرید</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <b-avatar
                                variant="light-danger"
                                rounded
                            >
                                <feather-icon
                                    icon="TrendingDownIcon"
                                    size="18"
                                />
                            </b-avatar>
                            <div class="ml-1">
                                <h5 class="mb-0">
                                    %{{data.levels_account[user.level_account-1].feeSellOrder}}
                                </h5>
                                <small>کارمزد سفارشات فروش</small>
                            </div>
                        </div>
                    </div>
                    <hr class="w-100">
                    <div class="d-flex align-items-center mt-2">
                        <div class="d-flex align-items-center mr-4">
                            <b-avatar
                                variant="light-success"
                                rounded
                            >
                                <feather-icon
                                    icon="TrendingUpIcon"
                                    size="18"
                                />
                            </b-avatar>
                            <div class="ml-1">
                                <h5 class="mb-0">
                                    %{{data.levels_account[user.level_account-1].feeBuyTrade}}
                                </h5>
                                <small>کارمزد معاملات خرید</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <b-avatar
                                variant="light-danger"
                                rounded
                            >
                                <feather-icon
                                    icon="TrendingDownIcon"
                                    size="18"
                                />
                            </b-avatar>
                            <div class="ml-1">
                                <h5 class="mb-0">
                                    %{{data.levels_account[user.level_account-1].feeSellTrade}}
                                </h5>
                                <small>کارمزد معاملات فروش</small>
                            </div>
                        </div>
                    </div>
                </b-col>
            </b-row>

            <vue-apex-charts v-if="user.level_account!==4"
                type="radialBar"
                height="350"
                :options="supportTrackerRadialBar.chartOptions"
                :series="[((data.sum_30days_all*100) / data.levels_account[user.level_account].amount_start).toFixed()]"
            />
            <vue-apex-charts v-else
                 type="radialBar"
                 height="350"
                 :options="supportTrackerRadialBar.chartOptions"
                 :series="[100]"
            />

            <h4 class="text-center mt-2">
                مقدار فعلی جمع سفارشات و معاملات در 30 روز گذشته:
            </h4>
            <h4 class="text-center">
                <strong class="text-primary">{{data.sum_30days_all.toLocaleString()}}</strong>
                <small>تومان</small>
            </h4>

        </b-col>
    </b-card>
</template>

<script>
    import {
        BCard, BCardHeader, BBadge, BCollapse, BLink, BCardBody, BRow, BCol, BInputGroup, BInputGroupAppend, BFormGroup, BFormInput, BTable, BButton,
        BMedia, BAvatar,BTab,BTabs
    } from 'bootstrap-vue';
    import Table from "@/views/vuexy/table/bs-table/Table";
    import BCardActions from "@core/components/b-card-actions/BCardActions";
    import VueApexCharts from 'vue-apexcharts'
    import { $themeColors } from '@themeConfig'

    export default {
        data() {
            return {
                supportTrackerRadialBar: {
                    chartOptions: {
                        chart: {
                            fontFamily: 'vazir'
                        },
                        plotOptions: {
                            radialBar: {
                                size: 150,
                                offsetY: 20,
                                startAngle: -150,
                                endAngle: 150,
                                hollow: {
                                    size: '65%',
                                },
                                track: {
                                    background: $themeColors.primary,
                                    strokeWidth: '5%',
                                },
                                dataLabels: {
                                    name: {
                                        offsetY: -5,
                                        color: '#5e5873',
                                        fontSize: '1rem',
                                    },
                                    value: {
                                        offsetY: 15,
                                        color: '#5e5873',
                                        fontSize: '1.714rem',
                                    },
                                },
                            },
                        },
                        colors: [$themeColors.success],
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shade: 'dark',
                                type: 'horizontal',
                                shadeIntensity: 0.5,
                                gradientToColors: [$themeColors.primary],
                                inverseColors: true,
                                opacityFrom: 1,
                                opacityTo: 1,
                                stops: [0, 100],
                            },
                        },
                        stroke: {
                            dashArray: 7,
                        },
                        labels: ['رفتن به سطح بعد'],
                    },
                },
            }
        },
        props:['user','data'],
        components: {
            VueApexCharts,

            Table,
            BTable,
            BLink,
            BCard,
            BBadge,
            BRow,
            BCol,
            BCardActions,
            BCardHeader,
            BCardBody,
            BCollapse,
            BButton,
            BInputGroup,BFormGroup, BInputGroupAppend,BFormInput,
            BMedia, BAvatar,BTab,BTabs
        }
    }
</script>

<style scoped>

</style>
