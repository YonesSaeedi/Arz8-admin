import {ref, watch, computed} from '@vue/composition-api'
import axios from '../../../../../libs/axios'
// Notification
import {useToast} from 'vue-toastification/composition'
import ToastificationContent from '@core/components/toastification/ToastificationContent.vue'
import jalaliMmoment from "jalali-moment";


export default function useUsersList(isCrypto=true) {

    const NowDate = jalaliMmoment()

    // Use toast
    const toast = useToast()

    const refOrdersListTable = ref(null)

    // Table Handlers
    const tableColumns = [
        {key: 'id', label: 'شناسه', sortable: true},
        {key: 'coin', label: 'ارز'},
        {key: 'nameFamily', label: 'نام و فامیل'},
        {key: 'type', label: 'نوع', sortable: true},
        {key: 'amount_usdt', label: 'مقدار تتری', sortable: true},
        {key: 'fee_usdt', label: 'نرخ تتر', sortable: true},
        {key: 'amount', label: 'مبلغ کل', sortable: true},
        {key: 'amount_coin', label: 'مقدار',sortable: true},
        {key: 'date', label: 'تاریخ ثبت', sortable: true},
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
    const dateStartFilter = ref(NowDate.format('jYYYY/jMM/jDD 00:00'))
    const dateStopFilter = ref(NowDate.add(1,'jDay').format('jYYYY/jMM/jDD 00:00'))
    const amountStartFilter = ref(null)
    const amountStopFilter = ref(null)
    const viaFilter = ref(null)
    const idFilter = ref(null)
    const calculationTable = ref(null)
    const balance = ref(0)
    const isLoading = ref(true)
    const isFirst = ref(true)

    const dataMeta = computed(() => {
        const localItemsCount = refOrdersListTable.value ? refOrdersListTable.value.localItems.length : 0
        return {
            from: perPage.value * (currentPage.value - 1) + (localItemsCount ? 1 : 0),
            to: perPage.value * (currentPage.value - 1) + localItemsCount,
            of: totalRows.value,
        }
    })

    const refetchData = (cal = true) => {
        isLoading.value = true
        refOrdersListTable.value.refresh();

        if(cal)
            fetchCalculation();
    }

    var timer = null;
    watch([dateStartFilter,dateStopFilter], () => {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
        timer = setTimeout(() => {
            refetchData()
        }, 800);
    });
    watch([currentPage, perPage, searchQuery, typeFilter, statusFilter,amountStartFilter,amountStopFilter,viaFilter,idFilter], () => {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
        timer = setTimeout(() => {
            refetchData(false)
        }, 800);
    });

    const fetchCalculation = (ctx, callback) => {
        calculationTable.value = null;
        axios.post('audit/orders/calculation', {
            dateStart: dateStartFilter.value,
            dateStop: dateStopFilter.value,
            isCrypto:  isCrypto
        })
        .then(response => {
            const {sell,buy} = response.data
            calculationTable.value = response.data
            balance.value =  (sell.sum_amount_usdt - buy.sum_amount_usdt).toFixed(4)
        })
    }

    const fetchOrders = (ctx, callback) => {
        axios.post('audit/orders', {
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
            isCrypto:  isCrypto
        })
            .then(response => {
                const {list, total} = response.data

                callback(list)
                totalRows.value = total
                isLoading.value = false
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
        fetchCalculation,
        calculationTable,
        balance,
        isLoading
    }
}
