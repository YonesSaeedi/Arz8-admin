<template>
<div id="ticket-single">
    <b-row v-if="ticket">
        <b-col lg="3" sm="6">
            <statistic-card-vertical
                color="warning"
                icon="CalendarIcon"
                :statistic="ticket.created"
                statistic-title="تاریخ ایجاد"
            />
        </b-col>
        <b-col lg="3" sm="6">
            <statistic-card-vertical
                :color="`${resolveStatusVariant(ticket.status)}`"
                :colorText="`${resolveStatusVariant(ticket.status)}`"
                icon="CodesandboxIcon"
                :statistic="$t(ticket.status)"
                statistic-title="وضعیت"
            />
        </b-col>

        <b-col lg="3" sm="6">
            <statistic-card-vertical
                color="primary"
                icon="ClockIcon"
                :statistic="ticket.updated"
                statistic-title="آخرین برورسانی"
            />
        </b-col>
        <b-col lg="3" sm="6">
            <statistic-card-vertical
                color="success"
                icon="MessageSquareIcon"
                :statistic="ticketMessage.length"
                statistic-title="تعداد پیام ها"
            />
        </b-col>
    </b-row>

    <h4 class="mb-2 font-weight-bolder" v-if="ticket">
        موضوع: {{ticket.title}}
    </h4>

    <b-alert class="w-100 py-1" show variant="info" v-if="ticket && ticket.id_order">
        <b-link
            :to="{ name: 'order-view', params: { id: ticket.id_order } }"
            class="font-weight-bold d-block text-nowrap text-dark"
        >
        این تیکت مربوط به سفارش با شناسه #{{ticket.id_order}} یباشد.
        </b-link>
    </b-alert>

    <b-card v-if="ticketMessage"
        class="chat-widget"
        no-body
    >
        <b-card-header>
            <div class="d-flex align-items-center">
                <b-avatar
                    size="34"
                    :text="avatarText(user.name_display)"
                    :src="user.avatar"
                    class="mr-50 badge-minimal"
                    badge
                    badge-variant="success"
                />
                <h5 class="mb-0">
                    <b-link
                        :to="{ name: 'user-single', params: { id: user.id } }"
                        class="font-weight-bold d-block text-nowrap text-dark"
                    >
                    {{user.name?(user.name+' '+user.family):user.name_display}}
                    </b-link>
                </h5>
            </div>
            <b-dropdown right size="lg" variant="link" toggle-class="text-decoration-none px-0 text-dark" no-caret>
                <template #button-content>
                    <feather-icon icon="MoreVerticalIcon" size="18"/>
                </template>
                <b-dropdown-item class="text-danger" @click="remove()" href="#"><feather-icon icon="TrashIcon"/> حذف</b-dropdown-item>
                <b-dropdown-item @click="close()" href="#">بستن تیکت</b-dropdown-item>
            </b-dropdown>
        </b-card-header>

        <section class="chat-app-window">
            <!-- User Chat Area -->
            <vue-perfect-scrollbar
                ref="refChatLogPS"
                :settings="perfectScrollbarSettings"
                class="user-chats scroll-area"
            >
                <div class="chats">
                    <div
                        v-for="(msg, index) in ticketMessage"
                        :key="msg.id"
                        class="chat"
                        :class="{'chat-left': msg.author === 'admin'}"
                    >
                        <div class="chat-avatar">
                            <b-avatar
                                v-if="msg.author !== 'admin'"
                                size="36"
                                class="avatar-border-2 box-shadow-1"
                                :text="avatarText(user.name_display)"
                                :variant="`light-primary`"
                                :src="user.avatar "
                                :to="{   name: 'user-single', params: { id: user.id }}"
                            />
                            <img v-else class="mt-50" width="36px" :src="require('@/assets/images/logo/logo.png')">
                        </div>
                        <div class="chat-body">
                            <div class="chat-content">
                                <p style="white-space: pre-wrap;">{{ msg.message }}</p>
                                <p class="font-small-1 mt-50"><span v-if="msg.author === 'admin'">{{ msg.admin }} - </span>{{ msg.time }}</p>
                            </div>
                            <div class="chat-content cursor-pointer" v-if="msg.file" @click="srcImage=msg.src;popupFile=true;">
                                <b-skeleton-img v-if="msg.src === null" no-aspect height="180px" width="300px"></b-skeleton-img>
                                <img v-else :src="msg.src"/>
                            </div>
                        </div>
                    </div>
                </div>
            </vue-perfect-scrollbar>

            <div class="relative">
                <b-button class="msg-pattern" size="sm" variant="dark" :disabled="isLoading" @click="patternModal=true">
                    <span class="mr-25 align-middle">الگو های آماده</span>
                    <feather-icon icon="MessageSquareIcon" size="12"/>
                </b-button>
            </div>

            <!-- Message Input -->
            <!-- BODY -->
            <validation-observer #default="{ handleSubmit }" ref="refFormObserver">
                <!-- Form -->
                <b-form class="p-2" @submit.prevent="handleSubmit(onSubmit)" autocomplete="off" ref="form">
                    <validation-provider #default="{ errors }" rules="required">
                        <b-input-group class="input-group-merge form-send-message mr-1">
                            <b-form-textarea
                                v-model="msg"
                                :state="errors.length > 0 ? false:null"
                                placeholder="پیغام خود را درج کنید"
                            />
                        </b-input-group>
                    </validation-provider>
                    <b-row>
                        <b-col cols="12" md="4">
                            <b-form-group class="mt-1">
                                <b-form-file id="file"
                                             v-model="file" accept="image/*"
                                             placeholder="فایل را انتخاب کنید(jpg,png)"
                                             drop-placeholder="Drop file here..."
                                />
                            </b-form-group>
                        </b-col>
                        <b-col cols="12" md="8" class="text-right">
                            <b-button class="mt-md-1 ml-auto d-flex align-items-center justify-content-center"
                                      variant="primary" type="submit" ref="sendMsg" :disabled="isLoading">
                                <div>ارسال</div>
                                <div class="line-height-0 ml-25"><b-spinner v-if="isLoading" small></b-spinner></div>
                            </b-button>
                        </b-col>
                    </b-row>
                    <b-progress :value="uploadPercentage" v-if="uploadPercentage > 0" :max="100" show-progress animated></b-progress>
                </b-form>
            </validation-observer>
        </section>
    </b-card>

    <div v-if="!accessUserLogin['tickets']['single'] && activeUserInfo.role !== 'admin'">
        <NotAccessed/>
    </div>

    <b-modal v-model="popupFile" size="xl" scrollable >
        <img :src="srcImage" v-if="srcImage" style="width: 100%">
        <template #modal-footer>
            <div class="w-100">
                <b-button
                    variant="primary"
                    size="sm"
                    class="float-right"
                    @click="popupFile=false"
                >
                    بستن
                </b-button>
            </div>
        </template>
    </b-modal>

    <b-modal v-model="patternModal" scrollable title="الگوهای آماده">
        <b-row>
            <b-col cols="12" md="12">
                <b-input-group>
                    <b-form-input
                        v-model="searchPattern"
                        placeholder="جستجو ..."
                    />
                </b-input-group>
            </b-col>
            <b-col cols="12" md="12" class="mt-1">
                <b-form-radio-group
                    id="user-status-options"
                    v-model="pattern" v-if="!favoriteLoading"
                    stacked
                >
                    <!-- :options="userStatusOptions" -->
                    <b-form-radio
                        v-for="(option,key) in patternsFillter"
                        :key="key"
                        :value="option.msg"
                        class="py-50 mt-25 w-100 overflow-hidden" :class="pattern===option.msg?'border-right-primary border-bottom-primary':'border-right border-bottom'"
                    >
                        <h5>{{option.title}}</h5>
                        <div class="mb-0 msg-pattern">{{ option.msg }}</div>
                        <div class="trash-pattern text-danger" v-if="activeUserInfo.role === 'admin' || !option.general">
                            <feather-icon
                                v-if="removePatternLoadnig===null"
                                icon="TrashIcon"
                                class="cursor-pointer"
                                @click="removePattern(option)"
                            />
                            <b-spinner v-if="removePatternLoadnig!==null && (removePatternLoadnig.title === option.title && removePatternLoadnig.msg === option.msg)" small></b-spinner>
                        </div>
                        <div class="favorite-pattern" :class="option.favorite&&'text-primary'">
                            <feather-icon
                                icon="StarIcon"
                                class="cursor-pointer"
                                @click="favoritePattern(option)"
                            />
                        </div>
                    </b-form-radio>
                </b-form-radio-group>
            </b-col>
        </b-row>
        <template #modal-footer>
            <div class="w-100">
                <b-button variant="success" class="float-right mr-50" size="sm" @click="patternSend(true)">
                    ارسال
                </b-button>
                <b-button variant="dark" class="float-right mr-50" size="sm"  @click="patternSend(false)">
                    جایگذاری
                </b-button>
                <b-button variant="info" class="float-right mr-50" size="sm" @click="addPatternModal=true">
                    افزودن الگو
                </b-button>
            </div>
        </template>
    </b-modal>

    <b-modal v-model="addPatternModal" scrollable title="الگوهای آماده">
        <validation-observer #default="{ handleSubmit }" ref="refFormAddPattern">
            <b-form @submit.prevent="handleSubmit(addPattern)" autocomplete="off" ref="formAddPattern">
                <validation-provider #default="{ errors }" rules="required">
                    <b-input-group>
                        <b-form-input
                            v-model="titlePattern"
                            :state="errors.length > 0 ? false:null"
                            placeholder="عنوان الگو را درج کنید"
                        />
                    </b-input-group>
                </validation-provider>
                <validation-provider #default="{ errors }" rules="required">
                    <b-input-group class="mt-1">
                        <b-form-textarea
                            v-model="msgPattern"
                            :state="errors.length > 0 ? false:null"
                            placeholder="متن الگو را درج کنید"
                        />
                    </b-input-group>
                </validation-provider>
                <b-form-group class="mt-1" v-if="activeUserInfo.role === 'admin'">
                    <b-form-select
                        id="type"
                        v-model="patternGlobal"
                        :options="[{'text':'الگو فقط برای خودم','value':false},{'text':'الگو برای همه ادمین ها','value':true}]"
                        autofocus
                        trim
                    />
                </b-form-group>
                <b-button class="d-none" type="submit" ref="submitAddPattern"></b-button>
            </b-form>
        </validation-observer>
        <template #modal-footer>
            <div class="w-100">
                <b-button variant="dark" class="float-right mr-50" size="sm" :disabled="patternIsLoading" @click="addPatternModal=false">
                    بستن
                </b-button>
                <b-button variant="info" class="float-right mr-50" :disabled="patternIsLoading" size="sm" @click="$refs.submitAddPattern.click()">
                    افزودن الگو
                </b-button>
            </div>
        </template>
    </b-modal>
</div>
</template>

<script>
    import BCardActions from '@core/components/b-card-actions/BCardActions.vue'
    import {
        BCard, BCardHeader, BBadge, BCollapse, BLink, BCardBody, BRow, BCol, BInputGroup, BInputGroupAppend, BFormGroup,
        BAlert,BFormInput,BFormFile,BForm,BFormTextarea,BFormSelect, BTable, BButton,BAvatar,BSkeletonImg,BModal,BSpinner,BProgress,
        BDropdown,BDropdownItem,BFormRadio,BFormRadioGroup
    } from 'bootstrap-vue'
    import vSelect from 'vue-select'
    import axiosIns from "@/libs/axios";
    import ToastificationContent from "@core/components/toastification/ToastificationContent";
    import Table from "@/views/vuexy/table/bs-table/Table";
    import NotAccessed from "@/views/vuexy/pages/miscellaneous/NotAccessed";
    import {avatarText} from '@core/utils/filter'
    import VuePerfectScrollbar from 'vue-perfect-scrollbar'
    import {ValidationProvider, ValidationObserver} from 'vee-validate'
    import {required} from '@validations'
    import StatisticCardVertical from '@core/components/statistics-cards/StatisticCardVertical.vue'

    export default {
        data () {
            return {
                msg:'',
                file:null,

                pattern:null,
                patterns:null,
                patternModal:false,
                addPatternModal:false,
                searchPattern:'',
                titlePattern:'',
                msgPattern:'',
                patternGlobal:false,
                patternIsLoading:false,
                removePatternLoadnig:null,
                favoriteLoading:false,

                isLoading: false,
                uploadPercentage:0,
                popupFile:false,
                srcImage:null,
                fileIds:[],// for not duplicate download
                ticket: null,
                ticketMessage: null,
                user: null,
                visible: false,
                perfectScrollbarSettings: {
                    maxScrollbarLength: 150,
                    wheelPropagation: false,
                },

            }
        },
        components: {
            Table,
            BTable,
            BLink,
            BCard,
            BAlert,
            BBadge,
            BRow,
            BCol,
            BCardActions,
            BCardHeader,
            BCardBody,
            BCollapse,
            BButton,
            BAvatar,
            BInputGroup,BFormGroup, BInputGroupAppend,BFormInput,BFormFile,BForm,BFormTextarea,
            BSkeletonImg,BModal,BSpinner,BProgress,BDropdown,BDropdownItem,BFormSelect,BFormRadio,BFormRadioGroup,
            vSelect,NotAccessed,
            VuePerfectScrollbar,
            ValidationProvider, ValidationObserver,
            StatisticCardVertical
        },
        methods:{
            async getFile(msg) {
                var arrayMsg = this.fileIds.find(x => x.id === msg.id);
                if(arrayMsg){
                    msg.src = arrayMsg.src;return
                }
                axiosIns.get('/image/' + msg.file, {responseType: 'blob'})
                .then(response => {
                    let reader = new FileReader();
                    reader.readAsDataURL(response.data);
                    reader.onload = () => {
                        msg.src = reader.result;
                        this.fileIds.push({ 'id' : msg.id,'src':msg.src });
                    }
                })
            },
            onSubmit(){
                this.$refs.refFormObserver.validate().then(success => {
                    if (success) {
                        this.isLoading = true;
                        var imagefile = document.querySelector('#file');
                        var formData = new FormData();
                        if(imagefile.files[0])
                            formData.append("file", imagefile.files[0]);
                        formData.append("message", this.msg);
                        axiosIns.post('/tickets/'+this.ticket.id+'/new',formData,{
                            onUploadProgress: function( progressEvent ) {
                                this.uploadPercentage = parseInt( Math.round( ( progressEvent.loaded / progressEvent.total ) * 100 ));
                            }.bind(this)
                        })
                        .then(response => {
                            if(response.data.status == true){
                                this.$toast({
                                    component: ToastificationContent,
                                    props: {title: 'موفق!', text: response.data.msg, icon: 'CheckIcon', variant: 'success',},
                                })
                                this.isLoading = false;
                                this.getTicket(this.ticket.id);
                                this.$refs.form.reset()
                            }else{
                                this.$toast({
                                    component: ToastificationContent,
                                    props: {title: 'خطا!', text: response.data.msg, icon: 'AlertTriangleIcon', variant: 'danger',},
                                })
                                this.isLoading = false;
                            }
                            this.uploadPercentage = 0;
                        })
                        .catch((error) => {
                            this.errorFetching();
                            this.isLoading = false;
                        })
                    }
                })
            },
            getTicket(id){
                axiosIns.post('tickets/info/'+id)
                    .then(response => {
                        this.ticket = response.data.ticket
                        document.title = this.ticket.title
                        this.user = response.data.user
                        this.patterns = response.data.pattern
                        response.data.ticket_message.map((msg)=>{
                            msg.src = null
                        })
                        this.ticketMessage = response.data.ticket_message
                        this.ticketMessage.map((msg)=>{
                            if(msg.file)
                                this.getFile(msg)
                        })
                        this.$nextTick(() => {
                            this.psToBottom();
                        })
                        this.getGeneralInfoApi();
                    })
                    .catch((error) => {
                        console.log(error);
                        this.$toast({
                            component: ToastificationContent,
                            props: {
                                title: 'Error fetching data',
                                icon: 'AlertTriangleIcon',
                                variant: 'danger',
                            },
                        })
                    })
            },
            resolveStatusVariant(status){
                if (status === 'awaiting answer') return 'warning'
                if (status === 'answered') return 'success'
                if (status === 'closed') return 'dark'
                return 'warning'
            },
            psToBottom() {
                const scrollEl = this.$refs.refChatLogPS.$el || this.$refs.refChatLogPS
                scrollEl.scrollTop = scrollEl.scrollHeight
            },
            remove(){
                this.$swal({
                    title: 'از حذف تیکت اطمینان دارید؟',
                    text: 'با حذف تیکت تمامی تصاویر و پیام ها پاک میشوند.',
                    icon: 'warning',
                    showCancelButton: true,
                    showLoaderOnConfirm: true,
                    confirmButtonText: 'حذف شود',
                    customClass: {
                        confirmButton: 'btn px-2 btn-danger',
                        cancelButton: 'btn btn-outline-dark ml-1',
                    },
                    preConfirm: () => {
                        return  axiosIns.post('tickets/remove/'+this.ticket.id)
                            .then(response => {
                                return response;
                            })
                            .catch(() => {
                                this.errorFetching();
                            })
                    },
                    allowOutsideClick: () => false
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (result.value.data.status == true) {
                            this.getGeneralInfoApi();
                            this.$router.push('/tickets')
                            this.$swal({
                                icon: 'success',
                                title: 'موفق!',
                                text: result.value.data.msg,
                                confirmButtonText: 'باشه'
                            })
                        } else {
                            this.$swal({
                                icon: 'error',
                                title: 'ناموفق!',
                                text: result.value.data.msg,
                                confirmButtonText: 'باشه'
                            })
                        }
                    }
                })
            },
            close(){
                this.$swal({
                    title: 'از بستن تیکت اطمینان دارید؟',
                    text: 'با بستن تیکت کاربر امکان ارسال پیام در این تیکت را ندارد. ضمنا همه تیکت های باز بعد از یک هفته از آخرین بروزرسانی بسته میشود.',
                    icon: 'question',
                    showCancelButton: true,
                    showLoaderOnConfirm: true,
                    buttonsStyling: false,
                    preConfirm: () => {
                        return  axiosIns.post('tickets/close/'+this.ticket.id)
                            .then(response => {
                                return response;
                            })
                            .catch(() => {
                                this.errorFetching();
                            })
                    },
                    allowOutsideClick: () => false
                }).then((result) => {
                    if (result.value.data.status == true){
                        this.getTicket(this.ticket.id)
                        this.getGeneralInfoApi();
                        this.$swal({icon: 'success',title: 'موفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                    }else{
                        this.$swal({icon: 'error',title: 'ناموفق!',text: result.value.data.msg,confirmButtonText: 'باشه'})
                    }
                })
            },
            addPattern(){
                this.$refs.refFormAddPattern.validate().then(success => {
                    this.patternIsLoading = true
                    if (success) {
                        axiosIns.post('tickets/pattern',{
                            title: this.titlePattern,
                            msg: this.msgPattern,
                            global: this.patternGlobal
                        })
                        .then(response => {
                            if (response.data.status == true){
                                this.$swal({icon: 'success',title: 'موفق!',text: response.data.msg,confirmButtonText: 'باشه'})
                                this.addPatternModal = false;
                                this.titlePattern = '';
                                this.msgPattern = '';
                                this.getTicket(this.ticket.id)
                            }else{
                                this.$swal({icon: 'error',title: 'ناموفق!',text: response.data.msg,confirmButtonText: 'باشه'})
                            }
                            this.patternIsLoading = false
                        })
                        .catch(() => {
                            this.patternIsLoading = false
                            this.errorFetching();
                        })
                    }
                })
            },
            removePattern(pattern){
                this.removePatternLoadnig = pattern
                axiosIns.post('tickets/pattern/remove',pattern)
                .then(response => {
                    if (response.data.status == true){
                        this.$swal({icon: 'success',title: 'موفق!',text: response.data.msg,confirmButtonText: 'باشه'})
                        this.getTicket(this.ticket.id)
                    }else{
                        this.$swal({icon: 'error',title: 'ناموفق!',text: response.data.msg,confirmButtonText: 'باشه'})
                    }
                    this.removePatternLoadnig = null;
                })
                .catch(() => {
                    this.removePatternLoadnig = null
                    this.errorFetching();
                })
            },
            patternSend(send){
                if(!this.pattern){
                    this.$toast({
                        component: ToastificationContent,
                        props: {
                            title: 'لطفا یک الگو انتخاب کنید!',
                            icon: 'AlertTriangleIcon',
                            variant: 'danger',
                        },
                    })
                }else {
                    this.msg = this.pattern;
                    if(send)
                        this.$refs.sendMsg.click()
                    this.patternModal = false
                }
            },
            favoritePattern(pattern){
                this.favoriteLoading = true
                var favoritePattern = localStorage.getItem('favoritePattern')
                if(favoritePattern)
                    favoritePattern = JSON.parse(favoritePattern)
                else
                    favoritePattern = [];

                var isPattern = favoritePattern.indexOf(pattern.title)
                if(isPattern > -1)
                    favoritePattern.splice(isPattern, 1)
                else
                    favoritePattern.push(pattern.title)
                //console.log(favoritePattern);
                localStorage.setItem('favoritePattern', JSON.stringify(favoritePattern))
                this.$nextTick(() => {
                    this.favoriteLoading = false
                })
            }
        },
        created() {
            if(this.accessUserLogin['tickets']['single'] || this.activeUserInfo.role === 'admin')
                this.getTicket(this.$router.currentRoute.params.id)
        },
        computed:{
            patternsFillter(){
                var favoritePattern = localStorage.getItem('favoritePattern')
                favoritePattern = JSON.parse(favoritePattern)
                if(favoritePattern && this.patterns){
                    this.patterns.map((el)=>{
                        var index = favoritePattern.indexOf(el.title)
                        if(index > -1)
                            el.favorite = true
                        else
                            el.favorite = false
                    })
                }


                var patterns = []
                if(this.patterns && this.patterns.length >= 1) {
                    patterns = this.patterns.filter((el) => {
                        return JSON.stringify(el).indexOf(this.searchPattern) !== -1 || JSON.stringify(el).indexOf(this.searchPattern.toUpperCase()) !== -1
                    });
                }

                patterns.sort((a, b) =>  b.favorite - a.favorite);
                if(!this.favoriteLoading)
                    return patterns;
                return []
            }
        },
        setup(props) {
            return {avatarText}
        }
    }
</script>

<style lang="scss">
@import "~@core/scss/base/pages/app-chat.scss";
@import "~@core/scss/base/pages/app-chat-list.scss";

#ticket-single{
    .chat-widget .chat-app-window .user-chats{
        height: 500px;
    }
    .chat-content img{
        max-width: 300px;
    }
    .relative{
        position: relative;
    }
    .msg-pattern{
        position: absolute;
        top: -35px;
        left:5px;
    }
    @media screen and (max-width: 768px) {
        .msg-pattern{
            right: 5px;
            left:unset;
        }
    }
}
.trash-pattern{
    position: absolute;
    right: 4px;
    top: 2px;
}
.favorite-pattern{
    position: absolute;
    right: 25px;
    top: 2px;
}
.msg-pattern {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>
