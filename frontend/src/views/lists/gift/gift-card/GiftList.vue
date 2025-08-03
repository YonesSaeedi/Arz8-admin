<template>

    <div id="gift-list">

        <b-row v-if="!user">
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="GiftIcon"
                    :statistic="statistic && statistic.all? toFixFloat(statistic.all) :'0'"
                    statistic-title="کل کارت های هدیه"
                    id="all-gift"
                />
                <b-tooltip target="all-gift" variant="primary">
                    تمامی کارت های هدیه
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="UsersIcon"
                    color="success"
                    :statistic="statistic && statistic.active? toFixFloat(statistic.active) :'0'"
                    statistic-title="کارت های فعال"
                    id="gift-users-active"
                />
                <b-tooltip target="gift-users-active" variant="success">
                    تمامی کارت های هدیه ای که صدور شده اند و هنوز کسی اسفاده نکرده!
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="ActivityIcon"
                    color="danger"
                    :statistic="statistic && statistic.anount_usdt? toFixFloat(statistic.anount_usdt) :'0'"
                    statistic-title="مقدار دلاری"
                    id="gift-users-all"
                />
                <b-tooltip target="gift-users-all" variant="danger">
                    ارزش کل کارت های هدیه صادر شده به دلار
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="UserCheckIcon"
                    color="warning"
                    :statistic="statistic && statistic.anount_toman? toFixFloat(statistic.anount_toman) :'0'"
                    statistic-title="مقدار تومانی"
                    id="gift-users-id"
                />
                <b-tooltip target="gift-users-id" variant="warning">
                    ارزش کل کارت های هدیه صادر شده به تومان
                </b-tooltip>
            </b-col>
        </b-row>

        <gift-list-filters
            :date-start-filter.sync="dateStartFilter" :date-stop-filter.sync="dateStopFilter"
            :status-filter.sync="statusFilter" :status-options="statusOptions"
            :for-filter.sync="forFilter" :for-options="forOptions"
            @refetchData="refetchData" :isLoading="isLoading"
        />

        <gift-add-new
            :is-add-new-sidebar-active.sync="isAddNewSidebarActive"
            @refetch-data="refetchData"
            @remove="remove"
            :id-edit.sync="idEdit"
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
                        <b-button v-if="!user"
                            variant="primary"
                            @click="isAddNewSidebarActive = true"
                        >
                            <span class="text-nowrap">افزودن کارت هدیه</span>
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

                <!-- Column: coin -->
                <template #cell(coin)="data">
                    <div :id="`orders-row-${data.item.id}`">
                        <i class="cf" style="font-size: 35px" v-if="isFontExist(data.item.symbol)" :class="'cf-'+data.item.symbol.toLowerCase()" :style="{color:colorSymbol(data.item.symbol)}"></i>
                        <img :src="baseURL+'images/currency/' + iconSymbol(data.item.symbol)" width="35px" v-else />
                    </div>
                    <b-tooltip :target="`orders-row-${data.item.id}`" placement="top" variant="primary">
                        {{localeNameSymbol(data.item.symbol) ? localeNameSymbol(data.item.symbol)[localeHas] : $t(data.item.nameCoin)}}
                    </b-tooltip>
                </template>

                <!-- Column: cardNamber -->
                <template #cell(cardNamber)="data">
                    <div class="text-nowrap font-weight-bolder text-primary">
                        {{data.item.card_namber}}
                    </div>
                </template>

                <!-- Column: amount -->
                <template #cell(amount)="data">
                    <div class="text-nowrap">
                        <span>{{parseFloat(data.item.amount).toLocaleString()}} <small>{{data.item.symbol}}</small></span>
                    </div>
                </template>

                <!-- Column: Name Family issuer -->
                <template #cell(issuer)="data">
                    <div class="text-nowrap" v-if="data.item.id_issuer">
                        <b-link
                            :to="{ name: 'user-single', params: { id: data.item.id_issuer } }"
                            class="font-weight-bold d-block text-nowrap"
                        >
                            <img :src="require('@/assets/images/logo/logo-'+ levelAccount(data.item.level_account) +'.png')" width="30">
                            {{data.item.name_issuer+' '+data.item.family_issuer }}
                        </b-link>
                        <small class="text-muted">{{ data.item.mobile_issuer }}</small>
                    </div>
                    <div v-else class="text-nowrap d-flex align-items-center">
                        <img :src="require('@/assets/images/logo/logo.png')" width="25">
                        <div class="font-weight-bolder text-primary ml-1">امور مالی</div>
                    </div>
                </template>

                <!-- Column: Name Family consumer -->
                <template #cell(consumer)="data">
                    <div class="text-nowrap" v-if="data.item.id_user_consumer">
                        <b-link
                            :to="{ name: 'user-single', params: { id: data.item.id_user_consumer } }"
                            class="font-weight-bold d-block text-nowrap"
                        >
                            <img :src="require('@/assets/images/logo/logo-'+ levelAccount(data.item.consumer.level_account) +'.png')" width="25">
                            {{data.item.consumer.name+' '+data.item.consumer.family }}
                        </b-link>
                        <small class="text-muted">{{ data.item.consumer.mobile?data.item.consumer.mobile:data.item.consumer.email }}</small>
                    </div>
                    <div v-else-if="data.item.expired" class="text-nowrap">
                        <feather-icon class="text-primary font-weight-bold" @click="modalIdUsers = JSON.parse(data.item.data).id_user_used,modalUsersList=true" icon="UsersIcon" size="25"/>
                        کارت هدیه عمومی
                    </div>
                    <div v-else class="text-nowrap">
                        هنوز مصرف کننده ای ندارد
                    </div>
                </template>


                <!-- Column: created -->
                <template #cell(created)="data">
                    <div class="text-nowrap">
                        {{data.item.created}}
                    </div>
                </template>

                <!-- Column: activated -->
                <template #cell(activated)="data">
                    <div class="text-nowrap">
                        {{data.item.activated? data.item.activated:'-----'}}
                    </div>
                </template>

                <!-- Column: expired -->
                <template #cell(expired)="data">
                    <div class="text-nowrap" v-if="data.item.expired">
                        {{data.item.expired}}
                        <div v-if="data.item.interval" class="text-success font-small-1">
                            {{data.item.interval}}
                        </div>
                        <div v-else class="text-danger font-small-1">منقضی</div>
                    </div>
                    <div v-else class="text-nowrap">-----</div>
                </template>

                <!-- Column: count -->
                <template #cell(count)="data">
                    <div class="text-nowrap text-center">
                        {{data.item.count>=0? data.item.count:'------'}}<br>
                        {{data.item.started?data.item.started:''}}
                    </div>
                </template>



                <!-- Column: started -->
                <template #cell(started)="data">
                    <div class="text-nowrap">
                        {{data.item.started}}
                    </div>
                </template>


                <!-- Column: Actions -->
                <template #cell(actions)="data">
                    <div v-if="!data.item.id_issuer">
                        <feather-icon icon="EditIcon" class="text-primary" size="20" @click="isAddNewSidebarActive = true, idEdit = data.item.id"/>
                    </div>
                    <div v-else>
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


        <b-modal v-model="modalUsersList" size="xl" scrollable >
            <UsersList :idUsers="modalIdUsers"/>
            <template #modal-footer>
                <div class="w-100">
                    <b-button
                        variant="primary"
                        size="sm"
                        class="float-right"
                        @click="modalUsersList=false"
                    >
                        بستن
                    </b-button>
                </div>
            </template>
        </b-modal>
    </div>
</template>
<script>
import {
    BCard, BRow, BCol, BFormInput, BButton, BTable, BMedia, BAvatar, BLink,
    BBadge, BDropdown, BDropdownItem, BPagination, BTooltip, BModal
} from 'bootstrap-vue'
    import vSelect from 'vue-select'
    import {ref} from '@vue/composition-api'
    import {avatarText} from '@core/utils/filter'
    import useGiftList from './useGiftList'
    import GiftAddNew from './GiftAddNew.vue'
    import GiftListFilters from './GiftlListFilters.vue'
    import StatisticCardHorizontal from "@core/components/statistics-cards/StatisticCardHorizontal.vue";
    import GiftEdit from "@/views/lists/gift/gift-list/GiftEdit.vue";
    import UsersList from '@/views/lists/users/users-list/UsersList.vue';
    import axiosIns from "@/libs/axios";

    export default {
        props:['user'],
        components: {
            BModal,UsersList,
            GiftEdit,
            StatisticCardHorizontal,
            GiftAddNew,GiftListFilters,
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
            const idEdit = ref(null)
            const modalUsersList = ref(false)
            const modalIdUsers = ref(null)

            const statusOptions = [
                {label: 'کد های فعال و مصرف نشده', value: 'active'},
                {label: 'کد های مصرف شده', value: 'notactive'},
            ]

            const forOptions = [
                {label: 'کد های عمومی(توسط مدیریت)', value: 'admins'},
                {label: 'کد های خصوصی(توسط کاربران)', value: 'users'},
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
                statistic,fetchStatistic,
                forFilter,
                isLoading,
            } = useGiftList(props.user)

            return {
                // Sidebar
                isAddNewSidebarActive,idEdit,
                modalUsersList,modalIdUsers,

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
                dateStartFilter,dateStopFilter,
                statusFilter,statusOptions,
                statistic,fetchStatistic,
                forFilter,forOptions,
                isLoading,
            }
        },
        created() {
            this.fetchStatistic();
        },
        methods:{
            remove(id){
                this.$swal({
                    title: 'از حذف اطمینان دارید؟',
                    text: 'کارت هدیه با تمامی اطلاعات متصل به آن مانند کاربرانی که استفاده میکنند حذف میشود.',
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
                        return  axiosIns.delete('/gift/card/remove/'+ id )
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
                        //console.log(result.value.data.status)
                        if (result.value.data.status == true){
                            this.refetchData();
                            this.isAddNewSidebarActive = false;
                            this.$swal({icon: 'success',title: 'موفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }else{
                            this.$swal({icon: 'error',title: 'ناموفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }
                    }
                })
            }
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
