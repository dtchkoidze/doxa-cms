<template>
    <div class="relative inline-flex">
        <button ref="trigger"
            class="justify-between text-gray-600 bg-white border-gray-200 btn min-w-20 dark:bg-gray-800 dark:border-gray-700/60 hover:border-gray-300 dark:hover:border-gray-600 hover:text-gray-800 dark:text-gray-300 dark:hover:text-gray-100"
            aria-label="Select date range" aria-haspopup="true" @click.prevent="dropdownOpen = !dropdownOpen"
            :aria-expanded="dropdownOpen"
        >
            <span class="flex items-center">
                <slot name="toggle"></slot>
            </span>
            <svg class="ml-2 text-gray-400 fill-current shrink-0 dark:text-gray-500" width="11" height="7"
                viewBox="0 0 11 7">
                <path d="M5.4 6.8L0 1.4 1.4 0l4 4 4-4 1.4 1.4z" />
            </svg>
        </button>
        <transition 
            enter-active-class="transition duration-100 ease-out transform"
            enter-from-class="-translate-y-2 opacity-0" enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-100 ease-out" leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <div v-show="dropdownOpen"
                class="z-10 absolute top-full left-0 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/60 py-1.5 rounded-lg shadow-lg overflow-hidden mt-1">
                <div ref="dropdown" class="text-sm font-medium text-gray-600 dark:text-gray-300"
                    @focusin="dropdownOpen = true" @focusout="dropdownOpen = false"
                >
                    <slot name="options"></slot>
                </div>
            </div>
        </transition>
    </div>
</template>

<script>
// import { ref, onMounted, onUnmounted } from 'vue'

export default {
    name: 'DropdownClassic',
    props: ['options', 'selected'],
    emits: {
        closeDropdownOnSelect: () => {
            this.dropdownOpen = false;
        }
    },    
    data() {
        return {
            dropdownOpen: false,
            trigger: null,
            dropdown: null,
        }
    },
    created() {
        //console.log('created options: ',this.options);
        //this.registerGlobalEvents();
    },
    mounted() {
        this.registerGlobalEvents();

        document.addEventListener('click', this.clickHandler);
        document.addEventListener('keydown', this.keyHandler);

        //console.log('mounted options: ',this.options);

        
    },
    unmounted() {
        document.removeEventListener('click', this.clickHandler);
        document.removeEventListener('keydown', this.keyHandler);
    },
    methods: {
        clickHandler({ target }) {
            if (!this.dropdownOpen.value || this.dropdown.value.contains(target) || this.trigger.value.contains(target)) {
                return;
            }
            this.dropdownOpen.value = false
        },
        keyHandler({ keyCode }) {
            if (!this.dropdownOpen.value || keyCode !== 27) {
                return;
            }
            this.dropdownOpen.value = false
        },
        closeDropdown(){
            this.dropdownOpen = false;
        },
        registerGlobalEvents() {
            this.$emitter.on('close-dropdown', this.closeDropdown);
        },
    },
}
</script>