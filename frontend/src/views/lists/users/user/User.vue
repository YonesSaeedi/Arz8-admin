<template>
<div>
    <div id="user-single" v-if="user">
        <b-tabs v-model="active">
            <b-tab title="اطلاعات کاربری" >
                <HeaderInfo :user="user" @getUser="getUser()" :data="data"/>
                <Edit :user="user" @getUser="getUser()" :data="data" v-if="active==0"/>
                <Lists :user="user" @getUser="getUser()" :data="data" v-if="active==0"/>
            </b-tab>
            <b-tab title="پرتفوی" >
                <Portfolio :idUser="idUser" v-if="idUser && active==1"/>
            </b-tab>
        </b-tabs>
    </div>
    <div v-if="!accessUserLogin['users']['single'] && activeUserInfo.role !== 'admin'">
        <NotAccessed/>
    </div>
</div>

</template>

<script>
    import axiosIns from "@/libs/axios";
    import ToastificationContent from "@core/components/toastification/ToastificationContent";

    import BCardActions from '@core/components/b-card-actions/BCardActions.vue'
    import {
        BCard, BCardHeader, BBadge, BCollapse, BLink, BCardBody, BRow, BCol, BInputGroup, BInputGroupAppend, BFormGroup, BFormInput, BTable, BButton,
        BMedia, BAvatar,BTabs, BTab
    } from 'bootstrap-vue'

    import Table from "@/views/vuexy/table/bs-table/Table";

    import HeaderInfo from "./info/HeaderInfo";
    import Edit from "./info/edit/Edit";
    import Lists from "./info/Lists";
    import Portfolio from "./portfolio/Portfolio";
    import NotAccessed from "@/views/vuexy/pages/miscellaneous/NotAccessed";

    export default {
        data () {
            return {
                idUser: null,
                user: null,
                data: null,
                active:0,
            }
        },
        methods:{
            getUser(){
                axiosIns.post('users/info/' + this.idUser)
                    .then(response => {
                        this.user = response.data.user
                        this.data = response.data.data
                        document.title = (this.user.email?this.user.email:this.user.mobile);
                    })
                    .catch(() => {
                        this.$toast({
                            component: ToastificationContent,
                            props: {
                                title: 'Error fetching data',
                                icon: 'AlertTriangleIcon',
                                variant: 'danger',
                            },
                        })
                    })
            },

        },
        created() {
            if(this.accessUserLogin['users']['single'] || this.activeUserInfo.role === 'admin') {
                this.idUser = this.$router.currentRoute.params.id;
                this.getUser();
            }
        },
        components: {
            HeaderInfo,
            Edit,
            Lists,
            Portfolio,

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
            BButton,BTabs, BTab,
            BInputGroup,BFormGroup, BInputGroupAppend,BFormInput,
            BMedia, BAvatar,NotAccessed
        },
    }
</script>

<style scoped>

</style>
