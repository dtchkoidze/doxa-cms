<template>
  <div class="relative inline-flex w-full">
    <button
      ref="trigger"
      class="justify-between w-full text-gray-600 bg-white border-gray-200 btn min-w-44 dark:bg-gray-800 dark:border-gray-700/60 hover:border-gray-300 dark:hover:border-gray-600 hover:text-gray-800 dark:text-gray-300 dark:hover:text-gray-100"
      aria-label="Select date range"
      aria-haspopup="true"
      @click.prevent="dropdownOpen = !dropdownOpen"
      :aria-expanded="dropdownOpen"
    >
      <span class="flex items-center">
        <span>{{options[selected].title}}</span>
      </span>
      <svg class="ml-1 text-gray-400 fill-current shrink-0 dark:text-gray-500" width="11" height="7" viewBox="0 0 11 7">
        <path d="M5.4 6.8L0 1.4 1.4 0l4 4 4-4 1.4 1.4z" />
      </svg>
    </button>
    <transition
      enter-active-class="transition duration-100 ease-out transform"
      enter-from-class="-translate-y-2 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-100 ease-out"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-show="dropdownOpen" class="z-10 absolute top-full left-0 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/60 py-1.5 rounded-lg shadow-lg overflow-hidden mt-1">
        <div
          ref="dropdown"
          class="text-sm font-medium text-gray-600 divide-y divide-gray-200 dark:text-gray-300 dark:divide-gray-700/60"
          @focusin="dropdownOpen = true"
          @focusout="dropdownOpen = false"
        >
            
            <button
                v-for="(option, index) in options"
                :key="index"
                class="flex items-center justify-between w-full px-3 py-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/20"
                :class="index === selected && 'text-violet-500'"
                @click="clickOption(option, index)"
            >
            <span>{{option.title}}</span>
            <svg class="ml-2 fill-current shrink-0 text-violet-400" :class="index !== selected && 'invisible'" width="12" height="9" viewBox="0 0 12 9">
              <path d="M10.28.28L3.989 6.575 1.695 4.28A1 1 0 00.28 5.695l3 3a1 1 0 001.414 0l7-7A1 1 0 0010.28.28z" />
            </svg>
          </button>          
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue'

export default {
    name: 'DropdownFull',
    props: {
        options: {
            type: Array,
            required: true
        },
        _selected: {
            type: Number,
            required: false,
            default: 0
        }
    },
    props: ['options', '_selected'],
    data() {
        return {
            dropdownOpen: false,
            trigger: null,
            dropdown: null,
            selected: 0
        }
    },
    methods: {
        clickOption(option, index) {
            this.selected = index; 
            this.dropdownOpen = false;
            this.$emitter.emit('select-option', option.id);
        },
        clickHandler({ target }) {
            //console.log('clickHandler: ', target);
            if (!this.dropdownOpen || this.dropdown.contains(target) || this.trigger.contains(target)) {
                return;
            }
            
            this.dropdownOpen = false
        },
        keyHandler({ keyCode }) {
            //console.log('keyHandler: ', keyCode);
            if (!this.dropdownOpen || keyCode !== 27) {
                return;
            }
            this.dropdownOpen = false
        }
    },
    mounted() {
        console.log(this.options);
        this.trigger = this.$refs.trigger;
        this.dropdown = this.$refs.dropdown;
        this.selected = this._selected;
        document.addEventListener('click', this.clickHandler)
        document.addEventListener('keydown', this.keyHandler)
    },
    unmounted() {
        document.removeEventListener('click', this.clickHandler)
        document.removeEventListener('keydown', this.keyHandler)
    },

}

// export default {
//   name: 'DropdownFull',
//   setup() {

//     const dropdownOpen = ref(false)
//     const trigger = ref(null)
//     const dropdown = ref(null)    
//     const selected = ref(0)

//     const options = ref([
//       {
//         id: 0,
//         period: 'Most Popular'
//       },
//       {
//         id: 1,
//         period: 'Newest'
//       },
//       {
//         id: 2,
//         period: 'Lowest Price'
//       },
//       {
//         id: 3,
//         period: 'Highest Price'
//       }
//     ])

//     // close on click outside
//     const clickHandler = ({ target }) => {
//       if (!dropdownOpen.value || dropdown.value.contains(target) || trigger.value.contains(target)) return
//       dropdownOpen.value = false
//     }

//     // close if the esc key is pressed
//     const keyHandler = ({ keyCode }) => {
//       if (!dropdownOpen.value || keyCode !== 27) return
//       dropdownOpen.value = false
//     }

//     onMounted(() => {
//       document.addEventListener('click', clickHandler)
//       document.addEventListener('keydown', keyHandler)
//     })

//     onUnmounted(() => {
//       document.removeEventListener('click', clickHandler)
//       document.removeEventListener('keydown', keyHandler)
//     })    
    
//     return {
//       dropdownOpen,
//       trigger,
//       dropdown,
//       selected,
//       options,
//     }
//   }
// }
</script>