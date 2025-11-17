<template>
    <b-tabs v-model="active">
        <b-tab title="لیست کاربران">
            <UserList v-if="active==0"/>
        </b-tab>
        <b-tab title="نمودار">
            <Charts v-if="active==1"/>
        </b-tab>
    </b-tabs>
</template>

<script>
import {
 BTabs,BTab
} from 'bootstrap-vue'

import UserList from './users-list/UsersList'
import Charts from './charts/Charts'
export default {
    data () {
        return {
            active:0,
        }
    },
    components: {
        UserList,
        Charts,
        BTabs,BTab
    }
}
</script>

<style scoped>

</style>
