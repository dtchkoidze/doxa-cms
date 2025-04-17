import axios from "axios";
window.axios = axios;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
window.axios.defaults.baseURL = window.location.origin;
import { createApp } from "vue";
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
//app.use(Cookies);
app.mount("#auth-app");



