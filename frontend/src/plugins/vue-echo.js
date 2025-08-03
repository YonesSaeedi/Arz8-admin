// VueEcho
import VueEcho from 'vue-echo'
import pusher from 'pusher-js'
import Vue from 'vue'

Vue.use(VueEcho, {
  authEndpoint : 'http://localhost/zarinbit/Api-lumen/public/v1/broadcasting/auth',
 // authEndpoint : 'https://zarinbit.com/panel/api/public/v1/broadcasting/auth',
 // authEndpoint : 'https://zarinbit.com/panel/api/public/v1/broadcasting/auth',
  auth: {
    headers: {
      'Authorization': `Bearer ${localStorage.getItem('accessToken')}`
    }
  },
  broadcaster: 'pusher',
  key: '8b686d5b12e216596285',
  //wsHost: window.location.hostname,
  //wsPort: 6001,
  //forceTLS: false,
  cluster: 'eu'
})
export default VueEcho
