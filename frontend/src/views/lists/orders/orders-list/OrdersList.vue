<template>

    <div>

        <b-row v-if="!user">
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="ListIcon"
                    :statistic="statistic && statistic.total_orders? statistic.total_orders.toLocaleString() :'0'"
                    statistic-title="همه سفارشات"
                    id="total_orders"
                />
                <b-tooltip target="total_orders" variant="primary">
                    همه سفارشات موجود در سامانه
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="TrendingUpIcon"
                    color="success"
                    :statistic="statistic && statistic.buy_orders? statistic.buy_orders.toLocaleString() :'0'"
                    statistic-title="تعداد خرید ها"
                    id="buy_orders"
                />
                <b-tooltip target="buy_orders" variant="success">
                    تعداد خرید ها
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="TrendingDownIcon"
                    color="danger"
                    :statistic="statistic && statistic.sell_orders? statistic.sell_orders.toLocaleString() :'0'"
                    statistic-title="تعداد فروش ها"
                    id="balance-users"
                />
                <b-tooltip target="balance-users" variant="danger">
                    تعداد فروش ها
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="StarIcon"
                    color="warning"
                    :statistic="statistic && statistic.amount_orders? statistic.amount_orders :'0'"
                    statistic-title="مبلغ کل سفارشات"
                    format-number
                    id="amount_orders"
                />
                <b-tooltip target="amount_orders" variant="warning">
                   مبلغ کل سفارشات
                </b-tooltip>
            </b-col>
        </b-row>


        <!-- Filters -->
        <orders-list-filters
            :type-filter.sync="typeFilter" :type-options="typeOptions"
            :status-filter.sync="statusFilter" :status-options="statusOptions"
            :date-start-filter.sync="dateStartFilter" :date-stop-filter.sync="dateStopFilter"
            :amount-start-filter.sync="amountStartFilter" :amount-stop-filter.sync="amountStopFilter"
            :via-filter.sync="viaFilter" :via-options="viaOptions"
            :id-filter.sync="idFilter"
            @refetchData="refetchData" :isLoading="isLoading"
        />

        <!-- Table Container Card -->
        <b-card no-body class="mb-0">
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

                <!-- Column: id -->
                <template #cell(id)="data">
                    <div class="text-nowrap">
                        <b-link
                            :to="{ name: 'order-view', params: { id: data.item.id } }"
                            class="font-weight-bold d-block text-nowrap"
                        >
                            #{{ data.item.id }}
                        </b-link>
                    </div>
                </template>

                <!-- Column: coin -->
                <template #cell(coin)="data">
                    <Currency :item="data.item"/>
                </template>

                <!-- Column: Name Family -->
                <template #cell(nameFamily)="data">
                    <User :item="data.item"/>
                </template>

                <!-- Column: type -->
                <template #cell(type)="data">
                    <b-avatar
                        :id="`orders-row-${data.item.id}`"
                        size="32"
                        :variant="data.item.type == 'buy'? `light-success` : `light-danger`"
                    >
                        <feather-icon
                            :icon="data.item.type == 'buy' ? 'TrendingUpIcon' : 'TrendingDownIcon'"
                        />
                    </b-avatar>
                    <span>{{data.item.type == 'buy' ? 'خرید' :'فروش'}}</span>
                </template>

                <!-- Column: amount -->
                <template #cell(amount)="data">
                    <div class="text-nowrap vazir">
                        <span>{{data.item.amount.toLocaleString()}} <small>تومان</small></span>
                    </div>
                </template>

                <!-- Column: amount_coin -->
                <template #cell(amount_coin)="data">
                    <div class="text-nowrap">
                        <span>{{toFixFloat(data.item.amount_coin)}} <small>{{data.item.crypto.symbol}}</small></span>
                    </div>
                </template>

                <!-- Column: date -->
                <template #cell(date)="data">
                    <div class="text-nowrap vazir">
                        {{ $t(data.item.date) }}
                    </div>
                </template>

                <!-- Column: Status -->
                <template #cell(status)="data">
                    <b-badge
                        pill
                        :variant="`light-${resolveStatusVariant(data.item.status)}`"
                        class="text-capitalize"
                    >
                        {{ $t(data.item.status) }}
                    </b-badge>
                </template>



                <!-- Column: Actions -->
                <template #cell(actions)="data">
                    <b-link
                        :to="{ name: 'order-view', params: { id: data.item.id } }"
                        class="font-weight-bold d-block text-nowrap text-center"
                    >
                        <feather-icon icon="EditIcon" size="20"/>
                    </b-link>
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
        BBadge, BDropdown, BDropdownItem, BPagination,BTooltip
    } from 'bootstrap-vue'
    import vSelect from 'vue-select'
    import {ref, onUnmounted} from '@vue/composition-api'
    import {avatarText} from '@core/utils/filter'
    import OrdersListFilters from './OrdersListFilters.vue'
    import useOrdersList from './useOrdersList'
    import Currency from '@/views/lists/Currency.vue'
    import User from '@/views/lists/User.vue'
    import StatisticCardHorizontal from '@core/components/statistics-cards/StatisticCardHorizontal.vue'

    export default {
        props: ['user'],
        components: {
            OrdersListFilters,
            StatisticCardHorizontal,
            Currency,User,
            BCard,
            BRow,
            BCol,
            BFormInput,
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

            const typeOptions = [
                {label: 'خرید', value: 'buy'},
                {label: 'فروش', value: 'sell'}
            ]

            const viaOptions = [
                {label: 'وبسایت', value: 'website'},
                {label: 'اندروید', value: 'android'},
                {label: 'آی او اس', value: 'ios'}
            ]


            const statusOptions = [
            ]

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
                typeFilter,
                statusFilter,
                dateStartFilter,dateStopFilter,
                amountStartFilter,amountStopFilter,
                viaFilter,
                idFilter,
                statistic,fetchStatistic,
                isLoading

            } = useOrdersList(props.user)

            return {
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

                typeOptions,
                statusOptions,
                viaOptions,

                // Extra Filters
                typeFilter,
                statusFilter,
                dateStartFilter, dateStopFilter,
                amountStartFilter, amountStopFilter,
                idFilter,
                viaFilter,
                statistic,fetchStatistic,
                isLoading
            }
        },
        mounted() {
            if(!this.user)
                this.fetchStatistic();
            this.statusOptions = [
                {label: this.$i18n.t('pending'), value: 'pending'},
                {label: this.$i18n.t('success'), value: 'success'},
                {label: this.$i18n.t('return'), value: 'return'},
                {label: this.$i18n.t('suspend'), value: 'suspend'},
                {label: this.$i18n.t('unsuccessful'), value: 'unsuccessful'},
                {label: this.$i18n.t('canceled'), value: 'canceled'},
            ]
        }
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
