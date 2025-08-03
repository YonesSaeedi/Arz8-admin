<template>

    <div id="trades-list">

        <b-row>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="GiftIcon"
                    :statistic="statistic && statistic.count? parseInt(statistic.count).toLocaleString() :'0'"
                    statistic-title="کل شانس ها"
                    id="count"
                />
                <b-tooltip target="count" variant="primary">
                    تعداد کل گردونه های زده شده
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="DollarSignIcon"
                    color="success"
                    :statistic="statistic && statistic.amount?  parseInt(statistic.amount).toLocaleString() :'0'"
                    statistic-title="مبلغ کل جوایز"
                    id="amount"
                />
                <b-tooltip target="amount" variant="success">
                    مبلغ کل جوایز ارزی و تومانی
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="UsersIcon"
                    color="danger"
                    :statistic="statistic && statistic.users_count?  parseInt(statistic.users_count).toLocaleString() :'0'"
                    statistic-title="تعداد کاربران"
                    id="users_count"
                />
                <b-tooltip target="users_count" variant="danger">
                   تعداد کاربرانی که حداقل یک بار شناس خود را امتحان کرده اند.
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="MehIcon"
                    color="warning"
                    :statistic="statistic && statistic.empty_count?  parseInt(statistic.empty_count).toLocaleString() :'0'"
                    statistic-title="پوچ ها"
                    id="empty_count"
                />
                <b-tooltip target="empty_count" variant="warning">
                    تعداد شانس های پوچ
                </b-tooltip>
            </b-col>
        </b-row>


        <!-- Filters -->
        <list-filters
            :date-start-filter.sync="dateStartFilter" :date-stop-filter.sync="dateStopFilter"
            :amount-start-filter.sync="amountStartFilter" :amount-stop-filter.sync="amountStopFilter"
            :status-filter.sync="statusFilter" :status-options="statusOptions"
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
                        #{{ data.item.id }}
                    </div>
                </template>

                <!-- Column: nameFamily -->
                <template #cell(nameFamily)="data">
                    <div class="text-nowrap">
                        <b-link
                            :to="{ name: 'user-single', params: { id: data.item.id_user } }"
                            class="font-weight-bold d-block text-nowrap"
                        >
                            <img :src="require('@/assets/images/logo/logo-'+ levelAccount(data.item.level_account) +'.png')" width="25">
                            {{ data.item.name ? data.item.name+' '+data.item.family : '-------' }} <small>(level: {{data.item.level}})</small>
                        </b-link>
                        <small class="text-muted">{{ data.item.email }}</small>
                    </div>
                </template>

                <!-- Column: item count -->
                <template #cell(count)="data">
                    <div class="text-nowrap">
                        {{data.item.count.toLocaleString()}}
                    </div>
                </template>

                <!-- Column: item Name -->
                <template #cell(gift)="data">
                    <div class="text-nowrap">
                        {{data.item.gift}}
                    </div>
                </template>

                <!-- Column: possibility -->
                <template #cell(possibility)="data">
                    <div class="text-nowrap">
                        %{{data.item.possibility}}
                    </div>
                </template>

                <!-- Column: amount -->
                <template #cell(amount)="data">
                    <div class="text-nowrap">
                        {{data.item.amount.toLocaleString()}}
                    </div>
                </template>

                <!-- Column: level -->
                <template #cell(level)="data">
                    <div class="text-nowrap">
                        <img :src="require('@/assets/images/logo/logo-'+ levelAccount(data.item.level_gift) +'.png')" width="25">
                    </div>
                </template>

                <!-- Column: date -->
                <template #cell(date)="data">
                    <div class="text-nowrap">
                        {{data.item.date}}
                    </div>
                </template>

                <!-- Column: via -->
                <template #cell(via)="data">
                    <img src="@/assets/images/icons/website.png" class="mx-50" width="30" v-if="data.item.via === 'website'"
                         v-b-tooltip.hover :title="'ثبت از طریق وب سایت'"/>
                    <img src="@/assets/images/icons/android.png" class="mx-50" width="30" v-if="data.item.via === 'android'"
                         v-b-tooltip.hover :title="'ثبت از طریق اندروید'"/>
                    <img src="@/assets/images/icons/ios.png" class="mx-50" width="30" v-if="data.item.via === 'ios'"
                         v-b-tooltip.hover :title="'ثبت از طریق ios'"/>
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
    BBadge, BDropdown, BDropdownItem, BPagination, BTooltip, VBTooltip
} from 'bootstrap-vue'
    import vSelect from 'vue-select'
    import {ref, onUnmounted} from '@vue/composition-api'
    import {avatarText} from '@core/utils/filter'
    import ListFilters from './WheelListFilters.vue'
    import useWheelList from './useWheeList'
    import axiosIns from "@/libs/axios";
    import StatisticCardHorizontal from "@core/components/statistics-cards/StatisticCardHorizontal.vue";
    import LoginHistoryListFilters from "@/views/lists/login-history/LoginHistoryListFilters.vue";
import Ripple from "vue-ripple-directive";

    export default {
        props:['user'],
        directives: {
            'b-tooltip': VBTooltip,
            Ripple,
        },
        components: {
            LoginHistoryListFilters,
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
            vSelect,VBTooltip
        },
        setup(props) {
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
                dateStartFilter,dateStopFilter,
                amountStartFilter,amountStopFilter,
                statusFilter,
                statistic,fetchStatistic,
                isLoading,
            } = useWheelList(props.user)

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
                statusFilter,statusOptions,
                statistic,fetchStatistic,
                isLoading,
            }
        },
        methods: {
            remove(id){
                this.$swal({
                    title: 'از حذف اطمینان دارید؟',
                    text: 'جایزه ای که  گرفته حذف نمیشود و فقط رکورد گردونه شانس حذف میشود.',
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
                        return  axiosIns.delete('/gift/wheel/remove/'+id )
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
        mounted() {
            this.fetchStatistic();
            //if(!this.user)
                //this.fetchStatistic();
            this.statusOptions = [
                {label: this.$i18n.t('active'), value: 'active'},
                {label: this.$i18n.t('expired'), value: 'expired'},
            ]
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
