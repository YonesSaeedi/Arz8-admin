<template>
    <div>
        <!-- Filters -->
        <crypto-list-filters
            :type-filter.sync="typeFilter" :type-options="typeOptions"
            :status-filter.sync="statusFilter" :status-options="statusOptions"
            :date-start-filter.sync="dateStartFilter" :date-stop-filter.sync="dateStopFilter"
            :amount-start-filter.sync="amountStartFilter" :amount-stop-filter.sync="amountStopFilter"
            :via-filter.sync="viaFilter" :via-options="viaOptions"
            :id-filter.sync="idFilter"
            :id-orders-filter.sync="idOrdersFilter"
            :id-trades-filter.sync="idTradesFilter"
            :other-filter.sync="otherFilter" :other-options="otherOptions"
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
                ref="refCryptoListTable"
                class="position-relative"
                :items="fetchCrypto"
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
                            :to="{ name: 'tr-crypto-view', params: { id: data.item.id } }"
                            class="font-weight-bold d-block text-nowrap"
                        >
                            #{{ data.item.id }}
                        </b-link>
                    </div>
                </template>


                <!-- Column: Name Family -->
                <template #cell(nameFamily)="data">
                    <User :item="data.item"/>
                </template>

                <!-- Column: coin -->
                <template #cell(coin)="data">
                    <Currency :item="data.item"/>
                </template>

                <!-- Column: type -->
                <template #cell(type)="data">
                    <div class="text-nowrap">
                        <b-avatar
                            :id="`crypto-row-${data.item.id}`"
                            size="32"
                            :variant="data.item.type == 'deposit'? `light-success` : `light-danger`"
                        >
                            <feather-icon
                                :icon="data.item.type == 'deposit' ? 'TrendingUpIcon' : 'TrendingDownIcon'"
                            />
                        </b-avatar>
                        <span>{{$t(data.item.type)}}</span>
                    </div>
                </template>

                <!-- Column: amount -->
                <template #cell(amount)="data">
                    <div class="text-nowrap">
                        <span>{{toFixFloat(data.item.amount)}} <small>{{$t(data.item.crypto.symbol)}}</small></span>
                    </div>
                </template>

                <!-- Column: stock -->
                <template #cell(stock)="data">
                    <div class="text-nowrap">
                        <span>{{toFixFloat(data.item.stock)}} <small>{{$t(data.item.crypto.symbol)}}</small></span>
                    </div>
                </template>

                <!-- Column: date -->
                <template #cell(date)="data">
                    <div class="text-nowrap vazir">
                        {{ $t(data.item.date) }}
                    </div>
                </template>

                <!-- Column: description -->
                <template #cell(description)="data">
                    <div class="text-nowrap">
                        {{ $t(data.item.description) }}
                        <small v-if="data.item.order">(#{{data.item.order.id}})
                            <b-link
                                :to="{ name: 'order-view', params: { id: data.item.order.id } }"
                                class="font-weight-bold d-block text-nowrap"
                            >
                                #{{data.item.order.id}}
                            </b-link>
                        </small>
                        <small v-if="data.item.trade">(#{{data.item.trade.id}})
                            <b-link
                                :to="{ name: 'trade-view', params: { id: data.item.trade.id } }"
                                class="font-weight-bold d-block text-nowrap"
                            >
                                #{{data.item.trade.id}}
                            </b-link>
                        </small>
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
                        :to="{ name: 'tr-crypto-view', params: { id: data.item.id } }"
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
    import {ref, onUnmounted, watch} from '@vue/composition-api'
    import {avatarText} from '@core/utils/filter'
    import CryptoListFilters from './CryptoListFilters.vue'
    import useCryptoList from './useCryptoList'
    import Currency from '@/views/lists/Currency.vue'
    import User from '@/views/lists/User.vue'

    export default {
        props:['user','status'],
        components: {
            CryptoListFilters,
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
                {label: 'واریز', value: 'deposit'},
                {label: 'برداشت', value: 'withdraw'}
            ]

            const viaOptions = [
                {label: 'وبسایت', value: 'website'},
                {label: 'اندروید', value: 'android'},
                {label: 'آی او اس', value: 'ios'}
            ]

            const statusOptions = [
            ]

            const otherOptions = [
                {label: 'تراکنش های سفارشات', value: 'trOrders'},
                {label: 'تراکنش های معاملات', value: 'trTrades'},
                {label: 'تراکنش های عادی', value: 'trNoOrdersAndTrades'}
            ]

            const {
                fetchCrypto,
                tableColumns,
                perPage,
                currentPage,
                totalRows,
                dataMeta,
                perPageOptions,
                searchQuery,
                sortBy,
                isSortDirDesc,
                refCryptoListTable,
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
                idOrdersFilter,
                idTradesFilter,
                otherFilter,
                isLoading
            } = useCryptoList(props)

            return {
                fetchCrypto,
                tableColumns,
                perPage,
                currentPage,
                totalRows,
                dataMeta,
                perPageOptions,
                searchQuery,
                sortBy,
                isSortDirDesc,
                refCryptoListTable,
                refetchData,

                // Filter
                avatarText,

                // UI
                resolveStatusVariant,

                typeOptions,
                statusOptions,
                viaOptions,
                otherOptions,

                // Extra Filters
                typeFilter,
                statusFilter,
                dateStartFilter, dateStopFilter,
                amountStartFilter, amountStopFilter,
                idFilter,
                idOrdersFilter,
                idTradesFilter,
                viaFilter,
                otherFilter,
                isLoading
            }
        },
        mounted() {
            this.statusOptions = [
                {label: this.$i18n.t('pending'), value: 'pending'},
                {label: this.$i18n.t('success'), value: 'success'},
                {label: this.$i18n.t('suspend'), value: 'suspend'},
                {label: this.$i18n.t('unsuccessful'), value: 'unsuccessful'},
                {label: this.$i18n.t('reject'), value: 'reject'},
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
