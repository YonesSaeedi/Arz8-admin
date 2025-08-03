<template>
    <b-row>
        <b-col cols="12">
            <ViaRegisterLine v-if="data" :data="data && data.line_via"/>
            <b-skeleton v-else width="100%" height="300px"></b-skeleton>
        </b-col>
        <b-col cols="6">
            <ViaRegister v-if="data" :data="data && data.pie_via"/>
            <b-skeleton v-else width="100%" height="300px"></b-skeleton>
        </b-col>
        <b-col cols="6">
            <ViaRegisterAmount v-if="data" :data="data && data.pie_via_amount"/>
            <b-skeleton v-else width="100%" height="300px"></b-skeleton>
        </b-col>
        <b-col cols="12">
            <ViaRegisterAmountLine v-if="data" :data="data && data.line_via_amount"/>
            <b-skeleton v-else width="100%" height="300px"></b-skeleton>
        </b-col>
        <b-col cols="12">
            <TypeAmountBar v-if="data" :data="data && data.bar_type"/>
            <b-skeleton v-else width="100%" height="300px"></b-skeleton>
        </b-col>
    </b-row>
</template>

<script>
import {
    BRow, BCol,BSkeleton
} from 'bootstrap-vue'
import ViaRegister from "./ViaRegister";
import ViaRegisterLine from "./ViaRegisterLine";
import ViaRegisterAmount from "./ViaRegisterAmount";
import ViaRegisterAmountLine from "./ViaRegisterAmountLine";
import TypeAmountBar from "./TypeAmountBar";

import axiosIns from "@/libs/axios";
import ToastificationContent from "@core/components/toastification/ToastificationContent";
export default {
    components: {
        ViaRegister,
        ViaRegisterLine,
        ViaRegisterAmount,
        ViaRegisterAmountLine,
        TypeAmountBar,

        BRow,BCol,BSkeleton
    },
    data() {
        return {
            data:null
        }
    },
    methods:{
        getData(){
            axiosIns.post('/reports/orders/chart')
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
