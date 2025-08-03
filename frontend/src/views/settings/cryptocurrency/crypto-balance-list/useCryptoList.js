import {ref, watch, computed} from '@vue/composition-api'
import axios from '../../../../libs/axios'
import {title} from '@core/utils/filter'

// Notification
import {useToast} from 'vue-toastification/composition'
import ToastificationContent from '@core/components/toastification/ToastificationContent.vue'

export default function useUsersList() {
    // Use toast
    const toast = useToast()

    const refCryptoListTable = ref(null)

    // Table Handlers
    const tableColumns = [
        {key: 'logo', label: 'لوگو', sortable: true},
        {key: 'name', label: 'نام', sortable: true},
        {key: 'symbol', label: 'نماد', sortable: true},
        {key: 'balanceUsers', label: 'موجودی کل کاربران', sortable: true},
        {key: 'balanceUsersToman', label: 'موجودی کل به تومان', sortable: true},
        {key: 'balance', label: 'موجودی', sortable: true},
        {key: 'balanceWalletOther', label: 'موجودی کیف پول سایر', sortable: true},
        {key: 'actions', label: '#'},
    ];

    const perPage = ref(25)
    const totalRows = ref(0)
    const currentPage = ref(1)
    const perPageOptions = [10, 25, 50, 100,1000]
    const searchQuery = ref('')
    const sortBy = ref('id')
    const isSortDirDesc = ref(true)
    const tagFilter = ref(null)
    const statusBuySellFilter = ref(null)
    const withdrawFilter = ref(null)
    const depositFilter = ref(null)
    const otherFilter = ref(null)
    const exchangeFilter = ref(null)
    const networkFilter = ref(null)
    const networkslist = ref([])
    const exchangeAccountFilter = ref(null)
    const exchangeAccount = ref([])
    const symbolsList = ref([])
    const balanceTotal = ref(null)



    const dataMeta = computed(() => {
        const localItemsCount = refCryptoListTable.value ? refCryptoListTable.value.localItems.length : 0
        return {
            from: perPage.value * (currentPage.value - 1) + (localItemsCount ? 1 : 0),
            to: perPage.value * (currentPage.value - 1) + localItemsCount,
            of: totalRows.value,
        }
    })


    const listCrypto = computed(() => {
       return fetchCrypto;
    })


    const refetchData = (balance = true) => {
        refCryptoListTable.value.refresh();

        if(balance)
            fetchBalanceTotal();
    }

    var timer = null;
    watch([exchangeFilter,exchangeAccountFilter], () => {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
        timer = setTimeout(() => {
            refetchData()
        }, 800);
    });
    watch([currentPage, perPage, searchQuery, tagFilter, statusBuySellFilter,withdrawFilter,
        depositFilter, otherFilter, networkFilter], () => {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
        timer = setTimeout(() => {
            refetchData(false)
        }, 800);
    });

    const fetchBalanceTotal = (ctx, callback) => {
        balanceTotal.value = null;
        axios.post('setting/crypto/balance-total', {
            exchange: exchangeFilter.value,
            exchangeAccount: exchangeAccountFilter.value,
        })
        .then(response => {
            const {sell,buy} = response.data
            balanceTotal.value = response.data
        })
    }

    const fetchCrypto = (ctx, callback) => {
        const filters = {
            search: searchQuery.value,
            perPage: perPage.value,
            page: currentPage.value,
            sortBy: sortBy.value,
            sortDesc: isSortDirDesc.value,
            tag: tagFilter.value,
            withdraw: withdrawFilter.value,
            deposit: depositFilter.value,
            other: otherFilter.value,
            balanceTotal: true,
            network: networkFilter.value,
            exchange: exchangeFilter.value,
            exchangeAccount: exchangeAccountFilter.value,
        }

        // بررسی و افزودن فیلتر مناسب برای خرید یا فروش
        if (statusBuySellFilter.value === 'BuyTrue') {
            filters.statusBuy = true
        } else if (statusBuySellFilter.value === 'Buyfalse') {
            filters.statusBuy = false
        } else if (statusBuySellFilter.value === 'SellTrue') {
            filters.statusSell = true
        } else if (statusBuySellFilter.value === 'SellFalse') {
            filters.statusSell = false
        }

        axios.post('setting/crypto/balance', filters)
            .then(response => {
                const {lists, total} = response.data

                callback(lists)
                totalRows.value = total
                networkslist.value =  response.data.networks
                exchangeAccount.value = response.data.exchange_account
                symbolsList.value = lists.map(item => item.symbol)
            })
            .catch((e) => {
                toast({
                    component: ToastificationContent,
                    props: {
                        title: 'Error fetching data',
                        icon: 'AlertTriangleIcon',
                        variant: 'danger',
                    },
                })
            })
    }

    // *===============================================---*
    // *--------- UI ---------------------------------------*
    // *===============================================---*



    const resolveStatusVariant = status => {
        if (status === 0) return 'warning'
        if (status === 1) return 'success'
        return 'warning'
    }

    return {
        fetchCrypto,
        listCrypto,
        tableColumns,
        perPage,
        currentPage,
        totalRows,
        dataMeta,
        perPageOptions,
        searchQuery,
        sortBy,
        isSortDirDesc,
        refCryptoListTable,

        resolveStatusVariant,
        refetchData,

        // Extra Filters
        tagFilter,
        statusBuySellFilter,
        withdrawFilter,depositFilter,
        otherFilter,
        networkFilter,
        networkslist,
        exchangeAccountFilter,
        exchangeFilter,
        exchangeAccount,
        symbolsList,
        balanceTotal,fetchBalanceTotal
    }
}
