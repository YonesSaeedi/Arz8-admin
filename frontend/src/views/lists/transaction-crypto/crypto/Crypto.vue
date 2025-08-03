<template>
<div>
    <b-card v-if="transaction" class="border" :class="`border-${resolveStatusVariant(transaction.status)}`" style="border-width: 5px !important;">
        <b-card-body class="px-0 px-md-1">
            <div class="text-center">
                <i class="cf" style="font-size: 120px" v-if="isFontExist(transaction.symbol)" :class="'cf-'+transaction.symbol.toLowerCase()" :style="{color:colorSymbol(transaction.symbol)}"></i>
                <img :src="baseURL+'images/currency/' + iconSymbol(transaction.symbol)" width="120px" v-else />
                <h4 class="mt-1 text-capitalize"> {{localeNameSymbol(transaction.symbol)[localeHas]}} -  {{localeNameSymbol(transaction.symbol)['en']}}</h4>
            </div>
            <b-row class="col-lg-7 px-0 mx-auto">
                <hr class="w-100">
                <div  class="w-100 text-center d-md-flex">
                    <div>
                        <h3>{{transaction.description}}</h3>
                    </div>
                    <div class="ml-md-auto">
                        <h3>
                            <b-badge
                                pill
                                :variant="`${resolveStatusVariant(transaction.status)}`"
                                class="text-capitalize badge-glow px-2"
                            >
                                {{$t(transaction.status)}}
                            </b-badge>
                        </h3>
                    </div>
                </div>

                <b-alert class="w-100 mt-1 mb-0" variant="danger" show v-if="transaction.txid_duplicate.length >0">
                    <div class="alert-body">
                        <strong>نکته!</strong> تراکنش های دیگری با این شناسه تراکنش ثبت شده است:
                        <b-link class="link-duplicate" target="_blank" :to="{ name: 'tr-crypto-view', params: { id: item.id } }" v-for="(item,key) in transaction.txid_duplicate" :key="key">
                            <span>{{item.id}}</span>
                        </b-link>
                    </div>
                </b-alert>

                <b-alert class="w-100 mt-1 mb-0" variant="primary" show v-if="transaction.type == 'withdraw' && transaction.status == 'success' &&transaction.data">
                    <div class="alert-body">
                        <span v-if="JSON.parse(transaction.data).withdrawViaAdminAuto"><strong>نکته!</strong> تایید این تراکنش به صورت واریز اتوماتیک و توسط ادمین انجام شده است. </span>
                        <span v-else-if="JSON.parse(transaction.data).withdrawAuto"><strong>نکته!</strong> تایید این تراکنش به صورت واریز اتوماتیک و آنی در لحظه برداشت انجام شده است. </span>
                        <span v-else-if="JSON.parse(transaction.data).receivingUser">
                            <strong>نکته!</strong>
                            این تراکنش انتقال بین کاربران ما بوده است و شناسه کاربر دریافت کننده
                            <b-link :to="{ name: 'user-single', params: { id: JSON.parse(transaction.data).receivingUser } }">
                                #{{JSON.parse(transaction.data).receivingUser}}
                            </b-link>
                            میباشد.
                        </span>
                        <span v-else><strong>نکته!</strong> تایید این تراکنش به صورت واریز دستی انجام شده است. </span>
                    </div>
                </b-alert>
                <b-alert class="w-100 mt-1 mb-0" variant="warning" show v-if="transaction.status == 'reject' && transaction.data">
                    <div class="alert-body">
                        <span v-if="transaction.type == 'deposit'"><strong>نکته!</strong> واریز به حساب به دلیل "{{JSON.parse(transaction.data).reason}}" رد شده است. </span>
                        <span v-else><strong>نکته!</strong> برداشت از حساب به دلیل "{{JSON.parse(transaction.data).reason}}" رد شده است. همچنین مبلغ به کیف پول کاربر برگردانده شده است.</span>
                    </div>
                </b-alert>

                <b-alert class="w-100 mt-1 mb-0" variant="danger" show v-if="inquiryBinance && inquiryBinance.status !== 1">
                    <div class="alert-body">
                        <strong>نکته!</strong>
                        این تراکنش واریزی تا اکنون توسط بایننس وضعیت موفق نداشته است!
                    </div>
                </b-alert>
                <b-alert class="w-100 mt-1 mb-0" variant="success" show v-else-if="inquiryBinance && inquiryBinance.status === 1">
                    <div class="alert-body">
                        <strong>نکته!</strong>
                        این تراکنش واریزی توسط بایننس وضعیت موفق دارد!
                    </div>
                </b-alert>

                <hr class="w-100">

                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">شناسه:</span> <span>#{{transaction.id}}</span>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">ثبت:</span> <span>{{transaction.date}}</span>
                </b-col>

                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">کاربر:</span>
                    <b-link :to="{ name: 'user-single', params: { id: transaction.user.id } }">
                        <span>{{transaction.user.name+' '+transaction.user.family}} (سطح: {{transaction.user.level}})</span>
                    </b-link>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">ایمیل:</span> <span>{{transaction.user.email}}</span>
                </b-col>

                <b-col cols="6" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">نوع:</span>
                    <span class="ml-50 bullet bullet-sm" :class="transaction.type =='deposit'?'bullet-success':'bullet-danger'"/>
                    <span>{{$t(transaction.type)}}</span>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">واریز های موفق:</span> <span class="text-success">{{transaction.deposit_success}}</span> مرتبه
                </b-col>

                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <div>
                        <span class="font-weight-bolder">مقدار:</span>
                        <span class="text-right cursor-pointer text-warning" dir="ltr" v-clipboard:copy="toFixFloat(transaction.amount)" v-clipboard:success="onCopy" v-clipboard:error="onError">
                            <feather-icon icon="CopyIcon"/>
                            <span>{{toFixFloat(transaction.amount)}} <small>{{(transaction.symbol)}}</small></span>
                        </span>
                    </div>
                    <div v-if="transaction.withdraw_fee>0">
                        <span class="font-weight-bolder">مقدار با کسر کارمزد:</span>
                        <span class="text-right cursor-pointer text-info" dir="ltr" v-clipboard:copy="toFixFloat(transaction.amount-transaction.withdraw_fee)" v-clipboard:success="onCopy" v-clipboard:error="onError">
                            <feather-icon icon="CopyIcon"/>
                            <span>{{toFixFloat(transaction.amount-transaction.withdraw_fee)}} <small>{{(transaction.symbol)}}</small></span>
                        </span>
                    </div>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">موجودی بعد تراکنش:</span> <span>{{toFixFloat(transaction.stock)}} <small>{{(transaction.symbol)}}</small></span>
                </b-col>

                <b-col cols="12" md="6" class="border p-1 mb-1" v-if="transaction.receivingUser">
                    <span class="font-weight-bolder">کاربر گیرنده:</span>
                    <b-link :to="{ name: 'user-single', params: { id: JSON.parse(transaction.data).receivingUser } }">
                        <span>{{transaction.receivingUser.name}}</span>
                    </b-link>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1" v-if="transaction.receivingUser">
                    <span class="font-weight-bolder">پیدا کردن کاربر گیرنده از طریق:</span> <span>{{transaction.receivingUser.receivingUserFindBy}}</span>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1" v-if="transaction.senderUser">
                    <span class="font-weight-bolder">کاربر فرستنده:</span>
                    <b-link :to="{ name: 'user-single', params: { id: JSON.parse(transaction.data).senderUser } }">
                        <span>{{transaction.senderUser.name}}</span>
                    </b-link>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1" v-if="transaction.senderUser">
                    <span class="font-weight-bolder">پیدا کردن کاربر گیرنده از طریق:</span> <span>{{transaction.senderUser.receivingUserFindBy}}</span>
                </b-col>


                <b-col cols="12" md="6" class="border p-1 mb-1" v-if="transaction.network">
                    <span class="font-weight-bolder">شبکه انتقال:</span> <span>{{transaction.network.name}} - {{(transaction.network.symbol)}} </span>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1" v-if="transaction.withdraw_fee">
                    <span class="font-weight-bolder">کارمزد انتقال:</span> <span>{{toFixFloat(transaction.withdraw_fee)}} <small>{{(transaction.symbol)}}</small></span>
                </b-col>

                <b-col cols="12" md="12" class="border p-1 mb-1" v-if="transaction.destination" :class="transaction.status =='pending'?'border-primary':''">
                    <span class="font-weight-bolder">آدرس کیف پول:</span>
                    <div class="text-right cursor-pointer" dir="ltr" v-clipboard:copy="transaction.destination" v-clipboard:success="onCopy" v-clipboard:error="onError">
                        <feather-icon icon="CopyIcon"/> {{transaction.destination}}
                    </div>
                </b-col>

                <b-col cols="12" md="12" class="border p-1 mb-1" v-if="transaction.destination_tag" :class="transaction.status =='pending'?'border-primary':''">
                    <span class="font-weight-bolder">تگ آدرس کیف پول:</span> <span>{{transaction.destination_tag}}</span>
                </b-col>

                <b-col cols="12" md="12" class="border p-1 mb-1" v-if="transaction.txid" :class="transaction.status =='pending'?'border-primary':''">
                    <span class="font-weight-bolder">شناسه تراکنش در شبکه TxID:</span>
                    <div class="text-right cursor-pointer" dir="ltr" v-clipboard:copy="transaction.txid" v-clipboard:success="onCopy" v-clipboard:error="onError">
                        <feather-icon icon="CopyIcon"/> {{transaction.txid}}
                    </div>
                </b-col>

                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">طریقه:</span> <span class="text-capitalize">{{transaction.via ? transaction.via : '----'}}</span>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1">
                    <span class="font-weight-bolder">آی پی (ip):</span> <span>{{transaction.ip}}</span>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1" v-if="transaction.id_trade">
                    <span class="font-weight-bolder">شناسه معامله:</span>
                    <b-link :to="{ name: 'trade-view', params: { id: transaction.id_trade } }">
                        <span>#{{transaction.id_trade}}</span>
                    </b-link>
                </b-col>
                <b-col cols="12" md="6" class="border p-1 mb-1" v-if="transaction.id_order">
                    <span class="font-weight-bolder">شناسه سفارش:</span>
                    <b-link :to="{ name: 'order-view', params: { id: transaction.id_order } }">
                        <span>#{{transaction.id_order}}</span>
                    </b-link>
                </b-col>

                <b-col cols="12" md="12" class="border p-1 mb-1">
                    <span class="font-weight-bolder">توضیحات:</span>
                    <span>{{transaction.description}}</span>
                </b-col>

                <b-col cols="12" md="12" class="border p-1 mb-1" v-if="transaction.admin">
                    <span class="font-weight-bolder">آخرین تغییر توسط ادمین:</span>
                    <b-link :to="{ name: 'admin-view', params: { id: transaction.admin.id } }">
                        <span>{{transaction.admin.name}}</span>
                    </b-link>
                </b-col>

                <b-col cols="12" md="12" class="border p-1 mb-1" v-if="srcFile" :class="transaction.status =='pending'?'border-primary':''">
                    <span class="font-weight-bolder cursor-pointer" :class="visibleImg ? null : 'collapsed'"
                          :aria-expanded="visibleImg ? 'true' : 'false'"
                          aria-controls="collapse-img"
                          @click="visibleImg = !visibleImg"> + تصویر فیش:</span>
                    <b-collapse id="collapse-img" v-model="visibleImg">
                        <img :src="srcFile" width="100%" class="box-shadow-1 border">
                    </b-collapse>
                </b-col>


                <b-col cols="12" md="12" class="border p-1 mb-1">
                    <span class="font-weight-bolder cursor-pointer" :class="visible ? null : 'collapsed'"
                          :aria-expanded="visible ? 'true' : 'false'"
                          aria-controls="collapse-data"
                          @click="visible = !visible"> + داده های ذخیره شده:</span>
                    <b-collapse id="collapse-data" v-model="visible">
                        <div dir="ltr" class="text-right">
                            <pre v-html="JSON.stringify(JSON.parse(transaction.data), null, 4)"></pre>
                        </div>
                    </b-collapse>
                </b-col>

                <hr class="w-100">
                <b-col cols="12" md="12" class="mb-1 d-flex p-0">
                    <b-button variant="success" block v-if="transaction.status === 'pending'||transaction.status === 'reject'" class="mr-25 mt-0" @click="confirmTransaction(transaction.id)">
                        <feather-icon icon="CheckCircleIcon" class="mr-50"/>
                        <span class="align-middle">تایید تراکنش</span>
                    </b-button>
                    <b-button variant="dark" block v-if="transaction.status === 'pending'" class="mr-25 mt-0" @click="rejectCrypto(transaction.id)">
                        <feather-icon icon="XCircleIcon" class="mr-50"/>
                        <span class="align-middle">رَد تراکنش</span>
                    </b-button>
                    <b-button variant="danger" block v-if="activeUserInfo.role === 'admin'" class="mr-25 mt-0" @click="removeCrypto(transaction.id)">
                        <feather-icon icon="XCircleIcon" class="mr-50"/>
                        <span class="align-middle">حذف تراکنش</span>
                    </b-button>
                </b-col>

            </b-row>
        </b-card-body>
    </b-card>
    <div v-if="!accessUserLogin['tr-crypto']['single'] && activeUserInfo.role !== 'admin'">
        <NotAccessed/>
    </div>
    <div v-if="isNotFount">
        <NotFound/>
    </div>
</div>
</template>

<script>
    import BCardActions from '@core/components/b-card-actions/BCardActions.vue'
    import {
        BCard, BCardHeader, BBadge, BCollapse, BLink, BCardBody, BRow, BCol, BInputGroup, BInputGroupAppend, BFormGroup, BFormInput, BTable, BButton,BAlert
    } from 'bootstrap-vue'
    import vSelect from 'vue-select'
    import axiosIns from "@/libs/axios";
    import ToastificationContent from "@core/components/toastification/ToastificationContent";
    import Table from "@/views/vuexy/table/bs-table/Table";
    import NotAccessed from "@/views/vuexy/pages/miscellaneous/NotAccessed";
    import NotFound from "@/views/vuexy/pages/miscellaneous/NotFound";

    export default {
        name: "Order",
        data () {
            return {
                transaction: null,
                visible: false,
                srcFile: null,
                visibleImg: false,
                inquiryBinance: null,
                isNotFount:false
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
            BAlert,
            BInputGroup,BFormGroup, BInputGroupAppend,BFormInput,
            vSelect,NotAccessed,NotFound
        },
        methods:{
            getOrder(id){
                axiosIns.post('crypto/info/'+id)
                .then(response => {
                    this.transaction = response.data.transaction;
                    this.inquiryBinance = response.data.inquiry_binance;
                    if(this.transaction.photo)
                        this.getFile(this.transaction.photo,'srcFile')
                })
                .catch((err) => {
                    if(err.response.status === 404)
                        this.isNotFount = true
                    else
                        console.log(err.response.status)
                })
            },
            confirmTransaction(id){
                if(this.transaction.type == 'deposit')
                    this.confirmDeposit(id);
                else if(this.transaction.type == 'withdraw')
                    this.confirmWithdraw(id);
            },
            confirmDeposit(id){
                this.$swal({
                    title: 'از تایید واریز اطمینان دارید؟',
                    text: "با توجه به اطلاعات txid، در صورتی که تراکنش را تایید نمایید کیف پول کاربر به مقدار "+this.toFixFloat(this.transaction.amount)+' '+this.transaction.symbol+" شارژ میشود.",
                    icon: 'question',
                    showCancelButton: true,
                    showLoaderOnConfirm: true,
                    buttonsStyling: false,
                    preConfirm: (response) => {
                        return  axiosIns.post('crypto/confirm/'+id)
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
                    html: "مقدار: " + this.toFixFloat(this.transaction.amount)+' '+this.transaction.symbol+'<hr>'+
                            'آدرس ولت: '+'<div dir="ltr" class="text-right">'+this.transaction.destination+'</div><hr>'+
                            '<select id="auto_api" required class="swal2-input">'+
                            '<option value="" selected>واریز به صورت دستی</option>'+
                            '<option value="binance">واریز اتوماتیک از بایننس</option>'+
                            '<option value="kucoin">واریز اتوماتیک از کوکوین</option>'+
                            '<option value="exonyx">واریز اتوماتیک از اونیکس</option>'+
                            '</select>'+
                        'اگر واریز به صورت دستی را انتخاب کنید میبایست به مقدار این تراکنش بصورت دستی برای کاربر واریز را انجام دهید. '
                            ,
                    icon: 'question',
                    showCancelButton: true,
                    showLoaderOnConfirm: true,
                    buttonsStyling: false,
                    preConfirm: () => {
                        var autoApi = document.getElementById("auto_api").value;
                        return  axiosIns.post('crypto/confirm/'+id,{autoApi:autoApi})
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
            rejectCrypto(id){
                var text = '';
                if (this.transaction.type == 'deposit')
                    text = "با رد تراکنش دیگر امکان تایید آن وجود ندارد و لطفا دقت نمایید."
                else
                    text = "با رد کردن تراکنش، مبلغ به کیف پول کاربر برمیگردد و امکان تایید آن وجود ندارد و لطفا دقت نمایید."

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
                        return  axiosIns.post('crypto/reject/'+id,{reason:value})
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
            removeCrypto(id){
                this.$swal({
                    title: 'از حذف کردن اطمینان دارید؟',
                    text: 'بعد از حذف امکان بازیابی وجود ندارد!',
                    icon: 'question',
                    icon: 'question',
                    showCancelButton: true,
                    showLoaderOnConfirm: true,
                    buttonsStyling: false,
                    preConfirm: (response) => {
                        return  axiosIns.post('crypto/remove/'+id)
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
                            this.transaction = null;
                            this.getOrder(id);
                            this.$swal({icon: 'success',title: 'موفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }else{
                            this.$swal({icon: 'error',title: 'ناموفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                        }
                    }
                })
            },
            resolveStatusVariant(status){
                if (status === 'pending') return 'warning'
                if (status === 'success') return 'success'
                if (status === 'suspend') return 'dark'
                if (status === 'unsuccessful') return 'danger'
                if (status === 'reject') return 'danger'
                return 'warning'
            },
            ccformat (value) {
                return value.replace(/\W/gi, '').replace(/(.{4})/g, '$1 ');
            },
            getOptionsGatewayWithdraw(){
                var options = '<option value="manual">واریز به صورت دستی</option>';
                var self = this;
                this.gatewayWithdraw.map(function(value, key) {
                    options = options + '<option value="'+ value.name +'">واریز اتوماتیک '+ self.$i18n.t(value.name)+ ' ('+ value.name +')' +'</option>';
                });
                return options;
            },
            getOptionsAdminHesab(){
                var options = '';
                this.adminHesab.map(function(value, key) {
                    options = options + '<option value="'+ value.id +'">'+ value.name+ ' (موجودی: '+ value.stock.toLocaleString() +')' +'</option>';
                });
                return options;
            }
        },
        created() {
            if(this.accessUserLogin['tr-crypto']['single'] || this.activeUserInfo.role === 'admin')
                this.getOrder(this.$router.currentRoute.params.id)
        }
    }
</script>

<style scoped>
.link-duplicate:not(:last-child):after {
    content: '-';
    padding: 5px;
}
</style>
