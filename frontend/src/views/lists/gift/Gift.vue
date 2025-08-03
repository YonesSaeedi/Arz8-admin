<template>
    <div id="gift">
        <b-row>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="GiftIcon"
                    :statistic="statistic && statistic.all_gift? statistic.all_gift :'0'"
                    statistic-title="کد تخفیف های فعال"
                    id="all-gift"
                />
                <b-tooltip target="all-gift" variant="primary">
                    کد تخفیف هایی که اکنون توسط کاربران امکان ثبت را دارند.
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="UsersIcon"
                    color="success"
                    :statistic="statistic && statistic.gift_users_active? statistic.gift_users_active :'0'"
                    statistic-title="کاربران تخفیف دار"
                    id="gift-users-active"
                />
                <b-tooltip target="gift-users-active" variant="success">
                    کاربرانی که اکنون کد تخفیفی فعال دارند.
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="ActivityIcon"
                    color="danger"
                    :statistic="statistic && statistic.gift_users_all? statistic.gift_users_all :'0'"
                    statistic-title="کدهای استفاده شده"
                    id="gift-users-all"
                />
                <b-tooltip target="gift-users-all" variant="danger">
                    تعداد همه کدهایی که توسط کاربران استفاده شده است.
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="UserCheckIcon"
                    color="warning"
                    :statistic="statistic && statistic.gift_users_id? statistic.gift_users_id :'0'"
                    statistic-title="کد تخفیف اختصاصی"
                    id="gift-users-id"
                />
                <b-tooltip target="gift-users-id" variant="warning">
                    کد تخفیف هایی که حداقل یک یا چند کاربر مجاز برای آن تعریف شده باشد.
                </b-tooltip>
            </b-col>
        </b-row>

        <b-tabs>
            <b-tab>
                <template #title>
                    <feather-icon icon="GiftIcon" />
                    <span>لیست کد های تخفیف</span>
                </template>
                <Gift/>
            </b-tab>
            <b-tab>
                <template #title>
                    <feather-icon icon="UsersIcon" />
                    <span>لیست کاربران دارای تخفیف</span>
                </template>
                <GiftUsers/>
            </b-tab>
        </b-tabs>

    </div>
</template>

<script>
    import Gift from './gift-list/GiftList';
    import GiftUsers from './gift-user-list/GiftUsersList';
    import { BTabs, BTab, BCardText , VBTooltip, BTooltip, BRow, BCol} from 'bootstrap-vue'
    import axiosIns from "@/libs/axios";
    import StatisticCardHorizontal from '@core/components/statistics-cards/StatisticCardHorizontal.vue'


    export default {

        components: {
            Gift,GiftUsers,
            BTabs,
            BTab,
            BCardText,
            StatisticCardHorizontal,
            VBTooltip, BTooltip, BRow, BCol
        },
        data() {
            return {
                statistic: []
            }
        },
        methods:{
            fetchStatistic(){
                axiosIns.post('gift/statistic').then(response => {
                    this.statistic = response.data
                })
            }
        },
        created() {
            this.fetchStatistic();
        }
    }
</script>

<style lang="scss">
#gift{
    .nav-tabs .nav-item a{
        font-size: 18px;
    }
    .nav-tabs .nav-item svg{
        height: 20px;
        width: 20px;
    }
}
</style>
