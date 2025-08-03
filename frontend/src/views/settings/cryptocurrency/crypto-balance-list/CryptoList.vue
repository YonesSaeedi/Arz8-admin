<template>

    <div>


        <!-- Filters -->
        <crypto-list-filters
            :hide-filter.sync="hideFilter" :hide-options="hideOptions"
            :status-buy-sell-filter.sync="statusBuySellFilter" :status-buy-sell-options="statusBuySellOptions"
            :withdraw-filter.sync="withdrawFilter" :withdraw-options="withdrawOptions"
            :deposit-filter.sync="depositFilter" :deposit-options="depositOptions"
            :exchange-filter.sync="exchangeFilter" :exchange-options="exchangeOptions"
            :network-filter.sync="networkFilter" :network-options="networkFillterOptions"
            :exchange-account-filter.sync="exchangeAccountFilter" :exchange-account-options="exchangeAccountFillterOptions"
            :other-filter.sync="otherFilter" :other-options="otherOptions"
        />


        <!-- Table Container Card -->
        <b-card no-body class="mb-0">
            <div class="m-2">
                <b-row>
                    <b-col class="border py-1" cols="12" md="6" offset-md="3">
                        <b-row class="align-items-center">
                            <b-col cols="6" md="6" class="font-medium-1">
                                موجودی تومانی:
                            </b-col>
                            <b-col class="text-center font-medium-1" cols="6" md="6">
                                <span v-if="balanceTotal !== null">
                                    {{toFixFloat(parseFloat(balanceTotal.total_toman))}} تومان
                                </span>
                                <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                            </b-col>
                        </b-row>
                    </b-col>
                    <b-col class="border py-1" cols="12" md="6" offset-md="3">
                        <b-row class="align-items-center">
                            <b-col cols="6" md="6" class="font-medium-1">
                                موجودی دلاری:
                            </b-col>
                            <b-col class="text-center font-medium-1" cols="6" md="6">
                                <span v-if="balanceTotal !== null">
                                    {{toFixFloat(parseFloat(balanceTotal.total_usdt))}} دلار
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
                    <b-col
                        cols="12"
                        md="9"
                        class="mb-1 mb-md-0 text-right"
                    >
                        <b-button v-if="activeUserInfo.role === 'admin'"
                            variant="primary"
                            @click="blalnceFit()"
                        >
                            <span class="text-nowrap">بالاس موجودی گروهی</span>
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


                <!-- Column: balance -->
                <template #cell(balance)="data">
                    <div class="text-center">
                        <div>{{ (data.item.balance? toFixFloat(parseFloat(data.item.balance)): '0') }}</div>
                        <small class="text-muted">
                            دلاری:
                            {{ (data.item.balance_usdt? toFixFloat(parseFloat(data.item.balance_usdt)): '0') }} USDT
                        </small>
                    </div>
                </template>

                <!-- Column: balanceWalletOther -->
                <template #cell(balanceWalletOther)="data">
                    <div class="text-center">
                        <div>{{ (data.item.balance_other_wallet? toFixFloat(parseFloat(data.item.balance_other_wallet)): '0') }}</div>
                        <small class="text-muted">
                            دلاری:
                            {{ (data.item.balance_other_wallet? toFixFloat(parseFloat(data.item.balance_other_wallet_usdt)): '0') }} USDT
                        </small>
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
    BBadge, BDropdown, BDropdownItem, BPagination, BTooltip, BSkeleton
} from 'bootstrap-vue'
    import vSelect from 'vue-select'
    import {ref, onUnmounted, watch} from '@vue/composition-api'
    import {avatarText} from '@core/utils/filter'
    import CryptoListFilters from './CryptoListFilters.vue'
    import useCryptoList from './useCryptoList'
    import axiosIns from "@/libs/axios";

    export default {
        components: {
            BSkeleton,
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

            const statusBuySellOptions = [
                {label: 'خرید فعال', value: 'BuyTrue'},
                {label: 'خرید غیرفعال', value: 'Buyfalse'},
                {label: 'فروش فعال', value: 'SellTrue'},
                {label: 'فروش غیرفعال', value: 'SellFalse'}
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

            const otherOptions = [
                {label: 'دارای کول ولت', value: 'hasCoolWallet'},
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
                statusBuySellFilter,
                withdrawFilter, depositFilter,
                exchangeFilter,
                networkFilter,
                networkslist,
                exchangeAccountFilter,
                exchangeAccount,
                symbolsList,
                otherFilter,
                balanceTotal,fetchBalanceTotal
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
                statusBuySellOptions,
                withdrawOptions, depositOptions,
                exchangeOptions,
                networkFillterOptions,
                exchangeAccountFillterOptions,
                otherOptions,

                // Extra Filters
                hideFilter,
                statusBuySellFilter,
                withdrawFilter, depositFilter,
                exchangeFilter,
                networkFilter,
                networkslist,networkOptions,
                exchangeAccountFilter,
                exchangeAccount,exchangeAccountOptions,
                symbolsList,
                otherFilter,
                balanceTotal,fetchBalanceTotal
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
            },

        },
        methods:{
            blalnceFit(){
                this.$swal({
                    title: 'از بالانس گروهی موجودی اطمینان دارید؟',
                    text: 'تمامی ارز هایی که در لیست میبینید بالانس خواهند شد.',
                    icon: 'question',
                    showCancelButton: true,
                    showLoaderOnConfirm: true,
                    buttonsStyling: false,
                    preConfirm: (value) => {
                        return axiosIns.post('setting/crypto/fit-balance-gruop',{symbols: this.symbolsList})
                            .then(response => {
                                return response;
                            })
                            .catch(() => {
                                this.errorFetching();
                            })
                    },
                    allowOutsideClick: () => false
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (result.value.data.status == true){
                            this.refetchData();
                            this.$swal({icon: 'success',title: 'موفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }else{
                            this.$swal({icon: 'error',title: 'ناموفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }
                    }
                })
            }
        },
        created() {
            if(this.$route.query.network)
                this.networkFilter = this.$route.query.network;
        },
        mounted() {
            this.fetchBalanceTotal();
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
