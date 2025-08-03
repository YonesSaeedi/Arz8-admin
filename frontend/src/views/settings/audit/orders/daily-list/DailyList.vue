<template>

    <div>
        <order-add-new
            :is-add-new-sidebar-active.sync="isAddNewSidebarActive"
            @refetch-data="refetchData"
            :isCrypto="isCrypto"
        />

        <!-- Filters -->
        <daily-list-filters
            :date-start-filter.sync="dateStartFilter" :date-stop-filter.sync="dateStopFilter"
            :amount-start-filter.sync="amountStartFilter" :amount-stop-filter.sync="amountStopFilter"
        />

        <!-- Table Container Card -->
        <b-card no-body class="mb-0">
            <div class="m-2">
                <b-row>
                    <!-- balance -->
                    <b-col class="border py-1" cols="12" md="6" offset-md="3">
                        <b-row class="align-items-center">
                            <b-col cols="6" md="6" class="font-medium-1">
                                جمع کل سود:
                            </b-col>
                            <b-col class="text-center font-medium-1 text-success" cols="6" md="6">
                                <span v-if="sumBenefit !== null">
                                    {{(sumBenefit)?toFixFloat(sumBenefit):0}} تومان
                                </span>
                                <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                            </b-col>
                        </b-row>
                    </b-col>
                    <b-col class="border py-1" cols="12" md="6" offset-md="3">
                        <b-row class="align-items-center">
                            <b-col cols="6" md="6" class="font-medium-1">
                                جمع کل هزینه ها:
                            </b-col>
                            <b-col class="text-center font-medium-1 text-danger" cols="6" md="6">
                                <span v-if="sumCust !== null">
                                    {{(sumCust)?toFixFloat(sumCust):0}} تومان
                                </span>
                                <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                            </b-col>
                        </b-row>
                    </b-col>
                    <b-col class="border py-1" cols="12" md="6" offset-md="3">
                        <b-row class="align-items-center">
                            <b-col cols="6" md="6" class="font-medium-1">
                                سود خالص:
                            </b-col>
                            <b-col class="text-center font-medium-1" cols="6" md="6">
                                <span v-if="sumCust !== null && sumBenefit !== null">
                                    {{toFixFloat(sumBenefit-sumCust)}} تومان
                                </span>
                                <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                            </b-col>
                        </b-row>
                    </b-col>

                </b-row>
            </div>

            <div class="m-2">
                <!-- Table Top -->
                <b-row>
                    <!-- Search -->
                    <b-col cols="12" md="3">
                        <div class="d-flex align-items-center justify-content-end">
                            <b-form-input
                                v-model="searchQuery"
                                class="d-inline-block mr-1"
                                placeholder="جستجو ..."
                            />
                        </div>
                    </b-col>

                    <!-- Per Page -->
                    <b-col cols="12" md="9" class="mb-1 mb-md-0 text-right">
                        <b-button
                            variant="primary"
                            @click="isAddNewSidebarActive = true"
                        >
                            <span class="text-nowrap">ثبت سفارش</span>
                        </b-button>
                        <v-select
                            v-model="perPage"
                            :dir="$store.state.appConfig.isRTL ? 'rtl' : 'ltr'"
                            :options="perPageOptions"
                            :clearable="false"
                            class="per-page-selector d-inline-block mx-50"
                        />
                    </b-col>
                </b-row>
            </div>

            <b-table
                ref="refOrdersListTable"
                class="position-relative"
                :items="fetchOrders"
                responsive
                :fields="tableColumns"
                primary-key="id"
                :sort-by.sync="sortBy"
                show-empty
                striped hover
                empty-text="داده ای برای نمایش وجود ندارد"
                :sort-desc.sync="isSortDirDesc"
            >

                <!-- Column: amount sell -->
                <template #cell(amount_sell)="data">
                    <div class="text-nowrap">
                        <span>{{ toFixFloat(data.item.amount_sell)}} <small>دلار</small></span>
                    </div>
                </template>

                <!-- Column: avg price sell -->
                <template #cell(avg_price_sell)="data">
                    <div class="text-nowrap vazir">
                        <span>{{toFixFloat(data.item.avg_price_sell)}} <small>تومان</small></span>
                    </div>
                </template>

                <!-- Column: amount buy -->
                <template #cell(amount_buy)="data">
                    <div class="text-nowrap">
                        <span>{{ toFixFloat(data.item.amount_buy)}} <small>دلار</small></span>
                    </div>
                </template>

                <!-- Column: avg price buy -->
                <template #cell(avg_price_buy)="data">
                    <div class="text-nowrap vazir">
                        <span>{{toFixFloat(data.item.avg_price_buy)}} <small>تومان</small></span>
                    </div>
                </template>

                <!-- Column: benefit -->
                <template #cell(benefit)="data">
                    <div class="text-nowrap vazir" :class="data.item.benefit>=0?'text-success':'text-danger'">
                        {{ toFixFloat(data.item.benefit) }} <small>تومان</small>
                    </div>
                </template>

                <!-- Column: custs -->
                <template #cell(custs)="data">
                    <div class="text-nowrap vazir text-danger">
                        {{ toFixFloat(data.item.cust) }} <small>تومان</small>
                    </div>
                </template>

                <!-- Column: net -->
                <template #cell(net)="data">
                    <div class="text-nowrap vazir" :class="(data.item.benefit - data.item.cust)>=0?'text-success':'text-danger'">
                        <span dir="ltr">{{ toFixFloat(data.item.benefit - data.item.cust) }}</span> <small>تومان</small>
                    </div>
                </template>

                <!-- Column: date -->
                <template #cell(date)="data">
                    <div class="text-nowrap vazir">
                        {{ (data.item.date) }}
                    </div>
                </template>

                <!-- Column: balance -->
                <template #cell(balance)="data">
                    <div class="text-nowrap">
                        <span>{{toFixFloat(data.item.balance)}} <small>دلار</small></span>
                    </div>
                </template>

                <!-- Column: orders -->
                <template #cell(orders)="data">
                    <div class="text-nowrap text-center text-primary">
                        <feather-icon icon="ListIcon" size="20" @click="$emit('setDate',data.item.dateTime)"/>
                    </div>
                </template>


            </b-table>
            <div class="mx-2 mb-2">
                <b-row>
                    <b-col
                        cols="12"
                        sm="6"
                        class="d-flex align-items-center justify-content-center justify-content-sm-start"
                    >
                        <span class="text-muted">Showing {{ dataMeta.from }} to {{ dataMeta.to }} of {{ dataMeta.of }} entries</span>
                    </b-col>
                    <!-- Pagination -->
                    <b-col
                        cols="12"
                        sm="6"
                        class="d-flex align-items-center justify-content-center justify-content-sm-end"
                    >

                        <b-pagination
                            v-model="currentPage"
                            :total-rows="totalRows"
                            :per-page="perPage"
                            first-number
                            last-number
                            class="mb-0 mt-1 mt-sm-0"
                            prev-class="prev-item"
                            next-class="next-item"
                        >
                            <template #prev-text>
                                <feather-icon
                                    icon="ChevronLeftIcon"
                                    size="18"
                                />
                            </template>
                            <template #next-text>
                                <feather-icon
                                    icon="ChevronRightIcon"
                                    size="18"
                                />
                            </template>
                        </b-pagination>

                    </b-col>

                </b-row>
            </div>
        </b-card>
    </div>
</template>
<script>
    import {
        BCard, BRow, BCol, BFormInput, BButton, BTable, BMedia, BAvatar, BLink,
        BBadge, BDropdown, BDropdownItem, BPagination,BTooltip,BFormGroup,BSkeleton
    } from 'bootstrap-vue'
    import vSelect from 'vue-select'
    import {ref} from '@vue/composition-api'
    import {avatarText} from '@core/utils/filter'
    import DailyListFilters from './DailyListFilters.vue'
    import OrderAddNew from './OrderAddNew.vue'
    import useDailyList from './useDailyList'

    export default {
        props: ['isCrypto'],
        data () {
            return {

            }
        },
        components: {
            DailyListFilters,OrderAddNew,
            BCard,
            BRow,
            BCol,
            BFormInput,BFormGroup,BSkeleton,
            BButton,
            BTable,
            BMedia,
            BAvatar,
            BLink,
            BBadge,
            BDropdown,
            BDropdownItem,
            BPagination,
            BTooltip,
            vSelect,
        },
        setup(props) {


            const isAddNewSidebarActive = ref(false)

            const {
                fetchOrders,
                tableColumns,
                perPage,
                currentPage,
                totalRows,
                dataMeta,
                perPageOptions,
                searchQuery,
                sortBy,
                isSortDirDesc,
                refOrdersListTable,
                refetchData,

                // UI
                resolveStatusVariant,

                // Extra Filters
                dateStartFilter,dateStopFilter,
                amountStartFilter,amountStopFilter,
                sumBenefit,
                sumCust
            } = useDailyList(props.isCrypto)

            return {
                isAddNewSidebarActive,

                fetchOrders,
                tableColumns,
                perPage,
                currentPage,
                totalRows,
                dataMeta,
                perPageOptions,
                searchQuery,
                sortBy,
                isSortDirDesc,
                refOrdersListTable,
                refetchData,

                // Filter
                avatarText,

                // UI
                resolveStatusVariant,


                // Extra Filters
                dateStartFilter, dateStopFilter,
                amountStartFilter, amountStopFilter,
                sumBenefit,
                sumCust
            }
        },
    }
</script>

<style lang="scss" scoped>
    .per-page-selector {
        width: 90px;
    }
</style>

<style lang="scss">
    @import '@core/scss/vue/libs/vue-select.scss';
</style>
