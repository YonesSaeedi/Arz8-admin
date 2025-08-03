import {ref, watch, computed} from '@vue/composition-api'
import axios from '../../../libs/axios'

// Notification
import {useToast} from 'vue-toastification/composition'
import ToastificationContent from '@core/components/toastification/ToastificationContent.vue'

export default function useUsersList(props = null) {
    // Use toast
    const toast = useToast()

    const refCardsListTable = ref(null)

    // Table Handlers
    const tableColumns = [
        {key: 'id', label: 'شناسه', sortable: true},
        {key: 'nameFamily', label: 'نام و فامیل', sortable: true},
        {key: 'logo', label: 'لوگو', sortable: true},
        {key: 'bank', label: 'بانک', sortable: true},
        {key: 'cardNumber', label: 'شماره کارت', sortable: true},
        {key: 'nameFamilyAccount', label: 'نام صاحب حساب', sortable: true},
        {key: 'date', label: 'تاریخ ثبت', sortable: true},
        {key: 'depositId', label: 'واریز با شناسه'},
        {key: 'status', label: 'وضعیت'},
        {key: 'actions', label: '#'},
    ];

    const perPage = ref(25)
    const totalRows = ref(0)
    const currentPage = ref(1)
    const perPageOptions = [10, 25, 50, 100]
    const searchQuery = ref('')
    const sortBy = ref('id')
    const isSortDirDesc = ref(true)
    const typeFilter = ref(null)
    const statusFilter = ref(null)
    const dateStartFilter = ref(null)
    const dateStopFilter = ref(null)
    const bankFilter = ref(null)
    const isLoading = ref(true)

    if(props.status)
        statusFilter.value = props.status;

    const dataMeta = computed(() => {
        const localItemsCount = refCardsListTable.value ? refCardsListTable.value.localItems.length : 0
        return {
            from: perPage.value * (currentPage.value - 1) + (localItemsCount ? 1 : 0),
            to: perPage.value * (currentPage.value - 1) + localItemsCount,
            of: totalRows.value,
        }
    })

    const refetchData = () => {
        isLoading.value = true
        refCardsListTable.value.refresh()
        //fetchStatistic();
    }

    var timer = null;
    watch([currentPage, perPage, searchQuery, typeFilter, statusFilter,dateStartFilter,dateStopFilter, bankFilter], () => {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
        timer = setTimeout(() => {
            refetchData()
        }, 800);
    });

    const fetchCards = (ctx, callback) => {
        axios.post('card-bank/list', {
            search: searchQuery.value,
            perPage: perPage.value,
            page: currentPage.value,
            sortBy: sortBy.value,
            sortDesc: isSortDirDesc.value,
            status: statusFilter.value,
            dateStart: dateStartFilter.value,
            dateStop: dateStopFilter.value,
            bank: bankFilter.value,
            id_user: props.user ? props.user.id : null,
        })
            .then(response => {
                const {lists, total} = response.data

                callback(lists)
                totalRows.value = total
                isLoading.value = false
            })
            .catch((error) => {
                console.log(error)
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
        if (status === 'confirm') return 'success'
        if (status === 'reject') return 'danger'
        return 'warning'
    }

    return {
        fetchCards,
        tableColumns,
        perPage,
        currentPage,
        totalRows,
        dataMeta,
        perPageOptions,
        searchQuery,
        sortBy,
        isSortDirDesc,
        refCardsListTable,

        resolveStatusVariant,
        refetchData,

        // Extra Filters
        statusFilter,
        dateStartFilter,dateStopFilter,
        bankFilter,
        isLoading
    }
}
