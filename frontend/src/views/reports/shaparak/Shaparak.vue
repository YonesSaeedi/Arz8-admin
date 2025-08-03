<template>

    <b-card>
        <b-card-body>
            <b-row>
                <b-col cols="12" md="3" class="mb-md-0 mb-2" >
                    <b-form-group>
                        <label>از تاریخ</label>
                        <b-input-group>
                            <b-form-input
                                placeholder="از تاریخ"
                                v-model="dateStart"
                                id="registeryDateStart" name="registeryDateStart"
                            />
                            <b-input-group-append is-text>
                                <feather-icon icon="CalendarIcon" class="cursor-pointer" @click="showDatePickerStart=true"/>
                                <date-picker v-model="dateStart" :show="showDatePickerStart" @close="showDatePickerStart=false" :auto-submit="true" color="#7367f0"  type="datetime" :editable="true"
                                             :locale="localeHas" :locale-config="localeConfig" :format="(localeHas=='fa')?'jYYYY/jMM/jDD HH:mm' : 'YYYY-MM-DD HH:mm'" element="registeryDateStart">
                                </date-picker>
                            </b-input-group-append>
                        </b-input-group>
                    </b-form-group>
                </b-col>
                <b-col cols="12" md="3" class="mb-md-0 mb-2">
                    <b-form-group>
                        <label>تا تاریخ</label>
                        <b-input-group>
                            <b-form-input
                                placeholder="تا تاریخ"
                                v-model="dateStop"
                                id="registeryDateStop" name="registeryDateStop"
                            />
                            <b-input-group-append is-text>
                                <feather-icon icon="CalendarIcon" class="cursor-pointer" @click="showDatePickerStop=true"/>
                                <date-picker v-model="dateStop" :show="showDatePickerStop" @close="showDatePickerStop=false" :auto-submit="true" color="#7367f0"  type="datetime" :editable="true"
                                             :locale="localeHas" :locale-config="localeConfig" :format="(localeHas=='fa')?'jYYYY/jMM/jDD HH:mm' : 'YYYY-MM-DD HH:mm'" element="registeryDateStop">
                                </date-picker>
                            </b-input-group-append>
                        </b-input-group>
                    </b-form-group>
                </b-col>
            </b-row>
        </b-card-body>

        <b-row>
            <b-col cols="12" md="6" class="p-2" >
                <b-button block
                          size="lg"
                          v-ripple.400="'rgba(255, 255, 255, 0.15)'"
                          variant="info"
                          class="mr-2 d-flex align-items-center justify-content-center"
                          type="submit"
                          :disabled="isLoading[0]"
                          @click="downloadCsv(1)"
                >
                    <div>دانلود فایل تراکنش های ریالی</div>
                    <div class="line-height-0 ml-25"><b-spinner v-if="isLoading[0]" small></b-spinner></div>
                </b-button>
            </b-col>
            <b-col cols="12" md="6" class="p-2" >
                <b-button block
                          size="lg"
                          v-ripple.400="'rgba(255, 255, 255, 0.15)'"
                          variant="info"
                          class="mr-2 d-flex align-items-center justify-content-center"
                          type="submit"
                          :disabled="isLoading[1]"
                          @click="downloadCsv(2)"
                >
                    <div>دانلود فایل تراکنش های رمزارز</div>
                    <div class="line-height-0 ml-25"><b-spinner v-if="isLoading[1]" small></b-spinner></div>
                </b-button>
            </b-col>
            <b-col cols="12" md="6" class="p-2" >
                <b-button block
                          size="lg"
                          v-ripple.400="'rgba(255, 255, 255, 0.15)'"
                          variant="info"
                          class="mr-2 d-flex align-items-center justify-content-center"
                          type="submit"
                          :disabled="isLoading[2]"
                          @click="downloadCsv(3)"
                >
                    <div>دانلود فایل سفارشات</div>
                    <div class="line-height-0 ml-25"><b-spinner v-if="isLoading[2]" small></b-spinner></div>
                </b-button>
            </b-col>
            <b-col cols="12" md="6" class="p-2" >
                <b-button block
                          size="lg"
                          v-ripple.400="'rgba(255, 255, 255, 0.15)'"
                          variant="info"
                          class="mr-2 d-flex align-items-center justify-content-center"
                          type="submit"
                          :disabled="isLoading[3]"
                          @click="downloadCsv(4)"
                >
                    <div>دانلود فایل دارایی کاربران</div>
                    <div class="line-height-0 ml-25"><b-spinner v-if="isLoading[3]" small></b-spinner></div>
                </b-button>
            </b-col>
        </b-row>
    </b-card>

</template>

<script>
import {
    BButton,
    BCard,
    BCardBody,
    BCol,
    BFormGroup,
    BFormInput,
    BInputGroup,
    BInputGroupAppend,
    BRow, BSpinner
} from "bootstrap-vue";
import VuePersianDatetimePicker from 'vue-persian-datetime-picker'
import BCardActions from "@core/components/b-card-actions/BCardActions.vue";

import jalaliMmoment from "jalali-moment";
import Ripple from "vue-ripple-directive";
import axiosIns from "@/libs/axios";
import ToastificationContent from "@core/components/toastification/ToastificationContent.vue";
const NowDate = jalaliMmoment()


export default {
    name: "Shaparak",
    components: {
        BSpinner, BButton,
        BCardActions,
        BFormGroup, BInputGroup, BInputGroupAppend, BRow, BCol, BFormInput, BCardBody,BCard,
        datePicker: VuePersianDatetimePicker,
    },
    directives: {
        Ripple,
    },
    data () {
        return {
            dateStart:jalaliMmoment().subtract(1, 'jDay').format('jYYYY/jMM/jDD 00:00'),
            dateStop:jalaliMmoment().format('jYYYY/jMM/jDD 00:00'),
            showDatePickerStart: false,
            showDatePickerStop: false,
            localeConfig:{
                fa: {
                    dow: 6,     //  first day of week
                    dir: 'rtl',
                    displayFormat: 'jYYYY/jMM/jDD'
                },
                en: {
                    dow: 0,
                    dir: 'ltr',
                    displayFormat: 'YYYY-MM-DD'
                }
            },
            isLoading: [false,false,false,false]
        }
    },
    methods: {
        downloadCsv(model = 1) {
            this.$set(this.isLoading, model - 1, true);


            axiosIns.post('/reports/shaparak/tbl'+ model +'-download-csv',{
                dateStart: this.dateStart,
                dateStop: this.dateStop,
            }, {
                responseType: 'blob',
            })
                .then(response => {
                    const blob = new Blob([response.data], { type: 'text/csv' });

                    const disposition = response.headers['content-disposition'];
                    const match = disposition && disposition.match(/filename="?([^"]+)"?/);
                    const filename = match ? match[1] : 'user_balances.csv';

                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = filename;
                    link.click();
                    URL.revokeObjectURL(link.href);
                    this.$set(this.isLoading, model - 1, false);
                })
                .catch(() => {
                    this.$toast({
                        component: ToastificationContent,
                        props: {
                            title: 'خطا در دریافت فایل',
                            icon: 'AlertTriangleIcon',
                            variant: 'danger',
                        },
                    });
                    this.$set(this.isLoading, model - 1, false);
                });
        }
    }

}
</script>

<style scoped>

</style>
