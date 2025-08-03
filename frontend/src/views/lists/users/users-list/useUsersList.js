import {ref, watch, computed} from '@vue/composition-api'
import axios from '../../../../libs/axios'
import {title} from '@core/utils/filter'

// Notification
import {useToast} from 'vue-toastification/composition'
import ToastificationContent from '@core/components/toastification/ToastificationContent.vue'

export default function useUsersList(props) {
    // Use toast
    const toast = useToast()

    const refUserListTable = ref(null)

    // Table Handlers
    const tableColumns = [
        {key: 'email', label: 'ایمیل', sortable: true},
        {key: 'nameFamily', label: 'نام و فامیل', sortable: true},
        {key: 'mobile', label: 'موبایل', sortable: true},
        {key: 'balanceInternalWallet', label: 'موجودی', sortable: true},
        {key: 'level_account', label: 'سطح کاربری', sortable: true},
        {key: 'level', label: 'سطح احراز', sortable: true},
        {key: 'status', label: 'وضعیت'},
        {key: 'registeryDate', label: 'تاریخ عضویت', sortable: true},
        {key: 'actions', label: '#'},
    ];

    const perPage = ref(25)
    const totalUsers = ref(0)
    const currentPage = ref(1)
    const perPageOptions = [10, 25, 50, 100]
    const searchQuery = ref('')
    const sortBy = ref('registeryDate')
    const isSortDirDesc = ref(true)
    const levelFilter = ref(null)
    const levelAccountFilter = ref(null)
    const statusFilter = ref(null)
    const dateStartFilter = ref(null)
    const dateStopFilter = ref(null)
    const balanceStartFilter = ref(null)
    const balanceStopFilter = ref(null)
    const otherFilter = ref(null)
    const idsFilter = ref(null)
    const statistic = ref([])
    const isLoading = ref(true)

    if(props.status)
        statusFilter.value = props.status;

    if(props.idUsers)
        idsFilter.value = props.idUsers;

    const dataMeta = computed(() => {
        const localItemsCount = refUserListTable.value ? refUserListTable.value.localItems.length : 0
        return {
            from: perPage.value * (currentPage.value - 1) + (localItemsCount ? 1 : 0),
            to: perPage.value * (currentPage.value - 1) + localItemsCount,
            of: totalUsers.value,
        }
    })

    const refetchData = () => {
        isLoading.value = true
        refUserListTable.value.refresh()
        fetchStatistic();
    }

    var timer = null;
    watch([currentPage, perPage, searchQuery, levelFilter,levelAccountFilter, statusFilter,dateStartFilter,dateStopFilter,balanceStartFilter,balanceStopFilter,otherFilter,idsFilter], () => {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
        timer = setTimeout(() => {
            refetchData()
        }, 800);
    });

    const fetchStatistic = (ctx, callback) => {
        if(props.status || props.idUsers)
            return;
        axios.post('users/list/statistic')
        .then(response => {
            statistic.value = response.data
        })
    }

    const fetchUsers = (ctx, callback) => {
        axios.post('users/list', {
                search: searchQuery.value,
                perPage: perPage.value,
                page: currentPage.value,
                sortBy: sortBy.value,
                sortDesc: isSortDirDesc.value,
                level: levelFilter.value,
                levelAccount: levelAccountFilter.value,
                status: statusFilter.value,
                registeryDateStart: dateStartFilter.value,
                registeryDateStop: dateStopFilter.value,
                balanceStart: balanceStartFilter.value,
                balanceStop: balanceStopFilter.value,
                otherFilter: otherFilter.value,
                ids: idsFilter.value,
            })
            .then(response => {
                const {users, total} = response.data

                callback(users)
                totalUsers.value = total
                isLoading.value = false
            })
            .catch(() => {
                toast({
                    component: ToastificationContent,
                    props: {
                        title: 'Error fetching users list',
                        icon: 'AlertTriangleIcon',
                        variant: 'danger',
                    },
                })
            })
    }

    // *===============================================---*
    // *--------- UI ---------------------------------------*
    // *===============================================---*


    const resolveUserRoleIcon = role => {
        if (role === 'subscriber') return 'UserIcon'
        if (role === 'author') return 'SettingsIcon'
        if (role === 'maintainer') return 'DatabaseIcon'
        if (role === 'editor') return 'Edit2Icon'
        if (role === 'admin') return 'ServerIcon'
        return 'UserIcon'
    }

    const resolveUserStatusVariant = status => {
        //if (status === 'pending') return 'warning'
        if (status === 'accessible') return 'success'
        if (status === 'blocked') return 'secondary'
        return 'warning'
    }

    return {
        fetchUsers,
        tableColumns,
        perPage,
        currentPage,
        totalUsers,
        dataMeta,
        perPageOptions,
        searchQuery,
        sortBy,
        isSortDirDesc,
        refUserListTable,

        resolveUserRoleIcon,
        resolveUserStatusVariant,
        refetchData,

        // Extra Filters
        levelFilter,
        levelAccountFilter,
        statusFilter,
        dateStartFilter,dateStopFilter,
        balanceStartFilter,balanceStopFilter,
        otherFilter,

        fetchStatistic,
        statistic,
        isLoading
    }
}
