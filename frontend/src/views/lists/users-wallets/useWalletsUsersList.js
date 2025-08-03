import {ref, watch, computed} from '@vue/composition-api'
import axios from '@axios'
import {title} from '@core/utils/filter'

// Notification
import {useToast} from 'vue-toastification/composition'
import ToastificationContent from '@core/components/toastification/ToastificationContent.vue'

export default function useUsersList(user = null) {
    // Use toast
    const toast = useToast()

    const refListTable = ref(null)

    // Table Handlers
    const tableColumns = [
        {key: 'nameFamily', label: 'نام و فامیل',sortable: true},
        {key: 'coin', label: 'ارز',sortable: true},
        {key: 'network', label: 'شبکه', sortable: true},
        {key: 'created', label: 'شروع و بروزرسانی', sortable: true},
        {key: 'expired', label: 'زمان اتمام', sortable: true},
        {key: 'amount', label: 'مقدار و تعداد واریز', sortable: false},
        {key: 'address', label: 'آدرس و تگ', sortable: true},
        {key: 'actions', label: 'عملیات', sortable: false},
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
    const statusFilter = ref(null)
    const isLoading = ref(true)


    const dataMeta = computed(() => {
        const localItemsCount = refListTable.value ? refListTable.value.localItems.length : 0
        return {
            from: perPage.value * (currentPage.value - 1) + (localItemsCount ? 1 : 0),
            to: perPage.value * (currentPage.value - 1) + localItemsCount,
            of: totalRows.value,
        }
    })

    const refetchData = () => {
        isLoading.value = true
        refListTable.value.refresh()
    }

    var timer = null;
    watch([currentPage, perPage, searchQuery,dateStartFilter,dateStopFilter,statusFilter], () => {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
        timer = setTimeout(() => {
            refetchData()
        }, 800);
    });

    const fetchList = (ctx, callback) => {
        axios.post('wallets-crypto/list', {
                search: searchQuery.value,
                perPage: perPage.value,
                page: currentPage.value,
                sortBy: sortBy.value,
                sortDesc: isSortDirDesc.value,
                dateStart: dateStartFilter.value,
                dateStop: dateStopFilter.value,
                status: statusFilter.value,
                id_user: user ? user.id : null,
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
                        title: 'Error fetching list',
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
        if (status === 'active') return 'success'
        if (status === 'expired') return 'secondary'
        return 'warning'
    }

    return {
        fetchList,
        tableColumns,
        perPage,
        currentPage,
        totalRows,
        dataMeta,
        perPageOptions,
        searchQuery,
        sortBy,
        isSortDirDesc,
        refListTable,

        resolveStatusVariant,
        refetchData,

        // Extra Filters
        dateStartFilter,dateStopFilter,
        statusFilter,
        isLoading
    }
}
