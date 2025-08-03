<template>
    <b-tabs v-model="active" >
        <b-tab title="کیف پول ها" >
            <wallets :user="user" v-if="active==0 && (accessUserLogin.users.wallets || activeUserInfo.role === 'admin')"/>
            <NotAccessed v-else/>
        </b-tab>
        <b-tab title="سفارشات" >
           <orders :user="user" v-if="active==1 && (accessUserLogin.users.orders || activeUserInfo.role === 'admin')"/>
            <NotAccessed v-else/>
        </b-tab>
        <b-tab title="معاملات">
            <trades :user="user" v-if="active==2 && (accessUserLogin.users.trades || activeUserInfo.role === 'admin')"/>
            <NotAccessed v-else/>
        </b-tab>
        <b-tab title="تراکنش های داخلی">
            <InternalList :user="user" v-if="active==3 && (accessUserLogin.users['tr-internal'] || activeUserInfo.role === 'admin')"/>
            <NotAccessed v-else/>
        </b-tab>
        <b-tab title="تراکنش های رمز ارز">
            <CryptoList :user="user" v-if="active==4 && (accessUserLogin.users['tr-crypto'] || activeUserInfo.role === 'admin')"/>
            <NotAccessed v-else/>
        </b-tab>
        <b-tab title="کارت های بانکی">
            <CardList :user="user" v-if="active==5 && (accessUserLogin.users.cardbank || activeUserInfo.role === 'admin')"/>
            <NotAccessed v-else/>
        </b-tab>
        <b-tab title="بازاریابی">
            <b-tabs v-if="active==6 && (accessUserLogin.users.referral || activeUserInfo.role === 'admin')">
                <b-tab title="کاریران معرفی شده" active>
                    <userReferral  :user="user"/>
                </b-tab>
                <b-tab title="تراکنش های کاربران">
                    <referral :user="user"/>
                </b-tab>
            </b-tabs>
            <NotAccessed v-else/>
        </b-tab>
        <b-tab title="کد تخفیف">
            <userGift :user="user" v-if="active==7 && (accessUserLogin.users.gift || activeUserInfo.role === 'admin')"/>
            <NotAccessed v-else/>
        </b-tab>
        <b-tab title="تیکت ها">
            <tickets :user="user" v-if="active==8 && (accessUserLogin.users.tickets || activeUserInfo.role === 'admin')"/>
            <NotAccessed v-else/>
        </b-tab>
        <b-tab title="اطلاعیه ها">
            <notification :user="user" v-if="active==9 && (accessUserLogin.users.notification || activeUserInfo.role === 'admin')"/>
            <NotAccessed v-else/>
        </b-tab>
        <b-tab title="تاریخچه ورود">
            <LoginHistory :user="user" v-if="active==10 && (accessUserLogin.users['login-history'] || activeUserInfo.role === 'admin')"/>
            <NotAccessed v-else/>
        </b-tab>
        <b-tab title="گردونه شانس">
            <WheelGift :user="user" v-if="active==11 && (accessUserLogin.users['gift-wheel'] || activeUserInfo.role === 'admin')"/>
            <NotAccessed v-else/>
        </b-tab>
        <b-tab title="تاریخجه تماس ها">
            <CallHistory :user="user" v-if="active==12 && (accessUserLogin.users['call-history'] || activeUserInfo.role === 'admin')"/>
            <NotAccessed v-else/>
        </b-tab>
        <b-tab title="تاریخجه ولت های رمز ارز">
            <WalletsUsers :user="user" v-if="active==13 && (accessUserLogin.users['wallets-users'] || activeUserInfo.role === 'admin')"/>
            <NotAccessed v-else/>
        </b-tab>
        <b-tab title="شناسه های واریز">
            <DepositIdList :user="user" v-if="active==14 && (accessUserLogin.users['deposit-id'] || activeUserInfo.role === 'admin')"/>
            <NotAccessed v-else/>
        </b-tab>
        <b-tab title="کارت هدیه">
            <GiftCard :user="user" v-if="active==15 && (accessUserLogin.users['gift-card'] || activeUserInfo.role === 'admin')"/>
            <NotAccessed v-else/>
        </b-tab>
    </b-tabs>
</template>

<script>
    import { BTabs, BTab } from 'bootstrap-vue'
    import orders from '../../../orders/orders-list/OrdersList'
    import trades from '../../../trades/trades-list/TradesList'
    import InternalList from '../../../transaction-internal/internal-list/InternalList'
    import CryptoList from '../../../transaction-crypto/crypto-list/CryptoList'
    import CardList from '../../../card-bank/CardList'
    import referral from '../../../referral/referral-transaction-list/ReferralTransactionList'
    import userReferral from '../../../referral/referral-user-list/ReferralUsersList'
    import userGift from '../../../gift/gift-user-list/GiftUsersList'
    import Wallets from "./wallets/WalletsList";
    import tickets from '../../../tickets/tickets-list/TicketsList'
    import notification from '../../../../settings/notifications/notification-list/NotificationList'
    import LoginHistory from '../../../../lists/login-history/LoginHistoryList'
    import WheelGift from '../../../../lists/gift/wheel/WheelList.vue'
    import CallHistory from '../../../../lists/call-history/CallHistoryList.vue'
    import WalletsUsers from '../../../../lists/users-wallets/WalletsUsersList.vue'
    import DepositIdList from '../../../../lists/deposit-id/DepositIdList.vue'
    import GiftCard from '../../../gift/gift-card/GiftList.vue'
    import NotAccessed from "@/views/vuexy/pages/miscellaneous/NotAccessed";

    export default {
        props: ['user'],
        components: {
            BTabs,
            BTab,
            Wallets,
            orders,
            trades,
            InternalList,
            CryptoList,
            CardList,
            referral,
            userReferral,
            userGift,
            tickets,
            notification,
            LoginHistory,
            WheelGift,
            CallHistory,
            WalletsUsers,
            DepositIdList,
            GiftCard,
            NotAccessed
        },
        data () {
            return {
                active:0
            }
        },
    }
</script>

<style scoped>

</style>
