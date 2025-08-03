<template>

    <div id="trades-list">

        <b-row v-if="!user">
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="ListIcon" format-number
                    :statistic="statistic && statistic.total_trades? statistic.total_trades.toLocaleString() :'0'"
                    statistic-title="همه معاملات"
                    id="total_trades"
                />
                <b-tooltip target="total_trades" variant="primary">
                    همه معاملات موجود در سامانه
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="StarIcon" format-number
                    color="warning"
                    :statistic="statistic && statistic.success_trades? statistic.success_trades.toLocaleString() :'0'"
                    statistic-title="معاملات موفق"
                    id="success_trades"
                />
                <b-tooltip target="success_trades" variant="warning">
                    تعداد معاملات موفق
                </b-tooltip>
            </b-col>

            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="TrendingUpIcon" format-number
                    color="success"
                    :statistic="statistic && statistic.buy_trades? statistic.buy_trades.toLocaleString() :'0'"
                    statistic-title="تعداد خرید ها"
                    id="buy_trades"
                />
                <b-tooltip target="buy_trades" variant="success">
                    تعداد خرید های موفق
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="TrendingDownIcon" format-number
                    color="danger"
                    :statistic="statistic && statistic.sell_trades? statistic.sell_trades.toLocaleString() :'0'"
                    statistic-title="تعداد فروش ها"
                    id="sell_trades"
                />
                <b-tooltip target="sell_trades" variant="danger">
                    تعداد فروش های موفق
                </b-tooltip>
            </b-col>

        </b-row>


        <!-- Filters -->
        <list-filters
            :type-filter.sync="typeFilter" :type-options="typeOptions"
            :status-filter.sync="statusFilter" :status-options="statusOptions"
            :date-start-filter.sync="dateStartFilter" :date-stop-filter.sync="dateStopFilter"
            :amount-start-filter.sync="amountStartFilter" :amount-stop-filter.sync="amountStopFilter"
            :via-filter.sync="viaFilter" :via-options="viaOptions"
            :id-filter.sync="idFilter"
            :model-filter.sync="modelFilter" :model-options="modelOptions"
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
                ref="refListTable"
                class="position-relative"
                :items="fetchList"
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
                            :to="{ name: 'trade-view', params: { id: data.item.id } }"
                            class="font-weight-bold d-block text-nowrap"
                        >
                            #{{ data.item.id }}
                        </b-link>
                    </div>
                </template>

                <!-- Column: coin -->
                <template #cell(market)="data">
                    <div :id="`trades-row-${data.item.id}`" class="text-nowrap d-flex align-items-center">
                        <div class="quoteAsset">
                            <i class="cf" style="font-size: 30px" v-if="isFontExist(data.item.quoteSymbol)" :class="'cf-'+ data.item.quoteSymbol.toLowerCase()" :style="{color:colorSymbol(data.item.quoteSymbol)}"></i>
                            <img :src="baseURL+'images/currency/' + iconSymbol(data.item.quoteSymbol)" width="30px" height="30px" v-else/>
                        </div>
                        <div class="baseAsset">
                            <i class="cf" style="font-size: 30px" v-if="isFontExist(data.item.baseSymbol)" :class="'cf-'+ data.item.baseSymbol.toLowerCase()" :style="{color:colorSymbol(data.item.baseSymbol)}"></i>
                            <img :src="baseURL+'images/currency/' + iconSymbol(data.item.baseSymbol)" width="30px" height="30px" v-else/>
                        </div>
                        <div class="text-capitalize ml-25">{{data.item.marketName}}</div>
                    </div>
                </template>

                <!-- Column: Name Family -->
                <template #cell(nameFamily)="data">
                    <div class="text-nowrap">
                        <b-link
                            :to="{ name: 'user-single', params: { id: data.item.id_user } }"
                            class="font-weight-bold d-block text-nowrap"
                        >
                            <img :src="require('@/assets/images/logo/logo-'+ levelAccount(data.item.level_account) +'.png')" width="25">
                            {{ data.item.name ? data.item.name+' '+data.item.family : '-------' }}
                        </b-link>
                        <small class="text-muted">{{ data.item.email }}</small>
                    </div>
                </template>



                <!-- Column: type -->
                <template #cell(type)="data">
                    <div class="text-nowrap">
                        <b-avatar
                            size="32"
                            :variant="data.item.type == 'buy'? `light-success` : `light-danger`"
                        >
                            <feather-icon
                                :icon="data.item.type == 'buy' ? 'TrendingUpIcon' : 'TrendingDownIcon'"
                            />
                        </b-avatar>
                        <span>{{data.item.type == 'buy' ? 'خرید' :'فروش'}}</span>
                    </div>
                </template>

                <!-- Column: model -->
                <template #cell(model)="data">
                    <div class="text-nowrap">
                        <span>{{data.item.model}}</span>
                    </div>
                </template>

                <!-- Column: amountBase -->
                <template #cell(amountBase)="data">
                    <div class="text-nowrap">
                        <div v-if="data.item.status!='partial'">{{toFixFloat(data.item.amount_base)}} <small>{{data.item.baseSymbol}}</small></div>
                        <div v-else>
                            <div>{{toFixFloat(data.item.first_amount_base)}} <small>{{data.item.baseSymbol}}</small></div>
                            <div class="text-warning">{{toFixFloat(data.item.amount_base)}} <small>{{data.item.baseSymbol}}</small></div>
                        </div>
                    </div>
                </template>

                <!-- Column: amountQuote -->
                <template #cell(amountQuote)="data">
                    <div class="text-nowrap">
                        <span>{{toFixFloat(data.item.amount_quote)}} <small>{{data.item.quoteSymbol}}</small></span>
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
                    <div class="text-center">
                        <b-badge
                            pill
                            :variant="`${resolveStatusVariant(data.item.status)}`"
                            class="text-capitalize"
                        >
                            {{ $t(data.item.status) }}
                        </b-badge>
                    </div>
                </template>



                <!-- Column: Actions -->
                <template #cell(actions)="data">
                    <b-link
                        :to="{ name: 'trade-view', params: { id: data.item.id } }"
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
    import ListFilters from './TradesListFilters.vue'
    import useTradesList from './useTradesList'
    import StatisticCardHorizontal from '@core/components/statistics-cards/StatisticCardHorizontal.vue'

    export default {
        props:['user'],
        components: {
            ListFilters,
            StatisticCardHorizontal,
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

            const modelOptions = [
                {label: 'limit', value: 'limit'},
                {label: 'market', value: 'market'},
                {label: 'stop-limit', value: 'stop-limit'},
                {label: 'oco', value: 'oco'},
            ]


            const statusOptions = [
            ]

            const {
                fetchList,
                tableColumns,
                perPage,
                currentPage,
                totalRows,
                dataMeta,
                perPageOptions,
                searchQuery,
                sortBy,
                isSortDirDesc,
                refListTable,
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
                modelFilter,

                statistic,fetchStatistic,
                isLoading
            } = useTradesList(props.user)

            return {
                fetchList,
                tableColumns,
                perPage,
                currentPage,
                totalRows,
                dataMeta,
                perPageOptions,
                searchQuery,
                sortBy,
                isSortDirDesc,
                refListTable,
                refetchData,

                // Filter
                avatarText,

                // UI
                resolveStatusVariant,

                typeOptions,
                statusOptions,
                viaOptions,
                modelOptions,

                // Extra Filters
                typeFilter,
                statusFilter,
                dateStartFilter, dateStopFilter,
                amountStartFilter, amountStopFilter,
                idFilter,
                viaFilter,
                modelFilter,
                statistic,fetchStatistic,
                isLoading
            }
        },
        mounted() {
            if(!this.user)
                this.fetchStatistic();
            this.statusOptions = [
                {label: this.$i18n.t('open'), value: 'open'},
                {label: this.$i18n.t('success'), value: 'success'},
                {label: this.$i18n.t('revoke'), value: 'revoke'},
                {label: this.$i18n.t('expired'), value: 'expired'},
                {label: this.$i18n.t('canceled'), value: 'canceled'},
                {label: this.$i18n.t('partial'), value: 'partial'},
            ]
        }
    }
</script>

<style lang="scss" scoped>
    .per-page-selector {
        width: 90px;
    }

    #trades-list {
        .quoteAsset{
            opacity: 0.5;
            margin-right: -15px;
            z-index: 0;
        }
        .baseAsset{
            z-index: 1;
        }
    }
</style>

<style lang="scss">
    @import '@core/scss/vue/libs/vue-select.scss';
</style>
