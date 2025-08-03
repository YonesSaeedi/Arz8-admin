<template>

    <div>
        <!-- Filters -->
        <wage-list-filters
            :date-start-filter.sync="dateStartFilter" :date-stop-filter.sync="dateStopFilter"
            :amount-start-filter.sync="amountStartFilter" :amount-stop-filter.sync="amountStopFilter"
            :tableCalculate="tableCalculate"
        />

        <b-row>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="DollarSignIcon"
                    :statistic="'$'+(statistic && statistic.total_wage? toFixFloat(statistic.total_wage) :'0')"
                    statistic-title="کل کارمزد ها"
                    id="all-users"
                />
                <b-tooltip target="all-users" variant="primary">
                    کل کارمزد ها به ارزش دلاری تا پایان روز قبل
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="DivideSquareIcon"
                    color="success"
                    :statistic="'$'+(statistic && statistic.total_trade_wage? toFixFloat(statistic.total_trade_wage) :'0')"
                    statistic-title="کارمزد ترید"
                    id="email-users"
                />
                <b-tooltip target="email-users" variant="success">
                    کارمزد هایی که تبدیل به تتر شده اند
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="ActivityIcon"
                    color="danger"
                    :statistic="'$'+(statistic && statistic.total_wage_for_trade? toFixFloat(statistic.total_wage_for_trade) :'0')"
                    statistic-title="ترید نشده"
                    id="balance-users"
                />
                <b-tooltip target="balance-users" variant="danger">
                    مقدار کارمزدی که هنوز به تتر ترید نشده اند.
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="CropIcon"
                    color="warning"
                    :statistic="statistic && statistic.total_trades? statistic.total_trades.toLocaleString() :'0'"
                    statistic-title="تعداد ترید"
                    id="active-users"
                />
                <b-tooltip target="active-users" variant="warning">
                    تعداد ترید هایی که به تتر انجام شده است.
                </b-tooltip>
            </b-col>
        </b-row>

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

                <!-- Column: price -->
                <template #cell(price)="data">
                    <div class="text-nowrap">
                        <div>{{ toFixFloat(data.item.price_usdt) }} <small>USDT</small></div>
                        <small class="text-muted">قیمت به تومان: {{ toFixFloat(data.item.price_toman_sell) }}</small>
                    </div>
                </template>

                <!-- Column: wage -->
                <template #cell(wage)="data">
                    <div class="text-nowrap">
                        <div>{{data.item.wage_usdt? toFixFloat(data.item.wage_usdt):'0' }} <small>دلار</small></div>
                        <small class="text-muted">ارزش تومانی: {{data.item.wage_toman ? toFixFloat(data.item.wage_toman) :'0' }}</small>
                    </div>
                </template>

                <!-- Column: wageAmount -->
                <template #cell(wageAmount)="data">
                    <div class="text-nowrap">
                        <div>{{ data.item.wage_amount? toFixFloat(data.item.wage_amount) :'0' }} <small>{{data.item.symbol}}</small></div>
                    </div>
                </template>


                <!-- Column: Actions -->
                <template #cell(actions)="data">
                    <b-link @click="getWage(data.item.id)" class="font-weight-bold d-block text-nowrap text-center">
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

        <!-- card modal -->
        <wage :id="id" :modalShow="modalShow" @modalUpdate="modalUpdate" @refetchData="refetchData"/>
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
    import wage from './Wage.vue'
    import useWageList from './useWageList'
    import StatisticCardHorizontal from '@core/components/statistics-cards/StatisticCardHorizontal.vue'
    import WageListFilters from './WageListFilters.vue'

    export default {
        data () {
            return {
                id: null,
                modalShow: false,
            }
        },
        components: {
            WageListFilters,
            StatisticCardHorizontal,
            wage,
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

                statistic,fetchStatistic,
                tableCalculate,fetchTableCalculate,

                // Extra Filters
                dateStartFilter, dateStopFilter,
                amountStartFilter, amountStopFilter,

            } = useWageList()

            return {
                // Sidebar
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

                statistic,fetchStatistic,
                tableCalculate,fetchTableCalculate,

                // Extra Filters
                dateStartFilter, dateStopFilter,
                amountStartFilter, amountStopFilter,
            }
        },
        mounted() {
            if(!this.status){
                this.fetchStatistic();
                this.fetchTableCalculate();
            }
        },
        methods: {
            getWage(id){
                if(this.accessUserLogin['audit-wage']['single'] || this.activeUserInfo.role === 'admin'){
                    this.id = id;
                    this.modalShow = true
                }else{
                    this.$swal({icon: 'error',title: 'عدم دسترسی!',text: 'اجازه دسترسی به این بخش را ندارید.',confirmButtonText: 'باشه'})
                }

            },
            modalUpdate(val) {
                this.modalShow = val
            },
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
