<template>

    <div id="wallets-user">

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
                ref="refWalletsListTable"
                class="position-relative"
                :items="listWallets"
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

                <!-- Column: logo -->
                <template #cell(coin)="data">
                    <div class="text-nowrap">
                        <div v-if="data.item.symbol !== 'IRR'">
                            <i class="cf" style="font-size: 45px" v-if="isFontExist(data.item.symbol)" :class="'cf-'+data.item.symbol.toLowerCase()" :style="{color:colorSymbol(data.item.symbol)}"></i>
                            <img :src="baseURL+'images/currency/' + iconSymbol(data.item.symbol)" width="45px" v-else />
                        </div>
                        <div v-else>
                            <img :src="baseURL+'images/currency/iran.svg'" width="45px"/>
                        </div>
                    </div>
                </template>

                <!-- Column: name -->
                <template #cell(name)="data">
                    <div class="text-nowrap text-capitalize">
                        <div>{{ $t(data.item.name) }}</div>
                        <small class="text-muted">{{ localeNameSymbol(data.item.symbol) ? localeNameSymbol(data.item.symbol)[localeHas] : data.item.name }}</small>
                    </div>
                </template>

                <!-- Column: percent -->
                <template #cell(symbol)="data">
                    <div class="text-center">
                        <div>{{ (data.item.symbol) }}</div>
                    </div>
                </template>

                <!-- Column: balance -->
                <template #cell(balance)="data">
                    <div class="text-nowrap">
                        <div>{{data.item.balance? toFixFloat(data.item.balance):'0' }} <small>{{data.item.symbol}}</small></div>
                    </div>
                </template>

                <!-- Column: balance Toman -->
                <template #cell(balanceToman)="data">
                    <div class="text-nowrap" v-if="data.item.balance_toman_buy">
                        <div>{{data.item.balance_toman_buy.toLocaleString()}} <small>تومان</small></div>
                        <small class="text-muted">
                            فروش:
                            {{ data.item.balance_toman_sell? data.item.balance_toman_sell.toLocaleString():'---' }}
                            تومان
                        </small>
                    </div>
                    <div v-else> ---- </div>
                </template>


                <!-- Column: balance Usdt -->
                <template #cell(balanceUsdt)="data">
                    <div class="text-nowrap" v-if="data.item.balance_usdt">
                        <div>{{toFixFloat(data.item.balance_usdt) }} <small>USDT</small></div>
                    </div>
                    <div v-else> ---- </div>
                </template>

                <!-- Column: valueAvailable -->
                <template #cell(valueAvailable)="data">
                    <div class="text-center">
                        <div>{{ (data.item.balance_available? toFixFloat(data.item.balance_available): '0') }}</div>
                    </div>
                </template>

                <!-- Column: buyStatus -->
                <template #cell(buyStatus)="data">
                    <div class="text-center">
                        <b-badge pill
                                 :variant="`light-${resolveStatusVariant(data.item.buy_status)}`"
                                 class="text-capitalize">
                            {{ data.item.buy_status==1?'فعال':'غیرفعال' }}
                        </b-badge>
                    </div>
                </template>


                <!-- Column: Actions -->
                <template #cell(actions)="data">
                    <div class="text-center text-primary" @click="balanceCryptoChange(data.item.symbol)" v-if="data.item.symbol !== 'IRR'">
                        <feather-icon icon="SlidersIcon" size="20"/>
                    </div>
                    <div class="text-center text-primary" @click="balanceInternalChange(data.item.id)" v-else>
                        <feather-icon icon="SlidersIcon" size="20"/>
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

        <changeBalanceCrypto :modalBalanceCryptoStatus="modalBalanceCryptoStatus" :user="user" :symbol="symbolBalanceCrypto"
                             @change-status-balacne-crypto="balanceCryptoChange" @refetch-data="refetchData"/>

        <changeBalanceInternal :modalBalanceInternalStatus="modalBalanceInternalStatus" :user="user"
                             @change-status-balacne-internal="balanceInternalChange" @refetch-data="refetchData"
                             :idWalletChangeBalanceInternal="idWalletChangeBalanceInternal"/>

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
    import useWalletsList from './useWalletsList'
    import changeBalanceCrypto from './ChangeBalanceCrypto'
    import changeBalanceInternal from './ChangeBalanceInternal'

    export default {
        props:['user'],
        data() {
            return {
                modalBalanceCryptoStatus:false,
                symbolBalanceCrypto:null,

                modalBalanceInternalStatus:false,
                idWalletChangeBalanceInternal:null,
            }
        },
        components: {
            changeBalanceInternal,
            changeBalanceCrypto,
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
            const {
                listWallets,
                tableColumns,
                perPage,
                currentPage,
                totalRows,
                dataMeta,
                perPageOptions,
                searchQuery,
                sortBy,
                isSortDirDesc,
                refWalletsListTable,
                refetchData,

                // UI
                resolveStatusVariant,
            } = useWalletsList(props.user)

            return {
                // Sidebar

                listWallets,
                tableColumns,
                perPage,
                currentPage,
                totalRows,
                dataMeta,
                perPageOptions,
                searchQuery,
                sortBy,
                isSortDirDesc,
                refWalletsListTable,
                refetchData,

                // Filter
                avatarText,

                // UI
                resolveStatusVariant,

            }
        },
        watch:{

        },
        methods: {
            balanceCryptoChange(symbol = null){
                if(symbol){
                    this.modalBalanceCryptoStatus = true;
                    this.symbolBalanceCrypto = symbol;
                }else{
                    this.symbolBalanceCrypto = null;
                    this.modalBalanceCryptoStatus = false;
                }

            },
            balanceInternalChange(id){
                if(id){
                    this.modalBalanceInternalStatus = true;
                    this.idWalletChangeBalanceInternal = id;
                }else{
                    this.modalBalanceInternalStatus = false;
                }
            }
        }
    }
</script>

<style lang="scss">
    .per-page-selector {
        width: 90px;
    }
    #wallets-user{
        .b-table tbody tr:first-child:not(.b-table-empty-row) td {
            background-color: #ff9f433b !important;
        }
    }
</style>

<style lang="scss">
    @import '@core/scss/vue/libs/vue-select.scss';
</style>
