<template>
    <div>
        <b-modal
            v-model="modalStatus"
            id="withdraw-modal" ref="withdraw-modal"
            title="جزئیات تسویه پایا"
            ok-title="ذخیره تغییرات"
            cancel-title="بستن"
            cancel-variant="outline-secondary"
        >

            <b-card-text>
                <div class="text-center my-2" v-if="!withdraw">
                    <b-spinner style="width: 3rem; height: 3rem;"/>
                </div>
                <b-row v-if="withdraw">
                    <b-col cols="12">
                        <strong>شناسه کارت: </strong>
                        #{{withdraw.id_cardbank}}
                    </b-col>
                    <b-col cols="12">
                        <strong>شماره کارت: </strong>
                        <span>{{withdraw.cardbank.card_number}}</span>
                    </b-col>
                    <b-col cols="6" v-if="withdraw.admin">
                        <strong>ادمین: </strong>
                        {{withdraw.admin.name}}
                    </b-col>
                    <b-col cols="12" dir="ltr" class="text-right mt-3">
                        <pre v-html="JSON.stringify(JSON.parse(withdraw.data), null, 4)"></pre>
                    </b-col>
                </b-row>
            </b-card-text>
            <template #modal-footer>
                <div class="w-100">
                    <b-button variant="outline-secondary" class="float-right" @click="modalStatus=false">
                        بستن
                    </b-button>
                </div>
            </template>
        </b-modal>

    </div>
</template>

<script>
    import {
        BModal, BButton, VBModal, BAlert, BCardText, BFormGroup, BFormInput, BListGroup, BListGroupItem,BRow,BCol,BForm,
        BInputGroup,BInputGroupPrepend,BInputGroupAppend,BSpinner, BBadge
    } from 'bootstrap-vue';

    import Ripple from "vue-ripple-directive";
    import BCardCode from "@core/components/b-card-code";
    import axiosIns from "@/libs/axios";
    import { ValidationProvider, ValidationObserver } from 'vee-validate'
    import {
        required, email, confirmed, password,
    } from '@validations'
    import ToastificationContent from "@core/components/toastification/ToastificationContent";

    export default {
        components: {
            ValidationProvider, ValidationObserver,
            BCardCode,
            BButton,
            BModal,
            BAlert,
            BCardText,BFormGroup,BFormInput,BListGroup,BListGroupItem,BRow,BCol,BForm,
            BInputGroup,BInputGroupPrepend,BInputGroupAppend,
            BSpinner, BBadge
        },
        directives: {
            'b-modal': VBModal,
            Ripple,
        },
        name: "CardBank",
        props:['id','modalShow'],
        data(){
            return {
                withdraw: null,
                modalStatus: false,
            }
        },
        methods:{
            getWithdraw(){
                axiosIns.post('withdraw/info/'+this.id) .then(response => {
                    this.withdraw = response.data.withdraw;
                })
                .catch(() => {
                    this.errorFetching();
                })
            },
        },
        watch:{
            modalShow(val){
               if(val){
                   this.getWithdraw()
                   this.modalStatus = true;
               }else {
                   this.modalStatus = false;
               }
            },
            modalStatus(val){
                if(!val){
                    this.withdraw = null;
                    this.$emit('modalUpdate', false)
                }
            }
        }
    }
</script>

<style lang="scss">
</style>
