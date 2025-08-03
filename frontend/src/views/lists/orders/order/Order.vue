<template>
<div>
    <b-card v-if="order">
        <b-card-body class="px-0 px-md-1">
            <div class="text-center">
                <i class="cf" style="font-size: 120px" v-if="isFontExist(order.symbol)" :class="'cf-'+order.symbol.toLowerCase()" :style="{color:colorSymbol(order.symbol)}"></i>
                <img :src="baseURL+'images/currency/' + iconSymbol(order.symbol)" width="120px" v-else />
                <h4 class="mt-2 text-capitalize">
                    {{isCryptoCurrency(order.symbol) ? localeNameSymbol(order.symbol)[localeHas] : $t(order.name)}}
                    -
                    {{isCryptoCurrency(order.symbol) ? localeNameSymbol(order.symbol)['en'] : (order.name)}}
                </h4>
            </div>
            <b-row class="mt-4 col-lg-7 px-0 mx-auto">
                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">شناسه:</span> <span>#{{order.id}}</span>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">ثبت:</span> <span>{{order.date}}</span>
                </b-col>

                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">کاربر:</span>
                    <b-link :to="{ name: 'user-single', params: { id: order.user.id } }">
                        <span>{{order.user.name ? (order.user.name+' '+order.user.family) : order.user.name_display}}</span>
                    </b-link>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1" v-if="order.user.email">
                    <span class="font-weight-bolder">ایمیل:</span> <span>{{order.user.email}}</span>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1" v-else>
                    <span class="font-weight-bolder">موبایل:</span> <span>{{order.user.mobile}}</span>
                </b-col>

                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">نوع:</span>
                    <span class="ml-50 bullet bullet-sm" :class="order.type =='buy'?'bullet-success':'bullet-danger'"/>
                    <span>{{$t(order.type)}}</span>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">مبلغ کل:</span> <span>{{order.amount.toLocaleString()}} <small>تومان</small></span>
                </b-col>

                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">مقدار ارز:</span> <span>{{toFixFloat(order.amount_coin)}} <small>{{order.symbol}}</small></span>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">کارمزد:</span> <span>{{order.wage.toLocaleString()}} <small>تومان</small></span>
                </b-col>

                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">قیمت ارز:</span> <span>{{toFixFloat(order.fee)}} <small>تومان</small></span>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">وضعیت:</span>
                    <b-badge
                        pill
                        :variant="`light-${resolveStatusVariant(order.status)}`"
                        class="text-capitalize"
                    >
                        {{$t(order.status)}}
                    </b-badge>
                </b-col>

                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">طریقه:</span> <span class="text-capitalize">{{order.via}}</span>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">آی پی (ip):</span> <span>{{order.ip}}</span>
                </b-col>

                <b-col cols="12" md="6" class="border p-1 mb-1" v-if="order.referral_amount">
                    <span class="font-weight-bolder">مبلغ رفرال:</span>
                    <span>{{order.referral_amount.toLocaleString()}} <small>تومان</small></span>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1" v-if="order.referral">
                    <span class="font-weight-bolder">درصد معرفی کننده / معرفی شونده:</span>
                    <span>{{order.referral.percent_caller}} / {{order.referral.percent_referral}} </span>
                </b-col>

                <b-col cols="12" md="12" class="border p-1 mb-1" v-if="order.admin">
                    <span class="font-weight-bolder">آخرین تغییر توسط ادمین:</span>
                    <b-link :to="{ name: 'admin-view', params: { id: order.admin.id } }">
                        <span>{{order.admin.name}}</span>
                    </b-link>
                </b-col>

                <b-col cols="12" md="12" class="border p-1 mb-1">
                    <span class="font-weight-bolder">توضیحات:</span>
                    <span>{{order.description}}</span>
                </b-col>


                <b-col cols="12" md="12" class="border p-1 mb-1" v-if="order.dataParam">
                    <span class="font-weight-bolder">اطلاعات سفارش {{$t(order.name)}}:</span>
                    <div class="mb-0 mt-1 table-responsive">
                        <table class="table b-table table-bordered text-right">
                            <tr :key="key" v-for="(key,value) in order.dataParam">
                                <td class="cursor-pointer" v-clipboard:copy="key" v-clipboard:success="onCopy" v-clipboard:error="onError">
                                    {{key}} <feather-icon icon="CopyIcon"/>
                                </td>
                                <td>
                                    {{value}}
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="mt-1" v-if="order.symbol ==='PMV'">
                        <b-button variant="outline-info" v-b-modal.PMV-modal @click="checkPMV(order.dataParam.VOUCHER_NUM)">دریافت استعلام ووچر</b-button>
                    </div>
                </b-col>


                <b-col cols="12" md="12" class="border p-1 mb-1">
                    <span class="font-weight-bolder cursor-pointer" :class="visible ? null : 'collapsed'"
                          :aria-expanded="visible ? 'true' : 'false'"
                          aria-controls="collapse-4"
                          @click="visible = !visible"> + داده های ذخیره شده:</span>
                    <b-collapse id="collapse-4" v-model="visible">
                        <div dir="ltr" class="text-right">
                            <pre v-html="JSON.stringify(JSON.parse(order.data), null, 4)"></pre>
                        </div>
                    </b-collapse>
                </b-col>

                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <b-link :to="{ name: 'tr-internal', query: { id_order: order.id } }">
                        تراکنش های داخلی این سفارش
                    </b-link>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <b-link :to="{ name: 'tr-crypto', query: { id_order: order.id } }">
                        تراکنش های رمز ارز این سفارش
                    </b-link>
                </b-col>

            </b-row>
        </b-card-body>
    </b-card>

    <b-modal id="PMV-modal" centered title="جزئیات و استعلام ووچر پرفکت مانی">
        <b-card-text>
            <div class="mb-0 mt-1 table-responsive" dir="ltr">
                <table class="table b-table table-bordered text-right">
                    <tr :key="key" v-for="(value,key) in pmv">
                        <td class="cursor-pointer">
                            {{key}}:
                        </td>
                        <td>
                            {{value}}
                        </td>
                    </tr>
                </table>
            </div>
            <div v-if="!pmv" class="text-center">
                <b-spinner style="width: 3rem; height: 3rem;"></b-spinner>
            </div>
        </b-card-text>
        <template #modal-footer>
            <div class="w-100">
                <b-button variant="outline-secondary" class="float-right" @click="$bvModal.hide('PMV-modal')">
                    بستن
                </b-button>
            </div>
        </template>
    </b-modal>

    <div v-if="!accessUserLogin['orders']['single'] && activeUserInfo.role !== 'admin'">
        <NotAccessed/>
    </div>
</div>
</template>

<script>
    import BCardActions from '@core/components/b-card-actions/BCardActions.vue'
    import {
        BCard, BCardHeader, BBadge, BCollapse, BLink, BCardBody, BRow, BCol, BInputGroup, BInputGroupAppend, BFormGroup,
        BFormInput, BTable, BButton,BSpinner
    } from 'bootstrap-vue'
    import vSelect from 'vue-select'
    import axiosIns from "@/libs/axios";
    import ToastificationContent from "@core/components/toastification/ToastificationContent";
    import Table from "@/views/vuexy/table/bs-table/Table";
    import NotAccessed from "@/views/vuexy/pages/miscellaneous/NotAccessed";

    export default {
        name: "Order",
        data () {
            return {
                order: null,
                visible: false,
                pmv:null,
            }
        },
        components: {
            Table,
            BTable,
            BLink,
            BCard,
            BBadge,
            BRow,
            BCol,
            BCardActions,
            BCardHeader,
            BCardBody,
            BCollapse,
            BButton,
            BInputGroup,BFormGroup, BInputGroupAppend,BFormInput,BSpinner,
            vSelect,NotAccessed
        },
        methods:{
            getOrder(id){
                axiosIns.post('orders/info/'+id)
                    .then(response => {
                        this.order = response.data.order
                    })
                    .catch(() => {
                        this.$toast({component: ToastificationContent, props: {title: 'Error fetching data', icon: 'AlertTriangleIcon', variant: 'danger',},})
                    })
            },
            resolveStatusVariant(status){
                if (status === 'pending') return 'warning'
                if (status === 'success') return 'success'
                if (status === 'return') return 'secondary'
                if (status === 'suspend') return 'dark'
                if (status === 'unsuccessful') return 'danger'
                if (status === 'canceled') return 'secondary'
                return 'warning'
            },
            checkPMV(VOUCHER_NUM){
                this.pmv = null;
                axiosIns.post('orders/PMV/check',{ev_number:VOUCHER_NUM})
                    .then(response => {
                        this.pmv = response.data.data;
                    })
                    .catch(() => {
                        this.$toast({component: ToastificationContent, props: {title: 'Error fetching data', icon: 'AlertTriangleIcon', variant: 'danger',},})
                    })
            }
        },
        created() {
            if(this.accessUserLogin['orders']['single'] || this.activeUserInfo.role === 'admin')
                this.getOrder(this.$router.currentRoute.params.id)
        }
    }
</script>

<style scoped>

</style>
