import {ref, watch, computed} from '@vue/composition-api'
import axios from '../../../../../libs/axios'

// Notification
import {useToast} from 'vue-toastification/composition'
import ToastificationContent from '@core/components/toastification/ToastificationContent.vue'

export default function useUsersList(crypto) {
    // Use toast
    const toast = useToast()

    const refCryptoBalanceTable = ref(null)

    // Table Handlers
    const tableColumns = [
        {key: 'nameFamily', label: 'نام فامیل', sortable: true},
        {key: 'userLevel', label: 'سطح کاربر', sortable: true},
        {key: 'coin', label: 'ارز', sortable: false},
        {key: 'balance', label: 'موجودی', sortable: true},
        {key: 'balanceUsdt', label: 'موجودی دلاری', sortable: true},
        {key: 'balanceUsersToman', label: 'موجودی به تومان', sortable: true},
    ];

    const perPage = ref(25)
    const totalRows = ref(0)
    const currentPage = ref(1)
    const perPageOptions = [10, 25, 50, 100,1000]
    const searchQuery = ref('')
    const sortBy = ref('id')
    const isSortDirDesc = ref(true)



    const dataMeta = computed(() => {
        const localItemsCount = refCryptoBalanceTable.value ? refCryptoBalanceTable.value.localItems.length : 0
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
        refCryptoBalanceTable.value.refresh()
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
        axios.post('setting/crypto/balance/'+crypto.id, {
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
        refCryptoBalanceTable,

        refetchData,

    }
}
