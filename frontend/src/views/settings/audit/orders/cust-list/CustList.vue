<template>

    <div>
        <cust-add-new
            :is-add-new-sidebar-active.sync="isAddNewSidebarActive"
            @refetch-data="refetchData"
        />

        <!-- Filters -->
        <cust-list-filters
            :date-start-filter.sync="dateStartFilter" :date-stop-filter.sync="dateStopFilter"
            :amount-start-filter.sync="amountStartFilter" :amount-stop-filter.sync="amountStopFilter"
        />

        <!-- Table Container Card -->
        <b-card no-body class="mb-0">
            <div class="m-2">
                <b-row>
                    <!-- balance -->
                    <b-col class="border py-1" cols="12" md="6" offset-md="3">
                        <b-row class="align-items-center">
                            <b-col cols="6" md="6" class="font-medium-1">
                                جمع کل هزنیه های تومانی:
                            </b-col>
                            <b-col class="text-center font-medium-1 text-warning" cols="6" md="6">
                                <span v-if="sumCust !== null">
                                    {{(sumCust)?toFixFloat(sumCust):0}} تومان
                                </span>
                                <b-skeleton class="mx-auto mb-0" v-else width="30%"></b-skeleton>
                            </b-col>
                        </b-row>
                    </b-col>

                </b-row>
            </div>

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
                            <span class="text-nowrap">ثبت هزینه</span>
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
                ref="refCustListTable"
                class="position-relative"
                :items="fetchOrders"
                responsive
                :fields="tableColumns"
                primary-key="id"
                :sort-by.sync="sortBy"
                show-empty
                striped hover
                empty-text="داده ای برای نمایش وجود ندارد"
                :sort-desc.sync="isSortDirDesc"
            >
                <!-- Column: date -->
                <template #cell(date)="data">
                    <div class="text-nowrap vazir">
                        {{ (data.item.date) }}
                    </div>
                </template>

                <!-- Column: amount -->
                <template #cell(amount)="data">
                    <div class="text-nowrap">
                        <span>{{toFixFloat(data.item.amount)}} <small>{{data.item.asset ==='TOMAN'?'تومان':data.item.asset}}</small></span>
                        <div v-if="data.item.asset ==='BNB' || data.item.asset ==='CET'" class="font-small-3">
                            با قیمت:
                            {{toFixFloat(data.item.fee)}} تومان
                        </div>
                    </div>
                </template>

                <!-- Column: type -->
                <template #cell(type)="data">
                    <div class="text-nowrap">
                        <b-avatar
                            :id="`cust-row-${data.item.id}`"
                            size="32"
                            :variant="data.item.type == 'increase'? `light-success` : `light-danger`"
                        >
                            <feather-icon
                                :icon="data.item.type == 'increase' ? 'TrendingUpIcon' : 'TrendingDownIcon'"
                            />
                        </b-avatar>
                        <span>{{data.item.type == 'increase' ? 'شارژ' :'هزینه'}}</span>
                    </div>
                </template>

                <!-- Column: description -->
                <template #cell(description)="data">
                    <div class="text-nowrap">
                        <span>{{toFixFloat(data.item.description)}}</span>
                    </div>
                </template>

                <!-- Column: file -->
                <template #cell(file)="data">
                    <div class="text-nowrap" v-if="data.item.file">
                        <feather-icon icon="FileIcon" size="20" @click="downloadFile(data.item.id)"/>
                    </div>
                    <span v-else> ---- </span>
                </template>

                <!-- Column: action -->
                <template #cell(action)="data">
                    <div class="text-nowrap text-danger">
                        <feather-icon icon="TrashIcon" size="20" @click="removeCust(data.item.id)"/>
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
        BBadge, BDropdown, BDropdownItem, BPagination,BTooltip,BFormGroup,BSkeleton
    } from 'bootstrap-vue'
    import vSelect from 'vue-select'
    import {ref} from '@vue/composition-api'
    import {avatarText} from '@core/utils/filter'
    import CustListFilters from './CustListFilters.vue'
    import CustAddNew from './CustAddNew.vue'
    import useCustList from './useCustList'
    import axiosIns from "@/libs/axios";

    export default {
        data () {
            return {

            }
        },
        components: {
            CustListFilters,CustAddNew,
            BCard,
            BRow,
            BCol,
            BFormInput,BFormGroup,BSkeleton,
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


            const isAddNewSidebarActive = ref(false)

            const {
                fetchOrders,
                tableColumns,
                perPage,
                currentPage,
                totalRows,
                dataMeta,
                perPageOptions,
                searchQuery,
                sortBy,
                isSortDirDesc,
                refCustListTable,
                refetchData,

                // UI
                resolveStatusVariant,

                // Extra Filters
                dateStartFilter,dateStopFilter,
                amountStartFilter,amountStopFilter,
                sumCust
            } = useCustList()

            return {
                isAddNewSidebarActive,

                fetchOrders,
                tableColumns,
                perPage,
                currentPage,
                totalRows,
                dataMeta,
                perPageOptions,
                searchQuery,
                sortBy,
                isSortDirDesc,
                refCustListTable,
                refetchData,

                // Filter
                avatarText,

                // UI
                resolveStatusVariant,


                // Extra Filters
                dateStartFilter, dateStopFilter,
                amountStartFilter, amountStopFilter,
                sumCust
            }
        },
        methods:{
            removeCust(id){
                this.$swal({
                    title: 'از حذف اطمینان دارید؟',
                    text: 'هزینه با تمامی اطلاعات و فایلش حذف میشود.',
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
                        return  axiosIns.delete('/audit/cust/remove/'+id )
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
            },
            downloadFile(id){
                axiosIns.get( 'audit/cust/file/'+id,{responseType: 'blob'}).then((res) => {
                    //var nameOfTheCsv = res.data.name;
                    const downloadUrl = window.URL.createObjectURL(new Blob([res.data]));
                    const link = document.createElement('a');
                    link.href = downloadUrl;
                    link.setAttribute('download', res.headers['name']);
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                });
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
</style>
