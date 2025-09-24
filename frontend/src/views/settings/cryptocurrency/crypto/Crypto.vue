<template>
<div>
    <div v-if="accessUserLogin['setting-crypto']['single'] || activeUserInfo.role === 'admin'">


        <b-row v-if="statistic && statistic.balance && statistic.balance.balance">
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="UsersIcon"
                    color="info"
                    :statistic="statistic.userBalance.toLocaleString()"
                    statistic-title="کاربران موجودی"
                    id="userBalance"
                />
                <b-tooltip target="userBalance" variant="info">
                    کاربرانی که موجودی بیشتر از صفر دارند
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="ActivityIcon"
                    color="danger"
                    :statistic=" toFixFloat(parseFloat(statistic.allBalance.toFixed(crypto.percent))) "
                    statistic-title="موجودی کل"
                    id="allBalance"
                />
                <b-tooltip target="allBalance" variant="danger">
                    موجودی کل کاربران در پلتفرم
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="DiscIcon"
                    color="success"
                    :statistic="toFixFloat(parseFloat(statistic.allBalanceAvailable.toFixed(crypto.percent)))"
                    statistic-title="موجودی دسترس کل"
                    id="allBalanceAvailable"
                />
                <b-tooltip target="allBalanceAvailable" variant="success">
                    موجودی در دسترس کل کاربران در پلتفرم
                </b-tooltip>
            </b-col>


            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="CloudSnowIcon"
                    color="warning"
                    :classTitle="statistic && statistic.littleBalance>0 ?'text-success ltr':'text-danger ltr'"
                    :statistic="toFixFloat(parseFloat(statistic.littleBalance.toFixed(crypto.percent)))"
                    statistic-title="موجودی اندک"
                    id="littleBalance"
                />
                <b-tooltip target="littleBalance" variant="warning">
                    موجودی این ارز در قسمت موجودی های اندک
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="DivideIcon"
                    color="warning"
                    :statistic="toFixFloat(parseFloat(statistic.wageTrades.toFixed(crypto.percent)))"
                    statistic-title="کارمزد معاملات"
                    id="wageTrades"
                />
                <b-tooltip target="wageTrades" variant="warning">
                    موجودی ارز در قسمت کارمزد معاملات در حال حاضر
                </b-tooltip>
            </b-col>

            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="PlusIcon"
                    color="danger"
                    :statistic="settings && settings.coolwallet? toFixFloat(parseFloat(settings.coolwallet))  :'0'"
                    statistic-title="موجودی کول ولت ها"
                    id="allBalanceColl"
                />
                <b-tooltip target="allBalanceColl" variant="danger">
                    موجودی کول ولت ها
                </b-tooltip>
            </b-col>

            <b-col lg="3" sm="6"  v-for="(item, key) in statistic.balance.balance.binance">
                <statistic-card-horizontal
                    icon="HexagonIcon"
                    color="warning"
                    :statistic="toFixFloat(parseFloat(item.toFixed(crypto.percent)))"
                    :statistic-title="'موجودی بایننس'+key"
                    :id="'binance'+key"
                />
                <b-tooltip :target="'binance'+key" variant="warning">
                    موجودی این ارز در بایننس B{{key}} در حال حاضر
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6" v-for="(item, key) in statistic.balance.balance.coinex">
                <statistic-card-horizontal
                    icon="DropletIcon"
                    color="success"
                    :statistic="toFixFloat(parseFloat(item.toFixed(crypto.percent)))"
                    :statistic-title="'موجودی کوینکس'+key"
                    id="allBalanceCoinex"
                />
                <b-tooltip target="allBalanceCoinex" variant="success">
                    موجودی این ارز در کوینکس {{key}} در حال حاضر
                </b-tooltip>
            </b-col>
            <b-col lg="3" sm="6" v-for="(item, key) in statistic.balance.balance.kucoin">
                <statistic-card-horizontal
                    classTitle="font-medium-1"
                    icon="ShieldIcon"
                    color="info"
                    :statistic="'m:'+ toFixFloat(parseFloat(item.m.toFixed(crypto.percent)))+' | t:'+ toFixFloat(parseFloat(item.t.toFixed(crypto.percent))) + ' | s:' + toFixFloat(parseFloat(item.s.toFixed(crypto.percent)))"
                    :statistic-title="'موجودی کوکوین'+key"
                    id="allBalanceKucoinMain"
                />
                <b-tooltip target="allBalanceKucoinMain" variant="info">
                    موجودی کوکوین K{{key}} در ولت مخصوص خرید و فروش
                </b-tooltip>
            </b-col>

            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="PocketIcon"
                    color="info"
                    :statistic="toFixFloat(parseFloat((statistic.balance.balance.exonyx).toFixed(crypto.percent)))"
                    statistic-title="موجودی اونیکس"
                    id="allBalanceExonyx"
                />
                <b-tooltip target="allBalanceExonyx" variant="info">
                    موجودی اونیکس
                </b-tooltip>
            </b-col>

            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="PocketIcon"
                    color="success"
                    :statistic="toFixFloat(parseFloat((statistic.balance.sum_balance).toFixed(crypto.percent)))"
                    statistic-title="موجودی کل اکانت ها"
                    id="sum_balance"
                />
                <b-tooltip target="sum_balance" variant="success">
                    موجودی کل اکانت ها
                </b-tooltip>
            </b-col>

            <b-col lg="3" sm="6">
                <statistic-card-horizontal
                    icon="PackageIcon"
                    color="danger"
                    :statistic="toFixFloat(parseFloat(statistic.otherBalance.toFixed(crypto.percent)))"
                    statistic-title="موجودی سایر"
                    id="otherBalance"
                />
                <b-tooltip target="otherBalance" variant="danger">
                    موجودی سایر = موجودی کل کاربران - (موجودی کل اکانت ها)-(موجودی اندک+کارمزد معاملات)
                </b-tooltip>
            </b-col>
        </b-row>



        <b-overlay :show="!crypto" rounded="sm">
            <b-card v-if="crypto" id="st-crypto" class="border" style="border-width: 5px !important;" :style="{borderColor: crypto.color +' !important'}">
                <b-card-body class="px-0 px-md-1">
                    <div class="text-center">
                        <b-tabs align="center">
                            <b-tab :active="!settings.font" title="لوگو">
                                <img :src="baseURL+'images/currency/' + crypto.icon" width="120px"/>
                            </b-tab>
                            <b-tab :active="settings.font" :disabled="!crypto.hasFont" title="فونت آیکن">
                                <i class="cf" style="font-size: 120px" :class="'cf-'+crypto.symbol.toLowerCase()" :style="{color:crypto.color}"></i>
                            </b-tab>
                        </b-tabs>
                        <h4 class="mt-1 text-capitalize"> {{localeNameSymbol(crypto.symbol)[localeHas]}} -  {{localeNameSymbol(crypto.symbol)['en']}}</h4>
                    </div>

                    <b-row class="col-lg-6 px-0 mx-auto">
                        <hr class="w-100 my-2">
                        <validation-observer ref="simpleRules" class="w-100" >
                            <b-form ref="form" @submit.stop.prevent="handleSubmit" autocomplete="off">
                                <b-tabs>
                                    <b-tab active>
                                        <template #title>
                                            <feather-icon icon="SlidersIcon" />
                                            <span>اطلاعات پایه</span>
                                        </template>

                                        <b-col cols="12">
                                            <b-form-group label="نماد یا سیمبل" label-cols-md="4">
                                                <validation-provider #default="{ errors }" rules="required">
                                                    <b-form-input v-model="crypto.symbol" placeholder="نماد مانند BTC" :state="errors.length > 0 ? false:null"
                                                                  class="text-center text-uppercase"/>
                                                </validation-provider>
                                            </b-form-group>
                                        </b-col>

                                        <b-col cols="12">
                                            <b-form-group label="تعداد ارقام اعشار یا رند" label-cols-md="4">
                                                <validation-provider #default="{ errors }" rules="required|between:0,20">
                                                    <b-form-input v-model="crypto.percent" placeholder="اعشار نسبت به قیمت ارز" dir="ltr"
                                                                  maxlength="2" :state="errors.length > 0 ? false:null" class="text-center"/>
                                                </validation-provider>
                                            </b-form-group>
                                        </b-col>

                                        <b-col cols="12">
                                            <b-form-group label="وضعیت خرید" label-cols-md="4">
                                                <validation-provider #default="{ errors }" rules="required">
                                                    <b-form-select
                                                        v-model="crypto.buy_status"
                                                        :options="optionsStatus"
                                                        :state="errors.length > 0 ? false:null"
                                                    />
                                                </validation-provider>
                                            </b-form-group>
                                        </b-col>

                                        <b-col cols="12">
                                            <b-form-group label="وضعیت فروش" label-cols-md="4">
                                                <validation-provider #default="{ errors }" rules="required">
                                                    <b-form-select
                                                        v-model="crypto.sell_status"
                                                        :options="optionsStatus"
                                                        :state="errors.length > 0 ? false:null"
                                                    />
                                                </validation-provider>
                                            </b-form-group>
                                        </b-col>

                                        <b-col cols="12">
                                            <b-form-group label="وضعیت واریز" label-cols-md="4">
                                                <validation-provider #default="{ errors }" rules="required">
                                                    <b-form-select
                                                        v-model="crypto.deposit"
                                                        :options="optionsStatus"
                                                        :state="errors.length > 0 ? false:null"
                                                    />
                                                </validation-provider>
                                            </b-form-group>
                                        </b-col>

                                        <b-col cols="12">
                                            <b-form-group label="وضعیت برداشت" label-cols-md="4">
                                                <validation-provider #default="{ errors }" rules="required">
                                                    <b-form-select
                                                        v-model="crypto.withdraw"
                                                        :options="optionsStatus"
                                                        :state="errors.length > 0 ? false:null"
                                                    />
                                                </validation-provider>
                                            </b-form-group>
                                        </b-col>


                                        <b-col cols="12">
                                            <b-form-group label="وضعیت برداشت اتوماتیک" label-cols-md="4">
                                                <validation-provider #default="{ errors }" rules="required">
                                                    <b-form-select
                                                        v-model="crypto.withdraw_auto"
                                                        :options="optionsStatus"
                                                        :state="errors.length > 0 ? false:null"
                                                    />
                                                </validation-provider>
                                            </b-form-group>
                                        </b-col>

                                        <b-col cols="12">
                                            <b-form-group label="رنگ ارز" label-cols-md="4">
                                                <validation-provider #default="{ errors }" rules="required">
                                                    <b-form-input v-model="crypto.color"  placeholder="رنگ ارز را انتخاب کنید" type="color" :state="errors.length > 0 ? false:null" class="text-center"/>
                                                </validation-provider>
                                            </b-form-group>
                                        </b-col>




                                        <b-col cols="12">
                                            <b-form-group label="تغییر آیکن ارز" label-cols-md="4">
                                                <validation-provider #default="{ errors }">
                                                    <b-form-file id="icon"
                                                                 v-model="icon"
                                                                 placeholder="لوگو ارز را انتخاب کنید"
                                                                 drop-placeholder="Drop file here..."
                                                                 :state="errors.length > 0 ? false:null"
                                                    />
                                                    <small>فرمت مجاز png و svg</small>
                                                </validation-provider>
                                            </b-form-group>
                                        </b-col>

                                        <b-col cols="12" class="my-2">
                                            <b-form-group label="نحوه نمایش آیکن ارز" label-cols-md="4">
                                                <validation-provider #default="{ errors }">
                                                    <b-form-radio-group
                                                        v-model="settings.font"
                                                        :options="[{'text':'استفاده ار فونت','value':true},{'text':'استفاده ار لوگو','value':false}]"
                                                        class="demo-inline-spacing mt-0"
                                                        value-field="value"
                                                        text-field="text"
                                                        disabled-field="disabled"
                                                    />
                                                    <small>استفاده از فونت لوگو سبک تر و شکیل تر از آیکن میباشد.</small>
                                                </validation-provider>
                                            </b-form-group>
                                        </b-col>

                                        <b-col cols="12" class="my-2">
                                            <b-form-group label="وضعیت نمایش ارز" label-cols-md="4">
                                                <validation-provider #default="{ errors }">
                                                    <b-form-radio-group
                                                        v-model="settings.hidden"
                                                        :options="[{'text':'نمایان','value':true},{'text':'مخفی','value':false}]"
                                                        class="demo-inline-spacing mt-0"
                                                        value-field="value"
                                                        text-field="text"
                                                        disabled-field="disabled"
                                                    />
                                                    <small>وضعیت نمایش این رمز ارز در سیستم.</small>
                                                </validation-provider>
                                            </b-form-group>
                                        </b-col>

                                        <b-col cols="12">
                                            <b-form-group label="عدد جایگذاری در لیست" label-cols-md="4">
                                                <validation-provider #default="{ errors }" rules="between:0,100000">
                                                    <b-form-input v-model="crypto.sort" placeholder="عدد جایگیری در لیست"
                                                                  :state="errors.length > 0 ? false:null" class="text-center" dir="ltr"/>
                                                </validation-provider>
                                                <small>اختیاری و هر چه عدد بزرگتر باشد در بلاتر قرار میگیرد.</small>
                                            </b-form-group>
                                        </b-col>

                                    </b-tab>
                                    <b-tab>
                                        <template #title>
                                            <feather-icon icon="ZapIcon" />
                                            <span>نام ها</span>
                                        </template>
                                        <p class="px-1">
                                            با توجه به چند زبانه بودن پلتفرم باید برای هر زبانی که مورد استفاده قرار میگیرد نام ارز ها رو به آن زبان درج کنید.
                                        </p>
                                        <b-col cols="12">
                                            <b-form-group label="نام ارز به انگلیسی" label-cols-md="4">
                                                <validation-provider #default="{ errors }" rules="required">
                                                    <b-form-input v-model="crypto.name" placeholder="نام به انگلیسی مانند bitcoin" dir="ltr"
                                                                  :state="errors.length > 0 ? false:null" class="text-center"/>
                                                </validation-provider>
                                            </b-form-group>
                                        </b-col>
                                        <b-col cols="12" v-for="lang in locales">
                                            <b-form-group :label="'نام به '+lang.name" label-cols-md="4" v-if="lang.symbol!='en'">
                                                <validation-provider #default="{ errors }" rules="required">
                                                    <b-form-input v-model="cryptoLocale[lang.symbol].name" :placeholder="'نام به '+lang.name" :state="errors.length > 0 ? false:null" class="text-center"/>
                                                </validation-provider>
                                            </b-form-group>
                                        </b-col>
                                    </b-tab>

                                    <b-tab :disabled="crypto.network.length <=0">
                                        <template #title>
                                            <feather-icon icon="GridIcon" />
                                            <span>شبکه ها</span>
                                        </template>
                                        <b-col cols="12" v-if="crypto.network.length>0">
                                            <b-form-group label="شبکه دیفالت ارز" label-cols-md="4">
                                                <validation-provider #default="{ errors }" rules="required">
                                                    <b-form-select
                                                        v-model="networkDefault"
                                                        :options="networkOptions"
                                                        :state="errors.length > 0 ? false:null"
                                                    />
                                                </validation-provider>
                                            </b-form-group>
                                            <b-form-group :label="'شبکه '+(key+2)" label-cols-md="4" v-for="(item,key) in otherNetworkCrypto">
                                                <validation-provider #default="{ errors }" rules="required"
                                                                     class="d-flex align-items-center">
                                                    <b-form-select class="w-75"
                                                        v-model="otherNetworkCrypto[key].id_network"
                                                        :options="networkOptions"
                                                        :state="errors.length > 0 ? false:null"
                                                    />
                                                    <div class="text-center mx-auto">
                                                        <feather-icon @click="otherNetworkCrypto.splice(key, 1);" icon="XIcon" size="25" class="text-danger mx-auto cursor-pointer"/>
                                                        <feather-icon @click="otherNetworkCrypto[key].status = !otherNetworkCrypto[key].status" :icon="!item.status ? 'EyeOffIcon' :'EyeIcon'" size="20" class="text-primary mx-auto cursor-pointer"/>
                                                    </div>
                                                </validation-provider>
                                            </b-form-group>

                                            <b-col cols="8" offset-md="4" class="px-0">
                                                <b-button variant="outline-success" block class="text-center" @click="addNetwork()">
                                                    <div>افزودن شبکه</div>
                                                </b-button>
                                            </b-col>

                                        </b-col>
                                    </b-tab>

                                    <b-tab>
                                        <template #title>
                                            <feather-icon icon="DollarSignIcon" />
                                            <span>قیمت و موجودی</span>
                                        </template>

                                        <b-col cols="12" class="my-2">
                                            <b-form-group label="قیمت گذاری بر اساس؟" label-cols-md="4">
                                                <validation-provider #default="{ errors }">
                                                    <b-form-radio-group
                                                        v-model="settings.price_usdt_satatus"
                                                        :options="[{'text':'اتوماتیک','value':true},{'text':'قیمت دلاری ارز در اینجا','value':false}]"
                                                        class="demo-inline-spacing mt-0"
                                                        value-field="value"
                                                        text-field="text"
                                                        disabled-field="disabled"
                                                    />
                                                </validation-provider>
                                            </b-form-group>
                                        </b-col>

                                        <b-col cols="12">
                                            <b-form-group label="درج قیمت دلاری" label-cols-md="4">
                                                <validation-provider #default="{ errors }" rules="required|between:0,30000">
                                                    <b-form-input v-model="settings.price_usdt" placeholder="درج قیمت دلاری" :disabled="!settings.price_usdt_satatus"
                                                                  :state="errors.length > 0 ? false:null" class="text-center" dir="ltr"/>
                                                </validation-provider>
                                            </b-form-group>
                                        </b-col>


                                        <hr class="w-100">

                                        <b-col cols="12" class="mt-2">
                                            <b-form-group label="کارمزد خرید" label-cols-md="4">
                                                <validation-provider #default="{ errors }" rules="required|between:-5000,5000">
                                                    <b-form-input v-model="settings.wage_buy" placeholder="درصد برای خرید"
                                                                  :state="errors.length > 0 ? false:null" class="text-center" dir="ltr"/>
                                                </validation-provider>
                                                <small>بالای صد به تومان محاسبه میشود.</small>
                                            </b-form-group>
                                        </b-col>
                                        <b-col cols="12">
                                            <b-form-group label="کارمزد فروش" label-cols-md="4">
                                                <validation-provider #default="{ errors }" rules="required|between:-5000,5000">
                                                    <b-form-input v-model="settings.wage_sell" placeholder="درصد برای فروش"
                                                                  :state="errors.length > 0 ? false:null" class="text-center" dir="ltr"/>
                                                </validation-provider>
                                                <small>بالای صد به تومان محاسبه میشود.</small>
                                            </b-form-group>
                                        </b-col>


<!--
                                     <hr class="w-100">
                                     <b-col cols="12" class="my-2">
                                            <b-form-group label="استعلام موجودی بر اساس؟" label-cols-md="4">
                                                <validation-provider #default="{ errors }">
                                                    <b-form-radio-group
                                                        v-model="settings.stock_api"
                                                        :options="[{'text':'موجودی تتری','value':true},{'text':'موجودی در اینجا','value':false}]"
                                                        class="demo-inline-spacing mt-0"
                                                        value-field="value"
                                                        text-field="text"
                                                        disabled-field="disabled"
                                                    />
                                                </validation-provider>
                                            </b-form-group>
                                        </b-col>

                                        <b-col cols="12">
                                            <b-form-group label="موجودی" label-cols-md="4">
                                                <validation-provider #default="{ errors }" rules="required|between:0,1000000000">
                                                    <b-form-input v-model="settings.stock" placeholder="موجودی" :disabled="settings.stock_api"
                                                                  :state="errors.length > 0 ? false:null" class="text-center" dir="ltr"/>
                                                </validation-provider>
                                            </b-form-group>
                                        </b-col>-->

                                    </b-tab>

                                    <b-tab>
                                        <template #title>
                                            <feather-icon icon="FlagIcon" />
                                            <span>سایر</span>
                                        </template>

                                        <b-col cols="12">
                                            <b-form-group label="صرافی مربوطه" label-cols-md="4">
                                                <b-form-select
                                                    id="exchange"
                                                    v-model="crypto.exchange"
                                                    :options="exchangeOptions"
                                                    placeholder="صرافی مربوطه"
                                                />
                                            </b-form-group>
                                        </b-col>
                                        <b-col cols="12">
                                            <b-form-group label="اکانت صرافی مربوطه" label-cols-md="4">
                                                <b-form-select
                                                    id="exchange"
                                                    v-model="settings.exchange_account"
                                                    :options="exchangeAccountOptions"
                                                    placeholder="اکانت صرافی"
                                                />
                                            </b-form-group>
                                        </b-col>
                                        <hr class="w-100">

                                        <b-col cols="12">
                                            <b-form-group label="شناسه در coinmarketcap" label-cols-md="4">
                                                <validation-provider #default="{ errors }" >
                                                    <b-form-input v-model="settings.coinmarketcapId" :state="errors.length > 0 ? false:null"
                                                                  class="text-center text-uppercase"/>
                                                </validation-provider>
                                                <small>برای دریافت نمودار تغییر روزانه</small>
                                            </b-form-group>
                                        </b-col>

                                        <b-col cols="12">
                                            <b-form-group label="موجودی کول ولت" label-cols-md="4">
                                                <validation-provider #default="{ errors }" >
                                                    <b-form-input v-model="settings.coolwallet" :state="errors.length > 0 ? false:null"
                                                                  class="text-center text-uppercase"/>
                                                </validation-provider>
                                                <small>موجودی سایر ولت ها</small>
                                            </b-form-group>
                                        </b-col>

                                        <ChangeBalance :balanceBinance="statistic.allBalanceBinance"
                                                       :networks="networks"
                                                       :cryptoNetworks="crypto.network"
                                                       :crypto="crypto" v-if="activeUserInfo.role === 'admin'"
                                                        @getCrypto="getCrypto" @getList="forceRerender"
                                        />

                                    </b-tab>
                                </b-tabs>

                                <hr class="w-100">

                                <b-col cols="8" offset-md="4">
                                    <b-button variant="primary" block class="text-center d-flex align-items-center justify-content-center" type="submit" :disabled="isLoading">
                                        <div>ذخیره تغییرات</div>
                                        <div class="line-height-0 ml-25"><b-spinner v-if="isLoading" small></b-spinner></div>
                                    </b-button>
                                </b-col>

                            </b-form>
                        </validation-observer>
                    </b-row>
                </b-card-body>
            </b-card>
        </b-overlay>

        <balance-list :crypto="crypto" v-if="crypto && renderComponent"/>
    </div>
    <div v-else>
        <NotAccessed/>
    </div>
</div>
</template>

<script>
    import BCardActions from '@core/components/b-card-actions/BCardActions.vue'
    import {
        BCard,
        BCardHeader,
        BBadge,
        BCollapse,
        BLink,
        BCardBody,
        BRow,
        BForm,
        BCol,
        BInputGroup,
        BInputGroupAppend,
        BFormGroup,
        BFormInput,
        BTable,
        BTableSimple,
        BThead,
        BTr,
        BTh,
        BTd,
        BTbody,
        BButton,
        BAlert,
        BInputGroupPrepend,
        BFormSelect,
        BFormFile,
        BFormRadioGroup,
        BTab,
        BTabs,
        BSpinner,
        BTooltip,
        BOverlay,
        VBTooltip

    } from 'bootstrap-vue'
    import vSelect from 'vue-select'
    import axiosIns from "@/libs/axios";
    import ToastificationContent from "@core/components/toastification/ToastificationContent";
    import StatisticCardHorizontal from '@core/components/statistics-cards/StatisticCardHorizontal.vue'


    import Table from "@/views/vuexy/table/bs-table/Table";
    import { ValidationProvider, ValidationObserver } from 'vee-validate'
    import {
        required,between
    } from '@validations'
    import BalanceList from "./balance-list/BalanceList";
    import ChangeBalance from './ChangeBalance'
    import NotAccessed from "@/views/vuexy/pages/miscellaneous/NotAccessed";
    import Ripple from "vue-ripple-directive";

    export default {
        data () {
            return {
                renderComponent: true,
                isLoading: false,
                optionsStatus : [{'text':'فعال','value':'1'},{'text':'غیر فعال','value':'0'}],
                crypto: null,
                locales: null,
                cryptoLocale: [],
                networkDefault: null,
                networks: [],
                networkOptions: [],
                otherNetworkCrypto:[],
                icon: null,
                settings: [],
                data: null,
                statistic: [],
                exchangeOptions: [
                    {text: 'کوینکس', value: 'coinex'},
                    {text: 'بایننس', value: 'binance'},
                    {text: 'کوکوین', value: 'kucoin'},
                    {text:'صرافی موبوط به ارز را انتخاب کنید', value:null, disabled:true, hidden:true}
                ],
                exchangeAccountOptions: [

                ]
            }
        },
        directives: {
            'b-tooltip': VBTooltip,
            Ripple,
        },
        components: {
            ChangeBalance,
            BalanceList,
            Table,
            BTable,BThead, BTr, BTh, BTd, BTbody,BTableSimple,
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
            vSelect, ValidationProvider, ValidationObserver,BInputGroupPrepend,BForm, BFormSelect,
            BFormRadioGroup,BTab,BTabs,BSpinner,BTooltip,BOverlay,
            StatisticCardHorizontal, NotAccessed
        },
        methods:{
            forceRerender() {
                this.renderComponent = false;
                this.$nextTick(function () {
                    this.renderComponent = true;
                });
            },
            getCrypto(id){
                axiosIns.post('/setting/crypto/info/'+id)
                .then(response => {
                    this.locales = response.data.locales;
                    this.cryptoLocale = JSON.parse(response.data.crypto.locale);
                    this.settings = JSON.parse(response.data.crypto.settings);
                    this.data = JSON.parse(response.data.crypto.data);
                    this.locales.map(lang => {
                        if(!this.cryptoLocale[lang.symbol]){
                            this.cryptoLocale[lang.symbol] = {'name':''};
                        }
                    })
                    if(response.data.crypto.network.length > 0){
                        this.networkDefault = response.data.crypto.network.find(item => item.is_default && item.is_default === true).id_network;
                        this.otherNetworkCrypto = response.data.crypto.network.filter(item => !item.is_default || item.is_default !== true);
                    }
                    this.crypto = response.data.crypto;
                    this.networks = response.data.networks;
                    this.statistic = response.data.statistic;
                    this.exchange_list = response.data.exchange;

                    document.title = this.$t('settings')+' '+this.localeNameSymbol(this.crypto.symbol)[this.localeHas];

                })
                .catch((error) => { console.log(error); this.errorFetching(); })
            },
            handleSubmit() {
                this.$refs.simpleRules.validate().then(success => {
                    if (success) {
                        this.isLoading = true;
                        var formData = new FormData();
                        var imagefile = document.querySelector('#icon');
                        if( imagefile.files[0])
                            formData.append("file", imagefile.files[0]);

                        if (this.crypto.icon) {
                            delete this.crypto.icon;
                        }
                        for ( var key in this.crypto ) {
                            formData.append(key, this.crypto[key]);
                        }

                        formData.append("networkDefault", this.networkDefault);
                        formData.append("network",JSON.stringify(this.otherNetworkCrypto));
                        formData.append("localeCrypto",JSON.stringify(this.cryptoLocale));
                        formData.append("settings",JSON.stringify(this.settings));

                        axiosIns.post('setting/crypto/edit/'+this.crypto.id+'',formData) .then(response => {
                            if(response.data.status == true){
                                this.getCrypto(this.$router.currentRoute.params.id)
                                this.getGeneralInfoApi()
                                this.$toast({
                                    component: ToastificationContent,
                                    props: {
                                        title: 'انجام شد!',
                                        text: response.data.msg,
                                        icon: 'CheckCircleIcon',
                                        variant: 'success',
                                    },
                                })
                            }else {
                                this.$swal({icon: 'warning',title: 'نکته!',text: response.data.msg, confirmButtonText: 'باشه'})
                            }

                            this.isLoading = false;
                        })
                        .catch(() => {
                            this.errorFetching();
                            this.isLoading = false;
                        })
                    }else{
                        this.$swal({icon: 'warning',title: 'نکته!',text: 'تمامی فیلد ها رو بررسی کنید!',confirmButtonText: 'باشه'})
                    }
                })
            },
            addNetwork(){
                var obj = {status:false, id_network:null};
                this.otherNetworkCrypto.push(obj);
            }
        },
        watch:{
            'crypto.exchange'(val){
                this.exchangeAccountOptions = [];
                this.exchange_list[val].map((el)=>{
                    this.exchangeAccountOptions.push({text:el.name, value:el.key})
                })

                if(!this.settings.exchange_account)
                    this.settings.exchange_account = 0;
                //var obj = {status:false, id_network:null};
                //this.otherNetworkCrypto.push(obj);
            },
            'settings.font'(val){
                if(val && !this.crypto.hasFont){
                    this.$swal({
                        icon: 'warning',
                        title: 'نکته!',
                        text: 'متاسفانه برای این ارز فونت آیکنی وجود ندارد و به اجبار میبایست از  عکس به عنوان آیکن اسفاده شود.',
                        confirmButtonText: 'باشه'
                    }).then(()=>{
                        this.settings.font = false;
                    })
                }
            },
            networks(val){
                var networkOptions = [];
                val.map(function(item) {
                    var obj = {text:(item.name), value:item.id };
                    networkOptions.push(obj);
                });
                var obj = {text:'شبکه را انتخاب کنید', value:null, disabled:true, hidden:true};
                networkOptions.push(obj);
                this.networkOptions = networkOptions;
            }
        },
        created() {
            if(this.accessUserLogin['setting-crypto']['single'] || this.activeUserInfo.role === 'admin')
                this.getCrypto(this.$router.currentRoute.params.id)
        }
    }
</script>

<style lang="scss">
#st-crypto{
    .demo-inline-spacing.mt-0 .custom-radio{
        margin-top: 0px !important;
    }
}
</style>
