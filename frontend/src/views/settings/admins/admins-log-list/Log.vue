<template>
    <div>
        <b-modal
            v-model="modalStatus"
            id="withdraw-modal" ref="withdraw-modal"
            title="جزئیات فعالیت ادمین"
            ok-title="ذخیره تغییرات"
            cancel-title="بستن"
            cancel-variant="outline-secondary"
        >

            <b-card-text>
                <div class="text-center my-2" v-if="!log">
                    <b-spinner style="width: 3rem; height: 3rem;"/>
                </div>
                <b-row v-if="log">
                    <b-col cols="12" dir="ltr" class="text-right">
                        <pre v-html="JSON.stringify(JSON.parse(log.data), null, 4)"></pre>
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
        props:['id','modalShow'],
        data(){
            return {
                log: null,
                modalStatus: false,
            }
        },
        methods:{
            getLog(){
                axiosIns.post('admins/logs/info/'+this.id) .then(response => {
                    this.log = response.data.log;
                })
                .catch(() => {
                    this.errorFetching();
                })
            },
        },
        watch:{
            modalShow(val){
               if(val){
                   this.getLog()
                   this.modalStatus = true;
               }else {
                   this.modalStatus = false;
               }
            },
            modalStatus(val){
                if(!val){
                    this.log = null;
                    this.$emit('modalUpdate', false)
                }
            }
        }
    }
</script>

<style lang="scss">
</style>
