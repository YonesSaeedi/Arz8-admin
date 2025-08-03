<template>

    <div>


        <admin-add-new
            :is-add-new-sidebar-active.sync="isAddNewSidebarActive"
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
                    <b-col
                        cols="12"
                        md="9"
                        class="mb-1 mb-md-0 text-right"
                    >
                        <b-button
                            variant="primary"
                            @click="isAddNewSidebarActive = true"
                        >
                            <span class="text-nowrap">افزودن ادمین جدید</span>
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
                ref="refAdminListTable"
                class="position-relative"
                :items="listAdmin"
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

                <!-- Column: email -->
                <template #cell(email)="data">
                    <b-media vertical-align="center" class="align-items-center">
                        <template #aside>
                            <b-avatar
                                size="40"
                                :src="data.item.avatar"
                                :text="avatarText(data.item.name)"
                                :variant="`light-primary`"
                                :to="{ name: 'admin-view', params: { id: data.item.id } }"
                            />
                        </template>
                        <b-link
                            :to="{ name: 'admin-view', params: { id: data.item.id } }"
                            class="font-weight-bold d-block text-nowrap"
                        >
                            {{ data.item.email }}
                        </b-link>
                        <small class="text-muted">{{ data.item.name_display }}</small>
                    </b-media>
                </template>

                <!-- Column: Name Family -->
                <template #cell(nameFamily)="data">
                    <div class="text-nowrap">
                        <span>{{ data.item.name }}</span>
                    </div>
                </template>

                <!-- Column: mobile -->
                <template #cell(mobile)="data">
                    <div class="text-nowrap">
                        <span>{{ data.item.mobile ? data.item.mobile : '-------' }}</span>
                    </div>
                </template>

                <!-- Column: role -->
                <template #cell(role)="data">
                    <div class="text-nowrap">
                        <span>{{ data.item.role == 'admin' ? 'ادمین کل' : 'ادمین عادی' }}</span>
                    </div>
                </template>

                <!-- Column: access -->
                <template #cell(access)="data">
                    <div class="text-nowrap">
                        <span v-if="data.item.role == 'admin'">بدون محدودیت</span>
                        <span v-else v-for="(item,index) in getAccessIcon(data.item.access.section_access)">
                            <feather-icon :id="data.item.id+''+index"  :icon="item.icon" :class="'text-'+item.color" size="20" />
                            <b-tooltip :target="data.item.id+''+index" :variant="item.color">
                                {{item.title}}
                            </b-tooltip>
                        </span>
                    </div>
                </template>

                <!-- Column: isBlock -->
                <template #cell(isBlock)="data">
                    <b-badge
                        pill
                        :variant="`light-${resolveStatusVariant(data.item.access.is_block)}`"
                        class="text-capitalize"
                    >
                        {{ data.item.access.is_block ? 'بلاک' : 'فعال' }}
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
                        :to="{ name: 'admin-view', params: { id: data.item.id } }"
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
    import {ref, onUnmounted, watch} from '@vue/composition-api'
    import {avatarText} from '@core/utils/filter'
    import AdminListFilters from './AdminListFilters.vue'
    import useAdminList from './useAdminList'
    import AdminAddNew from './AdminAddNew.vue'

    export default {
        components: {
            AdminAddNew,
            AdminListFilters,
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
        setup() {
            const isAddNewSidebarActive = ref(false)

            const {
                listAdmin,
                tableColumns,
                perPage,
                currentPage,
                totalRows,
                dataMeta,
                perPageOptions,
                searchQuery,
                sortBy,
                isSortDirDesc,
                refAdminListTable,
                refetchData,

                // UI
                resolveStatusVariant,

                // Extra Filters

            } = useAdminList()

            return {
                // Sidebar
                isAddNewSidebarActive,

                listAdmin,
                tableColumns,
                perPage,
                currentPage,
                totalRows,
                dataMeta,
                perPageOptions,
                searchQuery,
                sortBy,
                isSortDirDesc,
                refAdminListTable,
                refetchData,

                // Filter
                avatarText,

                // UI
                resolveStatusVariant,

                // Extra Filters

            }
        },
        data(){
            return {
                access: {
                    'users':{icon:'UsersIcon',title:'کاربران',color:'success'},
                    'orders':{icon:'LayersIcon',title:'سفارشات',color:'warning'},
                    'gift':{icon:'GiftIcon',title:'کد تخفیف',color:'info'},
                    'tr-internal':{icon:'ListIcon',title:'تراکنش داخلی',color:'dark'},
                    'tr-crypto':{icon:'ListIcon',title:'تراکنش رمزارز',color:'warning'},
                    'cardbank':{icon:'CreditCardIcon',title:'کارت های بانکی',color:'info'},
                    'referral':{icon:'UserPlusIcon',title:'بازاریابی',color:'primary'},
                    'tr-referral':{icon:'ListIcon',title:'تراکنش های رفرال',color:'primary'},
                    'payment-gateway':{icon:'ZapIcon',title:'تسویه های اتوماتیک',color:'danger'},
                    'setting-crypto':{icon:'SlackIcon',title:'تنظیمات رمزارز',color:'Dark'},
                    'setting-networks':{icon:'WindIcon',title:'تنظیمات شبکه',color:'warning'},
                    'setting-markets':{icon:'AirplayIcon',title:'تنظیمات بازارها',color:'success'},
                    'setting':{icon:'SettingsIcon',title:'تنظیمات',color:'danger'},
                },
            }
        },
        methods:{
            getAccessIcon(section_access){
                var arr = [];
                Object.keys(section_access).map((key,index)=>{
                    if(this.access[key] && section_access[key] && (section_access[key].list === true))
                        arr.push(this.access[key]);
                })
                return arr;
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
