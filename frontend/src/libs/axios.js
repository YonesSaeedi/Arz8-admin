import Vue from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import store from '@/store'
import CryptoJS from 'crypto-js';


const axiosIns = axios.create({
  // You can add your headers here
  // ================================
   baseURL: 'https://admin.arz8x.com/api/v2',
   // baseURL: 'https://test-admin.arz8.com/api',
  // timeout: 1000,
  // headers: {'X-Custom-Header': 'foobar'}
    headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
        responseType: "json",
    },
})

// کلید خصوصی
const privateKey = store.state.data.privateKeyCrypt;
const keyApiSignature = CryptoJS.AES.decrypt(store.state.data.keyApiSignature, privateKey).toString(CryptoJS.enc.Utf8);

axiosIns.interceptors.request.use((config) => {
    const timestamp = Math.floor(Date.now() / 1000); // زمان فعلی به ثانیه

    let dataToHashData;
    if (config.data instanceof FormData) {
        // اگر داده‌ها `FormData` باشند
        const formDataCopy = {};
        config.data.forEach((value, key) => {
            if (key !== 'file') {
                formDataCopy[key] = value;
            }
        });
        dataToHashData = formDataCopy;
    } else {
        // اگر داده‌ها JSON باشند
        const { file, ...restData } = config.data || {}; // حذف فیلد `file`
        dataToHashData = restData;
    }

    const stringifiedDataToHash = Object.keys(dataToHashData || {}).length > 0 ? JSON.stringify(dataToHashData) : '[]';
    //console.log(stringifiedDataToHash);

    // داده‌هایی که باید هش شوند
    const dataToHash = `${config.method.toUpperCase()} api/v2/${config.url.replace(/^\//, '')} ${timestamp} ${stringifiedDataToHash}`;
    //console.log(dataToHash);

    // تولید هش
    const signature = CryptoJS.HmacSHA256(CryptoJS.enc.Utf8.parse(dataToHash), CryptoJS.enc.Utf8.parse(keyApiSignature)).toString(CryptoJS.enc.Hex);

    // اضافه کردن هدرها
    config.headers['X-Timestamp'] = timestamp;
    config.headers['X-Signature'] = signature;

    return config;
});


axiosIns.interceptors.response.use(
    response => {
        return Promise.resolve(response);
    },
    error => {
        const { status, data } = error.response;
        if (status === 408) {
            Swal.fire({
                title: 'عدم دسترسی',
                text: 'شما به این بخش دسترسی ندارید!',
                icon: 'warning',
                confirmButtonText: 'باشه'
            })
        }
        return Promise.reject(error);
    }
);


Vue.prototype.$http = axiosIns

export default axiosIns
