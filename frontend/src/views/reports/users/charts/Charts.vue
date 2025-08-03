<template>
    <b-row>
        <b-col cols="6">
            <ViaRegister v-if="data" :data="data && data.chart_user_via"/>
            <b-skeleton v-else width="100%" height="300px"></b-skeleton>
        </b-col>
        <b-col cols="6">
            <ViaRegisterLevel v-if="data" :data="data && data.pie_user_level"/>
            <b-skeleton v-else width="100%" height="300px"></b-skeleton>
        </b-col>
        <b-col cols="12">
            <ViaRegisterLine v-if="data" :data="data && data.chart_user_via_line"/>
            <b-skeleton v-else width="100%" height="300px"></b-skeleton>
        </b-col>
        <b-col cols="6">
            <TwoFaStatus v-if="data" :data="data && data.pie_twofa"/>
            <b-skeleton v-else width="100%" height="300px"></b-skeleton>
        </b-col>
        <b-col cols="6">
            <CityNationalCode v-if="data" :data="data && data.pie_user_city"/>
            <b-skeleton v-else width="100%" height="300px"></b-skeleton>
        </b-col>
        <b-col cols="12">
            <ProvinceNationalCode v-if="data" :data="data && data.pie_user_province"/>
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
import ViaRegisterLevel from "./ViaRegisterLevel";
import TwoFaStatus from "./TwoFaStatus";
import ProvinceNationalCode from "./ProvinceNationalCode";
import CityNationalCode from "./CityNationalCode";
import axiosIns from "@/libs/axios";
import ToastificationContent from "@core/components/toastification/ToastificationContent";
export default {
    components: {
        ViaRegister,
        ViaRegisterLine,
        ViaRegisterLevel,
        TwoFaStatus,
        CityNationalCode,
        ProvinceNationalCode,

        BRow,BCol,BSkeleton
    },
    data() {
        return {
            data:null
        }
    },
    methods:{
        getData(){
            axiosIns.post('/reports/users/chart')
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
