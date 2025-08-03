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
        {key: 'id', label: 'شناسه', sortable: true},
        {key: 'coin', label: 'ارز'},
        {key: 'amount', label: 'مقدار', sortable: true},
        {key: 'status', label: 'وضعیت',sortable: true},
        {key: 'side', label: 'نوع',sortable: true},
        {key: 'amountUsdt', label: 'مقدار دلاری',sortable: true},
        {key: 'from', label: 'برای'},
        {key: 'date', label: 'تاریخ ثبت', sortable: true},
        {key: 'actions', label: '#'},
    ];

    const perPage = ref(25)
    const totalRows = ref(0)
    const currentPage = ref(1)
    const perPageOptions = [10, 25, 50, 100,1000]
    const searchQuery = ref('')
    const sortBy = ref('id')
    const isSortDirDesc = ref(true)
    const fromFilter = ref(null)
    const statusFilter = ref(null)
    const dateStartFilter = ref(null)
    const dateStopFilter = ref(null)
    const isLoading = ref(true)




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
        refCryptoListTable.value.refresh()
    }

    var timer = null;
    watch([currentPage, perPage, searchQuery, fromFilter,statusFilter,dateStartFilter,dateStopFilter], () => {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
        timer = setTimeout(() => {
            refetchData()
        }, 800);
    });

    const fetchCrypto = (ctx, callback) => {
        axios.post('setting/crypto/auto-trade', {
                search: searchQuery.value,
                perPage: perPage.value,
                page: currentPage.value,
                sortBy: sortBy.value,
                sortDesc: isSortDirDesc.value,
                status: statusFilter.value,
                from: fromFilter.value,
                dateStart: dateStartFilter.value,
                dateStop: dateStopFilter.value,
            })
            .then(response => {
                const {lists, total} = response.data

                callback(lists)
                totalRows.value = total;
                isLoading.value = false;
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
        if (status === 'pending') return 'warning'
        if (status === 'success') return 'success'
        if (status === 'return') return 'secondary'
        if (status === 'suspend') return 'dark'
        if (status === 'unsuccessful') return 'danger'
        if (status === 'canceled') return 'secondary'
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
        fromFilter,
        statusFilter,
        dateStartFilter,dateStopFilter,

        isLoading,
    }
}
