<template>
    <b-card body-class="p-1">
        <b-tabs v-model="tabActive">
            <b-tab title="سطح صفر">
                <level0 :user="user" @getUser="getUser()" v-if="tabActive==0"/>
            </b-tab>
            <b-tab title="سطح یک">
                <level1 :user="user" @getUser="getUser()" v-if="tabActive==1"/>
            </b-tab>
            <b-tab title="سطح دو">
                <level2 :user="user" @getUser="getUser()" v-if="tabActive==2"/>
            </b-tab>
            <b-tab title="سطح سه">
                <level3 :user="user" @getUser="getUser()" v-if="tabActive==3"/>
            </b-tab>
            <b-tab title="سطح چهار">
                <level4 :user="user" @getUser="getUser()" v-if="tabActive==4"/>
            </b-tab>
            <b-tab title="سطح پنج و شش">
                <level5 :user="user" @getUser="getUser()" v-if="tabActive==5"/>
            </b-tab>
            <b-tab title="تنظیمات">
                <settings :user="user" :data="data" @getUser="getUser()" v-if="tabActive==6"/>
            </b-tab>
            <b-tab title="سطح کاربری">
                <LevelAccount :user="user" :data="data" v-if="tabActive==7"/>
            </b-tab>
        </b-tabs>
    </b-card>
</template>

<script>
    import {
        BCard, BCardHeader, BBadge, BCollapse, BLink, BCardBody, BRow, BCol, BInputGroup, BInputGroupAppend, BFormGroup, BFormInput, BTable, BButton,
        BMedia, BAvatar,BTab,BTabs
    } from 'bootstrap-vue';
    import Table from "@/views/vuexy/table/bs-table/Table";
    import BCardActions from "@core/components/b-card-actions/BCardActions";
    import Level0 from "./Level0";
    import Level1 from "./Level1";
    import Level2 from "./Level2";
    import Level3 from "./Level3";
    import Level4 from "./Level4";
    import Level5 from "./Level5";
    import settings from "./settings";
    import LevelAccount from "./LevelAccount";


    export default {
        data() {
            return {
                tabActive: this.user.level,
            }
        },
        props:['user','data'],
        components: {
            Level0,
            Level1,
            Level2,
            Level3,
            Level4,
            Level5,
            settings,
            LevelAccount,

            Table,
            BTable,
            BLink,
            BCard,
            BBadge,
            BRow,
            BCol,
            BCardActions,
            BCardHeader,
            BCardBody,
            BCollapse,
            BButton,
            BInputGroup,BFormGroup, BInputGroupAppend,BFormInput,
            BMedia, BAvatar,BTab,BTabs
        },
        methods:{
            getUser(){
                this.$emit('getUser')
            }
        },
    }
</script>

<style scoped>

</style>
