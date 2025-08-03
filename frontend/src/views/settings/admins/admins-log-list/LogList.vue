<template>

    <div>
        <!-- Filters-->
        <log-list-filters
            :date-start-filter.sync="dateStartFilter" :date-stop-filter.sync="dateStopFilter"
            :admin-filter.sync="adminFilter" :admins-options="adminsFillterOptions"
            :keyword-filter.sync="keywordFilter" :keyword-options="keywordOptions"
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
                    <b-col
                        cols="12"
                        md="9"
                        class="mb-1 mb-md-0 text-right"
                    >
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
                ref="refLogListTable"
                class="position-relative"
                :items="listLog"
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
                    <div class="text-nowrap">
                        <span>#{{ data.item.id }}</span>
                    </div>
                </template>

                <!-- Column: Name Family -->
                <template #cell(nameFamily)="data">
                    <div class="text-nowrap">
                        <span>{{ data.item.name }}</span>
                    </div>
                </template>

                <!-- Column: registeryDate -->
                <template #cell(registeryDate)="data">
                    <div class="text-nowrap vazir">
                        {{ $t(data.item.registeryDate) }}
                    </div>
                </template>

                <!-- Column: key -->
                <template #cell(key)="data">
                    <div class="text-nowrap">
                        <span>{{ getKeyword(data.item.key) }}</span>
                    </div>
                </template>

                <!-- Column: key -->
                <template #cell(key)="data">
                    <div class="text-nowrap">
                        <span>{{data.item.ip }}</span>
                    </div>
                </template>

                <!-- Column: text -->
                <template #cell(text)="data">
                    <div class="text-nowrap">
                        <span>{{ data.item.text }}</span>
                    </div>
                </template>

                <!-- Column: Actions -->
                <template #cell(actions)="data">
                    <div  @click="getLog(data.item.id)" class="font-weight-bold d-block text-nowrap cursor-pointer text-center">
                        <feather-icon icon="EditIcon" size="20"/>
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


            <!-- modal -->
            <log :id="id" :modalShow="modalShow" @modalUpdate="modalUpdate" @refetchData="refetchData"/>

        </b-card>
    </div>
</template>
<script>
    import {
        BCard, BRow, BCol, BFormInput, BButton, BTable, BMedia, BAvatar, BLink,
        BBadge, BDropdown, BDropdownItem, BPagination,BTooltip
    } from 'bootstrap-vue'
    import vSelect from 'vue-select'
    import {ref, onUnmounted, watch} from '@vue/composition-api'
    import {avatarText} from '@core/utils/filter'
    import LogListFilters from './LogListFilters.vue'
    import useLogList from './useLogList'
    import Log from './Log'

    export default {
        components: {
            LogListFilters,
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
            Log
        },
        data(){
            return {
                id: null,
                modalShow: false,
            }
        },
        setup() {
            const adminsFillterOptions = []
            const keywordOptions = [
                {value: 'login', label: 'ورود'},
                {value: 'logout', label: 'خروج'},
                {value: 'users.addAndRemeve', label: 'حذف و اضافه کاربر'},
                {value: 'users.edit', label: 'ویرایش کاربر'},
                {value: 'users.changeBalanceWallet', label: 'تغییر موجودی کاربر'},
                {value: 'trCrypto', label: 'تراکنش رمزارز'},
                {value: 'trInternal', label: 'تراکنش داخلی'},
                {value: 'cardbank.edit', label: 'ویرایش کارت بانکی'},
                {value: 'cardbank.status', label: 'تغییر وضعیت کارت بانکی'},
                {value: 'cardbank.inquiry', label: 'استعلام کارت بانکی'},
                {value: 'referral', label: 'بازاریابی'},
                {value: 'automaticDeposit', label: 'واریز اتوماتیک'},
                {value: 'settings', label: 'تنظیمات'},
                {value: 'withdraw', label: 'برداشت رمز ارز'},
                {value: 'trade', label: 'ترید رمز ارز به تتر'},
            ]

            const {
                listLog,
                tableColumns,
                perPage,
                currentPage,
                totalRows,
                dataMeta,
                perPageOptions,
                searchQuery,
                sortBy,
                isSortDirDesc,
                refLogListTable,
                refetchData,

                // UI
                resolveStatusVariant,

                // Extra Filters
                dateStartFilter,dateStopFilter,
                adminFilter,keywordFilter,
                adminslist,
            } = useLogList()

            return {

                listLog,
                tableColumns,
                perPage,
                currentPage,
                totalRows,
                dataMeta,
                perPageOptions,
                searchQuery,
                sortBy,
                isSortDirDesc,
                refLogListTable,
                refetchData,

                // Filter
                avatarText,

                // UI
                resolveStatusVariant,

                // Extra Filters
                dateStartFilter, dateStopFilter,
                adminFilter,keywordFilter,
                adminslist,
                adminsFillterOptions,keywordOptions
            }
        },
        watch:{
            adminslist(val){
                var adminsFillterOptions = [];
                val.map(function(item) {
                    var obj = {label:(item.name), value:item.id };
                    adminsFillterOptions.push(obj);
                });
                this.adminsFillterOptions = adminsFillterOptions;
            }
        },
        methods:{
            getLog(id){
                this.id = id;
                this.modalShow = true
            },
            modalUpdate(val){
                this.modalShow = val
            },
            getKeyword(key){
                switch (key){
                    case 'login': key ='ورود'; break
                    case 'logout': key ='خروج'; break
                    case 'users.addAndRemeve': key ='حذف و اضافه کاربر'; break
                    case 'users.edit': key ='ویرایش کاربر'; break
                    case 'users.changeBalanceWallet': key ='تغییر موجودی کاربر'; break
                    case 'trCrypto': key ='تراکنش رمزارز'; break
                    case 'trInternal': key ='تراکنش داخلی'; break
                    case 'cardbank.edit': key ='ویرایش کارت بانکی'; break
                    case 'cardbank.status': key ='تغییر وضعیت کارت بانکی'; break
                    case 'cardbank.inquiry': key ='استعلام کارت بانکی'; break
                    case 'referral': key ='بازاریابی'; break
                    case 'automaticDeposit': key ='واریز اتوماتیک'; break
                    case 'settings': key ='تنظیمات'; break
                }
                return key;
            }
        },
        created() {
            if(this.$route.query.admin)
                this.adminFilter = this.$route.query.admin;
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
