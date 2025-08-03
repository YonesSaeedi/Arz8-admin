import {ref, watch, computed} from '@vue/composition-api'
import axios from '../../../../libs/axios'

// Notification
import {useToast} from 'vue-toastification/composition'
import ToastificationContent from '@core/components/toastification/ToastificationContent.vue'

export default function useUsersList(props=null) {
    // Use toast
    const toast = useToast()

    const refMarketsListTable = ref(null)

    // Table Handlers
    const tableColumns = [
        {key: 'id', label: 'شناسه', sortable: true},
        {key: 'name', label: 'نام و فامیل', sortable: true},
        {key: 'title', label: 'عنوان', sortable: true},
        {key: 'message', label: 'پیغام', sortable: true},
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
    const dateStartFilter = ref(null)
    const dateStopFilter = ref(null)
    const tabActive = ref(props.user ? 1 : 0)



    const dataMeta = computed(() => {
        const localItemsCount = refMarketsListTable.value ? refMarketsListTable.value.localItems.length : 0
        return {
            from: perPage.value * (currentPage.value - 1) + (localItemsCount ? 1 : 0),
            to: perPage.value * (currentPage.value - 1) + localItemsCount,
            of: totalRows.value,
        }
    })


    const listNotifications = computed(() => {
       return fetchNotifications;
    })


    const refetchData = () => {
        refMarketsListTable.value.refresh()
    }

    var timer = null;
    watch([currentPage, perPage, searchQuery,dateStartFilter,dateStopFilter], () => {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
        timer = setTimeout(() => {
            refetchData()
        }, 800);
    });

    watch([tabActive], () => {
        refetchData()
    });

    const fetchNotifications = (ctx, callback) => {
        axios.post('setting/notification/list', {
                search: searchQuery.value,
                perPage: perPage.value,
                page: currentPage.value,
                sortBy: sortBy.value,
                sortDesc: isSortDirDesc.value,
                dateStart: dateStartFilter.value,
                dateStop: dateStopFilter.value,
                public: (tabActive.value === 0 ?true : false),
                id_user: props.user ? props.user.id : null,
            })
            .then(response => {
                const {lists, total} = response.data

                callback(lists)
                totalRows.value = total

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
        fetchNotifications,
        listNotifications,
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
        dateStartFilter,dateStopFilter,
        tabActive
    }
}
