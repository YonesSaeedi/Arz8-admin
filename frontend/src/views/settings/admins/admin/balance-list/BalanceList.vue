<template>

    <div>



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
                ref="refCryptoBalanceTable"
                class="position-relative"
                :items="listCrypto"
                responsive
                :fields="tableColumns"
                primary-key="id"
                :sort-by.sync="sortBy"
                show-empty
                striped
                empty-text="داده ای برای نمایش وجود ندارد"
                :sort-desc.sync="isSortDirDesc"
            >

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

                <!-- Column: User Level -->
                <template #cell(userLevel)="data">
                    <div class="text-nowrap">
                        <feather-icon icon="StarIcon" class="text-warning" size="16" v-for="(star) of data.item.level"/>
                    </div>
                </template>

                <!-- Column: logo -->
                <template #cell(coin)="data">
                    <div class="text-nowrap">
                        <i class="cf" style="font-size: 45px" v-if="isFontExist(data.item.symbol)" :class="'cf-'+data.item.symbol.toLowerCase()" :style="{color:colorSymbol(data.item.symbol)}"></i>
                        <img :src="baseURL+'images/currency/' + (iconSymbol(data.item.symbol) ? iconSymbol(data.item.symbol) :data.item.icon )" width="45px" v-else />
                    </div>
                </template>



                <!-- Column: balance -->
                <template #cell(balance)="data">
                    <div class="text-nowrap">
                        <div>{{data.item.balance? toFixFloat(data.item.balance):'---' }} {{data.item.symbol}}</div>
                    </div>
                </template>

                <!-- Column: balance Usdt -->
                <template #cell(balanceUsdt)="data">
                    <div class="text-nowrap">
                        <div>{{data.item.balance_usdt? toFixFloat(data.item.balance_usdt):'---' }} <small>USDT</small></div>
                    </div>
                </template>

                <!-- Column: balance Toman -->
                <template #cell(balanceUsersToman)="data">
                    <div class="text-nowrap">
                        <div>{{ data.item.balance_toman_buy? data.item.balance_toman_buy.toLocaleString():'---' }} <small>تومان</small></div>
                        <small class="text-muted">
                            فروش:
                            {{ data.item.balance_toman_sell? data.item.balance_toman_sell.toLocaleString():'---' }}
                            تومان
                        </small>
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
    import {ref, onUnmounted, watch} from '@vue/composition-api'
    import {avatarText} from '@core/utils/filter'
    import useCryptoBalanceList from './useCryptoBalanceList'

    export default {
        props:['crypto'],
        components: {
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
            const {
                listCrypto,
                tableColumns,
                perPage,
                currentPage,
                totalRows,
                dataMeta,
                perPageOptions,
                searchQuery,
                sortBy,
                isSortDirDesc,
                refCryptoBalanceTable,
                refetchData,

                // UI
                resolveStatusVariant,
            } = useCryptoBalanceList(props.crypto)

            return {

                listCrypto,
                tableColumns,
                perPage,
                currentPage,
                totalRows,
                dataMeta,
                perPageOptions,
                searchQuery,
                sortBy,
                isSortDirDesc,
                refCryptoBalanceTable,
                refetchData,

                // Filter
                avatarText,

                // UI
                resolveStatusVariant,

            }
        },
        watch:{
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
