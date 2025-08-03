<template>
    <div>


        <network-new
            :is-add-new-sidebar-active.sync="isAddNewSidebarActive"
            @refetch-data="getNetwork()"
        />

        <b-card>
            <template #header>
                <div class="d-flex w-100">
                    <h4 class="card-title">لیست شبکه های رمز ارز</h4>
                    <div class="ml-auto">
                        <b-button
                            variant="primary"
                            @click="isAddNewSidebarActive = true"
                        >
                            <span class="text-nowrap">افزودن شبکه جدید</span>
                        </b-button>
                    </div>
                </div>
            </template>

            <b-table striped hover :fields="fields" :items="networks">
                    <!-- Column: name -->
                    <template #cell(name)="data">
                        <b-form-input :id="'name'+data.item.id" :value=" data.item.name " placeholder="نام شبکه مانند TRC20"
                                      dir="ltr" class="text-left" :state="data.item.nameState"/>
                    </template>

                    <!-- Column: nameFa -->
                    <template #cell(nameFa)="data">
                        <b-form-input :id="'nameFa'+data.item.id" :value="data.item.locale['fa'].name" placeholder="نام فارسی مانند ترون"
                                      :state="data.item.nameFaState"/>
                    </template>

                    <!-- Column: symbol -->
                    <template #cell(symbol)="data">
                        <b-form-input :id="'symbol'+data.item.id" :value="data.item.symbol" placeholder="نماد مانند TRX" disabled
                                      :state="data.item.symbolState" dir="ltr" class="text-left"/>
                    </template>

                    <!-- Column: Exchange -->
                    <template #cell(exchange)="data">
                        <div class="d-flex align-items-center justify-content-center">
                            <b-form-select
                                :value="data.item.exchange"
                                :id="'exchange'+data.item.id"
                                :options="[{text:'بایننس',value:'binance'},{text:'کوینکس',value:'coinex'},{text:'کوکوین',value:'kucoin'},{text:'دستی',value:'manual'}]"
                            />
                            <div @click="data.toggleDetails" class="ml-50">
                                <feather-icon v-if="!data.detailsShowing" icon="ChevronsDownIcon" size="18"/>
                                <feather-icon v-else icon="ChevronsUpIcon" size="18"/>
                            </div>
                        </div>
                    </template>
                    <template #row-details="data">
                        <b-row>
                            <b-col cols="12" md="8">
                                <b-form-input :id="'address_wallet'+data.item.id" :value="data.item.address_wallet" placeholder="آدرس کیف پول"
                                              dir="ltr" class="text-left mt-25"/>
                            </b-col>
                            <b-col cols="12" md="4">
                                <b-form-input :id="'address_tag_wallet'+data.item.id" :value="data.item.address_tag_wallet" placeholder="تگ آدرس کیف پول"
                                              dir="ltr" class="text-left mt-25"/>
                            </b-col>
                        </b-row>
                    </template>

                    <!-- Column: tag -->
                    <template #cell(tag)="data">
                        <b-form-select
                            :value="data.item.tag"
                            :id="'tag'+data.item.id"
                            :options="[{text:'دارای تگ',value:1},{text:'تگ ندارد',value:0}]"
                        />
                    </template>

                    <!-- Column: status -->
                    <template #cell(status)="data">
                        <b-form-select
                            :value="data.item.status"
                            :id="'status'+data.item.id"
                            :options="[{text:'فعال',value:1},{text:'غیرفعال',value:0}]"
                        />
                    </template>

                    <!-- Column: count coin -->
                    <template #cell(coins)="data">
                        <b-link
                            :to="{ name: 'cryptos', query: { network: data.item.symbol } }"
                            class="font-weight-bold d-block text-nowrap text-center"
                        >
                            <span>{{data.item.coins}}</span>
                        </b-link>
                    </template>

                    <!-- Column: Actions -->
                    <template #cell(actions)="data">
                        <div class="text-center" v-if="!data.item.isLoading">
                            <feather-icon class="text-primary mr-25" icon="EditIcon" size="20" @click="editNetwork(data.item.id)"/>
                            <feather-icon class="text-danger" icon="XIcon" size="20" @click="removeNetwork(data.item.id)"/>
                        </div>
                        <div class="text-center" v-else>
                            <div class="line-height-0 ml-25"><b-spinner small></b-spinner></div>
                        </div>
                    </template>

            </b-table>
        </b-card>
    </div>
</template>

<script>
    import axiosIns from "@/libs/axios";
    import BCardActions from '@core/components/b-card-actions/BCardActions.vue'
    import {
        BCard, BCardHeader, BBadge, BCollapse, BLink, BCardBody, BRow,BForm, BCol, BInputGroup, BInputGroupAppend, BFormGroup, BFormInput, BTable, BButton,BAlert,
        BInputGroupPrepend, BFormSelect,BFormFile, BFormRadioGroup, BTab,BTabs ,BSpinner, BAvatarGroup, BAvatar

    } from 'bootstrap-vue'
    import { ValidationProvider, ValidationObserver } from 'vee-validate'
    import {
        required,between
    } from '@validations'
    import BalanceList from "@/views/settings/cryptocurrency/crypto/balance-list/BalanceList";
    import ToastificationContent from "@core/components/toastification/ToastificationContent";
    import NetworkNew from './NetworkNew.vue'

    export default {
        name: "Network",
        components: {
            NetworkNew,
            BalanceList,
            BTable,
            BLink,
            BCard,
            BBadge,
            BRow,
            BCol,
            BCardActions,
            BCardHeader,
            BCardBody,
            BCollapse,
            BButton,
            BAlert,
            BInputGroup,BFormGroup, BInputGroupAppend,BFormInput,BFormFile,
            ValidationProvider, ValidationObserver,BInputGroupPrepend,BForm, BFormSelect,
            BFormRadioGroup,BTab,BTabs,BSpinner
        },
        data () {
            return {
                isAddNewSidebarActive: false,
                isLoading: false,
                fields:[
                    {key: 'name', label: 'نام'},
                    {key: 'nameFa', label: 'نام فارسی'},
                    {key: 'symbol', label: 'نماد'},
                    {key: 'tag', label: 'تگ'},
                    {key: 'exchange', label: 'نحوه استعلام آدرس'},
                    {key: 'status', label: 'وضعیت'},
                    {key: 'coins', label: 'تعداد ارزها'},
                    {key: 'actions', label: '#'},
                ],
                networks: [],
            }
        },
        methods:{
            getNetwork(){
                axiosIns.post('/setting/network/list')
                .then(response => {
                    response.data.map(item=>{
                        item.isLoading = false
                    })
                    this.networks = response.data;

                })
                .catch((error) => { console.log(error); this.errorFetching(); })
            },
            editNetwork(id){
                var index = this.networks.findIndex(i => i.id === id)

                var name = document.getElementById("name"+id).value;
                var nameFa = document.getElementById("nameFa"+id).value;
                var symbol = document.getElementById("symbol"+id).value;
                var tag = document.getElementById("tag"+id).value;
                var status = document.getElementById("status"+id).value;
                var exchange = document.getElementById("exchange"+id).value;
                if(document.getElementById("address_wallet"+id)) {
                    var address_wallet = document.getElementById("address_wallet" + id).value;
                    var address_tag_wallet = document.getElementById("address_tag_wallet" + id).value;
                }else {
                    var address_wallet = this.networks[index].address_wallet
                    var address_tag_wallet = this.networks[index].address_tag_wallet
                }

                if(name !== '' && nameFa !== '' && symbol !== '') {
                    this.networks[index].isLoading = true;
                    axiosIns.post('/setting/network/edit/' + id, {
                        name: name, nameFa: nameFa, symbol: symbol, tag: tag, status: status,exchange:exchange ,
                        address_wallet:address_wallet, address_tag_wallet:address_tag_wallet
                    })
                        .then(response => {
                            if (response.data.status == true) {
                                this.$toast({
                                    component: ToastificationContent,
                                    props: {
                                        title: 'موفق!',
                                        text: response.data.msg,
                                        icon: 'CheckIcon',
                                        variant: 'success',
                                    },
                                })
                                this.networks[index].isLoading = false;
                                this.getNetwork();
                            } else {
                                this.networks[index].isLoading = false;
                                this.$toast({
                                    component: ToastificationContent,
                                    props: {
                                        title: 'خطا!',
                                        text: response.data.msg,
                                        icon: 'AlertTriangleIcon',
                                        variant: 'danger',
                                    },
                                })
                            }
                        })
                        .catch((error) => {
                            console.log(error);
                            this.errorFetching();
                        })
                }else{
                    this.$swal({icon: 'warning',title: 'توجه!',text: 'لطفا پارامتر های خواسته شده را تکمیل نمایید.',confirmButtonText: 'باشه'})
                }
            },
            removeNetwork(id){
                var index = this.networks.findIndex(i => i.id === id)
                this.networks[index].isLoading = true;
                axiosIns.delete('/setting/network/remove/'+id,)
                .then(response => {
                    if(response.data.status == true){
                        this.$toast({
                            component: ToastificationContent,
                            props: {
                                title: 'موفق!',
                                text: response.data.msg,
                                icon: 'CheckIcon',
                                variant: 'success',
                            },
                        })
                        this.getNetwork()
                    }else{
                        this.$toast({
                            component: ToastificationContent,
                            props: {
                                title: 'خطا!',
                                text: response.data.msg,
                                icon: 'AlertTriangleIcon',
                                variant: 'danger',
                            },
                        })
                    }
                })
                .catch((error) => { console.log(error); this.errorFetching(); })
            }
        },
        created() {
            this.getNetwork();
        }
    }
</script>

<style scoped>

</style>
