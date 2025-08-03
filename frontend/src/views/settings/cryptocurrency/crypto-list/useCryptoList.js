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
        {key: 'percent', label: 'اعشار', sortable: true},
        {key: 'balanceUsers', label: 'موجودی کل کاربران', sortable: true},
        {key: 'balanceUsersToman', label: 'موجودی کل به تومان', sortable: true},
        {key: 'sort', label: 'عدد مرتب سازی', sortable: true},
        //{key: 'balance', label: 'موجودی', sortable: true},
        {key: 'buyStatus', label: 'وضعیت خرید', sortable: true},
        {key: 'sellStatus', label: 'وضعیت فروش',sortable: true},
        {key: 'withdrawAuto', label: 'برداشت اتوماتیک',sortable: true},
        {key: 'actions', label: '#'},
    ];

    const perPage = ref(25)
    const totalRows = ref(0)
    const currentPage = ref(1)
    const perPageOptions = [10, 25, 50, 100,1000]
    const searchQuery = ref('')
    const sortBy = ref('id')
    const isSortDirDesc = ref(true)
    const hideFilter = ref(null)
    const statusBuyFilter = ref(null)
    const statusSellFilter = ref(null)
    const withdrawFilter = ref(null)
    const depositFilter = ref(null)
    const exchangeFilter = ref(null)
    const networkFilter = ref(null)
    const networkslist = ref([])
    const exchangeAccountFilter = ref(null)
    const exchangeAccount = ref([])



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


    const refetchData = () => {
        refCryptoListTable.value.refresh()
    }

    var timer = null;
    watch([currentPage, perPage, searchQuery, hideFilter, statusBuyFilter,statusSellFilter,withdrawFilter,
        depositFilter, exchangeFilter, networkFilter, exchangeAccountFilter], () => {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
        timer = setTimeout(() => {
            refetchData()
        }, 800);
    });

    const fetchCrypto = (ctx, callback) => {
        axios.post('setting/crypto/list', {
                search: searchQuery.value,
                perPage: perPage.value,
                page: currentPage.value,
                sortBy: sortBy.value,
                sortDesc: isSortDirDesc.value,
                hide: hideFilter.value,
                statusBuy: statusBuyFilter.value,
                statusSell: statusSellFilter.value,
                withdraw: withdrawFilter.value,
                deposit: depositFilter.value,
                exchange: exchangeFilter.value,
                network: networkFilter.value,
                exchangeAccount: exchangeAccountFilter.value,
            })
            .then(response => {
                const {lists, total} = response.data

                callback(lists)
                totalRows.value = total
                networkslist.value = response.data.networks
                exchangeAccount.value = response.data.exchange_account
            })
            .catch(() => {
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
        hideFilter,
        statusBuyFilter, statusSellFilter,
        withdrawFilter,depositFilter,
        exchangeFilter,
        networkFilter,
        networkslist,
        exchangeAccountFilter,
        exchangeAccount,
    }
}
