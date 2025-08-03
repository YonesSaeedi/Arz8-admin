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
                ref="refCryptoListTable"
                class="position-relative"
                :items="listCrypto"
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

                <!-- Column: logo -->
                <template #cell(logo)="data">
                    <div class="text-nowrap">
                        <b-link :to="{ name: 'st-crypto-view', params: { id: data.item.id_crypto } }"
                            class="font-weight-bold d-block text-nowrap">
                            <div>
                                <i class="cf" style="font-size: 45px" v-if="isFontExist(data.item.symbol)" :class="'cf-'+data.item.symbol.toLowerCase()" :style="{color:colorSymbol(data.item.symbol)}"></i>
                                <img :src="baseURL+'images/currency/' + (data.item.icon )" width="45px" v-else />
                            </div>
                        </b-link>
                    </div>
                </template>

                <!-- Column: name -->
                <template #cell(name)="data">
                    <div class="text-nowrap text-capitalize">
                        <div>{{ $t(data.item.name) }}</div>
                        <small class="text-muted">{{ localeNameSymbol(data.item.symbol) ? localeNameSymbol(data.item.symbol)[localeHas] : data.item.name }}</small>
                    </div>
                </template>

                <!-- Column: symbol -->
                <template #cell(symbol)="data">
                    <div class="text-nowrap">
                        {{ data.item.symbol }}
                    </div>
                </template>

                <!-- Column: network -->
                <template #cell(network)="data">
                    <div class="text-nowrap">
                        {{ data.item.net_name }} - {{ data.item.net_symbol }}
                    </div>
                </template>

                <!-- Column: address -->
                <template #cell(address)="data">
                    <div class="text-nowrap">
                        {{ data.item.address }}
                        <div class="text-nowrap text-warning" v-if="data.item.address_tag">
                            tag: {{ data.item.address_tag }}
                        </div>
                    </div>
                </template>

                <!-- Column: TxId -->
                <template #cell(TxId)="data">
                    <div class="text-nowrap">
                        <span :class="data.item.for_txid===1?'text-warning':''">{{data.item.for_txid===1 ?'مخصوص TxId':'ولت اختصاصی' }}</span>
                    </div>
                </template>

                <!-- Column: Count -->
                <template #cell(Count)="data">
                    <div class="text-nowrap">
                        <span>{{data.item.count_wallet }}</span>
                    </div>
                </template>

                <!-- Column: Amount -->
                <template #cell(Amount)="data">
                    <div class="text-nowrap">
                        <span dir="ltr">{{toFixFloat(data.item.amount_wallet) }} {{data.item.symbol}}</span>
                    </div>
                </template>

                <!-- Column: updated_at -->
                <template #cell(updated)="data">
                    <div class="text-nowrap">
                        {{data.item.updated}}
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
        BBadge, BDropdown, BDropdownItem, BPagination,BTooltip,BSkeleton
    } from 'bootstrap-vue'
    import vSelect from 'vue-select'
    import {ref, onUnmounted, watch} from '@vue/composition-api'
    import {avatarText} from '@core/utils/filter'
    import useCryptoList from './useCryptoList'

    export default {
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
            BSkeleton
        },
        setup() {

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
                refCryptoListTable,
                refetchData,

                // UI
                resolveStatusVariant,

            } = useCryptoList()

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
                refCryptoListTable,
                refetchData,

                // Filter
                avatarText,

                // UI
                resolveStatusVariant,

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
