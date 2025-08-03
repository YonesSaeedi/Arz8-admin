<template>

    <div id="trades-list">
        <b-row>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="UserPlusIcon"
                    color="info"
                    :statistic="statistic && statistic.users? statistic.users.toLocaleString() :'0'"
                    statistic-title="کاربران معرفی شده"
                    id="all-users"
                />
                <b-tooltip target="all-users" variant="info">
                    تمامی کاربرانی که توسط کد رفرال ثبت نام کرده اند.
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="ActivityIcon"
                    color="danger"
                    :statistic="statistic && statistic.transaction? statistic.transaction.toLocaleString() :'0'"
                    statistic-title="تعداد تراکنش ها"
                    id="transaction"
                />
                <b-tooltip target="transaction" variant="danger">
                    تعداد همه تراکنش هایی که پروسانت داده شده است.
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="UsersIcon"
                    color="success"
                    :statistic="statistic && statistic.amount? statistic.amount.toLocaleString() :'0'"
                    statistic-title="کل مبلغ رفرال"
                    id="gift-users-active"
                />
                <b-tooltip target="gift-users-active" variant="success">
                    جمع کل مبلغی که بعنوان پورسانت به کاربران معرف تعلق گرفته است.
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="UserCheckIcon"
                    color="warning"
                    :statistic="statistic && statistic.avg? statistic.avg.toLocaleString() :'0'"
                    statistic-title="میانگین پروسانت"
                    id="avg"
                />
                <b-tooltip target="avg" variant="warning">
                    میانگین پورسانت های داده شده در هر تراکنش
                </b-tooltip>
            </b-col>
        </b-row>

        <!-- Filters -->
        <list-filters
            :date-start-filter.sync="dateStartFilter" :date-stop-filter.sync="dateStopFilter"
            :amount-start-filter.sync="amountStartFilter" :amount-stop-filter.sync="amountStopFilter"
            :count-start-filter.sync="countStartFilter" :count-stop-filter.sync="countStopFilter"
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


                <!-- Column: user Caller -->
                <template #cell(userCaller)="data">
                    <div class="text-nowrap">
                        <b-link
                            :to="{ name: 'user-single', params: { id: data.item.id_user_caller } }"
                            class="font-weight-bold d-block text-nowrap"
                        >
                        {{ data.item.user_caller_name ? data.item.user_caller_name+' '+data.item.user_caller_family : '-------' }}
                        </b-link>
                        <small class="text-muted">{{ data.item.user_caller_email }}</small>
                    </div>
                </template>

                <!-- Column: user Referral -->
                <template #cell(userReferral)="data">
                    <div class="text-nowrap">
                        <b-link
                            :to="{ name: 'user-single', params: { id: data.item.id_user_referral } }"
                            class="font-weight-bold d-block text-nowrap"
                        >
                            {{ data.item.user_referral_name ? data.item.user_referral_name+' '+data.item.user_referral_family : '-------' }}
                        </b-link>
                        <small class="text-muted">{{ data.item.user_referral_email }}</small>
                    </div>
                </template>


                <!-- Column: percent All -->
                <template #cell(percentAll)="data">
                    <div class="text-nowrap text-center">
                        %{{ data.item.percent_all}}
                    </div>
                </template>
                <!-- Column: percent Caller -->
                <template #cell(percentCaller)="data">
                    <div class="text-nowrap text-center">
                        %{{ data.item.percent_caller}}
                    </div>
                </template>
                <!-- Column: percent Referral -->
                <template #cell(percentReferral)="data">
                    <div class="text-nowrap text-center">
                        %{{ data.item.percent_referral}}
                    </div>
                </template>

                <template #cell(countReferral)="data">
                    <div class="text-nowrap text-center">
                        {{ data.item.count_transaction}}
                    </div>
                </template>

                <template #cell(amountReferral)="data">
                    <div class="text-nowrap text-center">
                        {{ data.item.total_amount.toLocaleString()}} <small>تومان</small>
                    </div>
                </template>

                <!-- Column: date -->
                <template #cell(date)="data">
                    <div class="text-nowrap vazir">
                        {{ $t(data.item.date) }}
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
        BBadge, BDropdown, BDropdownItem, BPagination,BTooltip
    } from 'bootstrap-vue'
    import vSelect from 'vue-select'
    import {ref, onUnmounted} from '@vue/composition-api'
    import {avatarText} from '@core/utils/filter'
    import ListFilters from './ReferralUsersListFilters.vue'
    import useReferralUsersList from './useReferralUsersList'
    import StatisticCardHorizontal from '@core/components/statistics-cards/StatisticCardHorizontal.vue'


    export default {
        props:['user'],
        components: {
            StatisticCardHorizontal,
            ListFilters,
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
                dateStartFilter,dateStopFilter,
                amountStartFilter,amountStopFilter,
                countStartFilter,countStopFilter,

                statistic,fetchStatistic,
                isLoading
            } = useReferralUsersList(props.user)

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

                // Extra Filters
                dateStartFilter, dateStopFilter,
                amountStartFilter, amountStopFilter,
                countStartFilter, countStopFilter,

                statistic,fetchStatistic,
                isLoading
            }
        },
        mounted() {
            this.fetchStatistic();
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
