<template>

    <div id="trades-list">


        <!-- Filters -->
        <list-filters
            :date-start-filter.sync="dateStartFilter" :date-stop-filter.sync="dateStopFilter"
            :amount-start-filter.sync="amountStartFilter" :amount-stop-filter.sync="amountStopFilter"
            :for-filter.sync="forFilter" :for-options="forOptions"
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
                        <span>{{data.item.id}}</span>
                    </div>
                </template>

                <!-- Column: user -->
                <template #cell(user)="data">
                    <div class="text-nowrap">
                        <b-link
                            :to="{ name: 'user-single', params: { id: data.item.id_user } }"
                            class="font-weight-bold d-block text-nowrap"
                        >
                            {{ data.item.name ? data.item.name+' '+data.item.family : '-------' }}
                        </b-link>
                        <small class="text-muted">{{ data.item.email }}</small>
                    </div>
                </template>

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


                <!-- Column: amount -->
                <template #cell(amount)="data">
                    <div class="text-nowrap">
                        <span>{{toFixFloat(data.item.amount)}} <small>تومان</small></span>
                    </div>
                </template>

                <!-- Column: amountToman -->
                <template #cell(amountToman)="data">
                    <div class="text-nowrap">
                        <span>{{toFixFloat(data.item.amount_toman)}} <small>تومان</small></span>
                    </div>
                </template>


                <!-- Column: for -->
                <template #cell(for)="data">
                    <div class="text-nowrap text-center">
                        <b-link  v-if="data.item.id_order" :to="{ name: 'order-view', params: { id: data.item.id_order } }"
                                 class="font-weight-bold d-block text-nowrap">
                            سفارش #{{ data.item.id_order }}
                        </b-link>
                        <b-link  v-else :to="{ name: 'trade-view', params: { id: data.item.id_trade } }"
                                 class="font-weight-bold d-block text-nowrap">
                            معامله #{{ data.item.id_trade }}
                        </b-link>
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
    import ListFilters from './ReferralTransactionListFilters.vue'
    import useReferralTransactionList from './useReferralTransactionList'

    export default {
        props:['user'],
        components: {
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
            const forOptions = [
                {label: 'سفارشات', value: 'orders'},
                {label: 'معاملات', value: 'trades'},
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
                forFilter,

                // Extra Filters
                dateStartFilter,dateStopFilter,
                amountStartFilter,amountStopFilter,
                countStartFilter,countStopFilter,
                isLoading
            } = useReferralTransactionList(props.user)

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

                forOptions,
                forFilter,

                // Extra Filters
                dateStartFilter, dateStopFilter,
                amountStartFilter, amountStopFilter,
                countStartFilter, countStopFilter,
                isLoading
            }
        },
        mounted() {

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
