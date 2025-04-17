<template>
    <!-- Modal backdrop -->
    <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
        enter-to-class="opacity-100" leave-active-class="transition duration-100 ease-out"
        leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-show="modalOpen" class="fixed inset-0 z-50 transition-opacity bg-gray-900 bg-opacity-30" aria-hidden="true">
        </div>
    </transition>
    <!-- Modal dialog -->
    <transition enter-active-class="transition duration-200 ease-in-out" enter-from-class="translate-y-4 opacity-0"
        enter-to-class="translate-y-0 opacity-100" leave-active-class="transition duration-200 ease-in-out"
        leave-from-class="translate-y-0 opacity-100" leave-to-class="translate-y-4 opacity-0">
        <div v-show="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center px-4 my-4 overflow-hidden sm:px-6"
            role="dialog" aria-modal="true">
            <div ref="modalContent"
                class="w-full max-w-lg max-h-full overflow-auto bg-white rounded-lg shadow-lg dark:bg-gray-800">
                <div class="flex p-5 space-x-2">

                    <!-- Icon -->
                    <div class="flex items-start justify-center pt-2 pr-3 shrink-0">
                        <i v-if="data?.type === 'success'" class="text-green-500 fa-solid fa-circle-check fa-3x"></i>
                        <i v-if="data?.type === 'error'" class="text-red-700 fa-solid fa-triangle-exclamation fa-3x"></i>
                        <i v-if="data?.type === 'warning'" class="text-orange-500 fa-solid fa-triangle-exclamation fa-3x"></i>
                    </div>
                    <!-- Content -->
                    <div class="flex-1">
                        <!-- Modal header -->
                        <div class="mb-1">
                            <div class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ data?.title }}</div>
                        </div>
                        <!-- Modal content -->
                        <div class="mb-3 text-sm">
                            <div class="space-y-2">
                                {{ data?.message }}
                            </div>
                        </div>
                        <!-- Modal footer -->
                        <div class="flex flex-wrap justify-end gap-2">
                            <template v-for="button in data?.buttons" :key="button?.type">
                                <button v-if="button.type === 'cancel'"
                                    @click="closeModal"
                                    class="btn"
                                    :class="button.style ? 'btn-' + button.style : ''"
                                >
                                    {{ button.title }}
                                </button>
                                <button v-else
                                    @click="action(button)"
                                    class="btn"
                                    :class="button.style ? 'btn-' + button.style : 'btn-primary'"
                                    :disabled='button.disabled'
                                    v-html="button.timer && button.timer > 0 ? `<div class='flex items-center justify-between'><div>${button.title}</div><div class='w-6 text-right'>${button.timer}</div>` : button.title"
                                >
                                </button>
                            </template>
                            
                          </div>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
export default {
    data() {
        return {
            modalOpen: false,
            data: null,
        };
    },
    methods: {
        openModal(data) {
            //console.log('openModal() data',data);
            this.modalOpen = true;
            this.data = data;
            for(let i = 0; i < this.data.buttons.length; i++){
                if(this.data.buttons[i].timer && this.data.buttons[i].timer > 0){
                    this.data.buttons[i].disabled = true;
                    this.data.buttons[i].interval = setInterval(() => {
                        if(this.data.buttons[i].timer > 0){
                            this.data.buttons[i].timer -= 1;
                        } else {
                            this.data.buttons[i].disabled = false;
                        }
                    }, 1000);

                }
            }
        },
        closeModal(){
            this.modalOpen = false;
            if(this.data?.buttons){
                for(let i = 0; i < this.data.buttons.length; i++){
                    if(this.data.buttons[i].interval){
                        console.log(' destroyInterval');
                        clearInterval(this.data.buttons[i].interval);
                    }
                }
            }
        },
        action(button) {
            if(button.callback) {
                if(this.data.parent) {
                    this.data.parent[button.callback]();
                } else {
                    let callback = button.callback;
                    callback();
                }
            }
            if(button.url) {
                window.location.href = button.url;
            }
            this.modalOpen = false;
        },
        incrementCodeTimer(i) {
            setInterval(() => {
                if(this.resend_timer > 0){
                    this.resend_timer -= 1;
                }
            }, 1000);
        },
    },
    mounted() {
        this.$emitter.on('open-confirm-modal', this.openModal)
    },
    unmounted() {
        this.$emitter.off('open-confirm-modal', this.openModal)
    }
};
</script>






<!-- 
<script>
import { ref, onMounted, onUnmounted } from 'vue'

export default {
  name: 'ModalEmpty',
  props: ['id', 'modalOpen'],
  emits: ['close-modal'],
  setup(props, { emit }) {

    const modalContent = ref(null)

    // close on click outside
    const clickHandler = ({ target }) => {
      if (!props.modalOpen || modalContent.value.contains(target)) return
      emit('close-modal')
    }

    // close if the esc key is pressed
    const keyHandler = ({ keyCode }) => {
      if (!props.modalOpen || keyCode !== 27) return
      emit('close-modal')
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
      modalContent,
    }    
  }  
}
</script> -->