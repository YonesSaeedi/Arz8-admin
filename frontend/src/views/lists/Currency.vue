<template>
    <div>
        <div :id="`item-row-${item.id}`">
            <i class="cf" style="font-size: 35px"
               v-if="item.crypto && isFontExist(item.crypto.symbol)"
               :class="'cf-'+item.crypto.symbol.toLowerCase()"
               :style="{color:colorSymbol(item.crypto.symbol)}">

            </i>
            <img :src="baseURL+'images/currency/' + iconSymbol(  item.crypto.symbol)" width="35px" v-else />
        </div>
        <b-tooltip :target="`item-row-${item.id}`" placement="top" variant="primary">
            {{item.crypto && isCryptoCurrency(item.crypto.symbol) ? localeNameSymbol(item.crypto.symbol)[localeHas] : $t(item.crypto.name)}}
        </b-tooltip>
    </div>
</template>

<script>
import {BTooltip} from "bootstrap-vue";

export default {
    name: "Currency",
    components: {BTooltip},
    props:['item']
}
</script>

<style scoped>

</style>
