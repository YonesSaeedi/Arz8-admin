import {ref, watch, computed} from '@vue/composition-api'
import axios from '../../../../libs/axios'
import {title} from '@core/utils/filter'
import jalaliMmoment from "jalali-moment";


// Notification
import {useToast} from 'vue-toastification/composition'
import ToastificationContent from '@core/components/toastification/ToastificationContent.vue'

export default function useUsersList(user=null) {
    const NowDate = jalaliMmoment()
    var day = NowDate.subtract(30, 'jDay');

    // Use toast
    const toast = useToast()

    const refOrdersListTable = ref(null)

    // Table Handlers
    const tableColumns = [
        {key: 'id', label: 'شناسه', sortable: true},
        {key: 'coin', label: 'ارز'},
        {key: 'nameFamily', label: 'نام و فامیل'},
        {key: 'type', label: 'نوع', sortable: true},
        {key: 'amount', label: 'مبلغ کل', sortable: true},
        {key: 'amount_coin', label: 'مقدار',sortable: true},
        {key: 'date', label: 'تاریخ ثبت', sortable: true},
        {key: 'status', label: 'وضعیت'},
        {key: 'actions', label: '#'},
    ];

    const perPage = ref(25)
    const totalRows = ref(0)
    const currentPage = ref(1)
    const perPageOptions = [10, 25, 50, 100]
    const searchQuery = ref('')
    const sortBy = ref('id')
    const isSortDirDesc = ref(true)
    const typeFilter = ref(null)
    const statusFilter = ref(null)
    const dateStartFilter = ref(day.format('jYYYY/jMM/jDD 00:00'))
    const dateStopFilter = ref(null)
    const amountStartFilter = ref(null)
    const amountStopFilter = ref(null)
    const viaFilter = ref(null)
    const idFilter = ref(null)
    const statistic = ref([])
    const isLoading = ref(true)

    const dataMeta = computed(() => {
        const localItemsCount = refOrdersListTable.value ? refOrdersListTable.value.localItems.length : 0
        return {
            from: perPage.value * (currentPage.value - 1) + (localItemsCount ? 1 : 0),
            to: perPage.value * (currentPage.value - 1) + localItemsCount,
            of: totalRows.value,
        }
    })

    const refetchData = () => {
        isLoading.value = true
        refOrdersListTable.value.refresh()
        //fetchStatistic();
    }

    var timer = null;
    watch([currentPage, perPage, searchQuery, typeFilter, statusFilter,dateStartFilter,dateStopFilter,amountStartFilter,amountStopFilter,viaFilter,idFilter], () => {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
        timer = setTimeout(() => {
            refetchData()
        }, 800);
    });

    const fetchStatistic = (ctx, callback) => {
        axios.post('orders/list/statistic')
            .then(response => {
                statistic.value = response.data
            })
    }

    const fetchOrders = (ctx, callback) => {
        axios.post('orders/list', {
                search: searchQuery.value,
                perPage: perPage.value,
                page: currentPage.value,
                sortBy: sortBy.value,
                sortDesc: isSortDirDesc.value,
                type: typeFilter.value,
                status: statusFilter.value,
                dateStart: dateStartFilter.value,
                dateStop: dateStopFilter.value,
                amountStart: amountStartFilter.value,
                amountStop: amountStopFilter.value,
                via: viaFilter.value,
                id: idFilter.value,
                id_user: user ? user.id : null
            })
            .then(response => {
                const {list, total} = response.data
                callback(list)
                totalRows.value = total
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
        refOrdersListTable,

        resolveStatusVariant,
        refetchData,

        // Extra Filters
        typeFilter,
        statusFilter,
        dateStartFilter,dateStopFilter,
        amountStartFilter,amountStopFilter,
        viaFilter,
        idFilter,

        fetchStatistic,
        statistic,
        isLoading
    }
}
