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
        {key: 'id', label: 'شناسه',sortable: true},
        {key: 'user', label: 'واریز برای',sortable: true},
        {key: 'userCaller', label: 'معرفی کننده',sortable: true},
        {key: 'userReferral', label: 'معرفی شده',sortable: true},
        {key: 'amount', label: 'مبلغ کارمزد', sortable: true},
        {key: 'amountToman', label: 'معادل تومانی', sortable: true},
        {key: 'for', label: 'مربوط به'},
        {key: 'date', label: 'تاریخ ثبت', sortable: true},
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
    const forFilter = ref(null)
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
    watch([currentPage, perPage, searchQuery,dateStartFilter,dateStopFilter,amountStartFilter,amountStopFilter,forFilter], () => {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
        timer = setTimeout(() => {
            refetchData()
        }, 800);
    });

    const fetchList = (ctx, callback) => {
        axios.post('referral/transaction', {
                search: searchQuery.value,
                perPage: perPage.value,
                page: currentPage.value,
                sortBy: sortBy.value,
                sortDesc: isSortDirDesc.value,
                dateStart: dateStartFilter.value,
                dateStop: dateStopFilter.value,
                amountStart: amountStartFilter.value,
                amountStop: amountStopFilter.value,
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
        amountStartFilter,amountStopFilter,
        forFilter,
        isLoading
    }
}
