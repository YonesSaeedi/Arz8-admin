import {ref, watch, computed} from '@vue/composition-api'
import axios from '../../../../libs/axios'
import {title} from '@core/utils/filter'

// Notification
import {useToast} from 'vue-toastification/composition'
import ToastificationContent from '@core/components/toastification/ToastificationContent.vue'

export default function useUsersList() {
    // Use toast
    const toast = useToast()

    const refMarketsListTable = ref(null)

    // Table Handlers
    const tableColumns = [
        {key: 'id', label: 'شناسه', sortable: true},
        {key: 'name', label: 'نام', sortable: true},
        {key: 'symbol', label: 'نماد', sortable: true},
        {key: 'baseAsset', label: 'ارز پایه', sortable: true},
        {key: 'quoteAsset', label: 'ارز هدف', sortable: true},
        {key: 'countTrade', label: 'تعداد معاملات', sortable: true},
        {key: 'amountTrade', label: 'حجم معاملات', sortable: true},
        {key: 'status', label: 'وضعیت',sortable: true},
        {key: 'status_auto', label: 'وضعیت اتوماتیک',sortable: true},
        {key: 'actions', label: '#'},
    ];

    const perPage = ref(25)
    const totalRows = ref(0)
    const currentPage = ref(1)
    const perPageOptions = [10, 25, 50, 100,1000]
    const searchQuery = ref('')
    const sortBy = ref('id')
    const isSortDirDesc = ref(true)
    const statusFilter = ref(null)
    const statusAutoFilter = ref(null)
    const cryptocurrency = ref([])



    const dataMeta = computed(() => {
        const localItemsCount = refMarketsListTable.value ? refMarketsListTable.value.localItems.length : 0
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
        refMarketsListTable.value.refresh()
    }

    var timer = null;
    watch([currentPage, perPage, searchQuery, statusFilter, statusAutoFilter], () => {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
        timer = setTimeout(() => {
            refetchData()
        }, 800);
    });

    const fetchCrypto = (ctx, callback) => {
        axios.post('setting/markets/list', {
                search: searchQuery.value,
                perPage: perPage.value,
                page: currentPage.value,
                sortBy: sortBy.value,
                sortDesc: isSortDirDesc.value,
                status: statusFilter.value,
                status_auto: statusAutoFilter.value,
            })
            .then(response => {
                const {lists, total} = response.data

                callback(lists)
                totalRows.value = total

                cryptocurrency.value = response.data.cryptocurrency
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
        if (status === 'inactive') return 'warning'
        if (status === 'active') return 'success'
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
        refMarketsListTable,

        resolveStatusVariant,
        refetchData,

        // Extra Filters
        statusFilter,
        statusAutoFilter,
        cryptocurrency
    }
}
