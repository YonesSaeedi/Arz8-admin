<template>

    <div>

        <market-add-new
            :is-add-new-sidebar-active.sync="isAddNewSidebarActive"
            @refetch-data="refetchData" :crypto-options="cryptoOptions"
        />

        <!-- Filters -->
        <market-list-filters
            :status-filter.sync="statusFilter" :status-options="statusOptions"
            :status-auto-filter.sync="statusAutoFilter"
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
                        <b-button
                            variant="primary"
                            @click="isAddNewSidebarActive = true"
                        >
                            <span class="text-nowrap">افزودن بازار جدید</span>
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
                ref="refMarketsListTable"
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
                    <div class="text-nowrap text-capitalize">
                        <b-link :to="{ name: 'markets-view', params: { id: data.item.id } }"
                                class="font-weight-bold d-block text-nowrap">
                            <div>
                               #{{ data.item.id}}
                            </div>
                        </b-link>
                    </div>
                </template>

                <!-- Column: name -->
                <template #cell(name)="data">
                    <div class="text-nowrap text-capitalize">
                        <div>{{ $t(data.item.name) }}</div>
                        <small class="text-muted">{{ data.item.data.name.fa }}</small>
                    </div>
                </template>

                <!-- Column: symbol -->
                <template #cell(symbol)="data">
                    <div class="text-center text-nowrap">
                        <div>{{ (data.item.symbol) }}</div>
                    </div>
                </template>

                <!-- Column: baseAsset -->
                <template #cell(baseAsset)="data">
                    <div class="text-nowrap">
                        <div class="text-nowrap d-flex align-items-center">
                            <div>
                                <i class="cf" style="font-size: 30px" v-if="isFontExist(data.item.baseSymbol)" :class="'cf-'+ data.item.baseSymbol.toLowerCase()" :style="{color:colorSymbol(data.item.baseSymbol)}"></i>
                                <img :src="baseURL+'images/currency/' + iconSymbol(data.item.baseSymbol)" width="30px" height="30px" v-else/>
                            </div>
                            <div class="text-capitalize ml-25">{{ localeNameSymbol(data.item.baseSymbol) ? localeNameSymbol(data.item.baseSymbol)[localeHas] : data.item.baseSymbol }}</div>
                        </div>
                    </div>
                </template>

                <!-- Column: quoteAsset -->
                <template #cell(quoteAsset)="data">
                    <div class="text-nowrap">
                        <div class="text-nowrap d-flex align-items-center">
                            <div>
                                <i class="cf" style="font-size: 30px" v-if="isFontExist(data.item.quoteSymbol)" :class="'cf-'+ data.item.quoteSymbol.toLowerCase()" :style="{color:colorSymbol(data.item.quoteSymbol)}"></i>
                                <img :src="baseURL+'images/currency/' + iconSymbol(data.item.quoteSymbol)" width="30px" height="30px" v-else/>
                            </div>
                            <div class="text-capitalize ml-25">{{ localeNameSymbol(data.item.quoteSymbol) ? localeNameSymbol(data.item.quoteSymbol)[localeHas] : data.item.quoteSymbol }}</div>
                        </div>
                    </div>
                </template>


                <!-- Column: countTrade -->
                <template #cell(countTrade)="data">
                    <div class="text-center">
                        <div>{{ (data.item.count_trades) }}</div>
                    </div>
                </template>

                <!-- Column: amountTrade -->
                <template #cell(amountTrade)="data">
                    <div class="text-center">
                        <div>{{ data.item.amount_trades ? (data.item.amount_trades)+' '+data.item.baseSymbol :'---' }}</div>
                    </div>
                </template>

                <!-- Column: status -->
                <template #cell(status)="data">
                    <div class="text-center">
                        <b-badge pill
                            :variant="`light-${resolveStatusVariant(data.item.status)}`"
                            class="text-capitalize">
                            {{ data.item.status=='active'?'فعال':'غیرفعال' }}
                        </b-badge>
                    </div>
                </template>

                <!-- Column: status Auto -->
                <template #cell(status_auto)="data">
                    <div class="text-center">
                        <b-badge pill
                                 :variant="`light-${resolveStatusVariant(data.item.status_auto)}`"
                                 class="text-capitalize">
                            {{ data.item.status_auto=='active'?'فعال':'غیرفعال' }}
                        </b-badge>
                    </div>
                </template>


                <!-- Column: Actions -->
                <template #cell(actions)="data">
                    <b-link
                        :to="{ name: 'markets-view', params: { id: data.item.id } }"
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
    import MarketListFilters from './MarketListFilters.vue'
    import useMarketList from './useMarketList'
    import MarketAddNew from './MarketAddNew'

    export default {
        components: {
            MarketAddNew,
            MarketListFilters,
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
        setup() {
            const isAddNewSidebarActive = ref(false)


            const statusOptions = [
                {label: 'فعال', value: 'active'},
                {label: 'غیرفعال', value: 'inactive'}
            ]
            const cryptoOptions = []

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
                refMarketsListTable,
                refetchData,

                // UI
                resolveStatusVariant,

                // Extra Filters
                statusFilter,
                statusAutoFilter,
                cryptocurrency

            } = useMarketList()

            return {
                // Sidebar
                isAddNewSidebarActive,

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
                refMarketsListTable,
                refetchData,

                // Filter
                avatarText,

                // UI
                resolveStatusVariant,


                statusOptions,

                // Extra Filters
                statusFilter,
                statusAutoFilter,
                cryptocurrency,cryptoOptions
            }
        },
        watch:{
            cryptocurrency(val){
                var cryptoOptions = [];
                val.map(function(item) {
                    var obj = {label: this.localeNameSymbol(item.symbol)[this.localeHas] + ' ('+ item.symbol +')', value:item.symbol };
                    cryptoOptions.push(obj);
                },this);
                //var obj = {text:'شبکه دیفالت را انتخاب کنید', label:null};
                //cryptoOptions.push(obj);
                this.cryptoOptions = cryptoOptions;
            }
        },
        created() {

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
