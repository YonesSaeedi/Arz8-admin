<template>

    <div id="trades-list">


        <!-- Filters -->
        <list-filters
            :date-start-filter.sync="dateStartFilter" :date-stop-filter.sync="dateStopFilter"
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
                <!-- Column: nameFamily -->
                <template #cell(nameFamily)="data">
                    <div class="text-nowrap">
                        <b-link
                            :to="{ name: 'user-single', params: { id: data.item.id_user } }"
                            class="font-weight-bold d-block text-nowrap"
                        >
                            {{ data.item.name ? data.item.name+' '+data.item.family : '-------' }}
                        </b-link>
                        <small class="text-muted">{{ data.item.mobile?data.item.mobile:data.item.email }}</small>
                    </div>
                </template>

                <!-- Column: coin -->
                <template #cell(coin)="data">
                    <div class="d-flex align-content-between">
                        <div>
                            <i class="cf" style="font-size: 35px" v-if="isFontExist(data.item.symbol)" :class="'cf-'+data.item.symbol.toLowerCase()" :style="{color:colorSymbol(data.item.symbol)}"></i>
                            <img :src="baseURL+'images/currency/' + iconSymbol(data.item.symbol)" width="35px" v-else />
                        </div>
                        <div class="text-nowrap ml-1">
                            <div>{{ localeNameSymbol(data.item.symbol) ? localeNameSymbol(data.item.symbol)[localeHas] : data.item.name }}</div>
                            <small class="text-muted">{{data.item.symbol}}</small>
                        </div>
                    </div>
                </template>

                <!-- Column: network -->
                <template #cell(network)="data">
                    <div class="text-nowrap">
                        <span>{{data.item.net_name}} - {{data.item.net_symbol}}</span>
                    </div>
                </template>

                <!-- Column: created -->
                <template #cell(created)="data">
                    <div class="text-nowrap">
                        <div>{{ data.item.created }}</div>
                        <small class="text-muted">{{ data.item.updated }}</small>
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

                <!-- Column: amount -->
                <template #cell(amount)="data">
                    <div class="text-nowrap">
                        <div>{{toFixFloat(data.item.amount)}} <small>{{data.item.symbol}}</small></div>
                        <small class="text-muted">{{ data.item.count }}</small>
                    </div>
                </template>

                <!-- Column: address -->
                <template #cell(address)="data">
                    <div class="text-nowrap">
                        <div>{{ data.item.address }}</div>
                        <small class="text-muted" v-if="data.item.address_tag">tag: <span class="text-warning">{{ data.item.address_tag }}</span></small>
                    </div>
                </template>

                <!-- Column: Actions -->
                <template #cell(actions)="data">
                    <div class="text-center">
                        <feather-icon icon="XIcon" class="text-danger" size="20" @click="remove(data.item.id)"/>
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
    import ListFilters from './WalletsUsersListFilters.vue'
    import useWalletsUsersList from './useWalletsUsersList'
    import axiosIns from "@/libs/axios";

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
                statusFilter,
                isLoading
            } = useWalletsUsersList(props.user)

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
                statusFilter,statusOptions,
                isLoading
            }
        },
        methods: {
            remove(id){
                this.$swal({
                    title: 'از حذف اطمینان دارید؟',
                    text: 'ولت برای این کاربر حذف میشود.',
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
                        return  axiosIns.delete('/wallets-crypto/'+id )
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
                        console.log(result.value.data.status)
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
    @import '@core/scss/vue/libs/vue-select';
</style>
