<template>

    <div>
        <trade-list-filters
            :from-filter.sync="fromFilter" :from-options="fromOptions"
            :status-filter.sync="statusFilter" :status-options="statusOptions"
            :date-start-filter.sync="dateStartFilter" :date-stop-filter.sync="dateStopFilter"
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
                    <b-col
                        cols="12"
                        md="9"
                        class="mb-1 mb-md-0 text-right"
                    >
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
                :items="listCrypto"
                responsive
                :fields="tableColumns"
                primary-key="id"
                :sort-by.sync="sortBy"
                show-empty
                striped
                hover
                empty-text="داده ای برای نمایش وجود ندارد"
                :sort-desc.sync="isSortDirDesc"
            >

                <!-- Column: id -->
                <template #cell(id)="data">
                    <div class="text-nowrap text-primary" @click="showDetail(data.item)">
                        #{{ data.item.id }}
                    </div>
                </template>

                <!-- Column: coin -->
                <template #cell(coin)="data">
                    <b-link :to="{ name: 'st-crypto-view', params: { id: data.item.id_crypto } }" :id="`trade-auto-row-${data.item.id}`">
                        <div>
                            <i class="cf" style="font-size: 35px" v-if="isFontExist(data.item.symbol)" :class="'cf-'+data.item.symbol.toLowerCase()" :style="{color:colorSymbol(data.item.symbol)}"></i>
                            <img :src="baseURL+'images/currency/' + iconSymbol(data.item.symbol)" width="35px" v-else />
                        </div>
                    </b-link>
                    <b-tooltip :target="`trade-auto-row-${data.item.id}`" placement="top" variant="primary">
                        {{localeNameSymbol(data.item.symbol) ? localeNameSymbol(data.item.symbol)[localeHas] : $t(data.item.nameCoin)}}
                    </b-tooltip>
                </template>


                <!-- Column: amount -->
                <template #cell(amount)="data">
                    <div class="text-nowrap">
                        <span>{{toFixFloat(parseFloat(data.item.amount_coin))}} <small>{{data.item.symbol}}</small></span>
                    </div>
                </template>

                <!-- Column: from -->
                <template #cell(from)="data">
                    <div class="text-nowrap">
                        <span v-if="data.item.from==='balance'">کاهش موجودی</span>
                        <span v-else-if="data.item.from==='little'">موجودی اندک</span>
                        <span v-else-if="data.item.from==='wage_trade'">کارمزد تریدها</span>
                        <span v-else-if="data.item.from==='admin_balance_set'">تنظیم موجودی ادمین</span>
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

                <!-- Column: side -->
                <template #cell(side)="data">
                    <div v-if="data.item.side">
                    <b-avatar
                        :id="`orders-row-${data.item.side}`"
                        size="32"
                        :variant="data.item.side == 'buy'? `light-success` : `light-danger`"
                    >
                        <feather-icon
                            :icon="data.item.side == 'buy' ? 'TrendingUpIcon' : 'TrendingDownIcon'"
                        />
                    </b-avatar>
                    <span>{{data.item.side == 'buy' ? 'خرید' :'فروش'}}</span>
                    </div>
                    <div v-else> ---- </div>
                </template>

                <!-- Column: amountUsdt -->
                <template #cell(amountUsdt)="data">
                    <div class="text-nowrap">
                        <span>{{data.item.amount_usdt ? toFixFloat(parseFloat(data.item.amount_usdt)) : '-----'}} <small>USDT</small></span>
                    </div>
                </template>

                <!-- Column: date -->
                <template #cell(date)="data">
                    <div class="text-nowrap vazir">
                        {{ $t(data.item.date) }}
                    </div>
                </template>


                <!-- Column: Actions -->
                <template #cell(actions)="data">
                    <div class="text-nowrap text-primary" @click="showDetail(data.item)">
                        <feather-icon icon="EditIcon" size="20"/>
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

        <b-modal
            v-model="modalTrade"
            id="modalTrade"
            title="جزئیات ترید"
            cancel-variant="outline-secondary"
            centered
        >
            <b-card-text>
                <div dir="ltr" class="text-right">
                    <pre v-html="JSON.stringify(JSON.parse(itemDetail), null, 4)"></pre>
                </div>
            </b-card-text>
            <template #modal-footer>
                <div class="w-100 d-flex">
                    <b-button variant="outline-secondary" class="float-right ml-1" @click="modalTrade=false">
                        بستن
                    </b-button>
                </div>
            </template>
        </b-modal>

    </div>
</template>
<script>
    import {
        BCard, BRow, BCol, BFormInput, BButton, BTable, BMedia, BAvatar, BLink,
        BBadge, BDropdown, BDropdownItem, BPagination,BTooltip,BSkeleton
    } from 'bootstrap-vue'
    import vSelect from 'vue-select'
    import {ref, onUnmounted, watch} from '@vue/composition-api'
    import {avatarText} from '@core/utils/filter'
    import TradeListFilters from './TradeListFilters.vue'
    import useTradeList from './useTradeList'

    export default {
        components: {
            TradeListFilters,
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
            BSkeleton
        },
        data(){
            return {
                modalTrade: false,
                itemDetail:null
            }
        },
        methods:{
            showDetail(item){
                this.itemDetail = item.data;
                this.modalTrade = true;
            }
        },
        setup() {

            const fromOptions = [
                {label: 'کاهش موجودی', value: 'balance'},
                {label: 'موجودی اندک', value: 'little'},
                {label: 'کارمزد ترید', value: 'wage_trade'},
                {label: 'تنظیم موجودی ادمین', value: 'admin_balance_set'},
            ]

            const statusOptions = [
                {label: 'موفق', value: 'success'},
                {label: 'ناموفق', value: 'unsuccessful'}
            ]


            const {
                listCrypto,
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
                fromFilter,
                statusFilter,
                dateStartFilter,dateStopFilter,

                isLoading,
            } = useTradeList()

            return {

                listCrypto,
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

                fromOptions, statusOptions,

                // Extra Filters
                fromFilter,
                statusFilter,
                dateStartFilter,dateStopFilter,

                isLoading
            }
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
