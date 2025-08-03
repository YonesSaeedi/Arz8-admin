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
        {key: 'balance', label: 'موجودی ظرف ارز', sortable: true},
        {key: 'equivalent', label: 'معادل تومانی', sortable: true},
        {key: 'balanceToman', label: 'موجودی ظرف تومان', sortable: true},
        {key: 'priceUsdt', label: 'قیمت دلاری هر واحد', sortable: true},
        {key: 'actions', label: 'عملیات', sortable: false},
    ];

    const perPage = ref(25)
    const totalRows = ref(0)
    const currentPage = ref(1)
    const perPageOptions = [10, 25, 50, 100,1000]
    const searchQuery = ref('')
    const sortBy = ref('equivalent')
    const isSortDirDesc = ref(true)
    const statusBuyFilter = ref(null)
    const statusSellFilter = ref(null)
    const withdrawFilter = ref(null)
    const depositFilter = ref(null)
    const exchangeFilter = ref(null)
    const isLoading = ref(true)
    const calculationTable = ref(null)
    const feeUsdt = ref(null)




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
        isLoading.value = true
        calculationTable.value = null
        refCryptoListTable.value.refresh()
    }

    var timer = null;
    watch([currentPage, perPage, searchQuery, statusBuyFilter,statusSellFilter,withdrawFilter,
        depositFilter,exchangeFilter], () => {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
        timer = setTimeout(() => {
            refetchData()
        }, 800);
    });

    const fetchCrypto = (ctx, callback) => {
        axios.post('setting/crypto/little', {
                search: searchQuery.value,
                perPage: perPage.value,
                page: currentPage.value,
                sortBy: sortBy.value,
                sortDesc: isSortDirDesc.value,
                statusBuy: statusBuyFilter.value,
                statusSell: statusSellFilter.value,
                withdraw: withdrawFilter.value,
                deposit: depositFilter.value,
                exchange: exchangeFilter.value,
            })
            .then(response => {
                const {lists, total,sum,fee} = response.data

                callback(lists)
                totalRows.value = total;
                isLoading.value = false;
                calculationTable.value = sum;
                feeUsdt.value = fee;
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
        statusBuyFilter, statusSellFilter,
        withdrawFilter,depositFilter,
        exchangeFilter,

        isLoading,
        calculationTable,
        feeUsdt
    }
}
