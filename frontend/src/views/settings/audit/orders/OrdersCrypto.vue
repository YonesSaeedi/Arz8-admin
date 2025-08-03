<template>
    <div id="audit">
        <b-tabs v-model="tabActive">
            <b-tab>
                <template #title>
                    <feather-icon icon="DivideIcon" />
                    <span>سود و زیان</span>
                </template>
                <DailyList :isCrypto="isCrypto" @setDate="setDate"/>
            </b-tab>
            <b-tab>
                <template #title>
                    <feather-icon icon="FileTextIcon" />
                    <span>حسابرسی روزانه</span>
                </template>
                <OrdersList :isCrypto="isCrypto" :date="date"/>
            </b-tab>
            <b-tab>
                <template #title>
                    <feather-icon icon="FilePlusIcon" />
                    <span>هزنیه ها</span>
                </template>
                <CustList :date="date"/>
            </b-tab>
        </b-tabs>
    </div>
</template>

<script>
    import OrdersList from './orders-list/OrdersList';
    import DailyList from './daily-list/DailyList';
    import CustList from './cust-list/CustList';
    import { BTabs, BTab, BCardText , VBTooltip, BTooltip, BRow, BCol} from 'bootstrap-vue'


    export default {

        components: {
            OrdersList,
            DailyList,
            CustList,
            BTabs,
            BTab,
            BCardText,
            VBTooltip, BTooltip, BRow, BCol
        },
        data() {
            return {
                date:'',
                tabActive:0,
                isCrypto:true
            }
        },
        methods:{
            setDate(date){
                this.date = date;
                this.tabActive = 1;
            }
        },
        created() {

        }
    }
</script>

<style lang="scss">
#audit{
    @media (min-width: 768px) {
        .nav-tabs .nav-item a{
            font-size: 18px;
        }
        .nav-tabs .nav-item svg{
            height: 20px;
            width: 20px;
        }
    }
}
</style>
