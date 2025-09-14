<template>
    <div>
        <!-- Filters -->
        <internal-list-filters
            :type-filter.sync="typeFilter" :type-options="typeOptions"
            :status-filter.sync="statusFilter" :status-options="statusOptions"
            :date-start-filter.sync="dateStartFilter" :date-stop-filter.sync="dateStopFilter"
            :amount-start-filter.sync="amountStartFilter" :amount-stop-filter.sync="amountStopFilter"
            :via-filter.sync="viaFilter" :via-options="viaOptions"
            :id-filter.sync="idFilter"
            :gateway-filter.sync="gatewayFilter" :gateway-options="gatewayOptions"
            :other-filter.sync="otherFilter" :other-options="otherOptions"
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
                        <b-button
                            v-if="status === 'pending'"
                            variant="light"
                            @click="confirmGroupWithdraw()"
                        >
                            <feather-icon icon="CheckSquareIcon" class="mr-50"/>
                            <span class="text-nowrap">تایید گروهی</span>
                        </b-button>
                        <b-button
                            v-if="status === 'pending'"
                            variant="light"
                            @click="modalListIban=true"
                        >
                            <feather-icon icon="ListIcon" class="mr-50"/>
                            <span class="text-nowrap">لیست شبا</span>
                        </b-button>
                        <b-button
                            v-if="status === 'pending'"
                            variant="light"
                            @click="downloadXls()"
                        >
                            <feather-icon icon="SaveIcon" class="mr-50"/>
                            <span class="text-nowrap">خروجی</span>
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
                ref="refInternalListTable"
                class="position-relative"
                :items="fetchInternal"
                responsive
                :fields="tableColumns"
                primary-key="id"
                :sort-by.sync="sortBy"
                show-empty
                striped hover
                empty-text="داده ای برای نمایش وجود ندارد"
                :sort-desc.sync="isSortDirDesc"
            >

                <!-- Column: id -->
                <template #cell(id)="data">
                    <div class="text-nowrap">
                        <b-link
                            :to="{ name: 'tr-internal-view', params: { id: data.item.id } }"
                            class="font-weight-bold d-block text-nowrap"
                        >
                            #{{ data.item.id }}
                        </b-link>
                    </div>
                </template>


                <!-- Column: Name Family -->
                <template #cell(nameFamily)="data">
                    <User :item="data.item"/>
                </template>

                <!-- Column: type -->
                <template #cell(type)="data">
                    <div class="text-nowrap">
                        <b-avatar
                            :id="`internal-row-${data.item.id}`"
                            size="32"
                            :variant="data.item.type == 'deposit'? `light-success` : `light-danger`"
                        >
                            <feather-icon
                                :icon="data.item.type == 'deposit' ? 'TrendingUpIcon' : 'TrendingDownIcon'"
                            />
                        </b-avatar>
                        <span>{{$t(data.item.type)}}</span>
                    </div>
                </template>

                <!-- Column: amount -->
                <template #cell(amount)="data">
                    <div class="text-nowrap vazir">
                        <span>{{data.item.amount.toLocaleString()}} <small>تومان</small></span>
                    </div>
                </template>

                <!-- Column: amount -->
                <template #cell(stock)="data">
                    <div class="text-nowrap vazir">
                        <span>{{data.item.stock.toLocaleString()}} <small>تومان</small></span>
                    </div>
                </template>

                <!-- Column: date -->
                <template #cell(date)="data">
                    <div class="text-nowrap vazir">
                        {{ $t(data.item.date) }}
                    </div>
                </template>

                <!-- Column: description -->
                <template #cell(description)="data">
                    <div class="text-nowrap">
                        {{ $t(data.item.description) }}
                        <small v-if="data.item.payment_gateway">({{$t(data.item.payment_gateway)}})</small>
                        <small v-else-if="data.item.order">({{data.item.order.symbol}})
                            <b-link
                                :to="{ name: 'order-view', params: { id: data.item.order.id } }"
                                class="font-weight-bold d-block text-nowrap"
                            >
                             #{{data.item.order.id}}
                            </b-link>
                        </small>
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
                    <b-link
                        :to="{ name: 'tr-internal-view', params: { id: data.item.id } }"
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



        <b-modal
            size="lg"
            v-model="modalListIban"
            id="iban-list-modal" ref="iban-list-modal"
            title="لیست شبا"
            cancel-title="بستن"
            cancel-variant="outline-secondary"
            aria-modal="true"
        >
            <b-card-text>
                <h3 class="text-center">
                    جمع مبالغ لیست:
                    {{items.reduce((sum, item) => sum + item.amount, 0).toLocaleString()}}
                    <small>تومان</small>
                </h3>
                <b-table
                    ref="refInternalListTable"
                    class="position-relative"
                    :items="items"
                    responsive
                    primary-key="id"
                    :sort-by.sync="sortBy"
                    show-empty
                    :fields="[
                        {key: 'id', label: 'شناسه', sortable: true},
                        {key: 'nameFamily', label: 'نام و فامیل', sortable: true},
                        {key: 'amount', label: 'مبلغ', sortable: true},
                        {key: 'iban', label: 'شماره شبا', sortable: true},
                        {key: 'actions', label: '#'},
                    ]"
                    striped hover
                    empty-text="داده ای برای نمایش وجود ندارد"
                    :sort-desc.sync="isSortDirDesc"
                >
                    <!-- Column: id -->
                    <template #cell(id)="data">
                        <div class="text-nowrap">
                            <b-link
                                :to="{ name: 'tr-internal-view', params: { id: data.item.id } }"
                                class="font-weight-bold d-block text-nowrap"
                            >
                                #{{ data.item.id }}
                            </b-link>
                        </div>
                    </template>


                    <!-- Column: Name Family -->
                    <template #cell(nameFamily)="data">
                        <User :item="data.item"/>
                    </template>

                    <!-- Column: amount -->
                    <template #cell(amount)="data">
                        <div class="text-nowrap vazir" v-clipboard:copy="(data.item.amount *10).toString()" v-clipboard:success="onCopy" v-clipboard:error="onError">
                            <feather-icon icon="CopyIcon"/>
                            <span>{{(data.item.amount *10).toLocaleString()}} <small>ریال</small></span>
                        </div>
                    </template>

                    <!-- Column: iban -->
                    <template #cell(iban)="data">
                        <div class="text-nowrap" v-clipboard:copy="(data.item.card_bank.iban).replace(/\D/g, '')" v-clipboard:success="onCopy" v-clipboard:error="onError">
                            <span>IR{{(data.item.card_bank.iban).replace(/\D/g, "")}}</span>
                            <feather-icon icon="CopyIcon"/>
                        </div>
                    </template>

                    <!-- Column: Actions -->
                    <template #cell(actions)="data">
                        <div class="d-flex">
                            <feather-icon @click="rejectInternal(data.item.id)" class="text-danger mr-2 cursor-pointer" icon="XIcon" size="20"/>
                            <feather-icon @click="confirmWithdraw(data.item.id,data.item.amount)" class="text-success cursor-pointer" icon="CheckIcon" size="20"/>
                        </div>
                    </template>

                </b-table>
            </b-card-text>
            <template #modal-footer>
                <div class="w-100">
                    <b-button variant="outline-secondary" class="float-right" @click="modalListIban=false">
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
    BBadge, BDropdown, BDropdownItem, BPagination, BTooltip, BModal, BSpinner, BCardText
} from 'bootstrap-vue'
    import vSelect from 'vue-select'
    import {ref, onUnmounted, watch} from '@vue/composition-api'
    import {avatarText} from '@core/utils/filter'
    import InternalListFilters from './InternalListFilters.vue'
    import useInternalList from './useInternalList'
    import axiosIns from "@/libs/axios";
    import User from '@/views/lists/User.vue'
    import ToastificationContent from "@core/components/toastification/ToastificationContent.vue";

    export default {
        data() {
            return {
                modalListIban: false,
            }
        },
        props:['user','status'],
        components: {
            BCardText, BSpinner, BModal,
            InternalListFilters,
            User,
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

            const typeOptions = [
                {label: 'واریز', value: 'deposit'},
                {label: 'برداشت', value: 'withdraw'}
            ]

            const viaOptions = [
                {label: 'وبسایت', value: 'website'},
                {label: 'اندروید', value: 'android'},
                {label: 'آی او اس', value: 'ios'}
            ]

            const gatewayOptions = [
            ]

            const statusOptions = [
            ]

            const otherOptions = [
                {label: 'تراکنش های درگاهی', value: 'trGateway'},
                {label: 'تراکنش های سفارشات', value: 'trOrders'},
                {label: 'واریز با فیش', value: 'trReceipt'},
                {label: 'واریز با شناسه', value: 'trDepositID'},
                {label: 'واریز با کارت به کارت', value: 'trDepositCard'}
            ]

            const {
                fetchInternal,
                tableColumns,
                perPage,
                currentPage,
                totalRows,
                dataMeta,
                perPageOptions,
                searchQuery,
                sortBy,
                isSortDirDesc,
                refInternalListTable,
                refetchData,

                // UI
                resolveStatusVariant,

                // Extra Filters
                typeFilter,
                statusFilter,
                dateStartFilter,dateStopFilter,
                amountStartFilter,amountStopFilter,
                viaFilter,
                idFilter,
                gatewayFilter,
                otherFilter,
                isLoading,
                itemIds,items
            } = useInternalList(props)

            return {
                fetchInternal,
                tableColumns,
                perPage,
                currentPage,
                totalRows,
                dataMeta,
                perPageOptions,
                searchQuery,
                sortBy,
                isSortDirDesc,
                refInternalListTable,
                refetchData,

                // Filter
                avatarText,

                // UI
                resolveStatusVariant,

                typeOptions,
                statusOptions,
                viaOptions,
                gatewayOptions,
                otherOptions,

                // Extra Filters
                typeFilter,
                statusFilter,
                dateStartFilter, dateStopFilter,
                amountStartFilter, amountStopFilter,
                idFilter,
                viaFilter,
                gatewayFilter,
                otherFilter,
                isLoading,
                itemIds,items
            }
        },
        watch:{
        },
        methods:{
            confirmWithdraw(id,amount){
                this.$swal({
                    title: 'از تایید برداشت اطمینان دارید؟',
                    html: "مبلغ: " + amount.toLocaleString()+' تومان'+'<hr>'+
                        '<select id="viaWithdraw" required class="swal2-input">'+
                        '<option value="" disabled hidden selected>نحوه واریز را انتخاب کنید</option>'+
                        this.getOptionsGatewayWithdraw()+
                        '</select>',
                    icon: 'question',
                    showCancelButton: true,
                    showLoaderOnConfirm: true,
                    buttonsStyling: false,
                    preConfirm: () => {
                        var viaWithdraw = document.getElementById("viaWithdraw").value;
                        if(viaWithdraw == ''){
                            this.$toast({component: ToastificationContent,props: {title: 'نحوه واریز',
                                    text: 'نحوه واریز را انتخاب کنید و اگر بصورت دستی انتخاب شود یعنی شما میبایست خارج از پلتفرم مانند همراه بانک و ... با کاربر تسویه کنید.',
                                    icon: 'AlertTriangleIcon',variant: 'warning',},
                            })
                            return false;
                        }
                        return  axiosIns.post('internal/confirm/'+id,{viaWithdraw:viaWithdraw})
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
                            this.refetchData();
                            this.getGeneralInfoApi();
                            this.$swal({icon: 'success',title: 'موفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }else{
                            this.$swal({icon: 'error',title: 'ناموفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }
                    }
                })
            },
            rejectInternal(id){
                var text = "با رد کردن تراکنش، مبلغ به کیف پول کاربر برمیگردد و امکان تایید آن وجود ندارد و لطفا دقت نمایید."
                this.$swal({
                    title: 'از رد کردن اطمینان دارید؟',
                    icon: 'question',
                    html: text+'<hr>'+
                        '<select id="reason" required class="swal2-input">'+
                            '<option value="" disabled hidden selected>نحوه واریز را انتخاب کنید</option>'+
                            '<option value="برگشت از بانک">برگشت از بانک</option>'+
                            '<option value="درخواست لغو توسط کاربر">درخواست لغو توسط کاربر</option>'+
                            '<option value="رد تراکنش با پشتیبانی تماس بگیرید">رد تراکنش با پشتیبانی تماس بگیرید</option>'+
                        '</select>',
                    showCancelButton: true,
                    showLoaderOnConfirm: true,
                    buttonsStyling: false,
                    preConfirm: () => {
                        var reason = document.getElementById("reason").value;
                        if(reason == ''){
                            this.$toast({component: ToastificationContent,props: {title: 'دلیل رد کردن',
                                    text: 'دلیلی برای رد کردن انتخاب کنید',
                                    icon: 'AlertTriangleIcon',variant: 'warning'},
                            })
                            return false;
                        }
                        return  axiosIns.post('internal/reject/'+id,{reason:reason})
                            .then(response => {
                                return response;
                            })
                            .catch(() => {
                                this.errorFetching();
                            })
                    },
                    //allowOutsideClick: () => false
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (result.value.data.status == true){
                            this.refetchData();
                            this.getGeneralInfoApi();
                            this.$swal({icon: 'success',title: 'موفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }else{
                            this.$swal({icon: 'error',title: 'ناموفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }
                    }
                })
            },
            confirmGroupWithdraw(){
                this.$swal({
                    title: 'از تایید گروهی "برداشت ها" اطمینان دارید؟',
                    html: ' تایید گروهی شامل همه تراکنش های برداشت میشود که در صفحه جاری و در لیست مشاهده میکنید.'+'<hr>'+
                        '<select id="viaWithdraw" required class="swal2-input">'+
                        '<option value="" disabled hidden selected>نحوه واریز را انتخاب کنید</option>'+
                        this.getOptionsGatewayWithdraw()+
                        '</select><div id="bajeContainer"></div>',
                    icon: 'question',
                    showCancelButton: true,
                    showLoaderOnConfirm: true,
                    buttonsStyling: false,
                    didOpen: () => {
                        const viaSelect = document.getElementById("viaWithdraw");
                        viaSelect.addEventListener("change", (e) => {
                            if (e.target.value === "baje") {
                                let options = this.getGeneralInfo.bajeAccount.map(acc =>
                                    `<option value="${acc.accountName}">${acc.accountName}</option>`
                                ).join("");

                                document.getElementById("bajeContainer").innerHTML = `
                                    <select id="bajeAccountSelect" required class="swal2-input">
                                        <option value="" disabled hidden selected>انتخاب حساب باجه</option>
                                        ${options}
                                    </select>
                                `;
                            } else {
                                document.getElementById("bajeContainer").innerHTML = "";
                            }
                        });
                    },
                    preConfirm: () => {
                        var viaWithdraw = document.getElementById("viaWithdraw").value;
                        if(viaWithdraw == ''){
                            this.$toast({component: ToastificationContent,props: {title: 'نحوه واریز',
                                    text: 'نحوه واریز را انتخاب کنید و اگر بصورت دستی انتخاب شود یعنی شما میبایست خارج از پلتفرم مانند همراه بانک و ... با کاربر تسویه کنید.',
                                    icon: 'AlertTriangleIcon',variant: 'warning',},
                            })
                            return false;
                        }
                        if (viaWithdraw === 'baje') {
                            var bajeAccount = document.getElementById("bajeAccountSelect").value;
                            if (!bajeAccount) {
                                this.$toast({
                                    component: ToastificationContent,
                                    props: {
                                        title: 'انتخاب حساب',
                                        text: 'لطفاً یک حساب باجه انتخاب کنید',
                                        icon: 'AlertTriangleIcon',
                                        variant: 'warning',
                                    },
                                });
                                return false;
                            }
                        }

                        return axiosIns.post('internal/confirm/group',{
                                ids: this.itemIds,
                                viaWithdraw: viaWithdraw,
                                bajeAccount: bajeAccount
                            })
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
                            this.refetchData();
                            this.getGeneralInfoApi();
                            this.$swal({icon: 'success',title: 'موفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }else{
                            this.$swal({icon: 'error',title: 'ناموفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }
                    }
                })
            },
            getOptionsGatewayWithdraw(){
                var gatewayWithdraw = this.getGeneralInfo.gatewayslist.filter(gateway => gateway.withdraw === 1);
                var options = '<option value="manual">واریز به صورت دستی</option>';
                var self = this;
                gatewayWithdraw.map(function(value, key) {
                    options = options + '<option value="'+ value.name +'">واریز اتوماتیک '+ self.$i18n.t(value.name)+ ' ('+ value.name +')' +'</option>';
                });
                return options;
            },
            async downloadXls() {
                axiosIns.post('/internal/export', { ids: this.itemIds }, {
                    responseType: 'blob' // برای دریافت فایل به صورت باینری
                })
                .then((response) => {
                    // بررسی وضعیت موفقیت آمیز بودن درخواست
                    if (response.status === 200) {
                        // ایجاد یک لینک برای دانلود فایل
                        const url = window.URL.createObjectURL(new Blob([response.data]));
                        const link = document.createElement('a');
                        link.href = url;
                        link.setAttribute('download', 'transactions.xls'); // نام فایل دانلود
                        document.body.appendChild(link);
                        link.click(); // شروع دانلود فایل
                        document.body.removeChild(link); // حذف لینک پس از دانلود
                    }
                })
                .catch((error) => {
                    // در صورت بروز خطا در دانلود
                    console.error('Error downloading the file', error);
                    this.errorFetching();
                });
            },
        },
        mounted() {
            var gatewayOptions = [];
            this.getGeneralInfo.gatewayslist.map((item)=> {
                var obj = {label: this.$i18n.t(item.name), value:item.name };
                gatewayOptions.push(obj);
            });
            this.gatewayOptions = gatewayOptions;


            this.statusOptions = [
                {label: this.$i18n.t('pending'), value: 'pending'},
                {label: this.$i18n.t('success'), value: 'success'},
                {label: this.$i18n.t('return'), value: 'return'},
                {label: this.$i18n.t('suspend'), value: 'suspend'},
                {label: this.$i18n.t('unsuccessful'), value: 'unsuccessful'},
                {label: this.$i18n.t('canceled'), value: 'canceled'},
                {label: this.$i18n.t('reject'), value: 'reject'},
            ]
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
