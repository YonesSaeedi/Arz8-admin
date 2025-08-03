<template>
    <div>
        <!-- Filters -->
        <login-history-list-filters
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
                        <b-link class="font-weight-bold d-block text-nowrap" @click="getCard(data.item.id,data.item.name+' '+data.item.family)">
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
                            {{ data.item.name ? data.item.name+' '+data.item.family : '-------' }}
                        </b-link>
                        <small class="text-muted">{{ data.item.email }}</small>
                    </div>
                </template>

                <!-- Column: ip -->
                <template #cell(ip)="data">
                    <div class="text-nowrap">
                        <span>{{data.item.ip}}</span>
                    </div>
                </template>

                <!-- Column: via -->
                <template #cell(via)="data">
                    <div class="text-nowrap">
                        <img src="@/assets/images/icons/website.png" class="mx-50" width="30" v-if="data.item.via=='website'"
                             v-b-tooltip.hover.right :title="'ورود از طریق وب سایت'"/>
                        <img src="@/assets/images/icons/android.png" class="mx-50" width="30" v-else-if="data.item.via=='android'"
                             v-b-tooltip.hover.right :title="'ورود از طریق اندروید'"/>
                        <img src="@/assets/images/icons/ios.png" class="mx-50" width="30" v-else-if="data.item.via=='ios'"
                             v-b-tooltip.hover.right :title="'ورود از طریق ios'"/>
                    </div>
                </template>

                <!-- Column: os -->
                <template #cell(os)="data">
                    <div class="text-nowrap text-capitalize">
                        <span>{{(data.item.os)}}</span>
                    </div>
                </template>

                <!-- Column: device -->
                <template #cell(device)="data">
                    <div class="text-nowrap">
                        <span>{{data.item.device ? data.item.device :'----'}}</span>
                    </div>
                </template>

                <!-- Column: browser -->
                <template #cell(browser)="data">
                    <div class="text-nowrap">
                        <span>{{data.item.browser? data.item.browser :'----'}}</span>
                    </div>
                </template>

                <!-- Column: twofa -->
                <template #cell(twofa)="data">
                    <div class="text-nowrap">
                        <span>{{data.item.twofa ? $t(data.item.twofa) :'----'}}</span>
                    </div>
                </template>

                <!-- Column: date -->
                <template #cell(date)="data">
                    <div class="text-nowrap vazir">
                        <span>{{data.item.date}}</span>
                    </div>
                </template>


                <!-- Column: agent -->
                <template #cell(agent)="data">
                    <div class="text-nowrap">
                        <span>{{data.item.agent}}</span>
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
    import LoginHistoryListFilters from './LoginHistoryListFilters.vue'
    import useLoginHistoryList from './useLoginHistoryList'
    import Ripple from "vue-ripple-directive";

    export default {
        props:['user','status'],
        components: {
            LoginHistoryListFilters,
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
            } = useLoginHistoryList(props)

            return {
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
            getCard(id,nameFamily){
               this.id = id;
               this.nameFamily = nameFamily;
               this.modalShow = true
            },
            modalUpdate (val){
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
