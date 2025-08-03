<template>
    <div>
        <!-- Filters -->
        <cards-list-filters
            :status-filter.sync="statusFilter" :status-options="statusOptions"
            :date-start-filter.sync="dateStartFilter" :date-stop-filter.sync="dateStopFilter"
            :bank-filter.sync="bankFilter" :bank-options="bankOption"
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
                        <small class="text-muted">{{ data.item.mobile?data.item.mobile:data.item.email }}</small>
                    </div>
                </template>

                <!-- Column: logo -->
                <template #cell(logo)="data">
                    <div class="text-nowrap">
                        <img class="logo-bank" :src="require(`@/assets/images/banklogo/${ bankLogo(data.item.bank_name) }`)" width="35px">
                    </div>
                </template>

                <!-- Column: bank -->
                <template #cell(bank)="data">
                    <div class="text-nowrap">
                        <span>{{data.item.bank_name}}</span>
                    </div>
                </template>

                <!-- Column: cardNumber -->
                <template #cell(cardNumber)="data">
                    <div class="text-nowrap">
                        <span dir="ltr">{{ccformat(data.item.card_number)}}</span>
                    </div>
                </template>

                <!-- Column: nameFamilyAccount -->
                <template #cell(nameFamilyAccount)="data">
                    <div class="text-nowrap">
                        <span>{{data.item.name_family ? data.item.name_family :'- - - - - - - - -'}}</span>
                    </div>
                </template>

                <!-- Column: date -->
                <template #cell(date)="data">
                    <div class="text-nowrap vazir">
                        <span>{{data.item.date}}</span>
                    </div>
                </template>

                <!-- Column: depositId -->
                <template #cell(depositId)="data">
                    <div class="text-nowrap" v-if="data.item.depositId">
                        <span>{{$t(data.item.depositId.payment_gateway)}}<br>{{data.item.depositId.deposit_id}}</span>
                    </div>
                    <div class="text-nowrap" v-else>
                        <span>-----</span>
                    </div>
                </template>

                <!-- Column: Status -->
                <template #cell(status)="data">
                    <b-badge
                        pill
                        :variant="`light-${resolveStatusVariant(data.item.status)}`"
                        class="text-capitalize"
                    >
                        {{ $t(data.item.status) }}
                    </b-badge>
                </template>



                <!-- Column: Actions -->
                <template #cell(actions)="data">
                    <div  @click="getCard(data.item.id,data.item.name+' '+data.item.family)" class="font-weight-bold d-block text-nowrap cursor-pointer text-center">
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

            <!-- card modal -->
            <card-bank :id="id" :nameFamilyUser="nameFamily" :modalShow="modalShow" @modalUpdate="modalUpdate" @refetchData="refetchData"/>


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
    import CardsListFilters from './CardsListFilters.vue'
    import useCardsList from './useCardsList'
    import cardBank from './CardBank'

    export default {
        props:['user','status'],
        components: {
            cardBank,
            CardsListFilters,
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

            const bankOption = [
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
                statusFilter,
                dateStartFilter,dateStopFilter,
                bankFilter,
                isLoading
            } = useCardsList(props)

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

                statusOptions,
                bankOption,

                // Extra Filters
                typeFilter,
                statusFilter,
                dateStartFilter, dateStopFilter,
                bankFilter,
                isLoading
            }
        },
        mounted() {
            this.statusOptions = [
                {label: this.$i18n.t('pending'), value: 'pending'},
                {label: this.$i18n.t('confirm'), value: 'confirm'},
                {label: this.$i18n.t('reject'), value: 'reject'},
            ]

            for(var i=0; i<this.BankOptions.length; i++){
                this.bankOption[i] = {label:this.BankOptions[i].selectedText , value: this.BankOptions[i].selectedText};
            }
        },
        data(){
            return {
                id: null,
                nameFamily: null,
                modalShow: false,
                BankOptions: [
                    { value: 0, img:'shetab.svg', selectedText: 'سایر', slug: ''},
                    { value: 1, img:'melat-bank.svg', selectedText: 'بانک ملت', slug: '610433', slug2: '991975'},
                    { value: 2, img:'meli-bank.svg', selectedText: 'بانک ملی', slug: '603799'},
                    { value: 3, img:'tejarat-bank.svg', selectedText: 'بانک تجارت', slug: '627353', slug2: '585983'},
                    { value: 4, img:'sepah-bank.svg', selectedText: 'بانک سپه', slug: '589210'},
                    { value: 5, img:'tosee-saderat-bank.svg', selectedText: 'بانک توسعه صادرات', slug: '627648', slug2: '207177'},
                    { value: 6, img:'sanat-madan-bank.svg', selectedText: 'بانک صنعت و معدن', slug: '627961'},
                    { value: 7, img:'keshavarzi-bank.svg', selectedText: 'بانک کشاورزی', slug: '603770', slug2: '639217'},
                    { value: 8, img:'maskan-bank.svg', selectedText: 'بانک مسکن', slug: '628023'},
                    { value: 9, img:'post-bank.svg', selectedText: 'پست بانک', slug: '627760'},
                    { value: 10, img:'tosee-taavon-bank.svg', selectedText: 'بانک توسعه و تعاون', slug: '502908'},
                    { value: 11, img:'eghtesad-novin-bank.svg', selectedText: 'بانک اقتصاد نوین', slug: '627412'},
                    { value: 12, img:'parsian-bank.svg', selectedText: 'بانک پارسیان', slug: '622106', slug2: '639194', slug3: '627884'},
                    { value: 13, img:'pasargad-bank.svg', selectedText: 'بانک پاسارگاد', slug: '639347', slug2: '502229'},
                    { value: 14, img:'karafarin-bank.svg', selectedText: 'بانک کارآفرین', slug: '627488', slug2: '502910'},
                    { value: 15, img:'saman-bank.svg', selectedText: 'بانک سامان', slug: '62198610'},
                    { value: 16, img:'sina-bank.svg', selectedText: 'بانک سینا', slug: '639346'},
                    { value: 17, img:'sarmayeh-bank.svg', selectedText: 'بانک سرمایه', slug: '639607'},
                    { value: 18, img:'ayandeh-bank.svg', selectedText: 'بانک آینده', slug: '636214'},
                    { value: 19, img:'shahr-bank.svg', selectedText: 'بانک شهر', slug: '502806', slug2: '504706'},
                    { value: 20, img:'dey-bank.svg', selectedText: 'بانک دی', slug: '502938'},
                    { value: 21, img:'saderat-bank.svg', selectedText: 'بانک صادرات', slug: '603769'},
                    { value: 22, img:'refah-bank.svg', selectedText: 'بانک رفاه', slug: '589463'},
                    { value: 23, img:'ansar-bank.svg', selectedText: 'بانک انصار', slug: '627381'},
                    { value: 24, img:'iranzamin-bank.svg', selectedText: 'بانک ایران زمین', slug: '505785'},
                    { value: 25, img:'hekmat-iranian-bank.svg', selectedText: 'بانک حکمت ایرانیان', slug: '636949'},
                    { value: 26, img:'gardeshgari-bank.svg', selectedText: 'بانک گردشگری', slug: '505416'},
                    { value: 27, img:'mehr-iran-bank.svg', selectedText: 'بانک قرض الحسنه مهر ایران', slug: '606373'},
                    { value: 28, img:'kosar-bank.svg', selectedText: 'موسسه مالی کوثر', slug: '505801'},
                    { value: 29, img:'ghavamin-bank.svg', selectedText: 'موسسه قوامین', slug: '639599'},
                    { value: 30, img:'khavarmianeh-bank.svg', selectedText: 'بانک خاورمیانه', slug: '505809'},
                    { value: 31, img:'resalat-bank.svg', selectedText: 'بانک قرض الحسنه رسالت', slug: '504172'},
                    { value: 32, img:'noor-bank.svg', selectedText: 'موسسه نور', slug: '507677'},
                    { value: 33, img:'blu-bank.svg', selectedText: 'بلوبانک', slug: '62198619'},
                    { value: 34, img:'mehr-eghtesad-bank.svg', selectedText: 'بانک مهر اقتصاد', slug: '639370'},
                    { value: 35, img:'melal-bank.png', selectedText: 'موسسه ملل', slug: '606256'},
                    { value: 36, img:'markazi-bank.svg', selectedText: 'بانک مرکزی', slug: '636795'},
                ],
            }
        },
        methods:{
            ccformat (value) {
                return value.replace(/\W/gi, '').replace(/(.{4})/g, '$1 ');
            },
            getCard(id,nameFamily){
               this.id = id;
               this.nameFamily = nameFamily;
               this.modalShow = true
            },
            modalUpdate (val){
                this.modalShow = val
            },
            bankLogo(bankName){
                var bank = this.BankOptions.find(x => x.selectedText === bankName)
                return bank ? bank.img : this.BankOptions[0].img;
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
