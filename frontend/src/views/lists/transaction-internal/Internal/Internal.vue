<template>
<div>
    <b-card v-if="internal" class="border" :class="`border-${resolveStatusVariant(internal.status)}`" style="border-width: 5px !important;">
        <b-card-body class="px-0 px-md-1">
            <b-row class="col-lg-7 px-0 mx-auto">
                <div  class="w-100 text-center d-md-flex">
                    <div>
                        <h3>{{$t(internal.description)}}</h3>
                    </div>
                    <div class="ml-md-auto">
                        <h3>
                            <b-badge
                                pill
                                :variant="`${resolveStatusVariant(internal.status)}`"
                                class="text-capitalize badge-glow px-2"
                            >
                                {{$t(internal.status)}}
                            </b-badge>
                        </h3>
                    </div>
                </div>
                <b-alert class="w-100 mt-1 mb-0" variant="primary" show v-if="internal.type == 'withdraw' && internal.status == 'success' && !internal.id_order">
                    <div class="alert-body">
                        <span v-if="internal.payment_gateway == 'manual'"><strong>نکته!</strong> تایید این تراکنش به صورت واریز دستی انجام شده است. </span>
                        <span v-else><strong>نکته!</strong> تایید این تراکنش به صورت واریز اتوماتیک از درگاه {{$t(internal.payment_gateway)}} انجام شده است. </span>
                    </div>
                </b-alert>
                <b-alert class="w-100 mt-1 mb-0" variant="warning" show v-if="internal.status == 'reject' && internal.data">
                    <div class="alert-body">
                        <span v-if="internal.type == 'deposit'"><strong>نکته!</strong> اطلاعات فیش ثبت شده به دلیل "{{JSON.parse(internal.data).reason_reject}}" رد شده است. </span>
                        <span v-else><strong>نکته!</strong> برداشت از حساب به دلیل "{{JSON.parse(internal.data).reason_reject}}" رد شده است. همچنین مبلغ به کیف پول کاربر برگردانده شده است.</span>
                    </div>
                </b-alert>
                <hr class="w-100">

                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">شناسه:</span> <span>#{{internal.id}}</span>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">ثبت:</span> <span>{{internal.date}}</span>
                </b-col>

                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">کاربر:</span>
                    <b-link :to="{ name: 'user-single', params: { id: internal.user.id } }">
                        <span>{{internal.user.name?internal.user.name+' '+internal.user.family:internal.user.name_display}} (سطح: {{internal.user.level}})</span>
                    </b-link>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1" v-if="internal.user.email">
                    <span class="font-weight-bolder">ایمیل:</span> <span>{{internal.user.email}}</span>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1" v-else>
                    <span class="font-weight-bolder">موبایل:</span> <span>{{internal.user.mobile}}</span>
                </b-col>

                <b-col cols="12" md="12" class="border p-1 mb-1">
                    <span class="font-weight-bolder">نوع:</span>
                    <span class="ml-50 bullet bullet-sm" :class="internal.type =='deposit'?'bullet-success':'bullet-danger'"/>
                    <span>{{$t(internal.type)}}</span>
                </b-col>

                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">مبلغ کل:</span> <span>{{internal.amount.toLocaleString()}} <small>{{$t(internal.nameCurrency)}}</small></span>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">موجودی بعد تراکنش:</span> <span>{{internal.stock.toLocaleString()}} <small>{{$t(internal.nameCurrency)}}</small></span>
                </b-col>


                <b-col cols="12" md="6" class="border p-1 mb-1" v-if="internal.payment_gateway">
                    <span class="font-weight-bolder">درگاه پرداخت:</span> <span>{{internal.payment_gateway ? $t(internal.payment_gateway) :'----'}}</span>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1" v-if="internal.trace_number">
                    <span class="font-weight-bolder">کد رهگیری:</span> <span>{{internal.trace_number ? internal.trace_number :'----'}}</span>
                </b-col>


                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">طریقه:</span> <span class="text-capitalize">{{ internal.data ? JSON.parse(internal.data).via :'----'}}</span>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">آی پی (ip):</span> <span>{{internal.ip}}</span>
                </b-col>

                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">توضیحات:</span>
                    <span>{{$t(internal.description)}}</span>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1" v-if="internal.id_order">
                    <span class="font-weight-bolder">شناسه سفارش:</span>
                    <b-link :to="{ name: 'order-view', params: { id: internal.id_order } }">
                        <span>#{{internal.id_order}}</span>
                    </b-link>
                </b-col>

                <b-col cols="12" md="12" class="border p-1 mb-1" v-if="internal.admin">
                    <span class="font-weight-bolder">آخرین تغییر توسط ادمین:</span>
                    <b-link :to="{ name: 'admin-view', params: { id: internal.admin.id } }">
                        <span>{{internal.admin.name}}</span>
                    </b-link>
                </b-col>

                <b-col cols="12" md="12" class="border p-1 mb-1" :class="internal.status =='pending'?'border-primary':''" v-if="internal.cardbank">
                    <div class="font-weight-bolder cursor-pointer d-flex" :class="visibleCard ? null : 'collapsed'"
                          :aria-expanded="visibleCard ? 'true' : 'false'"
                          aria-controls="collapse-cardbank"
                          @click="visibleCard = !visibleCard">
                        <div>+ مشاهده مشخصات حساب بانکی:</div>
                        <div class="ml-auto" dir="ltr">{{ccformat(internal.cardbank.card_number)}}</div>

                    </div>
                    <b-collapse id="collapse-cardbank" v-model="visibleCard">
                        <b-row class="pt-1">
                            <b-col cols="12" md="7" class="cursor-pointer"
                                   v-clipboard:copy="internal.cardbank.card_number? internal.cardbank.card_number.replace(/[^0-9]/g, ''):internal.cardbank.card_number"
                                   v-clipboard:success="onCopy"
                                   v-clipboard:error="onError"
                            >
                                <span class="font-weight-bolder">شماره کارت: </span>
                                <span dir="ltr">
                                     <feather-icon icon="CopyIcon"/> {{ccformat(internal.cardbank.card_number)}}
                                </span>
                            </b-col>
                            <b-col cols="12" md="5" class="cursor-pointer"
                                   v-clipboard:copy="internal.cardbank.iban?internal.cardbank.iban.replace(/[^0-9]/g, ''):internal.cardbank.iban"
                                   v-clipboard:success="onCopy"
                                   v-clipboard:error="onError"
                            >
                                <span class="font-weight-bolder">شماره شبا: </span>
                                <span dir="ltr">
                                    <feather-icon icon="CopyIcon"/> {{internal.cardbank.iban ? internal.cardbank.iban : '-----'}}
                                </span>
                            </b-col>

                            <b-col cols="12" md="5">
                                <span class="font-weight-bolder">نام بانک: </span>
                                <span dir="ltr">{{internal.cardbank.bank_name ? internal.cardbank.bank_name : '-----'}}</span>
                            </b-col>
                        </b-row>
                    </b-collapse>
                </b-col>

                <b-col cols="12" md="12" class="border p-1 mb-1" :class="internal.status =='pending'?'border-primary':''" v-if="receipt">
                    <div class="font-weight-bolder cursor-pointer d-flex" :class="visibleReceipt ? null : 'collapsed'"
                         :aria-expanded="visibleCard ? 'true' : 'false'"
                         aria-controls="collapse-receipt"
                         @click="visibleReceipt = !visibleReceipt">
                        <div>+ مشاهده مشخصات فیش ثبتی:</div>
                        <div class="ml-auto" dir="ltr">{{receipt.payee.iban_number}}</div>
                    </div>
                    <b-collapse id="collapse-receipt" v-model="visibleReceipt">
                        <b-row class="pt-1">
                            <b-col cols="12" md="6">
                                <span class="font-weight-bolder">واریز از طریق: </span>
                                <span dir="ltr">{{receipt.via}}</span>
                            </b-col>
                            <b-col cols="12" md="6">
                                <span class="font-weight-bolder">تاریخ واریز: </span>
                                <span dir="ltr">{{receipt.dateTime}}</span>
                            </b-col>
                            <b-col cols="12" md="6" class="cursor-pointer"
                                   v-clipboard:copy="receipt.trackingCode"
                                   v-clipboard:success="onCopy"
                                   v-clipboard:error="onError"
                            >
                                <span class="font-weight-bolder">کد رهگیری: </span>
                                <span dir="ltr">
                                         <feather-icon icon="CopyIcon"/> {{receipt.trackingCode}}
                                    </span>
                            </b-col>
                            <b-col cols="12" md="6">
                                <span class="font-weight-bolder">شماره حساب مقصد: </span>
                                <span dir="ltr">{{receipt.payee.account_number}}</span>
                            </b-col>
                            <b-col cols="12" md="12">
                                <span class="font-weight-bolder">شماره کارت مقصد: </span>
                                <span dir="ltr">{{receipt.payee.card_number}}</span>
                            </b-col>
                            <b-col cols="12" md="12">
                                <span class="font-weight-bolder">شماره شبا مقصد: </span>
                                <span dir="ltr">{{receipt.payee.iban_number}}</span>
                            </b-col>
                            <b-col cols="12" md="12">
                                <span class="font-weight-bolder">تصویر فیش: </span>
                                <img :src="srcFile" width="100%" class="box-shadow-1 border">
                            </b-col>
                        </b-row>
                    </b-collapse>
                </b-col>

                <b-col cols="12" md="12" class="border p-1 mb-1">
                    <span class="font-weight-bolder cursor-pointer" :class="visible ? null : 'collapsed'"
                          :aria-expanded="visible ? 'true' : 'false'"
                          aria-controls="collapse-data"
                          @click="visible = !visible"> + داده های ذخیره شده:</span>
                    <b-collapse id="collapse-data" v-model="visible">
                        <div dir="ltr" class="text-right">
                            <pre v-html="JSON.stringify(JSON.parse(internal.data), null, 4)"></pre>
                        </div>
                    </b-collapse>
                </b-col>

                <hr class="w-100">
                <b-col cols="12" md="12" class="mb-1 d-flex p-0">
                    <b-button variant="success" block v-if="internal.status == 'pending'" class="mr-25 mt-0" @click="confirmInternal(internal.id)">
                        <feather-icon icon="CheckCircleIcon" class="mr-50"/>
                        <span class="align-middle">تایید تراکنش</span>
                    </b-button>
                    <b-button variant="info" block v-if="internal.status == 'pending' && receipt" class="mr-25 mt-0" @click="inquiryReceipt(internal.id)">
                        <feather-icon icon="InfoIcon" class="mr-50"/>
                        <span class="align-middle">استعلام تراکنش</span>
                    </b-button>
                    <b-button variant="dark" block v-if="internal.status == 'pending' || internal.status == 'success'" class="mr-25 mt-0" @click="rejectInternal(internal.id)">
                        <feather-icon icon="XCircleIcon" class="mr-50"/>
                        <span class="align-middle">رَد تراکنش</span>
                    </b-button>
                    <b-button variant="success" block v-if="internal.status == 'suspend' && internal.type =='deposit'" class="mr-25 mt-0" @click="verifyInternal(internal.id)">
                        <feather-icon icon="CheckCircleIcon" class="mr-50"/>
                        <span class="align-middle">وریفای تراکنش</span>
                    </b-button>
                </b-col>

            </b-row>
        </b-card-body>
    </b-card>
    <div v-if="!accessUserLogin['tr-internal']['single'] && activeUserInfo.role !== 'admin'">
        <NotAccessed/>
    </div>

    <b-modal
        v-model="modalInquiryReceipt"
        id="inquiry-modal" ref="inquiry-modal"
        title="نتیجه استعلام"
        ok-title="ذخیره تغییرات"
        cancel-title="بستن"
        cancel-variant="outline-secondary"
    >
        <b-card-text>
            <div class="text-center my-2" v-if="!inquiryReceiptData">
                <b-spinner style="width: 3rem; height: 3rem;"/>
            </div>
            <div v-else dir="ltr" class="text-right">
                <pre v-html="JSON.stringify(inquiryReceiptData, null, 6)"></pre>
            </div>
        </b-card-text>
        <template #modal-footer>
            <div class="w-100">
                <b-button variant="outline-secondary" class="float-right" @click="modalInquiryReceipt=false">
                    بستن
                </b-button>
            </div>
        </template>
    </b-modal>
</div>
</template>

<script>
    import BCardActions from '@core/components/b-card-actions/BCardActions.vue'
    import {
        BCard,
        BCardHeader,
        BBadge,
        BCollapse,
        BLink,
        BCardBody,
        BRow,
        BCol,
        BInputGroup,
        BInputGroupAppend,
        BFormGroup,
        BFormInput,
        BTable,
        BButton,
        BAlert,
        BModal, BCardText, BSpinner
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
                internal: null,
                visible: false,
                visibleCard: false,
                visibleReceipt: false,
                receipt: false,
                srcFile: null,
                bajeAccount:null,
                adminHesab:null,

                modalInquiryReceipt:false,
                inquiryReceiptData:null
            }
        },
        components: {
            BSpinner, BCardText, BModal,
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
            BAlert,
            BInputGroup,BFormGroup, BInputGroupAppend,BFormInput,
            vSelect,NotAccessed
        },
        methods:{
            getOrder(id){
                axiosIns.post('internal/info/'+id)
                .then(response => {
                    this.internal = response.data.internal
                    this.bajeAccount = response.data.baje
                    this.adminHesab = response.data.admin_hesab
                    if(this.internal.receipt){
                        this.receipt = this.internal.receipt;
                        if(this.internal.receipt.photo)
                            this.getFile(this.internal.receipt.photo,'srcFile')
                    }

                })
                .catch(() => { this.errorFetching(); })
            },
            confirmInternal(id){
                if(this.receipt && this.internal.type == 'deposit')
                    this.confirmReceipt(id);
                else if(this.internal.type == 'withdraw')
                    this.confirmWithdraw(id);
            },
            confirmReceipt(id){
                this.$swal({
                    title: 'از تایید واریز اطمینان دارید؟',
                    text: "با توجه به اطلاعات فیش بانکی ثبت شده، در صورتی که تراکنش را تایید نمایید کیف پول کاربر به مقدار این مبلغ تراکنش شارژ میشود.",
                    icon: 'question',
                    input:'text',
                    inputPlaceholder: 'مبلغ را درج کنید',
                    inputValue: this.internal.amount.toLocaleString('en-US'),
                    showCancelButton: true,
                    showLoaderOnConfirm: true,
                    buttonsStyling: false,
                    onOpen: function (el) {
                        const el2 = el.getElementsByClassName('swal2-input')[0];
                        el2.addEventListener('keyup', function(e) {
                            var amount = e.target.value.replace(/[^\d.-]/g, '')
                            e.target.value =  parseInt(amount).toLocaleString('en-US');
                        })
                    },
                    inputValidator: (value) => {
                        if (!value) {
                            return 'لطفا فیلدها را تکمیل نمایید!'
                        }
                    },
                    preConfirm: (value) => {
                        return  axiosIns.post('internal/confirm/'+id,{amount:value.replace(/[^\d.-]/g, '')})
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
                            this.getOrder(id);
                            this.getGeneralInfoApi();
                            this.$swal({icon: 'success',title: 'موفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }else{
                            this.$swal({icon: 'error',title: 'ناموفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }
                    }
                })
            },
            confirmWithdraw(id){
                this.$swal({
                    title: 'از تایید برداشت اطمینان دارید؟',
                    html: `
                        مبلغ: ${this.internal.amount.toLocaleString()} تومان<hr>
                        شماره کارت: <span dir="ltr">${this.ccformat(this.internal.cardbank.card_number)}</span><br>
                        شماره شبا: <small dir="ltr">${this.internal.cardbank.iban}</small><hr>
                        <select id="viaWithdraw" required class="swal2-input">
                            <option value="" disabled hidden selected>نحوه واریز را انتخاب کنید</option>
                            ${this.getOptionsGatewayWithdraw()}
                        </select>
                        <div id="bajeContainer"></div> <!-- محل قرارگیری سلکت دوم -->
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    showLoaderOnConfirm: true,
                    buttonsStyling: false,
                    didOpen: () => {
                        const viaSelect = document.getElementById("viaWithdraw");
                        viaSelect.addEventListener("change", (e) => {
                            if (e.target.value === "baje") {
                                let options = this.bajeAccount.map(acc =>
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

                        return axiosIns.post('internal/confirm/' + id, {
                            viaWithdraw: viaWithdraw,
                            bajeAccount: bajeAccount
                        }).then(response => {
                            return response;
                        }).catch(() => {
                            this.errorFetching();
                        });
                    },
                    allowOutsideClick: () => false
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (result.value.data.status == true){
                            this.getOrder(id);
                            this.getGeneralInfoApi();
                            this.$swal({icon: 'success',title: 'موفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }else{
                            this.$swal({icon: 'error',title: 'ناموفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }
                    }
                })
            },
            rejectInternal(id){
                if (this.internal.type == 'deposit')
                    var text = "با رد تراکنش دیگر امکان تایید آن وجود ندارد و لطفا دقت نمایید."
                else
                    var text = "با رد کردن تراکنش، مبلغ به کیف پول کاربر برمیگردد و امکان تایید آن وجود ندارد و لطفا دقت نمایید."

                this.$swal({
                    title: 'از رد کردن اطمینان دارید؟',
                    text: text,
                    input:'textarea',
                    inputPlaceholder: 'دلیلی جهت رد کردن و نمایش به کاربر درج کنید',
                    icon: 'question',
                    inputValidator: (value) => {
                        if (!value) {
                            return 'لطفا فیلدها را تکمیل نمایید!'
                        }
                    },
                    showCancelButton: true,
                    showLoaderOnConfirm: true,
                    buttonsStyling: false,
                    preConfirm: (value) => {
                        return  axiosIns.post('internal/reject/'+id,{reason:value})
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
                            this.getOrder(id);
                            this.getGeneralInfoApi();
                            this.$swal({icon: 'success',title: 'موفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }else{
                            this.$swal({icon: 'error',title: 'ناموفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }
                    }
                })
            },
            verifyInternal(id){
                this.$swal({
                    title: 'از وریفای کردن تراکنش اطمینان دارید؟',
                    text: 'اگر پرداخت شده باشد که وریفای موفق میشود و به کیف پول کاربر اضافه میگردد و وضعیت تراکنش موفق خواهد شد.',
                    icon: 'question',
                    showCancelButton: true,
                    showLoaderOnConfirm: true,
                    buttonsStyling: false,
                    preConfirm: (value) => {
                        return  axiosIns.post('internal/verify/'+id,{reason:value})
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
                            this.getOrder(id);
                            this.$swal({icon: 'success',title: 'موفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }else{
                            this.$swal({icon: 'error',title: 'ناموفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }
                    }
                })
            },
            inquiryReceipt(id){
                this.inquiryReceiptData = null;
                this.modalInquiryReceipt = true;
                axiosIns.post('internal/inquiry-receipt/'+id).then(response => {
                    this.inquiryReceiptData = response.data;
                }).catch((error) => {console.log(error);this.errorFetching();})
            },
            resolveStatusVariant(status){
                if (status === 'pending') return 'warning'
                if (status === 'success') return 'success'
                if (status === 'return') return 'secondary'
                if (status === 'suspend') return 'dark'
                if (status === 'unsuccessful') return 'danger'
                if (status === 'reject') return 'danger'
                if (status === 'canceled') return 'secondary'
                return 'warning'
            },
            ccformat (value) {
                return value.replace(/\W/gi, '').replace(/(.{4})/g, '$1 ');
            },
            getOptionsGatewayWithdraw(){
                var gatewayWithdraw = this.getGeneralInfo.gatewayslist.filter(gateway => gateway.withdraw === 1);
                var options = '<option value="manual">واریز به صورت دستی</option>';
                var self = this;
                gatewayWithdraw.map(function(value, key) {
                    options = options + '<option value="'+ value.name +'">واریز اتوماتیک '+ self.$i18n.t(value.name)+ ' ('+ value.name +')' +'</option>';
                });
                return options;
            }
        },
        created() {
            if(this.accessUserLogin['tr-internal']['single'] || this.activeUserInfo.role === 'admin')
                this.getOrder(this.$router.currentRoute.params.id)
        }
    }
</script>

<style scoped>

</style>
