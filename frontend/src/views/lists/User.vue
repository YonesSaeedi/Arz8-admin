<template>
    <div>
        <div class="text-nowrap">
            <b-link
                :to="{ name: 'user-single', params: { id: item.user.id } }"
                class="font-weight-bold d-block text-nowrap"
            >
                <img :src="require('@/assets/images/logo/logo-'+ levelAccount(item.user.level_account) +'.png')" width="25">
                {{ item.user.name + ' ' + item.user.family || item.user.name_display }}
            </b-link>
            <small class="text-muted">{{item.user.mobile || item.user.email}}</small>
        </div>
    </div>
</template>

<script>
import {BLink, BTooltip} from "bootstrap-vue";

export default {
    name: "User",
    components: {BLink, BTooltip},
    props:['item']
}
</script>

<style scoped>

</style>
