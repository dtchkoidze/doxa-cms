import axios from "axios";
window.axios = axios;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
window.axios.defaults.baseURL = window.location.origin;
window.axios.defaults.withCredentials = true;
window.axios.interceptors.request.use((config) => {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
    if (match) {
        config.headers["X-XSRF-TOKEN"] = decodeURIComponent(match[1]);
    }
    return config;
});

const pageQuery = Object.fromEntries(new URLSearchParams(window.location.search));
if (Object.keys(pageQuery).length > 0) {
    window.axios.interceptors.request.use((config) => {
        config.params = { ...pageQuery, ...(config.params || {}) };
        return config;
    });
}
import { createApp } from "vue";
import { loadDictionary, vocab } from "@doxa-dict/useDictionary.js";
import Emitter from "./utils/emitter";
import Login from "./apps/Login.vue";
import Register from "./apps/Register.vue";
import Suspended from "./apps/Suspended.vue";
import Verify from "./apps/Verify.vue";
import Password from "./apps/Password.vue";
import WaitingForActivate from "./apps/WaitingForActivate.vue";
import Recovery from "./apps/Recovery.vue";
import WrongVerificationLink from "./apps/WrongVerificationLink.vue";
import SessionExpired from "./apps/SessionExpired.vue";
import GoogleLink from "./apps/GoogleLink.vue";
import FacebookLink from "./apps/FacebookLink.vue";
import TwoFactor from "./apps/TwoFactor.vue";


//import WrongVerificationToken from "./apps/WrongVerificationToken.vue";
// import Cookies from 'vue-cookies';

window.emailValidate = function (email) {
    var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
};

const app = createApp({
    components: {
        Login,
        Register,
        Suspended,
        Verify,
        Password,
        WaitingForActivate,
        Recovery,
        WrongVerificationLink,
        SessionExpired,
        GoogleLink,
        FacebookLink,
        TwoFactor,
    },
    data() {
        return {};
    },
    methods: {},
    mounted() {
        //console.log("user.js mounted");
    },
});

[Emitter].forEach((plugin) => app.use(plugin));

// Как в Eventer public app: сначала словарь, потом window + globalProperties, потом mount
const locale = document.documentElement.lang || "en";
loadDictionary(locale).then(() => {
    window.vocab = vocab;
    app.config.globalProperties.vocab = vocab;
    app.mount("#auth-app");
});
