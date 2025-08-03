<template>
    <b-row>
        <b-col cols="12">
            <CountUserBar v-if="data" :data="data && data.bar_user"/>
            <b-skeleton v-else width="100%" height="300px"></b-skeleton>
        </b-col>
        <b-col cols="6">
            <PiePercentCaller v-if="data" :data="data && data.pie_percent_caller"/>
            <b-skeleton v-else width="100%" height="300px"></b-skeleton>
        </b-col>
        <b-col cols="6">
            <PiePercentReferral v-if="data" :data="data && data.pie_percent_referral"/>
            <b-skeleton v-else width="100%" height="300px"></b-skeleton>
        </b-col>
        <b-col cols="12">
            <RegisterAmountLine v-if="data" :data="data && data.line_time"/>
            <b-skeleton v-else width="100%" height="300px"></b-skeleton>
        </b-col>
    </b-row>
</template>

<script>
import {
    BRow, BCol,BSkeleton
} from 'bootstrap-vue'
import PiePercentCaller from "./PiePercentCaller";
import PiePercentReferral from "./PiePercentReferral";
import RegisterAmountLine from "./RegisterAmountLine";
import CountUserBar from "./CountUserBar";

import axiosIns from "@/libs/axios";
import ToastificationContent from "@core/components/toastification/ToastificationContent";
export default {
    components: {
        PiePercentCaller,PiePercentReferral,
        RegisterAmountLine,
        CountUserBar,

        BRow,BCol,BSkeleton
    },
    data() {
        return {
            data:null
        }
    },
    methods:{
        getData(){
            axiosIns.post('/reports/referral/chart')
            .then(response => {
                this.data = response.data;
            }).catch(() => {
                this.$toast({
                    component: ToastificationContent,
                    props: {
                        title: 'Error fetching data',
                        icon: 'AlertTriangleIcon',
                        variant: 'danger',
                    },
                })
            })
        }
    },
    created() {
        this.getData();
    }
}
</script>
