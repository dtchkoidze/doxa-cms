<template>
    <div class="relative inline-flex">
        <button ref="trigger" class="inline-flex items-center justify-center group" aria-haspopup="true"
            @click.prevent="dropdownOpen = !dropdownOpen" :aria-expanded="dropdownOpen">
            <img class="w-8 h-8 rounded-full" :src="UserAvatar" width="32" height="32" alt="User" />
            <div class="flex items-center truncate">
                <span
                    class="ml-2 text-sm font-medium text-gray-600 truncate dark:text-gray-100 group-hover:text-gray-800 dark:group-hover:text-white">
                    {{ user?.name || 'User' }}
                </span>
                <svg class="w-3 h-3 ml-1 text-gray-400 fill-current shrink-0 dark:text-gray-500" viewBox="0 0 12 12">
                    <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
                </svg>
            </div>
        </button>
        <transition enter-active-class="transition duration-200 ease-out transform"
            enter-from-class="-translate-y-2 opacity-0" enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-out" leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <div v-show="dropdownOpen"
                class="origin-top-right z-10 absolute top-full min-w-44 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/60 py-1.5 rounded-lg shadow-lg overflow-hidden mt-1"
                :class="align === 'right' ? 'right-0' : 'left-0'">
                <div class="pt-0.5 pb-2 px-3 mb-1 border-b border-gray-200 dark:border-gray-700/60">
                    <div class="font-medium text-gray-800 dark:text-gray-100">{{ user?.name || 'User' }}</div>
                    <div v-if="!user?.other_roles?.length" class="text-xs italic text-gray-500 dark:text-gray-400">{{
                        user?.role
                        || 'Role' }}</div>
                </div>
                <!-- <div v-if="user?.other_roles?.length"
          class="pt-0.5 pb-2 px-3 mb-1 border-b border-gray-200 dark:border-gray-700/60">
          <select id="switch-role" @change="handleRoleChange($event)"
            class="w-full transition duration-150 border-gray-300 rounded-md form-select dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 focus:ring-violet-500 focus:border-violet-500">
            <option disabled selected value="{{ user?.role }}">{{ user?.role }}</option>
            <option v-for="role in user?.other_roles" :key="role.id" :value="role.name">
              {{ role.name }}
            </option>
          </select>
        </div> -->
                <ul ref="dropdown" @focusin="dropdownOpen = true" @focusout="dropdownOpen = false">
                    <!-- <li>
            <a class="flex items-center px-3 py-1 text-sm font-medium text-violet-500 hover:text-violet-600 dark:hover:text-violet-400"
              href="#" @click="dropdownOpen = false">Settings</a>
          </li> -->
                    <li>
                        <a class="flex items-center px-3 py-1 text-sm font-medium text-violet-500 hover:text-violet-600 dark:hover:text-violet-400"
                            href="/auth/logout" @click="triggerSignOutSideEffects">Sign Out</a>
                    </li>
                </ul>
            </div>
        </transition>
    </div>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue'
import UserAvatar from '../../images/user-avatar-32.png'

export default {
    name: 'DropdownProfile',
    props: ['align'],
    data() {
        return {
            UserAvatar: UserAvatar,
            user: null,
        }
    },

    methods: {
        triggerSignOutSideEffects() {
            this.dropdownOpen = false;
            if (window.PasswordCredential) {
                navigator.credentials.preventSilentAccess();
            }
        },
        fetchUser() {
            axios.get('admin/api/current_user')
                .then(response => {
                    //console.log("response: ", response.data);
                    this.user = response.data.user;
                })
                .catch(error => {
                    console.log(error);
                });

            //clog('this user:', this.user);
        },
        handleRoleChange(event) {
            //console.log('event:', event.target.value);
            axios.post('admin/api/switch_role', {
                new_role: event.target.value
            })
                .then(response => {
                    //console.log("response: ", response.data);
                    window.location.reload();
                })
                .catch(error => {
                    //console.log(error);
                });
        }
    },

    mounted() {
        this.fetchUser();
        // console.log('this.user', this.user);
    },

    watch: {
        user: function (newVal, oldVal) {
            //console.log('newVal:', newVal);
            //console.log('oldVal:', oldVal);
            this.user = newVal;
        }
    },
    setup() {

        const dropdownOpen = ref(false)
        const trigger = ref(null)
        const dropdown = ref(null)

        // close on click outside
        const clickHandler = ({ target }) => {
            if (!dropdownOpen.value || target.closest('select') || dropdown.value.contains(target) || trigger.value.contains(target)) return
            dropdownOpen.value = false
        }

        // close if the esc key is pressed
        const keyHandler = ({ keyCode }) => {
            if (!dropdownOpen.value || keyCode !== 27) return
            dropdownOpen.value = false
        }

        onMounted(() => {
            document.addEventListener('click', clickHandler)
            document.addEventListener('keydown', keyHandler)
        })

        onUnmounted(() => {
            document.removeEventListener('click', clickHandler)
            document.removeEventListener('keydown', keyHandler)
        })

        return {
            dropdownOpen,
            trigger,
            dropdown,
        }
    }
}
</script>
