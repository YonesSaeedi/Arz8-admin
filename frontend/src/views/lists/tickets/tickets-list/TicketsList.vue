<template>

    <div id="tickets-list">
        <b-row v-if="!user">
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="ListIcon" format-number
                    :statistic="statistic && statistic.total_tickets? statistic.total_tickets.toLocaleString() :'0'"
                    statistic-title="همه تیکت ها"
                    id="total_tickets"
                />
                <b-tooltip target="total_tickets" variant="primary">
                    همه تیکت های موجود در سامانه
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="StarIcon" format-number
                    color="warning"
                    :statistic="statistic && statistic.pending_tickets? statistic.pending_tickets.toLocaleString() :'0'"
                    statistic-title="تیکت های در انتظار"
                    id="pending_tickets"
                />
                <b-tooltip target="pending_tickets" variant="warning">
                    تعداد تیکت هایی که در انتظار پاسخ پشتیبانی میباشد.
                </b-tooltip>
            </b-col>

            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="MessageCircleIcon" format-number
                    color="success"
                    :statistic="statistic && statistic.total_messages? statistic.total_messages.toLocaleString() :'0'"
                    statistic-title="تعداد پیام ها"
                    id="total_messages"
                />
                <b-tooltip target="total_messages" variant="success">
                    تعداد کل پیام های داخل همه تیکت ها
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="MessageSquareIcon" format-number
                    color="danger"
                    :statistic="statistic && statistic.open_tickets? statistic.open_tickets.toLocaleString() :'0'"
                    statistic-title="تیکت های باز"
                    id="open_tickets"
                />
                <b-tooltip target="open_tickets" variant="danger">
                    همه تیکت هایی که پاسخ داده شده اند و یا در انتظار پساخ هستند. تیکت ها بعد از گذشت 7 روز از آخرین بروزرسانی بسته خواهند شد.
                </b-tooltip>
            </b-col>

        </b-row>


        <!-- Filters -->
        <list-filters
            :status-filter.sync="statusFilter" :status-options="statusOptions"
            :date-start-filter.sync="dateStartFilter" :date-stop-filter.sync="dateStopFilter"
            :via-filter.sync="viaFilter" :via-options="viaOptions"
            :id-filter.sync="idFilter"
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
                        <b-link
                            :to="{ name: 'ticket-view', params: { id: data.item.id } }"
                            class="font-weight-bold d-block text-nowrap"
                        >
                            #{{ data.item.id }}
                        </b-link>
                    </div>
                </template>

                <!-- Column: Name Family -->
                <template #cell(nameFamily)="data">
                    <User :item="data.item"/>
                </template>

                <!-- Column: Title -->
                <template #cell(title)="data">
                    <div class="text-nowrap">
                        <b-link
                            :to="{ name: 'ticket-view', params: { id: data.item.id } }"
                            class="font-weight-bolder d-block text-nowrap font-small-3"
                        >
                        {{ data.item.title }}
                        </b-link>
                    </div>
                </template>


                <!-- Column: date -->
                <template #cell(date)="data">
                    <div class="text-nowrap vazir">
                        {{ $t(data.item.date) }}
                    </div>
                </template>

                <!-- Column: Status -->
                <template #cell(status)="data">
                    <div class="text-center">
                        <b-badge
                            pill
                            :variant="`${resolveStatusVariant(data.item.status)}`"
                            class="text-capitalize"
                        >
                            {{ $t(data.item.status) }}
                        </b-badge>
                    </div>
                </template>



                <!-- Column: Actions -->
                <template #cell(actions)="data">
                    <b-link
                        :to="{ name: 'ticket-view', params: { id: data.item.id } }"
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
    import {ref, onUnmounted} from '@vue/composition-api'
    import {avatarText} from '@core/utils/filter'
    import ListFilters from './TicketsListFilters.vue'
    import useTicketsList from './useTicketsList'
    import StatisticCardHorizontal from '@core/components/statistics-cards/StatisticCardHorizontal.vue'
    import User from '@/views/lists/User.vue'

    export default {
        props:['user','status'],
        components: {
            ListFilters,
            StatisticCardHorizontal,
            User,
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
            const viaOptions = [
                {label: 'وبسایت', value: 'website'},
                {label: 'اندروید', value: 'android'},
                {label: 'آی او اس', value: 'ios'}
            ]

            const statusOptions = [
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

                // Extra Filters
                statusFilter,
                dateStartFilter,dateStopFilter,
                viaFilter,
                idFilter,
                statistic,fetchStatistic,
                isLoading
            } = useTicketsList(props)

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

                statusOptions,
                viaOptions,

                // Extra Filters
                statusFilter,
                dateStartFilter, dateStopFilter,
                idFilter,
                viaFilter,
                statistic,fetchStatistic,
                isLoading
            }
        },
        mounted() {
            if(!this.user)
                this.fetchStatistic();
            this.statusOptions = [
                {label: this.$i18n.t('awaiting answer'), value: 'awaiting answer'},
                {label: this.$i18n.t('answered'), value: 'answered'},
                {label: this.$i18n.t('closed'), value: 'closed'}
            ]
        }
    }
</script>

<style lang="scss" scoped>
    .per-page-selector {
        width: 90px;
    }

    #tickets-list {
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
