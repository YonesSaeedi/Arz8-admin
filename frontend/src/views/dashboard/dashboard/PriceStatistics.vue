<template>
    <b-row  class="match-height">
        <b-col lg="3" sm="12">
            <b-card body-class="p-1 pb-0">
                <div class="text-center text-success font-large-1">
                    <i class="font-large-4 cf cf-usdt" style="color: #69b7a1"></i>
                </div>
                <div class="text-right">
                    <div v-if="data.theter">
                        USDT: b:{{toFixFloat(parseInt(data.theter.buy))}} | s:{{toFixFloat(parseInt(data.theter.sell))}} <br>
                         ba:{{toFixFloat(parseInt(data.theter.sum_balance))}} |  ban:{{toFixFloat(parseInt(data.theter.sum_balance_net))}}
                    </div>
                    <b-skeleton v-else width="100%"></b-skeleton>
                </div>

                <div class="text-right">
                    <div v-if="data.theter" >
                        A1: b:{{toFixFloat(parseInt(data.perfectmoney.buy))}} | s:{{toFixFloat(parseInt(data.perfectmoney.sell))}} | ba:{{toFixFloat(parseInt(data.perfectmoney.balance))}} <br>
                        B1: b:{{toFixFloat(parseInt(data.psvouchers.buy))}} | s:{{toFixFloat(parseInt(data.psvouchers.sell))}} | ba:{{toFixFloat(parseInt(data.psvouchers.balance))}}<br>
                        C1: b:{{toFixFloat(parseInt(data.utopia.buy))}} | s:{{toFixFloat(parseInt(data.utopia.sell))}} | ba:{{toFixFloat(parseInt(data.utopia.balance))}}<br>
                    </div>
                    <b-skeleton v-else width="100%" height="40px"></b-skeleton>
                </div>
            </b-card>
        </b-col>

        <b-col lg="3" sm="12">
            <b-card body-class="p-1 pb-0">
                <div class="text-center text-success font-large-1 pb-1 mb-1">
                    <img :src="require(`@/assets/images/exchange/binance.png`)" width="250px">
                </div>
                <div v-if="data.theter" >
                    <div class="d-flex justify-content-between" v-for="(item, key) in data.theter.balance.binance">
                        <div>B{{key}}:</div>
                        <div class="text-right">
                            {{toFixFloat(parseInt(item))}}<small class="pr-25">USDT</small>
                        </div>
                    </div>
                </div>
                <b-skeleton v-else width="100%" height="40px"></b-skeleton>
            </b-card>
        </b-col>

        <b-col lg="3" sm="12">
            <b-card body-class="p-1 pb-0">
                <div class="text-center text-success font-large-1 pb-1 mb-1">
                    <img :src="require(`@/assets/images/exchange/kucoin.png`)" width="250px">
                </div>
                <div v-if="data.theter" >
                    <div class="d-flex justify-content-between" v-for="(item, key) in data.theter.balance.kucoin">
                        <div>K{{key}}:</div>
                        <div class="text-right" v-if="key!=='sum'">
                            m:{{toFixFloat(parseInt(item.m))}}<small class="pr-25">USDT</small> | t:{{toFixFloat(parseInt(item.t))}}<small class="pr-25">USDT</small>
                        </div>
                        <div class="text-right" v-else>
                            {{toFixFloat(parseInt(item))}}<small class="pr-25">USDT</small>
                        </div>
                    </div>
                </div>
                <b-skeleton v-else width="100%" height="40px"></b-skeleton>
            </b-card>
        </b-col>

        <b-col lg="3" sm="12">
            <b-card body-class="p-1 pb-0">
                <div class="text-center text-success font-large-1 pb-1 mb-1">
                    <img :src="require(`@/assets/images/exchange/coinex.png`)" width="200px">
                </div>
                <div v-if="data.theter" >
                    <div class="d-flex justify-content-between" v-for="(item, key) in data.theter.balance.coinex">
                        <div>C{{key}}:</div>
                        <div class="text-right">
                            {{toFixFloat(parseInt(item))}}<small class="pr-25">USDT</small>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <div>exonyx:</div>
                        <div class="text-right">
                            {{toFixFloat(parseInt(data.theter.balance.exonyx))}}<small class="pr-25">USDT</small>
                        </div>
                    </div>
                </div>
                <b-skeleton v-else width="100%" height="40px"></b-skeleton>
            </b-card>
        </b-col>

    </b-row>

</template>

<script>
import {
    BRow, BCol,BCard,BSpinner,BSkeleton
} from 'bootstrap-vue'
import axiosIns from "@/libs/axios";

export default {
    components: {
        BRow,BCol,BCard,BSpinner,BSkeleton
    },
    data() {
        return {
            data: {},
            interval:null
        }
    },
    methods:{
        getInfo(){
            axiosIns.post('/dashboard/price')
            .then(response => {
                this.data = response.data
            })
            .catch((error) => { console.log(error); this.errorFetching(); })
        }
    },
    mounted() {
        this.getInfo();
        this.interval = setInterval(()=>{
            this.getInfo();
        },60000)
    },
    beforeDestroy() {
        clearInterval(this.interval)
    }
}
</script>

