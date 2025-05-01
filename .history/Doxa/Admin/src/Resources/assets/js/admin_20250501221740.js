import axios from "axios";
window.axios = axios;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
window.axios.defaults.baseURL = window.location.origin;
// import socket from "./plugins/io";
import { createApp } from "vue";

import Banner from "./components/Banner.vue";
import HeaderVue from "./components/Header.vue";

import Emitter from "./plugins/emitter";

import Sidebar from "./partials/Sidebar.vue";

import Omni from "./base/Omni.vue";
import TreeView from "./components/TreeView.vue";
import Acl from "./partials/Acl.vue";
import Configuration from "./components/configuration/Configuration.vue";

import { color_log } from "./tools/logger";

window.clog = color_log;

const app = createApp({
	components: {
		Banner,
		HeaderVue,
		TreeView,
		Acl,
		Sidebar,
		Omni,
		Configuration,
	},
	data() {
		return {};
	},
	methods: {},
	mounted() {
		// socket.emit("testing", { data: "testing_data" });
		//console.log("test admin.js  mounted vue vrum vruuum");
		// Check if pkg reads both consumer and local env variables
		// console.log(import.meta.env);
	},
});

[Emitter].forEach((plugin) => app.use(plugin));

app.mount("#admin");
