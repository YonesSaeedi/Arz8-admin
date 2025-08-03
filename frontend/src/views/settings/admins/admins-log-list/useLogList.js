import {ref, watch, computed} from '@vue/composition-api'
import axios from '../../../../libs/axios'
import {title} from '@core/utils/filter'

// Notification
import {useToast} from 'vue-toastification/composition'
import ToastificationContent from '@core/components/toastification/ToastificationContent.vue'

export default function useLogList() {
    // Use toast
    const toast = useToast()

    const refLogListTable = ref(null)

    // Table Handlers
    const tableColumns = [
        {key: 'id', label: 'شناسه', sortable: true},
        {key: 'registeryDate', label: 'تاریخ ثبت', sortable: true},
        {key: 'nameFamily', label: 'نام و فامیل', sortable: true},
        {key: 'key', label: 'کلیدواژه', sortable: true},
        {key: 'ip', label: 'آی پی', sortable: true},
        {key: 'text', label: 'توضیحات', sortable: true},
        {key: 'actions', label: '#'},
    ];

    const perPage = ref(25)
    const totalRows = ref(0)
    const currentPage = ref(1)
    const perPageOptions = [10, 25, 50, 100,1000]
    const searchQuery = ref('')
    const sortBy = ref('id')
    const isSortDirDesc = ref(true)
    const dateStartFilter = ref(null)
    const dateStopFilter = ref(null)
    const adminFilter = ref(null)
    const keywordFilter = ref(null)
    const adminslist = ref([])


    const dataMeta = computed(() => {
        const localItemsCount = refLogListTable.value ? refLogListTable.value.localItems.length : 0
        return {
            from: perPage.value * (currentPage.value - 1) + (localItemsCount ? 1 : 0),
            to: perPage.value * (currentPage.value - 1) + localItemsCount,
            of: totalRows.value,
        }
    })


    const listLog = computed(() => {
       return fetchLog;
    })


    const refetchData = () => {
        refLogListTable.value.refresh()
    }

    var timer = null;
    watch([currentPage, perPage, searchQuery,dateStartFilter,dateStopFilter,adminFilter,keywordFilter], () => {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
        timer = setTimeout(() => {
            refetchData()
        }, 800);
    });

    const fetchLog = (ctx, callback) => {
        axios.post('admins/logs/list', {
                search: searchQuery.value,
                perPage: perPage.value,
                page: currentPage.value,
                sortBy: sortBy.value,
                sortDesc: isSortDirDesc.value,
                registeryDateStart: dateStartFilter.value,
                registeryDateStop: dateStopFilter.value,
                admin: adminFilter.value,
                key: keywordFilter.value,
            })
            .then(response => {
                const {users, total} = response.data
                callback(users)
                totalRows.value = total
                adminslist.value =  response.data.admins

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
        if (status === true) return 'danger'
        if (status === false) return 'success'
        return 'warning'
    }

    return {
        fetchLog,
        listLog,
        tableColumns,
        perPage,
        currentPage,
        totalRows,
        dataMeta,
        perPageOptions,
        searchQuery,
        sortBy,
        isSortDirDesc,
        refLogListTable,

        resolveStatusVariant,
        refetchData,

        // Extra Filters
        dateStartFilter,dateStopFilter,
        adminslist,adminFilter,
        keywordFilter
    }
}
