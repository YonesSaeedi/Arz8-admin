<template>
    <div v-if="formData">

        <validation-observer
            v-if="accessUserLogin['setting']['payment-gateway'] || activeUserInfo.role === 'admin'"
            #default="{ handleSubmit }"
            ref="refFormGatewayObserver"
        >
            <!-- Form -->
            <b-form
                class="px-2"
                @submit.prevent="handleSubmit(onSubmit)"
                autocomplete="off"
            >


                <b-card title="تنظیمات درگاه پرداخت">
                    <p>
                        <strong>نکته 1:</strong>
                        اگر فقط یک درگاه فعال باشد آنگاه دیگر لیست درگاه ها به کاربر نمایش داده نمیشود و بصورت اتوماتیک به همان یک درگاه فعال هدایت میشود.
                    </p>
                    <p>
                        <strong>نکته 2:</strong>
                        نمیتوانید درگاهی را غیر فعال کنید که در حالت پیش فرض قرار گرفته باشد.
                    </p>

                    <b-row class="mt-1">
                        <b-col cols="6" class="mx-auto">
                            <validation-provider #default="{ errors }" name="وضعیت" rules="required">
                                <b-form-group label="درگاه پیش فرض" label-for="name">
                                    <b-form-select
                                        v-model="defaultGateway"
                                        :options="defaultOptions"
                                        :state="errors.length > 0 ? false:null"
                                    />
                                </b-form-group>
                            </validation-provider>
                        </b-col>
                        <hr class="w-100">

                        <b-col cols="12">
                            <b-row v-for="item in paymentGatewayList">
                                <b-col cols="2" class="d-flex align-items-center">
                                    <img :src="require('@/assets/images/payment-gateway/'+item.name+'.png')" class="mr-1" width="40px">
                                    <h5>{{$t(item.name)}}</h5>
                                </b-col>

                                <b-col cols="6">
                                    <validation-provider #default="{ errors }" rules="required">
                                        <b-form-group :label="'توکن '+$t(item.name)" label-for="tokan">
                                            <b-form-input
                                                v-model="paymentGatewayList[item.name].token"
                                                :state="errors.length > 0 ? false:null"
                                                placeholder="Token" class="text-center"
                                            />
                                        </b-form-group>
                                    </validation-provider>
                                </b-col>
                                <b-col cols="2">
                                    <validation-provider #default="{ errors }" name="وضعیت" rules="required">
                                        <b-form-group label="وضعیت" label-for="name">
                                            <b-form-select
                                                v-model="paymentGatewayList[item.name].status"
                                                :options="[{text:'فعال',value:1},{text:'غیرفعال',value:0}]"
                                                :state="errors.length > 0 ? false:null"
                                            />
                                        </b-form-group>
                                    </validation-provider>
                                </b-col>
                                <b-col cols="2">
                                    <validation-provider #default="{ errors }" name="وضعیت" rules="required">
                                        <b-form-group label="برداشت اتوماتیک" label-for="name">
                                            <b-form-select
                                                v-model="paymentGatewayList[item.name].withdraw"
                                                :options="[{text:'فعال',value:1},{text:'غیرفعال',value:0}]"
                                                :state="errors.length > 0 ? false:null"
                                            />
                                        </b-form-group>
                                    </validation-provider>
                                </b-col>
                                <hr class="w-100">
                            </b-row>

                        </b-col>

                    </b-row>
                </b-card>


                <!-- Form Actions -->
                <div class="my-2 w-50 mx-auto">
                    <b-button block
                              v-ripple.400="'rgba(255, 255, 255, 0.15)'"
                              variant="primary"
                              class="mr-2 d-flex align-items-center justify-content-center"
                              type="submit"
                              :disabled="formData.isLoading"
                    >
                        <div>ثبت تغییرات</div>
                        <div class="line-height-0 ml-25"><b-spinner v-if="formData.isLoading" small></b-spinner></div>
                    </b-button>
                </div>

            </b-form>
        </validation-observer>
        <div v-else>
            <NotAccessed/>
        </div>
    </div>
</template>

<script>
    import {
         BCard, BForm, BFormGroup, BFormInput,BFormSelect, BFormInvalidFeedback, BButton, BFormFile, BSpinner,BRow,BCol
    } from 'bootstrap-vue'
    import {ValidationProvider, ValidationObserver} from 'vee-validate'
    import {ref} from '@vue/composition-api'
    import {required, alphaNum, between} from '@validations'
    import Ripple from 'vue-ripple-directive'
    import {MODEL_EVENT_NAME} from "bootstrap-vue/src/mixins/form-radio-check";
    import ToastificationContent from "@core/components/toastification/ToastificationContent";
    import axiosIns from "@/libs/axios";
    import NotAccessed from "@/views/vuexy/pages/miscellaneous/NotAccessed";

    export default {
        props:['settings','paymentGatewayList'],
        components: {
            BCard,
            BForm,
            BFormGroup,
            BFormInput,
            BFormInvalidFeedback,
            BButton,
            BFormSelect,
            BFormFile,
            BSpinner,
            BRow,BCol,NotAccessed,

            // Form Validation
            ValidationProvider,
            ValidationObserver,
        },
        directives: {
            Ripple,
        },
        data() {
            return {
                defaultGateway: null,
                defaultOptions: [],
                formData:{

                },
                status: true,
            }
        },
        methods:{
            onSubmit(){
                this.status = true;
                this.$refs.refFormGatewayObserver.validate().then(success => {
                    if (success) {
                        Object.keys(this.paymentGatewayList).map(function(key, index) {
                            var gateway = this.paymentGatewayList[key];
                            if(gateway.status === 0 && this.defaultGateway === key){
                                this.$swal({icon: 'warning',title: 'توجه!',text: 'درگاه پیش فرض امکان غیر فعال کردن ندارد!',confirmButtonText: 'باشه'});
                                this.status = false;
                            }
                        },this);

                        if(this.status === false)
                            return false;

                        axiosIns.post('/setting/settings/gateway',{gateways:this.paymentGatewayList,defaultGateway:this.defaultGateway})
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
                            .catch((error) => {
                                this.errorFetching();
                            })
                    }
                })
            }
        },
        created() {
            //this.defaultGateway = this.paymentGatewayList.find(i => i.select === 1).name

            Object.keys(this.paymentGatewayList).map(function(key, index) {
                var gateway = this.paymentGatewayList[key];
                if(gateway.select === 1)
                    this.defaultGateway = gateway.name;
                this.defaultOptions.push({text:this.$i18n.t(key),value:gateway.name});
            },this);

            Object.keys(this.formData).map(function(key, index) {
                this.formData[key] = this.settings[key];
            },this)
        }
    }
</script>

<style scoped>

</style>
