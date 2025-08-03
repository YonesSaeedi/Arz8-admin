import {ref, watch, computed} from '@vue/composition-api'
import axios from '../../../../libs/axios'
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
        {key: 'issuer', label: 'صادر کننده', sortable: true},
        {key: 'coin', label: 'ارز',sortable: true},
        {key: 'cardNamber', label: 'کد هدیه',sortable: true},
        {key: 'amount', label: 'مقدار',sortable: true},
        {key: 'consumer', label: 'مصرف کننده', sortable: true},
        {key: 'created', label: 'زمان صدور', sortable: true},
        {key: 'activated', label: 'زمان مصرف', sortable: true},
        {key: 'count', label: 'تعداد مانده/شروع',sortable: true},
        {key: 'expired', label: 'انقضا', sortable: true},
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
    const forFilter = ref(null)
    const statistic = ref([])
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
        fetchStatistic()
    }

    const fetchStatistic = (ctx, callback) => {
        if(user)
            return;
        axios.post('gift/card/statistic',{
            id_user: user ? user.id : null,
            dateStart: dateStartFilter.value,
            dateStop: dateStopFilter.value,
        })
        .then(response => {
            statistic.value = response.data
        })
    }

    var timer = null;
    watch([currentPage, perPage, searchQuery,dateStartFilter,dateStopFilter,statusFilter,forFilter], () => {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
        timer = setTimeout(() => {
            refetchData()
        }, 800);
    });

    const fetchList = (ctx, callback) => {
        axios.post('gift/card/list', {
                search: searchQuery.value,
                perPage: perPage.value,
                page: currentPage.value,
                sortBy: sortBy.value,
                sortDesc: isSortDirDesc.value,
                dateStart: dateStartFilter.value,
                dateStop: dateStopFilter.value,
                status: statusFilter.value,
                for: forFilter.value,
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
        if (status === 'open') return 'warning'
        if (status === 'success') return 'success'
        if (status === 'expired') return 'secondary'
        if (status === 'canceled') return 'dark'
        if (status === 'revoke') return 'danger'
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
        forFilter,
        fetchStatistic,
        statistic,
        isLoading,
    }
}
