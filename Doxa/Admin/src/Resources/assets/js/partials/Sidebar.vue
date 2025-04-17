<template>
    <div class="min-w-fit">

        <div id="sidebar" ref="sidebar"
            class="hidden lg:!flex flex-col rounded-r-2xl shadow-sm absolute z-40 left-0 top-0 lg:static lg:left-auto lg:top-auto h-[100dvh] overflow-y-scroll lg:overflow-y-auto no-scrollbar transition-all duration-200 ease-in-out"
            :class="[
                sidebar_expanded ? 'w-64' : 'w-20',
                sidebar_expanded ? 'translate-x-0' : '-translate-x-30',
                'bg-white dark:bg-gray-800 p-4'
            ]">

            <!-- Sidebar header -->
            <div :class="[sidebar_expanded ? 'ml-1' : 'pr-3', 'flex justify-between  mb-10 sm:px-2']">
                <!-- Logo -->
                <a href="/admin/dashboard">
                    <svg class="fill-violet-500" xmlns="http://www.w3.org/2000/svg" width="32" height="32">
                        <path
                            d="M31.956 14.8C31.372 6.92 25.08.628 17.2.044V5.76a9.04 9.04 0 0 0 9.04 9.04h5.716ZM14.8 26.24v5.716C6.92 31.372.63 25.08.044 17.2H5.76a9.04 9.04 0 0 1 9.04 9.04Zm11.44-9.04h5.716c-.584 7.88-6.876 14.172-14.756 14.756V26.24a9.04 9.04 0 0 1 9.04-9.04ZM.044 14.8C.63 6.92 6.92.628 14.8.044V5.76a9.04 9.04 0 0 1-9.04 9.04H.044Z" />
                    </svg>
                </a>
            </div>

            <!-- Menu -->
            <div class="space-y-8">
                <div>
                    <ul class="">
                        <template v-for="(item, index) in menu" :key="index">
                            <li class="px-2 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))]"
                                :class="isActive(item.key) && 'from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]'">
                                <a :href="item.url" class="block text-gray-800 truncate transition dark:text-gray-100"
                                    :class="isActive(item.key) ? '' : 'hover:text-gray-900 dark:hover:text-white'">
                                    <div class="flex items-center pl-1.5">
                                        <div class="flex items-center justify-center w-6 h-6">
                                            <i class="fa fa-fw"
                                                :class="[item.icon, isActive(item.key) ? 'dark:text-white text-sky-800' : '']"></i>
                                        </div>
                                        <span
                                            class="ml-4 text-sm font-medium duration-200 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100">
                                            {{ item.name }}
                                        </span>
                                    </div>
                                </a>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>

            <div class="flex-grow"></div>
            <!-- expand btn bottom -->
            <div class="sticky bottom-0 right-0 left-4 w-full bg-white dark:bg-gray-800 p-4 shadow-md">
                <button class="text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400 w-full"
                    @click.prevent="sidebar_expanded = !sidebar_expanded">
                    <span class="sr-only">Expand / collapse sidebar</span>
                    <svg :class="[sidebar_expanded ? 'rotate-180' : '', 'shrink-0 fill-current text-gray-400 dark:text-gray-500']"
                        xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                        <path
                            d="M15 16a1 1 0 0 1-1-1V1a1 1 0 1 1 2 0v14a1 1 0 0 1-1 1ZM8.586 7H1a1 1 0 1 0 0 2h7.586l-2.793 2.793a1 1 0 1 0 1.414 1.414l4.5-4.5A.997.997 0 0 0 12 8.01M11.924 7.617a.997.997 0 0 0-.217-.324l-4.5-4.5a1 1 0 0 0-1.414 1.414L8.586 7M12 7.99a.996.996 0 0 0-.076-.373Z" />
                    </svg>
                </button>
            </div>


            <template v-if="sidebar_expanded">
                <quick-settings></quick-settings>
            </template>
        </div>
    </div>
</template>


<script>
import QuickSettings from './QuickSettings.vue';
export default {
    props: [
    ],
    components: {
        QuickSettings
    },
    data() {
        return {
            menu: [],
            sidebar_expanded: localStorage.getItem('sidebar-expanded') === 'true' || false,
        }
    },
    mounted() {
        axios.get('/admin/sidebar_menu')
            .then(response => {
                //console.log("menu: ", response.data.menu);
                this.menu = response.data.menu;
            })
            .catch((error) => {
                console.log(error);
            });


        //console.log("this.sidebar_expanded: ", this.sidebar_expanded);
    },
    methods: {
        isActive(key) {
            let url = window.location.pathname;
            return url.includes(key);
        },
        isExactActive() {
            //
        },
    },

    watch: {
        sidebar_expanded(value) {
            localStorage.setItem('sidebar-expanded', value);
        }
    }
};
</script>
