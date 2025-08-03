<template>
    <b-card
        v-if="tableData"
        no-body
        class="card-company-table"
    >
        <b-table
            :items="reports"
            responsive
            striped
            :fields="fields"
            class="mb-0"
        >
            <!-- title -->
            <template #cell(title)="data">
                <div class="font-weight-bolder d-block text-nowrap">
                    <div class="d-flex align-items-center">

                            <feather-icon
                                size="18"
                                :class="'mr-1 text-'+getTitle(data.item.title)[1]"
                                :icon="getTitle(data.item.title)[2]"
                            />

                        <span>{{getTitle(data.item.title)[0]}}</span>
                    </div>
                </div>
            </template>

            <!-- today -->
            <template #cell(today)="data">
                <div class="text-nowrap">
                    <span>{{ data.item.today?data.item.today.toLocaleString('en-US'):0 }}</span>
                </div>
            </template>

            <!-- yesterday -->
            <template #cell(yesterday)="data">
                <div class="text-nowrap">
                    <span>{{ data.item.yesterday.toLocaleString('en-US') }}</span>
                </div>
            </template>

            <!-- week -->
            <template #cell(week)="data">
                <div class="text-nowrap">
                    <span>{{ data.item.week.toLocaleString('en-US') }}</span>
                </div>
            </template>

            <!-- month -->
            <template #cell(month)="data">
                <div class="text-nowrap">
                    <span>{{ data.item.month.toLocaleString('en-US') }}</span>
                </div>
            </template>

            <!-- year -->
            <template #cell(year)="data">
                <div class="text-nowrap">
                    <span>{{ data.item.year.toLocaleString('en-US') }}</span>
                </div>
            </template>
        </b-table>
    </b-card>
</template>

<script>
import {
    BCard, BTable, BAvatar, BImg,
} from 'bootstrap-vue'

export default {
    components: {
        BCard,
        BTable,
        BAvatar,
        BImg,
    },
    props: {
        tableData: {
            type: Object,
            default: () => {},
        },
    },
    computed:{
      reports(){
          var arr = [];
          Object.keys(this.tableData.today).forEach((ii,kk)=>{
              var obj = {};
              Object.keys(this.tableData).forEach((i,k)=>{
                  //console.log(i,k,ii,kk,this.tableData[i][ii]);
                  obj[i]=this.tableData[i][ii];
              })
              obj['title'] = ii
              arr.push(obj)
          })
          //console.log(arr);
          return arr;
      }
    },

    data() {
        return {
            fields: [
                {key: 'title', label: '#'},
                {key: 'today', label: 'امروز'},
                {key: 'yesterday', label: 'دیروز'},
                {key: 'week', label: 'هفته جاری'},
                {key: 'month', label: 'ماه جاری'},
                {key: 'year', label: 'سال جاری'},
            ],
        }
    },
    methods:{
        getTitle(title){
            switch (title){
                case 'AmountTrades': return ['ارزش معاملات به دلار','danger','ActivityIcon'];
                case 'MaxAmount': return ['مبلغ بیشترین سفارش','info','ActivityIcon'];
                case 'UniqueUsers': return ['کاربران سفارشات','dark','UsersIcon'];
                case 'CountTrades': return ['تعداد معاملات','primary','ActivityIcon'];
                case 'CountOrders': return ['تعداد سفارشات','primary','MonitorIcon'];
                case 'AmountOrders': return ['مبلغ سفارشات','info','DollarSignIcon'];
                case 'CountOrdersSell': return ['تعداد سفارشات فروش','danger','ChevronsUpIcon'];
                case 'AmountOrdersSell': return ['مبلغ سفارشات فروش','danger','DollarSignIcon'];
                case 'CountOrdersBuy': return ['تعداد سفارشات خرید','success','ChevronsDownIcon'];
                case 'AmountOrdersBuy': return ['مبلغ سفارشات خرید','success','DollarSignIcon'];
                case 'CountUser': return ['کاربر جدید','dark','UserPlusIcon'];
                case 'AmountMaxSell': return ['مبلغ بیشترین فروش','success','ArrowUpIcon'];
                case 'AmountMaxBuy': return ['مبلغ بیشترین خرید','danger','ArrowDownIcon'];
                case 'CountOrdersCrypto': return ['تعداد سفارشات رمزارزها','primary','CommandIcon'];
                case 'AmountOrdersCrypto': return ['مبلغ سفارشات رمزارزها','success','CoffeeIcon'];
                case 'CountOrdersPerfoctmoney': return ['تعداد سفارشات پرفکت مانی','danger','DiscIcon'];
                case 'AmountOrdersPerfoctmoney': return ['مبلغ سفارشات پرفکت مانی','danger','DollarSignIcon'];
                case 'CountOrdersPMvoucher': return ['تعداد سفارشات ووچر پرفکت','danger','DiscIcon'];
                case 'AmountOrdersPMvoucher': return ['مبلغ سفارشات ووچر پرفکت','danger','DollarSignIcon'];
                case 'CountOrdersPSVouchers': return ['تعداد سفارشات ووچرز','primary','DiscIcon'];
                case 'AmountOrdersPSVouchers': return ['مبلغ سفارشات ووچرز','primary','DollarSignIcon'];
                case 'CountOrdersDigital': return ['تعداد سفارشات پول های دیجیتالی','info','DiscIcon'];
                case 'AmountOrdersDigital': return ['مبلغ سفارشات پول های دیجیتالی','info','DollarSignIcon'];
                case 'CountWheel': return ['تعداد چرخش گردونه','dark','GiftIcon'];
                default: return title
            }

        }
    }
}
</script>

<style lang="scss" scoped>
@import '~@core/scss/base/bootstrap-extended/include';
@import '~@core/scss/base/components/variables-dark';

.card-company-table ::v-deep td .b-avatar.badge-light-company {
    .dark-layout & {
        background: $theme-dark-body-bg !important;
    }
}
</style>
