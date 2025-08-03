<template>

    <div>
        <!-- Filters -->
        <factor-list-filters
            :date-start-filter.sync="dateStartFilter" :date-stop-filter.sync="dateStopFilter"
            :amount-start-filter.sync="amountStartFilter" :amount-stop-filter.sync="amountStopFilter"
            :type-filter.sync="typeFilter" :type-options="typeOptions"
            :type-deposit-filter.sync="typeDepositFilter" :type-deposit-options="typeDepositOptions"
            :dateStartPriod="dateStartPriod"
            :tableCalculate="tableCalculate"
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
                                  variant="light"
                                  @click="downloadXls()"
                                  :disabled="isLoadingExport"
                        >

                            <span class="text-nowrap" v-if="!isLoadingExport">
                                <feather-icon icon="SaveIcon" class="mr-50"/>
                                خروجی
                            </span>
                            <div class="line-height-0 ml-25"><b-spinner v-if="isLoadingExport" small></b-spinner></div>
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

                <!-- Column: id_factor -->
                <template #cell(id_factor)="data">
                    <div class="text-nowrap">
                        <b-link
                            :to="{ name: 'tr-internal-view', params: { id: data.item.id } }"
                            class="font-weight-bold d-block text-nowrap"
                        >
                            #{{ data.item.id }}
                        </b-link>
                    </div>
                </template>

                <!-- Column: nameFamily -->
                <template #cell(nameFamily)="data">
                    <User :item="data.item"/>
                </template>

                <!-- Column: mobile -->
                <template #cell(mobile)="data">
                    <div class="text-nowrap">
                        {{ data.item.user.mobile }}
                    </div>
                </template>

                <!-- Column: type -->
                <template #cell(type)="data">
                    <div class="text-nowrap">
                        <b-avatar
                            :id="`internal-row-${data.item.id}`"
                            size="32"
                            :variant="data.item.type == 'deposit'? `light-success` : `light-danger`"
                        >
                            <feather-icon
                                :icon="data.item.type == 'deposit' ? 'TrendingUpIcon' : 'TrendingDownIcon'"
                            />
                        </b-avatar>
                        <span>{{$t(data.item.type == 'deposit'? 'buy':'sell')}}</span>
                    </div>
                </template>

                <!-- Column: amount -->
                <template #cell(amount)="data">
                    <div class="text-nowrap vazir">
                        <span>{{data.item.amount.toLocaleString()}} <small>تومان</small></span>
                    </div>
                </template>

                <!-- Column: wage -->
                <template #cell(wage)="data">
                    <div class="text-nowrap vazir">
                        <span>
                            <span>{{data.item.wage.toLocaleString()}}</span>
                            <small>تومان</small>
                        </span>
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
                    <b-link :to="{ name: 'audit-factor-single', params: { id: data.item.id } }"
                            target="_blank" class="font-weight-bold d-block text-nowrap text-center">
                        <feather-icon icon="ListIcon" size="20"/>
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
        BBadge, BDropdown, BDropdownItem, BPagination,BTooltip,BSpinner
    } from 'bootstrap-vue'
    import vSelect from 'vue-select'
    import {ref, onUnmounted, watch} from '@vue/composition-api'
    import {avatarText} from '@core/utils/filter'
    import useFactorList from './useFactorList'
    import StatisticCardHorizontal from '@core/components/statistics-cards/StatisticCardHorizontal.vue'
    import FactorListFilters from './FactorListFilters.vue'
    import axiosIns from "@/libs/axios";
    import InternalListFilters from "@/views/lists/transaction-internal/internal-list/InternalListFilters.vue";
    import User from '@/views/lists/User.vue'

    export default {
        components: {
            User,
            InternalListFilters,
            FactorListFilters,
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
            BSpinner
        },
        setup() {

            const isLoadingExport = false;

            const typeOptions = [
                {label: 'خرید', value: 'deposit'},
                {label: 'فروش', value: 'withdraw'}
            ]
            const typeDepositOptions = [
                {label: 'درگاه بانکی', value: 'dateway'},
                {label: 'کارت به کارت', value: 'cardTocard'},
                {label: 'واریز با شناسه', value: 'depositId'},
                {label: 'واریز با فیش', value: 'receipt'}
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

                statistic,fetchStatistic,
                tableCalculate,fetchTableCalculate,


                // Extra Filters
                typeFilter,
                typeDepositFilter,
                dateStartFilter, dateStopFilter,
                amountStartFilter, amountStopFilter,
                dateStartPriod

            } = useFactorList()

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
                typeOptions,
                typeDepositOptions,

                // Extra Filters
                typeFilter,
                typeDepositFilter,
                dateStartFilter, dateStopFilter,
                amountStartFilter, amountStopFilter,
                dateStartPriod,
                isLoadingExport
            }
        },
        mounted() {
            if(!this.status){
                this.fetchStatistic();
                this.fetchTableCalculate();
            }
        },
        methods:{
            async downloadXls() {
                this.isLoadingExport = true;
                axiosIns.post('audit/export-gov', { dateStart:this.dateStartFilter,dateStop:this.dateStopFilter,type:this.typeFilter,typeDeposit:this.typeDepositFilter }, {
                    responseType: 'blob' // برای دریافت فایل به صورت باینری
                })
                    .then((response) => {
                        // بررسی وضعیت موفقیت آمیز بودن درخواست
                        if (response.status === 200) {
                            // ایجاد یک لینک برای دانلود فایل
                            const url = window.URL.createObjectURL(new Blob([response.data]));
                            const link = document.createElement('a');
                            link.href = url;
                            link.setAttribute('download', 'transactions.xls'); // نام فایل دانلود
                            document.body.appendChild(link);
                            link.click(); // شروع دانلود فایل
                            document.body.removeChild(link); // حذف لینک پس از دانلود
                            this.isLoadingExport = false;
                        }
                    })
                    .catch((error) => {
                        // در صورت بروز خطا در دانلود
                        console.error('Error downloading the file', error);
                        this.errorFetching();
                        this.isLoadingExport = false;
                    });
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
