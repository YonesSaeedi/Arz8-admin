<template>

    <div id="gift-list">

        <gift-add-new
            :is-add-new-sidebar-active.sync="isAddNewSidebarActive"
            @refetch-data="refetchData"
        />

        <gift-edit
            :is-edit-sidebar-active.sync="isEditSidebarActive"
            :id-edit.sync="idEdit"
            @refetch-data="refetchData"
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
                        <b-button
                            variant="primary"
                            @click="isAddNewSidebarActive = true"
                        >
                            <span class="text-nowrap">افزودن کد تخفیف</span>
                        </b-button>
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

                <!-- Column: name -->
                <template #cell(name)="data">
                    <div class="text-nowrap text-center">
                        <strong class="text-capitalize">{{data.item.name}}</strong>
                        <br>
                        <small>{{data.item.description.fa}}</small>
                    </div>
                </template>

                <!-- Column: count -->
                <template #cell(count)="data">
                    <div class="text-nowrap text-center">
                        {{data.item.count.toLocaleString()}}
                    </div>
                </template>

                <!-- Column: discount -->
                <template #cell(discount)="data">
                    <div class="text-nowrap">
                        خرید: <strong class="text-success">{{data.item.buy_discount}}%</strong>
                        <br>
                        فروش: <strong class="text-danger">{{data.item.sell_discount}}%</strong>
                    </div>
                </template>

                <!-- Column: users -->
                <template #cell(users)="data">
                    <div>
                        <span v-if="!data.item.users"> ------ </span>
                        <div class="" v-else >
                            <b-link :to="{ name: 'user-single', params: { id: user } }" v-for="user in data.item.users"
                                    class="font-weight-bold mr-25">
                                #{{ user }}
                            </b-link>
                        </div>
                    </div>
                </template>

                <!-- Column: started -->
                <template #cell(started)="data">
                    <div class="text-nowrap">
                        {{data.item.started}}
                    </div>
                </template>

                <!-- Column: expired -->
                <template #cell(expired)="data">
                    <div class="text-nowrap">
                        {{data.item.expired}}
                        <div v-if="data.item.interval" class="text-success font-small-1">
                            {{data.item.interval}}
                        </div>
                        <div v-else class="text-danger font-small-1">منقضی</div>
                    </div>
                </template>

                <!-- Column: Actions -->
                <template #cell(actions)="data">
                    <feather-icon icon="EditIcon" class="text-primary" size="20" @click="isEditSidebarActive = true, idEdit = data.item.id"/>
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
    import useGiftList from './useGiftList'
    import GiftAddNew from './GiftAddNew.vue'
    import GiftEdit from './GiftEdit.vue'

    export default {
        props:['user'],
        components: {
            GiftAddNew,GiftEdit,
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
            const isAddNewSidebarActive = ref(false)
            const isEditSidebarActive = ref(false)
            const idEdit = ref(null)

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

            } = useGiftList(props.user)

            return {
                // Sidebar
                isAddNewSidebarActive,
                isEditSidebarActive,idEdit,


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

</style>

<style lang="scss">
    @import '@core/scss/vue/libs/vue-select.scss';
</style>
