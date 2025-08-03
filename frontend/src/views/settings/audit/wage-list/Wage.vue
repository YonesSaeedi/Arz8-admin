<template>
    <div>
        <b-modal
            v-model="modalStatus"
            id="card-modal" ref="card-modal"
            title="جزئیات کارمزد"
            cancel-title="بستن"
            size="lg"
            cancel-variant="outline-secondary"
        >

            <b-card-text>
                <div class="text-center my-2" v-if="!crypto">
                    <b-spinner style="width: 3rem; height: 3rem;"/>
                </div>

                <div v-else>
                    <div class="text-center">
                        <div class="mb-2">
                            <i class="cf" style="font-size: 100px" v-if="isFontExist(crypto.symbol)" :class="'cf-'+crypto.symbol.toLowerCase()" :style="{color:colorSymbol(crypto.symbol)}"></i>
                            <img :src="baseURL+'images/currency/' + (crypto.icon )" width="100px" v-else />

                            <h4 class="mt-1">کارمزد: <span class="text-primary">{{toFixFloat(wage[0])}}</span> {{crypto.symbol}}
                            </h4>
                            <p>معادل: USDT {{toFixFloat(wage[1])}} و {{toFixFloat(wage[2])}} تومان</p>
                        </div>
                    </div>

                    <p class="mt-3 mb-1">تاریخچه تبدیل کارمزدها به تتر:</p>
                    <b-table
                        small striped hover :fields="fields" :items="trades"
                        responsive
                        empty-text="داده ای برای نمایش وجود ندارد"
                        primary-key="id"
                        class="position-relative"
                    >
                        <template #cell(id)="data">
                            {{ data.item.id }}
                        </template>
                        <template #cell(date)="data">
                            <div class="text-nowrap">
                                {{ data.item.date }}
                            </div>
                        </template>
                        <template #cell(amount_coin)="data">
                            <div class="text-nowrap">
                                {{ toFixFloat(data.item.amount_coin) }} {{crypto.symbol}}
                            </div>
                        </template>
                        <template #cell(amount_usdt)="data">
                            <div class="text-nowrap">
                                {{ toFixFloat(data.item.amount_usdt) }} USDT
                            </div>
                        </template>
                        <template #cell(data)="data">
                            <div class="text-nowrap">
                                <b-link @click="dataTrade=data.item.data,modalTrade=true" class="font-weight-bold d-block text-nowrap text-center">
                                    <feather-icon icon="EditIcon" size="20"/>
                                </b-link>
                            </div>
                        </template>

                    </b-table>
                </div>
            </b-card-text>
            <template #modal-footer>
                <div class="w-100">
                    <b-button variant="outline-secondary" class="float-right mr-1" @click="modalStatus=false">
                        بستن
                    </b-button>
                </div>
            </template>
        </b-modal>

        <b-modal
            v-model="modalTrade"
            id="withdraw-modal" ref="withdraw-modal"
            title="جزئیات ترید"
            cancel-title="بستن"
            cancel-variant="outline-secondary"
        >
            <b-card-text>
                <div dir="ltr" class="text-right">
                    <pre v-html="JSON.stringify(JSON.parse(dataTrade), null, 4)"></pre>
                </div>
            </b-card-text>
            <template #modal-footer>
                <div class="w-100">
                    <b-button variant="outline-secondary" class="float-right mr-1" @click="modalTrade=false">
                        بستن
                    </b-button>
                </div>
            </template>
        </b-modal>
    </div>
</template>

<script>
    import {
        BModal, BButton, VBModal, BTable, BCardText,BSpinner,

    } from 'bootstrap-vue';

    import Ripple from "vue-ripple-directive";
    import BCardCode from "@core/components/b-card-code";
    import axiosIns from "@/libs/axios";
    import { ValidationProvider, ValidationObserver } from 'vee-validate'
    import ToastificationContent from "@core/components/toastification/ToastificationContent";

    export default {
        components: {
            ValidationProvider, ValidationObserver,
            BCardCode,
            BButton,
            BModal,
            BCardText,
            BTable,BSpinner,
            ToastificationContent
        },
        directives: {
            'b-modal': VBModal,
            Ripple,
        },
        props:['id','modalShow'],
        data(){
            return {
                modalStatus: false,
                wage: null,
                crypto: null,
                trades: null,
                isLoadingTrade: false,
                fields:[
                    {key: 'id', label: 'شناسه', sortable: false},
                    {key: 'date', label: 'تاریخ ثبت', sortable: false},
                    {key: 'amount_coin', label: 'مقدار ارز', sortable: false},
                    {key: 'amount_usdt', label: 'مقدار تتر', sortable: false},
                    {key: 'data', label: 'جزئیات ترید', sortable: false}
                ],
                modalTrade:false,
                dataTrade:null,
            }
        },
        methods: {
            getWage() {
                axiosIns.post('audit/wage/' + this.id).then(response => {
                    this.crypto = response.data.crypto;
                    this.trades = response.data.trades;
                    this.wage = response.data.wage;
                })
                    .catch(() => {
                        this.errorFetching();
                    })
            },
        },
        watch:{
            modalShow(val){
               if(val){
                   this.getWage()
                   this.modalStatus = true;
               }else {
                   this.modalStatus = false;
               }
            },
            modalStatus(val){
                if(!val){
                    setTimeout(() => {
                        this.crypto = null;
                    }, 500)
                    this.$emit('modalUpdate', false)
                }
            },
        }
    }
</script>

<style lang="scss">
</style>
