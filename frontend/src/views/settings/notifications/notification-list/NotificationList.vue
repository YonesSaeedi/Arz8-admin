<template>

    <div>

        <notification-add-new
            :is-add-new-sidebar-active.sync="isAddNewSidebarActive"
            @refetch-data="refetchData" :user="user"
        />

        <!-- Filters -->
        <notification-list-filters
            :date-start-filter.sync="dateStartFilter" :date-stop-filter.sync="dateStopFilter"
        />

        <b-tabs v-model="tabActive" v-if="!user">
            <b-tab title="اطلاعیه های عمومی">
            </b-tab>
            <b-tab title="اطلاعیه های خصوصی">
            </b-tab>
        </b-tabs>

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
                            <span class="text-nowrap">ارسال اطلاعیه جدید</span>
                        </b-button>
                        <v-select
                            v-model="msgLocale"
                            :dir="$store.state.appConfig.isRTL ? 'rtl' : 'ltr'"
                            :options="getGeneralInfo.locales.map(item => item.symbol)"
                            :clearable="false"
                            class="per-page-selector d-inline-block mx-50"
                        />
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
                ref="refMarketsListTable"
                class="position-relative"
                :items="listNotifications"
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

                <!-- Column: id -->
                <template #cell(id)="data">
                    <div class="text-nowrap text-capitalize">
                        <b-link @click="getNotif(data.item.id)" class="font-weight-bold d-block text-nowrap">
                            <div>
                               #{{ data.item.id}}
                            </div>
                        </b-link>
                    </div>
                </template>

                <!-- Column: name -->
                <template #cell(name)="data">
                    <div class="text-nowrap text-capitalize" v-if="data.item.name">
                        <div>{{ (data.item.name+' '+data.item.family) }}</div>
                        <small class="text-muted">{{ data.item.email }}</small>
                    </div>
                    <div class="text-nowrap text-capitalize" v-else>
                        همه کاربران
                    </div>
                </template>

                <!-- Column: title -->
                <template #cell(title)="data">
                    <div class="text-center text-nowrap">
                        <div>{{ $t(data.item.title) }}</div>
                    </div>
                </template>

                <!-- Column: message -->
                <template #cell(message)="data">
                    <div>{{ (data.item.message[msgLocale]) }}</div>
                </template>

                <!-- Column: date -->
                <template #cell(date)="data">
                    <div class="text-center text-nowrap">
                        <div>{{ (data.item.date) }}</div>
                    </div>
                </template>

                <!-- Column: Actions -->
                <template #cell(actions)="data">
                    <b-link @click="getNotif(data.item.id)" class="font-weight-bold d-block text-nowrap text-center">
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

        <!-- notification modal -->
        <notification :id="id" :modalShow="modalShow" @modalUpdate="modalUpdate" @refetchData="refetchData"/>

    </div>
</template>
<script>
    import {
        BCard, BRow, BCol, BFormInput, BButton, BTable, BMedia, BAvatar, BLink,
        BBadge, BDropdown, BDropdownItem, BPagination,BTooltip,BTabs,BTab
    } from 'bootstrap-vue'
    import vSelect from 'vue-select'
    import {ref, onUnmounted, watch} from '@vue/composition-api'
    import {avatarText} from '@core/utils/filter'
    import NotificationListFilters from './NotificationListFilters.vue'
    import useNotificationList from './useNotificationList'
    import NotificationAddNew from './NotificationAddNew'
    import notification from './Notification'

    export default {
        props:['user'],
        data () {
            return {
                id: null,
                modalShow: false,
                msgLocale:'fa'
            }
        },
        components: {
            notification,
            NotificationAddNew,
            NotificationListFilters,
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
            BTabs,BTab
        },
        setup(props) {
            const isAddNewSidebarActive = ref(false)


            const statusOptions = [
                {label: 'فعال', value: 'active'},
                {label: 'غیرفعال', value: 'inactive'}
            ]

            const {
                listNotifications,
                tableColumns,
                perPage,
                currentPage,
                totalRows,
                dataMeta,
                perPageOptions,
                searchQuery,
                sortBy,
                isSortDirDesc,
                refMarketsListTable,
                refetchData,

                // UI
                resolveStatusVariant,

                // Extra Filters
                dateStartFilter, dateStopFilter,
                tabActive,
            } = useNotificationList(props)

            return {
                // Sidebar
                isAddNewSidebarActive,

                listNotifications,
                tableColumns,
                perPage,
                currentPage,
                totalRows,
                dataMeta,
                perPageOptions,
                searchQuery,
                sortBy,
                isSortDirDesc,
                refMarketsListTable,
                refetchData,

                // Filter
                avatarText,

                // UI
                resolveStatusVariant,


                statusOptions,

                // Extra Filters
                dateStartFilter, dateStopFilter,
                tabActive
            }
        },
        methods: {
            getNotif(id){
                this.id = id;
                this.modalShow = true
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
