<template>
    <div>
        <b-row v-if="!status && !idUsers">
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="UsersIcon"
                    :statistic="statistic && statistic.total_users? statistic.total_users.toLocaleString() :'0'"
                    statistic-title="همه کاربران"
                    id="all-users"
                />
                <b-tooltip target="all-users" variant="primary">
                    همه کاربران موجود در سامانه
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="UserIcon"
                    color="success"
                    :statistic="statistic && statistic.email_users? statistic.email_users.toLocaleString() :'0'"
                    statistic-title="کاربران ایمیل"
                    id="email-users"
                />
                <b-tooltip target="email-users" variant="success">
                    کاربرانی که ایمیل خود را وریفای کرده اند
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="ActivityIcon"
                    color="danger"
                    :statistic="statistic && statistic.balance_users? statistic.balance_users.toLocaleString() :'0'"
                    statistic-title="کاربران موجودی"
                    id="balance-users"
                />
                <b-tooltip target="balance-users" variant="danger">
                    کاربرانی که موجودی یکی ار کیف پول های آنها بیشتر از صفر است
                    <br>
                    موجودی کل:
                    <span class="font-medium-2 font-weight-bold">
                        {{statistic && statistic.balance_all_amount?statistic.balance_all_amount.toLocaleString('en-US') : '0'}}
                    </span>
                    تومان
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="UserCheckIcon"
                    color="warning"
                    :statistic="statistic && statistic.active_users? statistic.active_users.toLocaleString() :'0'"
                    statistic-title="کاربران فعال"
                    id="active-users"
                />
                <b-tooltip target="active-users" variant="warning">
                    کاربرانی که حداقل یک سفارش یا معامله داشته اند
                </b-tooltip>
            </b-col>
        </b-row>


        <user-list-add-new
            :is-add-new-user-sidebar-active.sync="isAddNewUserSidebarActive"
            @refetch-data="refetchData"
        />


        <!-- Filters -->
        <users-list-filters
            :level-filter.sync="levelFilter" :level-options="levelOptions"
            :level-account-filter.sync="levelAccountFilter" :level-account-options="levelAccountOptions"
            :status-filter.sync="statusFilter" :status-options="statusOptions"
            :date-start-filter.sync="dateStartFilter" :date-stop-filter.sync="dateStopFilter"
            :balance-start-filter.sync="balanceStartFilter" :balance-stop-filter.sync="balanceStopFilter"
            :other-filter.sync="otherFilter" :other-options="otherOptions"
            @refetchData="refetchData" :isLoading="isLoading"
        />

        <!-- Table Container Card -->
        <b-card
            no-body
            class="mb-0"
        >

            <div class="m-2">

                <!-- Table Top -->
                <b-row>

                    <!-- Search -->
                    <b-col
                        cols="12"
                        md="3"
                    >
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
                        v-if="!status && !idUsers"
                    >
                        <b-button
                            variant="primary"
                            @click="isAddNewUserSidebarActive = true"
                        >
                            <span class="text-nowrap">افزودن کاربر</span>
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
                ref="refUserListTable"
                class="position-relative"
                :items="fetchUsers"
                responsive
                :fields="tableColumns"
                primary-key="id"
                :sort-by.sync="sortBy"
                show-empty hover
                empty-text="No matching records found"
                :sort-desc.sync="isSortDirDesc"
            >

                <!-- Column: email -->
                <template #cell(email)="data" class="w-25">
                    <b-media vertical-align="center">
                        <template #aside>
                            <b-avatar
                                size="32"
                                :src="data.item.avatar"
                                :text="avatarText(data.item.name_display)"
                                :variant="`light-primary`"
                                :to="{   name: 'user-single', params: { id: data.item.id }}"
                            />
                        </template>
                        <b-link
                            :to="{ name: 'user-single', params: { id: data.item.id } }"
                            class="font-weight-bold d-block text-nowrap" style="max-width: 250px;overflow: hidden;text-overflow: ellipsis;"
                        >
                            {{ data.item.email ? data.item.email : data.item.mobile }}
                        </b-link>
                        <small class="text-muted">{{ data.item.name_display }}</small>
                    </b-media>
                </template>

                <!-- Column: Name Family -->
                <template #cell(nameFamily)="data">
                    <div class="text-nowrap">
                        <span>{{ data.item.name ? data.item.name+' '+data.item.family : '-------' }}</span>
                    </div>
                </template>

                <!-- Column: mobile -->
                <template #cell(mobile)="data">
                    <div class="text-nowrap">
                        <span>{{ data.item.mobile ? data.item.mobile : '-------' }}</span>
                    </div>
                </template>

                <!-- Column: nationalCode -->
                <template #cell(balanceInternalWallet)="data">
                    <div class="text-nowrap">
                        <span>{{ data.item.balanceInternal ? parseInt(data.item.balanceInternal).toLocaleString() : '0' }}</span>
                    </div>
                </template>

                <!-- Column: level -->
                <template #cell(level_account)="data">
                    <div class="text-nowrap d-flex align-items-center">
                        <img :src="require('@/assets/images/logo/logo-'+ levelAccount(data.item.level_account) +'.png')" width="30" class="mr-25">
                        <div>{{ $t('l-'+data.item.level_account) }}</div>
                    </div>
                </template>

                <!-- Column: Status -->
                <template #cell(status)="data">
                    <b-badge
                        pill
                        :variant="`light-${resolveUserStatusVariant(data.item.access)}`"
                        class="text-capitalize"
                    >
                        {{ $t(data.item.access) }}
                    </b-badge>
                </template>

                <!-- Column: registeryDate -->
                <template #cell(registeryDate)="data">
                    <div class="text-nowrap vazir">
                        {{ $t(data.item.registeryDate) }}
                    </div>
                </template>

                <!-- Column: Actions -->
                <template #cell(actions)="data">
                    <b-link
                        :to="{ name: 'user-single', params: { id: data.item.id } }"
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
                            :total-rows="totalUsers"
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
        BBadge, BDropdown, BDropdownItem, BPagination, VBTooltip, BTooltip
    } from 'bootstrap-vue'
    import vSelect from 'vue-select'
    import store from '@/store'
    import {ref, onUnmounted} from '@vue/composition-api'
    import {avatarText} from '@core/utils/filter'
    import UsersListFilters from './UsersListFilters.vue'
    import useUsersList from './useUsersList'
    import UserListAddNew from './UserListAddNew.vue'
    import StatisticCardHorizontal from '@core/components/statistics-cards/StatisticCardHorizontal.vue'

    export default {
        props:['status','idUsers'],
        components: {
            UsersListFilters,
            UserListAddNew,

            StatisticCardHorizontal,

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
            VBTooltip,BTooltip,
            vSelect,
        },
        directives: {
            'b-tooltip': VBTooltip,
        },
        setup(props) {
            const isAddNewUserSidebarActive = ref(false)


            const levelOptions = [
                {label: 'صفر', value: '0'},
                {label: 'یک', value: '1'},
                {label: 'دو', value: '2'},
                {label: 'سه', value: '3'},
                {label: 'چهار', value: '4'},
                {label: 'پنج', value: '5'},
            ]
            const levelAccountOptions = [
                {label: 'برنزی', value: '1'},
                {label: 'نقره ای', value: '2'},
                {label: 'طلایی', value: '3'},
                {label: 'کریستال', value: '4'},
            ]

            const otherOptions = [
                {label: 'فعال بودن پرتفوی', value: 'portfolio'},
                {label: 'دسترسی به ارزهای دلاری', value: 'digitalCurrencyAccess'},
                {label: 'ایمیل وریفای شده', value: 'emailVerified'},
                {label: 'ایمیل وریفای نشده', value: 'emailNotVerified'},
                {label: 'توسط رفرال عضو شدگان', value: 'referral'},
                {label: 'توسط رفرال عضو نشدگان', value: 'referralNot'},
                {label: 'نوتیفیکشن فعال', value: 'notifActive'},
                {label: 'نوتیفیکشن غیرفعال', value: 'notifNotActive'},
                {label: 'ورود دو مرحله ای فعال', value: '2faActive'},
                {label: 'ورود دو مرحله ای غیر فعال', value: '2faNotActive'},
                {label: 'ورود دو مرحله ای پیامک فعال', value: '2faSmsActive'},
                {label: 'ورود دو مرحله ای ایمیل فعال', value: '2faEmailActive'},
                {label: 'ورود دو مرحله ای گوگل فعال', value: '2faGoogleActive'},
                {label: 'ورود دو مرحله ای تلگرام فعال', value: '2faTelegramActive'},
                {label: 'ثبت نام از وبسایت', value: 'registerWebsite'},
                {label: 'ثبت نام از اندروید', value: 'registerAndroid'},
                {label: 'ثبت نام از آی او اس', value: 'registerIos'},
            ]


            const statusOptions = [
            ]

            const {
                fetchUsers,
                tableColumns,
                perPage,
                currentPage,
                totalUsers,
                dataMeta,
                perPageOptions,
                searchQuery,
                sortBy,
                isSortDirDesc,
                refUserListTable,
                refetchData,

                // UI
                resolveUserRoleIcon,
                resolveUserStatusVariant,

                // Extra Filters
                levelFilter,
                levelAccountFilter,
                statusFilter,
                dateStartFilter,dateStopFilter,
                balanceStartFilter,balanceStopFilter,
                otherFilter,

                statistic,fetchStatistic,
                isLoading
            } = useUsersList(props)

            return {

                // Sidebar
                isAddNewUserSidebarActive,

                fetchUsers,
                tableColumns,
                perPage,
                currentPage,
                totalUsers,
                dataMeta,
                perPageOptions,
                searchQuery,
                sortBy,
                isSortDirDesc,
                refUserListTable,
                refetchData,

                // Filter
                avatarText,

                // UI
                resolveUserRoleIcon,
                resolveUserStatusVariant,

                levelOptions,
                levelAccountOptions,
                statusOptions,
                otherOptions,

                // Extra Filters
                levelFilter,
                levelAccountFilter,
                statusFilter,
                dateStartFilter, dateStopFilter,
                balanceStartFilter, balanceStopFilter,
                otherFilter,

                statistic,fetchStatistic,
                isLoading
            }
        },
        mounted() {
            this.fetchStatistic();

            this.statusOptions = [
                {label: this.$i18n.t('pending'), value: 'pending'},
                {label: this.$i18n.t('pendingIdentification'), value: 'pendingIdentification'},
                {label: this.$i18n.t('pendingAuthImg'), value: 'pendingAuthImg'},
                {label: this.$i18n.t('pendingLocation'), value: 'pendingLocation'},
                {label: this.$i18n.t('accessible'), value: 'accessible'},
                {label: this.$i18n.t('blocked'), value: 'blocked'},
            ]
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
