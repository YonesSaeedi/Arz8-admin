<template>
    <div>
        <internal-list-add-new
            :is-add-new-sidebar-active.sync="isAddNewSidebarActive"
            :gatewayOptions="gatewayOptions"
            @refetch-data="refetchData"
        />

        <!-- Filters -->
        <internal-list-filters
            :type-filter.sync="typeFilter" :type-options="typeOptions"
            :status-filter.sync="statusFilter" :status-options="statusOptions"
            :date-start-filter.sync="dateStartFilter" :date-stop-filter.sync="dateStopFilter"
            :amount-start-filter.sync="amountStartFilter" :amount-stop-filter.sync="amountStopFilter"
            :via-filter.sync="viaFilter" :via-options="viaOptions"
            :id-filter.sync="idFilter"
            :gateway-filter.sync="gatewayFilter" :gateway-options="gatewayOptions"
            :other-filter.sync="otherFilter" :other-options="otherOptions"
            @refetchData="refetchData" :isLoading="isLoading"
        />

        <!-- Table Container Card -->
        <b-card no-body class="mb-0">
            <b-row class="mt-1">
                <!-- balance -->
                <b-col class="border py-1" cols="12" md="6" offset-md="3">
                    <b-row class="align-items-center">
                        <b-col cols="6" md="6" class="font-medium-1">
                            جمع کل واریزی ها:
                        </b-col>
                        <b-col class="text-center font-medium-1 text-success" cols="6" md="6">
                            <span v-if="tableCalculate">
                                {{toFixFloat(tableCalculate.sum_deposit)}} تومان
                            </span>
                            <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                        </b-col>
                    </b-row>
                </b-col>
                <b-col class="border py-1" cols="12" md="6" offset-md="3">
                    <b-row class="align-items-center">
                        <b-col cols="6" md="6" class="font-medium-1">
                            جمع کل برداشت ها:
                        </b-col>
                        <b-col class="text-center font-medium-1 text-warning" cols="6" md="6">
                            <span v-if="tableCalculate">
                                {{toFixFloat(tableCalculate.sum_withdraw)}} تومان
                            </span>
                            <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                        </b-col>
                    </b-row>
                </b-col>
                <b-col class="border py-1" cols="12" md="6" offset-md="3">
                    <b-row class="align-items-center">
                        <b-col cols="6" md="6" class="font-medium-1">
                            مانده:
                        </b-col>
                        <b-col class="text-center font-medium-1" cols="6" md="6">
                            <span v-if="tableCalculate">
                                {{toFixFloat(tableCalculate.residual)}} تومان
                            </span>
                            <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                        </b-col>
                    </b-row>
                </b-col>

            </b-row>

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
                        <b-button
                            variant="primary"
                            @click="isAddNewSidebarActive = true"
                        >
                            <span class="text-nowrap">افزودن تراکنش</span>
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
                ref="refInternalListTable"
                class="position-relative"
                :items="fetchInternal"
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
                            :to="{ name: 'tr-internal-view', params: { id: data.item.id } }"
                            class="font-weight-bold d-block text-nowrap"
                        >
                            #{{ data.item.id }}
                        </b-link>
                    </div>
                </template>


                <!-- Column: Name Family -->
                <template #cell(nameFamily)="data">
                    <div class="text-nowrap">
                        <b-link
                            :to="{ name: 'user-single', params: { id: data.item.id_user } }"
                            class="font-weight-bold d-block text-nowrap"
                        >
                        {{ data.item.name ? data.item.name+' '+data.item.family : data.item.name_display }}
                        </b-link>
                        <small class="text-muted">{{ data.item.email }}</small>
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
                        <span>{{$t(data.item.type)}}</span>
                    </div>
                </template>

                <!-- Column: amount -->
                <template #cell(amount)="data">
                    <div class="text-nowrap vazir">
                        <span>{{data.item.amount.toLocaleString()}} <small>{{$t(data.item.nameCurrency)}}</small></span>
                    </div>
                </template>

                <!-- Column: payment -->
                <template #cell(payment)="data">
                    <div class="text-nowrap">
                        <span>{{$t(data.item.payment_gateway)}}</span>
                    </div>
                </template>

                <!-- Column: date -->
                <template #cell(date)="data">
                    <div class="text-nowrap vazir">
                        {{ $t(data.item.date) }}
                    </div>
                </template>

                <!-- Column: cardbank -->
                <template #cell(cardbank)="data">
                    <div class="text-nowrap">
                        <span v-if="data.item.type == 'deposit'">{{$t(data.item.card_number)}}</span>
                        <span v-else>IR{{data.item.iban}}</span>
                    </div>
                </template>

                <!-- Column: Status -->
                <template #cell(status)="data">
                    <b-badge
                        pill
                        :variant="`light-${resolveStatusVariant(data.item.status)}`"
                        class="text-capitalize"
                    >
                        {{ $t(data.item.status) }}
                    </b-badge>
                </template>



                <!-- Column: Actions -->
                <template #cell(actions)="data">
                    <b-link
                        :to="{ name: 'tr-internal-view', params: { id: data.item.id } }"
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
        BBadge, BDropdown, BDropdownItem, BPagination,BTooltip,BSkeleton
    } from 'bootstrap-vue'
    import vSelect from 'vue-select'
    import {ref, onUnmounted, watch} from '@vue/composition-api'
    import {avatarText} from '@core/utils/filter'
    import InternalListFilters from './InternalListFilters.vue'
    import InternalListAddNew from './InternalListAddNew.vue'
    import useInternalList from './useInternalList'
    import jalaliMmoment from "jalali-moment";

    export default {
        props:['user','status'],
        components: {
            InternalListFilters,InternalListAddNew,
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
            BSkeleton,
            vSelect,
        },
        setup(props) {
            const isAddNewSidebarActive = ref(false)

            const typeOptions = [
                {label: 'واریز', value: 'deposit'},
                {label: 'برداشت', value: 'withdraw'}
            ]

            const viaOptions = [
                {label: 'وبسایت', value: 'website'},
                {label: 'اندروید', value: 'android'},
                {label: 'آی او اس', value: 'ios'}
            ]

            const gatewayOptions = [
            ]

            const statusOptions = [
            ]

            const otherOptions = [
                {label: 'تراکنش های درگاهی', value: 'trGateway'},
                {label: 'تراکنش های سفارشات', value: 'trOrders'},
                {label: 'واریز با فیش', value: 'trReceipt'}
            ]

            const {
                fetchInternal,
                tableColumns,
                perPage,
                currentPage,
                totalRows,
                dataMeta,
                perPageOptions,
                searchQuery,
                sortBy,
                isSortDirDesc,
                refInternalListTable,
                refetchData,

                // UI
                resolveStatusVariant,

                // Extra Filters
                typeFilter,
                statusFilter,
                dateStartFilter,dateStopFilter,
                amountStartFilter,amountStopFilter,
                viaFilter,
                idFilter,
                gatewayFilter, gatewayslist,tableCalculate,
                otherFilter,
                isLoading
            } = useInternalList(props)

            return {
                // Sidebar
                isAddNewSidebarActive,

                fetchInternal,
                tableColumns,
                perPage,
                currentPage,
                totalRows,
                dataMeta,
                perPageOptions,
                searchQuery,
                sortBy,
                isSortDirDesc,
                refInternalListTable,
                refetchData,

                // Filter
                avatarText,

                // UI
                resolveStatusVariant,

                typeOptions,
                statusOptions,
                viaOptions,
                gatewayOptions,
                otherOptions,

                // Extra Filters
                typeFilter,
                statusFilter,
                dateStartFilter, dateStopFilter,
                amountStartFilter, amountStopFilter,
                idFilter,
                viaFilter,
                gatewayFilter,gatewayslist,tableCalculate,
                otherFilter,
                isLoading
            }
        },
        watch:{
            gatewayslist(val){
                var gatewayOptions = [];
                var self = this;
                val.map(function(item) {
                    var obj = {label:self.$i18n.t(item.name), value:item.name };
                    gatewayOptions.push(obj);
                });
                this.gatewayOptions = gatewayOptions;
            },

        },
        mounted() {
            const NowDate = jalaliMmoment();
            var month =  NowDate.startOf('jMonth');
            this.dateStartFilter = month.format('jYYYY/jMM/jDD 00:00');

            this.statusOptions = [
                {label: this.$i18n.t('pending'), value: 'pending'},
                {label: this.$i18n.t('success'), value: 'success'},
                {label: this.$i18n.t('return'), value: 'return'},
                {label: this.$i18n.t('suspend'), value: 'suspend'},
                {label: this.$i18n.t('unsuccessful'), value: 'unsuccessful'},
                {label: this.$i18n.t('canceled'), value: 'canceled'},
                {label: this.$i18n.t('reject'), value: 'reject'},
            ]
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
