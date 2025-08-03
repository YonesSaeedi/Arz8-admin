import {ref, watch, computed} from '@vue/composition-api'
import axios from '../../../../libs/axios'
import {title} from '@core/utils/filter'
import router from '@/router'
import jalaliMmoment from "jalali-moment";

// Notification
import {useToast} from 'vue-toastification/composition'
import ToastificationContent from '@core/components/toastification/ToastificationContent.vue'

export default function useUsersList(props = null) {
    const NowDate = jalaliMmoment()
    var day = NowDate.subtract(30, 'jDay');

    // Use toast
    const toast = useToast()

    const refInternalListTable = ref(null)

    // Table Handlers
    const tableColumns = [
        {key: 'id', label: 'شناسه', sortable: true},
        {key: 'nameFamily', label: 'نام و فامیل'},
        {key: 'type', label: 'نوع', sortable: true},
        {key: 'amount', label: 'مبلغ', sortable: true},
        {key: 'stock', label: 'موجودی', sortable: true},
        {key: 'date', label: 'تاریخ ثبت', sortable: true},
        {key: 'description', label: 'توضیحات', sortable: true},
        {key: 'status', label: 'وضعیت'},
        {key: 'actions', label: '#'},
    ];

    const perPage = ref(50)
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
    const gatewayFilter = ref(null)
    const otherFilter = ref(null)
    const idFilter = ref(null)
    const isLoading = ref(true)
    const itemIds  = ref([]);
    const items  = ref([]);

    if(props.status)
        statusFilter.value =   props.status;

    const dataMeta = computed(() => {
        const localItemsCount = refInternalListTable.value ? refInternalListTable.value.localItems.length : 0
        return {
            from: perPage.value * (currentPage.value - 1) + (localItemsCount ? 1 : 0),
            to: perPage.value * (currentPage.value - 1) + localItemsCount,
            of: totalRows.value,
        }
    })

    const refetchData = () => {
        isLoading.value = true
        refInternalListTable.value.refresh()
        //fetchStatistic();
    }

    var timer = null;
    watch([currentPage, perPage, searchQuery, typeFilter, statusFilter,dateStartFilter,dateStopFilter,
        amountStartFilter,amountStopFilter,viaFilter,idFilter, gatewayFilter,otherFilter], () => {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
        timer = setTimeout(() => {
            refetchData()
        }, 800);
    });

    const fetchInternal = (ctx, callback) => {

        axios.post('internal/list', {
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
                gateway: gatewayFilter.value,
                other: otherFilter.value,
                id: idFilter.value,
                id_order: router.currentRoute.query.id_order ? router.currentRoute.query.id_order : null,
                id_user: props.user ? props.user.id : null,
            })
            .then(response => {
                const {lists, total} = response.data

                itemIds.value = lists.filter(item => item.type === 'withdraw').map(item => item.id);
                items.value = lists.filter(item => item.type === 'withdraw');

                callback(lists)
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
        if (status === 'reject') return 'danger'
        if (status === 'canceled') return 'secondary'
        return 'warning'
    }

    return {
        fetchInternal,
        tableColumns,
        perPage,
        currentPage,
        totalRows,
        dataMeta,
        perPageOptions,
        searchQuery,
        sortBy,
        isSortDirDesc,
        refInternalListTable,

        resolveStatusVariant,
        refetchData,

        // Extra Filters
        typeFilter,
        statusFilter,
        dateStartFilter,dateStopFilter,
        amountStartFilter,amountStopFilter,
        viaFilter,
        idFilter,
        gatewayFilter,
        otherFilter,
        isLoading,
        itemIds,
        items
    }
}
