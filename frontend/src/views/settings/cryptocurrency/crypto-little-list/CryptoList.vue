<template>

    <div>
        <crypto-list-filters
            :status-buy-filter.sync="statusBuyFilter" :status-buy-options="statusBuyOptions"
            :status-sell-filter.sync="statusSellFilter" :status-sell-options="statusSellOptions"
            :withdraw-filter.sync="withdrawFilter" :withdraw-options="withdrawOptions"
            :deposit-filter.sync="depositFilter" :deposit-options="depositOptions"
            :exchange-filter.sync="exchangeFilter" :exchange-options="exchangeOptions"
            @refetchData="refetchData" :isLoading="isLoading"
        />


        <!-- Table Container Card -->
        <b-card no-body class="mb-0">
            <div class="m-2">
                <p>کاربر وقتی میخرد ظرفمون منفی میشود و وقتی به منفی 10 دلار بالاتر رسید یه سفارش خرید از اون ارز ایجاد میکنیم تا موجودی ظرف صفر شود</p>
                <p>کاربر وقتی میفروشد ظرفمون مثبت میشود و وقتی به مثبت 10 دلار بالاتر رسید یه سفارش فروش از اون ارز ایجاد میکنیم تا موجودی ظرف صفر شود</p>
                <b-row>
                    <!-- balance -->
                    <b-col class="border py-1" cols="12" md="6"  offset-md="3">
                        <b-row class="align-items-center">
                            <b-col cols="6" md="6">
                                جمع کل مقدار ارزش دلاری:
                            </b-col>
                            <b-col class="text-center" cols="6" md="6">
                                <span v-if="calculationTable">
                                     {{toFixFloat(calculationTable.sum_balance_usdt)}} دلار
                                </span>
                                <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                            </b-col>
                        </b-row>
                        <hr>
                        <b-row class="align-items-center">
                            <b-col cols="6" md="6">
                                جمع کل ارزش  لحظه ای تومانی:
                            </b-col>
                            <b-col class="text-center" cols="6" md="6">
                                <span v-if="calculationTable">
                                    {{calculationTable.sum_balance_usdt > 0 ? parseInt(calculationTable.sum_balance_usdt*feeUsdt.sell).toLocaleString() : parseInt(calculationTable.sum_balance_usdt*feeUsdt.buy).toLocaleString() }}
                                    تومان
                                </span>
                                <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                            </b-col>
                        </b-row>
                        <hr>

                        <b-row class="align-items-center">
                            <b-col cols="6" md="6">
                                جمع کل تومانی:
                            </b-col>
                            <b-col class="text-center" cols="6" md="6">
                                <span v-if="calculationTable">
                                    {{toFixFloat(parseFloat(calculationTable.sum_balance_toman))}} تومان
                                </span>
                                <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                            </b-col>
                        </b-row>
                        <hr>
                        <b-row class="align-items-center">
                            <b-col cols="6" md="6">
                                جمع دلاری مثبت ها:
                            </b-col>
                            <b-col class="text-center" cols="6" md="6">
                                <span v-if="calculationTable" class="text-success">
                                     {{toFixFloat(calculationTable.plus_balance_usdt)}} دلار
                                </span>
                                <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                            </b-col>
                        </b-row>
                        <hr>

                        <b-row class="align-items-center">
                            <b-col cols="6" md="6">
                                جمع تومانی مثبت ها:
                            </b-col>
                            <b-col class="text-center" cols="6" md="6">
                                <span v-if="calculationTable" class="text-success">
                                    {{toFixFloat(parseFloat(calculationTable.plus_balance_toman))}} تومان
                                </span>
                                <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                            </b-col>
                        </b-row>
                        <hr>
                        <b-row class="align-items-center">
                            <b-col cols="6" md="6">
                                جمع دلاری منفی ها:
                            </b-col>
                            <b-col class="text-center" cols="6" md="6">
                                <span v-if="calculationTable" class="text-danger">
                                     {{toFixFloat(calculationTable.mines_balance_usdt)}} دلار
                                </span>
                                <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                            </b-col>
                        </b-row>
                        <hr>

                        <b-row class="align-items-center">
                            <b-col cols="6" md="6">
                                جمع تومانی منفی ها:
                            </b-col>
                            <b-col class="text-center" cols="6" md="6">
                                <span v-if="calculationTable" class="text-danger">
                                    {{toFixFloat(parseFloat(calculationTable.mines_balance_toman))}} تومان
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


                <!-- Column: balance -->
                <template #cell(balance)="data">
                    <div class="text-nowrap">
                        <div :class="data.item.amount_coin<0?'text-danger':(data.item.amount_coin>0?'text-success':'')">{{data.item.amount_coin? toFixFloat(data.item.amount_coin):'0' }}</div>
                    </div>
                </template>

                <!-- Column: equivalent -->
                <template #cell(equivalent)="data">
                    <div class="text-nowrap">
                        <div :class="data.item.amount_coin<0?'text-danger':(data.item.amount_coin>0?'text-success':'')">
                            {{data.item.balance_usdt? (data.item.balance_usdt > 0 ? parseInt(data.item.balance_usdt*feeUsdt.sell).toLocaleString() : parseInt(data.item.balance_usdt*feeUsdt.buy).toLocaleString() ):'0' }}
                        </div>
                        <small class="text-muted" v-if="data.item.amount_coin">
                            معادل دلاری: {{toFixFloat(data.item.balance_usdt)}}
                        </small>
                    </div>
                </template>

                <!-- Column: balanceToman -->
                <template #cell(balanceToman)="data">
                    <div class="text-nowrap">
                        <div :class="data.item.amount_toman<0?'text-danger':(data.item.amount_toman>0?'text-success':'')">{{ data.item.amount_toman? data.item.amount_toman.toLocaleString():'0' }} <small>تومان</small></div>
                    </div>
                </template>



                <!-- Column: priceUsdt -->
                <template #cell(priceUsdt)="data">
                    <div class="text-nowrap">
                        <div>{{ (data.item.price_usdt? toFixFloat(parseFloat(data.item.price_usdt)): '0') }}</div>
                    </div>
                </template>

                <!-- Column: Actions -->
                <template #cell(actions)="data">
                    <div class="text-center">
                        <feather-icon icon="XIcon" class="text-danger cursor-pointer" size="20" @click="remove(data.item.id)"/>
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
        BBadge, BDropdown, BDropdownItem, BPagination,BTooltip,BSkeleton
    } from 'bootstrap-vue'
    import vSelect from 'vue-select'
    import {ref, onUnmounted, watch} from '@vue/composition-api'
    import {avatarText} from '@core/utils/filter'
    import CryptoListFilters from './CryptoListFilters.vue'
    import useCryptoList from './useCryptoList'
    import axiosIns from "@/libs/axios";

    export default {
        components: {
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
            BSkeleton
        },
        setup() {

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
                {label: 'کوینکس', value: 'coinex'},
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
                tagFilter,
                statusBuyFilter, statusSellFilter,
                withdrawFilter, depositFilter,
                exchangeFilter,

                isLoading,
                calculationTable,feeUsdt
            } = useCryptoList()

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

                statusBuyOptions, statusSellOptions,
                withdrawOptions, depositOptions,
                exchangeOptions,

                // Extra Filters
                tagFilter,
                statusBuyFilter, statusSellFilter,
                withdrawFilter, depositFilter,
                exchangeFilter,

                isLoading,
                calculationTable,feeUsdt
            }
        },
        methods: {
            remove(id){
                this.$swal({
                    title: 'از حذف اطمینان دارید؟',
                    text: 'کلا ظرف موجودی اندک این ارز خالی و صفر میشود.',
                    icon: 'question',
                    showCancelButton: true,
                    showLoaderOnConfirm: true,
                    confirmButtonText: 'حذف شود',
                    customClass: {
                        confirmButton: 'btn px-2 btn-danger',
                        cancelButton: 'btn btn-outline-dark ml-1',
                    },
                    buttonsStyling: false,
                    preConfirm: () => {
                        return  axiosIns.delete('/setting/crypto/little/'+id )
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
                            this.refetchData()
                            this.$swal({icon: 'success',title: 'موفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }else{
                            this.$swal({icon: 'error',title: 'ناموفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }
                    }
                })
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
