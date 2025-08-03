import {ref, watch, computed} from '@vue/composition-api'
import axios from '../../../../libs/axios'
import {title} from '@core/utils/filter'

// Notification
import {useToast} from 'vue-toastification/composition'
import ToastificationContent from '@core/components/toastification/ToastificationContent.vue'

export default function useUsersList() {
    // Use toast
    const toast = useToast()

    const refCryptoListTable = ref(null)

    // Table Handlers
    const tableColumns = [
        {key: 'logo', label: 'لوگو', sortable: true},
        {key: 'name', label: 'نام', sortable: true},
        {key: 'symbol', label: 'نماد', sortable: true},
        {key: 'network', label: 'شبکه', sortable: true},
        {key: 'address', label: 'آدرس', sortable: true},
        {key: 'TxId', label: 'مخصوص txId', sortable: true},
        {key: 'Count', label: 'تعداد ولت ها', sortable: true},
        {key: 'Amount', label: 'کل واریزی ها', sortable: false},
        {key: 'updated', label: 'بروزرسانی', sortable: true},
    ];

    const perPage = ref(50)
    const totalRows = ref(0)
    const currentPage = ref(1)
    const perPageOptions = [10, 25, 50, 100,1000]
    const searchQuery = ref('')
    const sortBy = ref('id')
    const isSortDirDesc = ref(true)
    const isLoading = ref(true)




    const dataMeta = computed(() => {
        const localItemsCount = refCryptoListTable.value ? refCryptoListTable.value.localItems.length : 0
        return {
            from: perPage.value * (currentPage.value - 1) + (localItemsCount ? 1 : 0),
            to: perPage.value * (currentPage.value - 1) + localItemsCount,
            of: totalRows.value,
        }
    })


    const listCrypto = computed(() => {
       return fetchCrypto;
    })


    const refetchData = () => {
        isLoading.value = true
        refCryptoListTable.value.refresh()
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

    const fetchCrypto = (ctx, callback) => {
        axios.post('setting/crypto/wallets', {
                search: searchQuery.value,
                perPage: perPage.value,
                page: currentPage.value,
                sortBy: sortBy.value,
                sortDesc: isSortDirDesc.value,
            })
            .then(response => {
                const {lists, total,sum,fee} = response.data

                callback(lists)
                totalRows.value = total;
                isLoading.value = false;
            })
            .catch((err) => {
                console.log(err)
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
        fetchCrypto,
        listCrypto,
        tableColumns,
        perPage,
        currentPage,
        totalRows,
        dataMeta,
        perPageOptions,
        searchQuery,
        sortBy,
        isSortDirDesc,
        refCryptoListTable,


        resolveStatusVariant,
        refetchData,
    }
}
