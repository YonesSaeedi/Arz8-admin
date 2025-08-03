import {ref, watch, computed} from '@vue/composition-api'
import axios from '../../../../../../libs/axios'
import {title} from '@core/utils/filter'

// Notification
import {useToast} from 'vue-toastification/composition'
import ToastificationContent from '@core/components/toastification/ToastificationContent.vue'

export default function useUsersList(user) {
    // Use toast
    const toast = useToast()

    const refWalletsListTable = ref(null)

    // Table Handlers
    const tableColumns = [
        {key: 'coin', label: 'ارز', sortable: true},
        {key: 'name', label: 'نام', sortable: true},
        {key: 'symbol', label: 'نماد', sortable: true},
        {key: 'balance', label: 'موجودی', sortable: true},
        {key: 'balanceToman', label: 'معادل تومانی', sortable: true},
        {key: 'balanceUsdt', label: 'معادل دلاری', sortable: true},
        {key: 'valueAvailable', label: 'موجودی در دسترس', sortable: true},
        {key: 'actions', label: 'تغییر موجودی'},
    ];

    const perPage = ref(25)
    const totalRows = ref(0)
    const currentPage = ref(1)
    const perPageOptions = [10, 25, 50, 100,1000]
    const searchQuery = ref('')
    const sortBy = ref('balanceUsdt')
    const isSortDirDesc = ref(true)



    const dataMeta = computed(() => {
        const localItemsCount = refWalletsListTable.value ? refWalletsListTable.value.localItems.length : 0
        return {
            from: perPage.value * (currentPage.value - 1) + (localItemsCount ? 1 : 0),
            to: perPage.value * (currentPage.value - 1) + localItemsCount,
            of: totalRows.value,
        }
    })


    const listWallets = computed(() => {
       return fetchWallets;
    })


    const refetchData = () => {
        refWalletsListTable.value.refresh()
    }

    var timer = null;
    watch([currentPage, perPage, searchQuery], () => {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
        timer = setTimeout(() => {
            refetchData()
        }, 800);
    });

    const fetchWallets = (ctx, callback) => {
        axios.post('users/wallets/'+user.id+'/list', {
                search: searchQuery.value,
                perPage: perPage.value,
                page: currentPage.value,
                sortBy: sortBy.value,
                sortDesc: isSortDirDesc.value,
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
        if (status === 0) return 'warning'
        if (status === 1) return 'success'
        return 'warning'
    }

    return {
        fetchWallets,
        listWallets,
        tableColumns,
        perPage,
        currentPage,
        totalRows,
        dataMeta,
        perPageOptions,
        searchQuery,
        sortBy,
        isSortDirDesc,
        refWalletsListTable,

        resolveStatusVariant,
        refetchData,

        // Extra Filters
    }
}
