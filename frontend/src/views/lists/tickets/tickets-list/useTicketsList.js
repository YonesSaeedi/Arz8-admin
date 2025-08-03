import {ref, watch, computed} from '@vue/composition-api'
import axios from '../../../../libs/axios'
import jalaliMmoment from "jalali-moment";


// Notification
import {useToast} from 'vue-toastification/composition'
import ToastificationContent from '@core/components/toastification/ToastificationContent.vue'

export default function useUsersList(props = null) {
    const NowDate = jalaliMmoment()
    var day = NowDate.subtract(30, 'jDay');

    // Use toast
    const toast = useToast()

    const refListTable = ref(null)

    // Table Handlers
    const tableColumns = [
        {key: 'id', label: 'شناسه', sortable: true},
        {key: 'nameFamily', label: 'نام و فامیل', sortable: true},
        {key: 'title', label: 'عنوان'},
        {key: 'status', label: 'وضعیت'},
        {key: 'date', label: 'بروزرسانی', sortable: true},
        {key: 'actions', label: '#'},
    ];

    const perPage = ref(25)
    const totalRows = ref(0)
    const currentPage = ref(1)
    const perPageOptions = [10, 25, 50, 100]
    const searchQuery = ref('')
    const sortBy = ref('id')
    const isSortDirDesc = ref(true)
    const statusFilter = ref(null)
    const dateStartFilter = ref(day.format('jYYYY/jMM/jDD 00:00'))
    const dateStopFilter = ref(null)
    const viaFilter = ref(null)
    const idFilter = ref(null)
    const isLoading = ref(true)


    if(props.status)
        statusFilter.value = props.status;

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
        //fetchStatistic();
    }

    var timer = null;
    watch([currentPage, perPage, searchQuery, statusFilter,dateStartFilter,dateStopFilter,
        viaFilter,idFilter], () => {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
        timer = setTimeout(() => {
            refetchData()
        }, 800);
    });

    const statistic = ref([])
    const fetchStatistic = (ctx, callback) => {
        axios.post('tickets/list/statistic')
            .then(response => {
                statistic.value = response.data
            })
    }

    const fetchList = (ctx, callback) => {
        axios.post('tickets/list', {
                search: searchQuery.value,
                perPage: perPage.value,
                page: currentPage.value,
                sortBy: sortBy.value,
                sortDesc: isSortDirDesc.value,
                status: statusFilter.value,
                dateStart: dateStartFilter.value,
                dateStop: dateStopFilter.value,
                via: viaFilter.value,
                id: idFilter.value,
                id_user: props.user ? props.user.id : null,
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
        if (status === 'awaiting answer') return 'warning'
        if (status === 'answered') return 'success'
        if (status === 'closed') return 'dark'
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
        statusFilter,
        dateStartFilter,dateStopFilter,
        viaFilter,
        idFilter,
        fetchStatistic,
        statistic,
        isLoading
    }
}
