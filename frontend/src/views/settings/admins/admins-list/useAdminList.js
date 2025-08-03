import {ref, watch, computed} from '@vue/composition-api'
import axios from '../../../../libs/axios'
import {title} from '@core/utils/filter'

// Notification
import {useToast} from 'vue-toastification/composition'
import ToastificationContent from '@core/components/toastification/ToastificationContent.vue'

export default function useAdminList() {
    // Use toast
    const toast = useToast()

    const refAdminListTable = ref(null)

    // Table Handlers
    const tableColumns = [
        {key: 'email', label: 'ایمیل', sortable: true},
        {key: 'nameFamily', label: 'نام و فامیل', sortable: true},
        {key: 'mobile', label: 'موبایل', sortable: true},
        {key: 'role', label: 'سطح', sortable: true},
        {key: 'access', label: 'دسترسی ها'},
        {key: 'isBlock', label: 'وضعیت'},
        {key: 'registeryDate', label: 'تاریخ ثبت', sortable: true},
        {key: 'actions', label: '#'},
    ];

    const perPage = ref(25)
    const totalRows = ref(0)
    const currentPage = ref(1)
    const perPageOptions = [10, 25, 50, 100,1000]
    const searchQuery = ref('')
    const sortBy = ref('id')
    const isSortDirDesc = ref(true)

    const dataMeta = computed(() => {
        const localItemsCount = refAdminListTable.value ? refAdminListTable.value.localItems.length : 0
        return {
            from: perPage.value * (currentPage.value - 1) + (localItemsCount ? 1 : 0),
            to: perPage.value * (currentPage.value - 1) + localItemsCount,
            of: totalRows.value,
        }
    })


    const listAdmin = computed(() => {
       return fetchAdmin;
    })


    const refetchData = () => {
        refAdminListTable.value.refresh()
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

    const fetchAdmin = (ctx, callback) => {
        axios.post('admins/list', {
                search: searchQuery.value,
                perPage: perPage.value,
                page: currentPage.value,
                sortBy: sortBy.value,
                sortDesc: isSortDirDesc.value,
            })
            .then(response => {
                const {users, total} = response.data
                callback(users)
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
        if (status === true) return 'danger'
        if (status === false) return 'success'
        return 'warning'
    }

    return {
        fetchAdmin,
        listAdmin,
        tableColumns,
        perPage,
        currentPage,
        totalRows,
        dataMeta,
        perPageOptions,
        searchQuery,
        sortBy,
        isSortDirDesc,
        refAdminListTable,

        resolveStatusVariant,
        refetchData,

        // Extra Filters

    }
}
