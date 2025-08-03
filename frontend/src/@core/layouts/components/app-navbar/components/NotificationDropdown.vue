<template>
    <b-nav-item-dropdown
        class="dropdown-notification mr-25"
        menu-class="dropdown-menu-media"
        right
    >
        <template #button-content>
            <feather-icon
                :badge="countSum"
                badge-classes="bg-danger"
                class="text-body"
                icon="BellIcon"
                size="21"
            />
        </template>

        <!-- Header -->
        <li class="dropdown-menu-header">
            <div class="dropdown-header d-flex">
                <h4 class="notification-title vazir mb-0 mr-auto">
                    اعلانات
                </h4>
                <b-badge
                    pill
                    variant="light-primary"
                >
                    {{countSum}} New
                </b-badge>
            </div>
        </li>

        <!-- Notifications -->
        <vue-perfect-scrollbar v-if="cardbank"
            v-once
            :settings="perfectScrollbarSettings"
            class="scrollable-container media-list scroll-area"
            tagname="li"
        >
            <!-- tickets -->
            <b-link :to="{ name: 'action-tickets' }" v-if="tickets && tickets.count > 0">
                <b-media>
                    <template #aside>
                        <b-avatar size="32" variant="light-info">
                            <feather-icon icon="MessageSquareIcon"/>
                        </b-avatar>
                    </template>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="media-heading">
                                <span class="font-weight-bolder">
                                    <b-badge pill variant="light-info">
                                    {{tickets.count}}
                                    </b-badge>
                                    تیکت در انتظار پاسخ
                                </span>
                            </div>
                            <small class="notification-text">عنوان: {{ tickets.msg }}</small>
                        </div>
                        <div class="notification-text">{{tickets.time}}</div>
                    </div>
                </b-media>
            </b-link>

            <!-- cardbank -->
            <b-link :to="{ name: 'action-cardbank' }" v-if="cardbank && cardbank.count > 0">
                <b-media>
                    <template #aside>
                        <b-avatar size="32" variant="light-danger">
                            <feather-icon icon="CreditCardIcon"/>
                        </b-avatar>
                    </template>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="media-heading">
                                <span class="font-weight-bolder">
                                    <b-badge pill variant="light-danger">
                                    {{cardbank.count}}
                                    </b-badge>
                                    کارت بانکی در انتظار تایید
                                </span>
                            </div>
                            <small class="notification-text">{{ cardbank.msg }}</small>
                        </div>
                        <div class="notification-text">{{cardbank.time}}</div>
                    </div>
                </b-media>
            </b-link>

            <!-- transactionCrypto -->
            <b-link :to="{ name: 'action-transaction-crypto' }" v-if="transactionCrypto && transactionCrypto.count > 0">
                <b-media>
                    <template #aside>
                        <b-avatar size="32" variant="light-warning">
                            <feather-icon icon="ActivityIcon"/>
                        </b-avatar>
                    </template>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="media-heading">
                                <span class="font-weight-bolder">
                                    <b-badge pill variant="light-warning">{{transactionCrypto.count}}</b-badge>
                                    تراکنش رمز ارز در انتظار تایید
                                </span>
                            </div>
                            <small class="notification-text">{{ transactionCrypto.msg }}</small>
                        </div>
                        <div class="notification-text">{{transactionCrypto.time}}</div>
                    </div>
                </b-media>
            </b-link>

            <!-- transactionCrypto -->
            <b-link :to="{ name: 'action-transaction-internal' }" v-if="transactionInternal && transactionInternal.count > 0">
                <b-media>
                    <template #aside>
                        <b-avatar size="32" variant="light-success">
                            <feather-icon icon="UmbrellaIcon"/>
                        </b-avatar>
                    </template>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="media-heading">
                                <span class="font-weight-bolder">
                                    <b-badge pill variant="light-success">{{transactionInternal.count}}</b-badge>
                                    تراکنش داخلی در انتظار تایید
                                </span>
                            </div>
                            <small class="notification-text">{{ transactionInternal.msg }}</small>
                        </div>
                        <div class="notification-text">{{transactionInternal.time}}</div>
                    </div>
                </b-media>
            </b-link>

            <!-- Users -->
            <b-link :to="{ name: 'action-users' }" v-if="users && users.count > 0">
                <b-media>
                    <template #aside>
                        <b-avatar
                            size="32"
                            :src="users.avatar"
                            :text="users.msg"
                            variant="light-primary"
                        />
                    </template>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="media-heading">
                                <span class="font-weight-bolder">
                                    <b-badge pill variant="light-primary">{{users.count}}</b-badge>
                                  کاربر در انتظار تایید مدارک
                                </span>
                            </p>
                            <small class="notification-text">{{ users.msg }}</small>
                        </div>
                        <div class="notification-text">{{users.time}}</div>
                    </div>
                </b-media>
            </b-link>


        </vue-perfect-scrollbar>

        <!-- Cart Footer
        <li class="dropdown-menu-footer">
            <b-button
                v-ripple.400="'rgba(255, 255, 255, 0.15)'"
                variant="primary"
                block
            >Read all notifications
            </b-button>
        </li>
        -->
    </b-nav-item-dropdown>
</template>

<script>
    import {
        BNavItemDropdown, BBadge, BMedia, BLink, BAvatar, BButton, BFormCheckbox,
    } from 'bootstrap-vue'
    import VuePerfectScrollbar from 'vue-perfect-scrollbar'
    import Ripple from 'vue-ripple-directive'

    export default {
        data() {
            return {
                countSum:0,
                tickets: null,
                cardbank: null,
                transactionCrypto:null,
                transactionInternal:null,
                users:null,
            }
        },
        components: {
            BNavItemDropdown,
            BBadge,
            BMedia,
            BLink,
            BAvatar,
            VuePerfectScrollbar,
            BButton,
            BFormCheckbox,
        },
        directives: {
            Ripple,
        },
        setup() {
            /* eslint-disable global-require */
            const notifications = [
                {
                    title: 'Congratulation Sam 🎉',
                    avatar: require('@/assets/images/avatars/6-small.png'),
                    subtitle: 'Won the monthly best seller badge',
                    type: 'light-success',
                },
                {
                    title: 'New message received',
                    avatar: require('@/assets/images/avatars/9-small.png'),
                    subtitle: 'You have 10 unread messages',
                    type: 'light-info',
                },
                {
                    title: 'Revised Order 👋',
                    avatar: 'MD',
                    subtitle: 'MD Inc. order updated',
                    type: 'light-danger',
                },
            ]
            /* eslint-disable global-require */

            const systemNotifications = [
                {
                    title: 'Server down',
                    subtitle: 'USA Server is down due to hight CPU usage',
                    type: 'light-danger',
                    icon: 'XIcon',
                },
                {
                    title: 'Sales report generated',
                    subtitle: 'Last month sales report generated',
                    type: 'light-success',
                    icon: 'CheckIcon',
                },
                {
                    title: 'High memory usage',
                    subtitle: 'BLR Server using high memory',
                    type: 'light-warning',
                    icon: 'AlertTriangleIcon',
                },
            ]

            const perfectScrollbarSettings = {
                maxScrollbarLength: 60,
                wheelPropagation: false,
            }

            return {
                notifications,
                systemNotifications,
                perfectScrollbarSettings,
            }
        },
        watch:{
            '$store.state.verticalMenu.notification'(value){
                this.countSum = value.count_sum;
                this.cardbank = value.cardbank;
                this.tickets = value.tickets;
                this.transactionCrypto = value.transaction_crypto;
                this.transactionInternal = value.transaction_internal;
                this.users = value.users;
            }
        },
        created() {

        }
    }
</script>

<style>

</style>
