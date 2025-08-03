<template>
  <div class="misc-wrapper">
    <div class="misc-inner px-2 pt-0">
      <div class="w-100 text-center">
        <h2 class="mb-1">
          اطلاعاتی برای نمایش وجود ندارد!🕵🏻‍♀
        </h2>
        <p class="mb-2">
          دسترسی این بخش امکان پذیر نیست و میتوانید به صفحه قبل بازگردید!!
        </p>
        <b-button
          variant="primary"
          class="mb-1 btn-sm-block"
          @click="backRoute()"
        >صفحه قبل</b-button>
          <br>
        <b-img
          fluid
          :src="imgUrl"
          alt="404"
        />
      </div>
    </div>
  </div>
</template>

<script>
/* eslint-disable global-require */
import { BLink, BImg, BButton } from 'bootstrap-vue'
import VuexyLogo from '@core/layouts/components/Logo.vue'
import store from '@/store/index'
import { getHomeRouteForLoggedInUser } from '@/auth/utils'

export default {
  components: {
    BLink, BImg, BButton, VuexyLogo,
  },
  data() {
    return {
      downImg: require('@/assets/images/pages/not-authorized.svg'),
    }
  },
  computed: {
      imgUrl() {
          if (store.state.appConfig.layout.skin === 'dark') {
              // eslint-disable-next-line vue/no-side-effects-in-computed-properties
              this.downImg = require('@/assets/images/pages/404.png')
              return this.downImg
          }
          return this.downImg
      },
  },
  methods: {
      backRoute() {
          this.$router.back();
      },
  },
}
</script>

<style lang="scss">
@import '@core/scss/vue/pages/page-misc.scss';
</style>
