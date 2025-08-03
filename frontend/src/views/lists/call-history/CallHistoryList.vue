<template>
    <div>

        <call-add-new
            :is-add-new-sidebar-active.sync="isAddNewSidebarActive"
            :mobile="user ?user.mobile:null"
            @refetch-data="refetchData"
        />

        <!-- Filters -->
        <call-history-list-filters
            :via-filter.sync="viaFilter" :via-options="viaOptions"
            :twofa-filter.sync="twofaFilter" :twofa-options="twofaOptions"
            :date-start-filter.sync="dateStartFilter" :date-stop-filter.sync="dateStopFilter"
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
                        <b-button
                            variant="primary"
                            @click="isAddNewSidebarActive = true"
                        >
                            <span class="text-nowrap">افزودن تماس</span>
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
                ref="refCardsListTable"
                class="position-relative"
                :items="fetchCards"
                responsive
                :fields="tableColumns"
                primary-key="id"
                :sort-by.sync="sortBy"
                show-empty
                striped
                empty-text="داده ای برای نمایش وجود ندارد"
                :sort-desc.sync="isSortDirDesc"
            >

                <!-- Column: id -->
                <template #cell(id)="data">
                    <div class="text-nowrap">
                        #{{ data.item.id }}
                    </div>
                </template>


                <!-- Column: Name Family -->
                <template #cell(nameFamily)="data">
                    <div class="text-nowrap">
                        <b-link
                            :to="{ name: 'user-single', params: { id: data.item.id_user } }"
                            class="font-weight-bold d-block text-nowrap"
                        >
                            <img :src="require('@/assets/images/logo/logo-'+ levelAccount(data.item.level_account) +'.png')" width="25">
                            {{ data.item.name ? data.item.name+' '+data.item.family : '-------' }}
                        </b-link>
                        <small class="text-muted">{{ data.item.email }}</small>
                    </div>
                </template>

                <!-- Column: text -->
                <template #cell(text)="data">
                    <div class="text-call">
                        <span>{{data.item.text}}</span>
                    </div>
                </template>

                <!-- Column: Name Family -->
                <template #cell(admin)="data">
                    <div class="text-nowrap">
                       <div>
                        {{ data.item.admin_name ? data.item.admin_name : '-------' }}
                       </div>
                        <small class="text-muted">{{ data.item.admin_email }}</small>
                    </div>
                </template>

                <!-- Column: date -->
                <template #cell(date)="data">
                    <div class="text-nowrap vazir">
                        <span>{{data.item.date}}</span>
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
        BBadge, BDropdown, BDropdownItem, BPagination,BTooltip,VBTooltip,
    } from 'bootstrap-vue'
    import vSelect from 'vue-select'
    import {ref, onUnmounted, watch} from '@vue/composition-api'
    import {avatarText} from '@core/utils/filter'
    import CallHistoryListFilters from './CallHistoryListFilters.vue'
    import useCallHistoryList from './useCallHistoryList'
    import Ripple from "vue-ripple-directive";
    import CallAddNew from "./CallAddNew.vue";
    import axiosIns from "@/libs/axios";

    export default {
        props:['user','status'],
        components: {
            CallAddNew,
            CallHistoryListFilters,
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
            BTooltip,VBTooltip,
            vSelect,
        },
        directives: {
            'b-tooltip': VBTooltip,
            Ripple,
        },
        setup(props) {
            const isAddNewSidebarActive = ref(false)

            const viaOptions = [
                {label: 'وبسایت', value: 'website'},
                {label: 'اندروید', value: 'android'},
                {label: 'آی او اس', value: 'ios'}
            ]
            const twofaOptions = [
                {label: 'پیامک', value: 'sms'},
                {label: 'ایمیل', value: 'email'},
                {label: 'گوگل', value: 'google'},
                {label: 'بدون ورود دو مرحله ای', value: 'nothing'}
            ]


            const {
                fetchCards,
                tableColumns,
                perPage,
                currentPage,
                totalRows,
                dataMeta,
                perPageOptions,
                searchQuery,
                sortBy,
                isSortDirDesc,
                refCardsListTable,
                refetchData,

                // UI
                resolveStatusVariant,

                // Extra Filters
                typeFilter,
                viaFilter,
                twofaFilter,
                dateStartFilter,dateStopFilter,
                bankFilter,
                isLoading
            } = useCallHistoryList(props)

            return {
                isAddNewSidebarActive,

                fetchCards,
                tableColumns,
                perPage,
                currentPage,
                totalRows,
                dataMeta,
                perPageOptions,
                searchQuery,
                sortBy,
                isSortDirDesc,
                refCardsListTable,
                refetchData,

                // Filter
                avatarText,

                // UI
                resolveStatusVariant,

                viaOptions,
                twofaOptions,

                // Extra Filters
                typeFilter,
                viaFilter,
                twofaFilter,
                dateStartFilter, dateStopFilter,
                bankFilter,
                isLoading
            }
        },
        mounted() {

        },
        data(){
            return {
                id: null,
                nameFamily: null,
                modalShow: false,
            }
        },
        methods:{
            remove(id){
                this.$swal({
                    title: 'از حذف اطمینان دارید؟',
                    text: '',
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
                        return  axiosIns.delete('/call-history/remove/'+id )
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
    .text-call{
        //max-width: 400px;
        //white-space: normal !important;
    }
</style>
