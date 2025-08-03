<template>

    <div>

        <crypto-add-new
            :is-add-new-sidebar-active.sync="isAddNewSidebarActive"
            @refetch-data="refetchData" @general-info="getGeneralInfoApi()" :network-options="networkOptions"
        />

        <!-- Filters -->
        <crypto-list-filters
            :hide-filter.sync="hideFilter" :hide-options="hideOptions"
            :status-buy-filter.sync="statusBuyFilter" :status-buy-options="statusBuyOptions"
            :status-sell-filter.sync="statusSellFilter" :status-sell-options="statusSellOptions"
            :withdraw-filter.sync="withdrawFilter" :withdraw-options="withdrawOptions"
            :deposit-filter.sync="depositFilter" :deposit-options="depositOptions"
            :exchange-filter.sync="exchangeFilter" :exchange-options="exchangeOptions"
            :network-filter.sync="networkFilter" :network-options="networkFillterOptions"
            :exchange-account-filter.sync="exchangeAccountFilter" :exchange-account-options="exchangeAccountFillterOptions"
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
                            <span class="text-nowrap">افزودن رمز ارز جدید</span>
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

                <!-- Column: logo -->
                <template #cell(logo)="data">
                    <div class="text-nowrap">
                        <b-link :to="{ name: 'st-crypto-view', params: { id: data.item.id } }"
                            class="font-weight-bold d-block text-nowrap">
                            <div>
                                <i class="cf" style="font-size: 45px" v-if="isFontExist(data.item.symbol)" :class="'cf-'+data.item.symbol.toLowerCase()" :style="{color:colorSymbol(data.item.symbol)}"></i>
                                <img :src="baseURL+'images/currency/' + (data.item.icon )" width="45px" v-else />
                            </div>
                        </b-link>
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
                <template #cell(percent)="data">
                    <div class="text-center">
                        <div>{{ (data.item.percent) }}</div>
                    </div>
                </template>

                <!-- Column: balanceUsers -->
                <template #cell(balanceUsers)="data">
                    <div class="text-nowrap">
                        <div>{{data.item.balance_users? toFixFloat(data.item.balance_users):'---' }} {{data.item.symbol}}</div>
                        <small class="text-muted">قیمت دلاری: {{data.item.price_usdt ? toFixFloat(data.item.price_usdt) :'---' }}</small>
                    </div>
                </template>

                <!-- Column: balanceUsersToman -->
                <template #cell(balanceUsersToman)="data">
                    <div class="text-nowrap">
                        <div>{{ data.item.balance_users_toman_buy? data.item.balance_users_toman_buy.toLocaleString():'---' }} <small>تومان</small></div>
                        <small class="text-muted">
                            فروش:
                            {{ data.item.balance_users_toman_sell? data.item.balance_users_toman_sell.toLocaleString():'---' }}
                            تومان
                        </small>
                    </div>
                </template>


                <!-- Column: sort -->
                <template #cell(sort)="data">
                    <div class="text-center">
                        <div>{{ (data.item.sort? data.item.sort: '---') }}</div>
                    </div>
                </template>

                <!-- Column: balance -->
                <template #cell(balance)="data">
                    <div class="text-center">
                        <div>{{ (data.item.balance? toFixFloat(parseFloat(data.item.balance)): '0') }}</div>
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

                <!-- Column: withdrawAuto -->
                <template #cell(withdrawAuto)="data">
                    <div class="text-center">
                        <b-badge pill
                                 :variant="`light-${resolveStatusVariant(data.item.withdraw_auto)}`"
                                 class="text-capitalize">
                            {{ data.item.withdraw_auto==1?'فعال':'غیرفعال' }}
                        </b-badge>
                    </div>
                </template>

                <!-- Column: sellStatus -->
                <template #cell(sellStatus)="data">
                    <div class="text-center">
                        <b-badge pill
                                 :variant="`light-${resolveStatusVariant(data.item.sell_status)}`"
                                 class="text-capitalize">
                            {{ data.item.sell_status==1?'فعال':'غیرفعال' }}
                        </b-badge>
                    </div>
                </template>


                <!-- Column: Actions -->
                <template #cell(actions)="data">
                    <b-link
                        :to="{ name: 'st-crypto-view', params: { id: data.item.id } }"
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
    import CryptoAddNew from './CryptoAddNew.vue'

    export default {
        components: {
            CryptoAddNew,
            CryptoListFilters,
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

            const hideOptions = [
                {label: 'نمایان', value: 'true'},
                {label: 'مخفی', value: 'false'}
            ]

            const statusBuyOptions = [
                {label: 'خرید فعال', value: 'true'},
                {label: 'خرید غیرفعال', value: 'false'}
            ]

            const statusSellOptions = [
                {label: 'فروش فعال', value: 'true'},
                {label: 'فروش غیرفعال', value: 'false'}
            ]

            const withdrawOptions = [
                {label: 'برداشت فعال', value: 'true'},
                {label: 'برداشت غیرفعال', value: 'false'}
            ]

            const depositOptions = [
                {label: 'واریز فعال', value: 'true'},
                {label: 'واریز غیرفعال', value: 'false'}
            ]

            const exchangeOptions = [
                {label: 'بایننس', value: 'binance'},
                {label: 'کوکوین', value: 'kucoin'},
                {label: 'کوینکس', value: 'coinex'}
            ]


            const networkOptions = []
            const networkFillterOptions = []

            const exchangeAccountOptions = []
            const exchangeAccountFillterOptions = []

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
                hideFilter,
                statusBuyFilter, statusSellFilter,
                withdrawFilter, depositFilter,
                exchangeFilter,
                networkFilter,
                networkslist,
                exchangeAccountFilter,
                exchangeAccount,
            } = useCryptoList()

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
                refCryptoListTable,
                refetchData,

                // Filter
                avatarText,

                // UI
                resolveStatusVariant,

                hideOptions,
                statusBuyOptions, statusSellOptions,
                withdrawOptions, depositOptions,
                exchangeOptions,
                networkFillterOptions,
                exchangeAccountFillterOptions,

                // Extra Filters
                hideFilter,
                statusBuyFilter, statusSellFilter,
                withdrawFilter, depositFilter,
                exchangeFilter,
                networkFilter,
                networkslist,networkOptions,
                exchangeAccountFilter,
                exchangeAccount,exchangeAccountOptions
            }
        },
        watch:{
            networkslist(val){
                var networkOptions = [];
                var networkFillterOptions = [];
                val.map(function(item) {
                    var obj = {text:(item.name), value:item.symbol };
                    networkOptions.push(obj);

                    var obj = {label:(item.name), value:item.symbol };
                    networkFillterOptions.push(obj);
                });
                var obj = {text:'شبکه دیفالت را انتخاب کنید', value:null, disabled:true, hidden:true};
                networkOptions.push(obj);
                this.networkOptions = networkOptions;
                this.networkFillterOptions = networkFillterOptions;
            },
            exchangeAccount(val){
                var exchangeAccountOptions = [];
                var exchangeAccountFillterOptions = [];
                val.map(function(item) {
                    var obj = {text:item, value:item };
                    exchangeAccountOptions.push(obj);

                    var obj = {label:item, value:item };
                    exchangeAccountFillterOptions.push(obj);
                });
                var obj = {text:'اکانت را انتخاب کنید', value:null, disabled:true, hidden:true};
                exchangeAccountOptions.push(obj);
                this.exchangeAccountOptions = exchangeAccountOptions;
                this.exchangeAccountFillterOptions = exchangeAccountFillterOptions;
            }
        },
        created() {
            if(this.$route.query.network)
                this.networkFilter = this.$route.query.network;
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
