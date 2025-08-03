import {ref, watch, computed} from '@vue/composition-api'
import axios from '../../../../../libs/axios'
import {title} from '@core/utils/filter'

// Notification
import {useToast} from 'vue-toastification/composition'
import ToastificationContent from '@core/components/toastification/ToastificationContent.vue'

export default function useUsersList(user=null) {

    // Use toast
    const toast = useToast()

    const refCustListTable = ref(null)

    // Table Handlers
    const tableColumns = [
        //{key: 'id', label: 'شناسه', sortable: true},
        {key: 'date', label: 'تاریخ', sortable: true},
        {key: 'amount', label: 'مقدار',sortable: true},
        {key: 'type', label: 'نوع', sortable: true},
        {key: 'description', label: 'توضیحات', sortable: true},
        {key: 'file', label: 'فایل', sortable: true},
        {key: 'action', label: 'حذف',sortable: false},
    ];

    const perPage = ref(25)
    const totalRows = ref(0)
    const currentPage = ref(1)
    const perPageOptions = [10, 25, 50, 100]
    const searchQuery = ref('')
    const sortBy = ref('id')
    const isSortDirDesc = ref(true)
    const dateStartFilter = ref(null)
    const dateStopFilter = ref(null)
    const amountStartFilter = ref(null)
    const amountStopFilter = ref(null)
    const sumCust = ref(null)

    const dataMeta = computed(() => {
        const localItemsCount = refCustListTable.value ? refCustListTable.value.localItems.length : 0
        return {
            from: perPage.value * (currentPage.value - 1) + (localItemsCount ? 1 : 0),
            to: perPage.value * (currentPage.value - 1) + localItemsCount,
            of: totalRows.value,
        }
    })

    const refetchData = () => {
        refCustListTable.value.refresh()
    }

    var timer = null;
    watch([currentPage, perPage, searchQuery,dateStartFilter,dateStopFilter,amountStartFilter,amountStopFilter], () => {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
        timer = setTimeout(() => {
            refetchData()
        }, 800);
    });


    const fetchOrders = (ctx, callback) => {
        sumCust.value = null;
        axios.post('audit/cust', {
                search: searchQuery.value,
                perPage: perPage.value,
                page: currentPage.value,
                sortBy: sortBy.value,
                sortDesc: isSortDirDesc.value,
                dateStart: dateStartFilter.value,
                dateStop: dateStopFilter.value,
                amountStart: amountStartFilter.value,
                amountStop: amountStopFilter.value,
            })
            .then(response => {
                const {list, total,sum_cust} = response.data

                callback(list)
                totalRows.value = total
                sumCust.value = sum_cust

                //balance.value =  (calculation_table.sell.sum_amount_usdt - calculation_table.buy.sum_amount_usdt).toFixed(4)
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
        fetchOrders,
        tableColumns,
        perPage,
        currentPage,
        totalRows,
        dataMeta,
        perPageOptions,
        searchQuery,
        sortBy,
        isSortDirDesc,
        refCustListTable,

        resolveStatusVariant,
        refetchData,

        // Extra Filters
        dateStartFilter,dateStopFilter,
        amountStartFilter,amountStopFilter,
        sumCust,
    }
}
