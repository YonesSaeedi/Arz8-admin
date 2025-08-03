import {ref, watch, computed} from '@vue/composition-api'
import axios from '../../../../libs/axios'
import {title} from '@core/utils/filter'

// Notification
import {useToast} from 'vue-toastification/composition'
import ToastificationContent from '@core/components/toastification/ToastificationContent.vue'
import jalaliMmoment from "jalali-moment";

export default function useWageList() {
    // data start
    const NowDate = jalaliMmoment()
    var year =  NowDate.startOf('jMonth').subtract(0, 'jMonth');

    // Use toast
    const toast = useToast()

    const refCryptoListTable = ref(null)

    // Table Handlers
    const tableColumns = [
        {key: 'id_factor', label: 'شماره فاکتور', sortable: true},
        {key: 'nameFamily', label: 'نام', sortable: true},
        {key: 'mobile', label: 'موبایل', sortable: true},
        {key: 'type', label: 'نوع', sortable: true},
        {key: 'amount', label: 'مبلغ', sortable: true},
        {key: 'wage', label: 'کارمزد', sortable: true},
        {key: 'date', label: 'تاریخ ثبت', sortable: true},
        {key: 'actions', label: 'مشاهده فاکتور'},
    ];

    const perPage = ref(25)
    const totalRows = ref(0)
    const currentPage = ref(1)
    const perPageOptions = [10, 25, 50, 100,1000]
    const searchQuery = ref('')
    const sortBy = ref('id')
    const isSortDirDesc = ref(true)
    const dateStartFilter = ref(year.format('jYYYY/jMM/jDD'))
    const dateStopFilter = ref(null)
    const amountStartFilter = ref(null)
    const amountStopFilter = ref(null)
    const statistic = ref([])
    const tableCalculate = ref(null)
    const dateStartPriod = ref(year.format('jYYYY/jMM/jDD'))
    const typeFilter = ref(null)
    const typeDepositFilter = ref(null)
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


    const refetchData = (cal = true) => {
        isLoading.value = true
        refCryptoListTable.value.refresh();

        if(cal)
            fetchTableCalculate();
    }

    var timer = null;
    watch([currentPage, perPage, searchQuery,dateStartFilter,dateStopFilter,amountStartFilter,amountStopFilter,typeFilter,typeDepositFilter], () => {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
        timer = setTimeout(() => {
            refetchData()
        }, 800);
    });

    watch([dateStartFilter,dateStopFilter,amountStartFilter,amountStopFilter,typeFilter,typeDepositFilter], () => {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
        timer = setTimeout(() => {
            refetchData()
        }, 800);
    });

    const fetchCrypto = (ctx, callback) => {
        axios.post('audit/factor', {
                search: searchQuery.value,
                perPage: perPage.value,
                page: currentPage.value,
                sortBy: sortBy.value,
                sortDesc: isSortDirDesc.value,
                dateStart: dateStartFilter.value,
                dateStop: dateStopFilter.value,
                amountStart: amountStartFilter.value,
                amountStop: amountStopFilter.value,
                type: typeFilter.value,
                typeDeposit: typeDepositFilter.value,
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

    const fetchStatistic = (ctx, callback) => {
        /*
        axios.post('audit/factor/statistic')
            .then(response => {
                statistic.value = response.data
            })
         */
    }

    const fetchTableCalculate = (ctx, callback) => {
        tableCalculate.value = null;
        axios.post('audit/factor/table-calculate',{
            dateStart: dateStartFilter.value,
            dateStop: dateStopFilter.value,
            amountStart: amountStartFilter.value,
            amountStop: amountStopFilter.value,
            type: typeFilter.value,
            typeDeposit: typeDepositFilter.value,
        })
        .then(response => {
            tableCalculate.value = response.data
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

        fetchStatistic,
        statistic,
        fetchTableCalculate,
        tableCalculate,

        // Extra Filters
        typeFilter,
        typeDepositFilter,
        dateStartFilter,dateStopFilter,
        amountStartFilter,amountStopFilter,
        dateStartPriod
    }
}
