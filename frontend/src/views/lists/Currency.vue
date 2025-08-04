<template>
    <div>
        <div :id="`item-row-${item.id}`">
            <i class="cf" style="font-size: 35px"
               v-if="item.crypto && isFontExist(normalizeSymbol(item.crypto.symbol))"
               :class="'cf-' + normalizeSymbol(item.crypto.symbol).toLowerCase()"
               :style="{color: colorSymbol(normalizeSymbol(item.crypto.symbol))}">
            </i>
            <img :src="baseURL + 'images/currency/' + iconSymbol(normalizeSymbol(item.crypto.symbol))" width="35px" v-else />

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
    props:['item'],
    methods: {
        normalizeSymbol(symbol) {
            const map = {
                'USDT1': 'USDT',
                'USDT2': 'USDT',
                'USDT3': 'USDT',
                'USDT4': 'USDT'
            };
            return map[symbol] || symbol;
        }
    }
}
</script>

<style scoped>

</style>
