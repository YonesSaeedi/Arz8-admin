<template>
    <b-card
        v-if="data"
        no-body
        class="card-statistics"
    >
        <b-card-header class="pb-0">
            <b-card-title>آمار و ارقام</b-card-title>
            <b-card-text class="font-small-2 mr-25 mb-0 vazir">
                <feather-icon icon="RefreshCwIcon" class="cursor-pointer" :class="isLoading?'spinner':''" @click="$emit('getData',true)"/>
                آخرین بروزرسانی {{timeAgo}}
            </b-card-text>
        </b-card-header>
        <b-card-body class="statistics-body">
            <b-row>
                <b-col xl="4" sm="6" class="mb-2 mb-xl-0">
                    <b-media no-body>
                        <b-media-aside class="mr-2">
                            <b-avatar size="48" variant="light-primary">
                                <feather-icon size="24" icon="UsersIcon"/>
                            </b-avatar>
                        </b-media-aside>
                        <b-media-body class="my-auto">
                            <h4 class="font-weight-bolder mb-0">
                                {{toThousand(data.users)}}
                            </h4>
                            <b-card-text class="font-small-3 mb-0">
                                کاربران
                            </b-card-text>
                        </b-media-body>
                    </b-media>
                </b-col>

                <b-col xl="4" sm="6" class="mb-2 mb-xl-0">
                    <b-media no-body>
                        <b-media-aside class="mr-2">
                            <b-avatar size="48" variant="light-info">
                                <feather-icon size="24" icon="ListIcon"/>
                            </b-avatar>
                        </b-media-aside>
                        <b-media-body class="my-auto">
                            <h4 class="font-weight-bolder mb-0">
                                {{toThousand(data.orders)}}
                            </h4>
                            <b-card-text class="font-small-3 mb-0">
                                سفارشات
                            </b-card-text>
                        </b-media-body>
                    </b-media>
                </b-col>

                <b-col xl="4" sm="6" class="mb-2 mb-sm-0">
                    <b-media no-body>
                        <b-media-aside class="mr-2">
                            <b-avatar size="48" variant="light-danger">
                                <feather-icon size="24" icon="ActivityIcon"/>
                            </b-avatar>
                        </b-media-aside>
                        <b-media-body class="my-auto">
                            <h4 class="font-weight-bolder mb-0">
                                {{toThousand(data.trades)}}
                            </h4>
                            <b-card-text class="font-small-3 mb-0">
                                معاملات
                            </b-card-text>
                        </b-media-body>
                    </b-media>
                </b-col>

                <b-col xl="4" sm="6" class="mt-2">
                    <b-media no-body>
                        <b-media-aside class="mr-2">
                            <b-avatar size="48" variant="light-success">
                                <feather-icon size="24" icon="DollarSignIcon"/>
                            </b-avatar>
                        </b-media-aside>
                        <b-media-body class="my-auto">
                            <h4 class="font-weight-bolder mb-0">
                                ${{toThousand(data.all_balance_usdt)}}
                            </h4>
                            <b-card-text class="font-small-3 mb-0">
                                موجودی کاربران~
                            </b-card-text>
                        </b-media-body>
                    </b-media>
                </b-col>


                <b-col xl="4" sm="6" class="mt-2">
                    <b-media no-body>
                        <b-media-aside class="mr-2">
                            <b-avatar size="48" variant="light-success">
                                <i class="font-large-1 cf cf-usdt"></i>
                            </b-avatar>
                        </b-media-aside>
                        <b-media-body class="my-auto">
                            <h4 class="font-weight-bolder mb-0">
                                {{amountLocalFloat(data.balance_theter.toString(),4)}}
                            </h4>
                            <b-card-text class="font-small-3 mb-0">
                                موجودی تتر کاربران
                            </b-card-text>
                        </b-media-body>
                    </b-media>
                </b-col>

                <b-col xl="4" sm="6" class="mt-2">
                    <b-media no-body>
                        <b-media-aside class="mr-2">
                            <b-avatar size="48" variant="light-danger">
                                <img :src="baseURL+'images/currency/iran.svg'" width="35px">
                            </b-avatar>
                        </b-media-aside>
                        <b-media-body class="my-auto">
                            <h4 class="font-weight-bolder mb-0">
                                {{toFixFloat(data.balance_toman)}}
                            </h4>
                            <b-card-text class="font-small-3 mb-0">
                                موجودی ریالی کاربران
                            </b-card-text>
                        </b-media-body>
                    </b-media>
                </b-col>
            </b-row>
        </b-card-body>
    </b-card>
</template>

<script>
    import {
        BCard, BCardHeader, BCardTitle, BCardText, BCardBody, BRow, BCol, BMedia, BMediaAside, BAvatar, BMediaBody,
    } from 'bootstrap-vue'
    import jalaliMmoment from "jalali-moment";
    jalaliMmoment.updateLocale('fa', {
        relativeTime: {
            future: "تا %s",
            past: "%s قبل",
            s: 'چند ثانیه',
            ss: '%d ثانیه',
            m: "یک دقیقه",
            mm: "%d دقیقه",
            h: "یک ساعت",
            hh: "%d ساعت",
            d: "یک روز",
            dd: "%d روز",
            M: "یک ماه",
            MM: "%d ماه",
            y: "یک سال",
            yy: "%d سال"
        }
    });
    export default {
        components: {
            BRow,
            BCol,
            BCard,
            BCardHeader,
            BCardTitle,
            BCardText,
            BCardBody,
            BMedia,
            BAvatar,
            BMediaAside,
            BMediaBody,
        },
        data() {
            return {
                timeAgo:null,
                timeInterval:null
            }
        },
        props: {
            data: {
                type: Object ,
                default: () => {},
            },
            isLoading: {
                type: Boolean ,
            },
        },
        methods:{
            timeAgoCompute(){
                this.timeAgo = jalaliMmoment(Date.now()).to(this.data ? this.data.time : Date.now());
            }
        },
        watch:{
            isLoading(){
                this.timeAgoCompute();
            }
        },
        created() {
            this.timeAgoCompute();
            this.timeInterval = setInterval(function(){
                this.timeAgoCompute();
            }.bind(this), 6000);
        },
        beforeDestroy: function () {
            clearInterval(this.timeInterval);
        },
    }
</script>
