<template>
    <transition 
        enter-active-class="transition duration-200 ease-out" 
        enter-from-class="opacity-0"
        enter-to-class="opacity-100" 
        leave-active-class="transition duration-100 ease-out"
        leave-from-class="opacity-100" 
        leave-to-class="opacity-0"
        id="confirm-modal"
    >
        <div v-show="isOpen" 
            class="fixed inset-0 z-50 transition-opacity bg-gray-900 bg-opacity-30"
            aria-hidden="true"></div>
    </transition>

    <!-- Modal dialog -->
    <transition enter-active-class="transition duration-200 ease-in-out" enter-from-class="translate-y-4 opacity-0"
        enter-to-class="translate-y-0 opacity-100" leave-active-class="transition duration-200 ease-in-out"
        leave-from-class="translate-y-0 opacity-100" leave-to-class="translate-y-4 opacity-0">
        <div v-show="isOpen" :id="id"
            class="fixed inset-0 z-50 flex items-center justify-center px-4 my-4 overflow-hidden sm:px-6" role="dialog"
            aria-modal="true">
            <div ref="modalContent"
                class="w-full max-w-lg max-h-full overflow-auto bg-white rounded-lg shadow-lg dark:bg-gray-800">
                <!-- Modal header -->
                <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-700/60">
                    <div class="flex items-center justify-between">
                        <div class="font-semibold text-gray-800 dark:text-gray-100">{{ title }}</div>
                        <button class="text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400"
                            @click="disagree">
                            <div class="sr-only">Close</div>
                            <svg class="fill-current" width="16" height="16" viewBox="0 0 16 16">
                                <path
                                    d="M7.95 6.536l4.242-4.243a1 1 0 111.415 1.414L9.364 7.95l4.243 4.242a1 1 0 11-1.415 1.415L7.95 9.364l-4.243 4.243a1 1 0 01-1.414-1.415L6.536 7.95 2.293 3.707a1 1 0 011.414-1.414L7.95 6.536z" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="px-5 pt-4 pb-1">
                    <div class="text-sm">
                        <div class="space-y-2">
                        <p>{{ message }}</p>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Modal footer -->
                  <div class="px-5 py-4">
                    <div class="flex flex-wrap justify-end space-x-2">

                        <button type="button" class="btn btn-secondary"
                            @click="disagree">
                            {{ options.btnDisagree }}
                        </button>

                        <template v-if="buttons.length">
                            <button type="button"
                                class="btn"
                                v-for="button in buttons" :key="button.title"
                                :class="button.button_class ? 'btn-' + button.button_class : 'btn-primary'"
                                @click="runCallback(button.callback)"
                            >
                                {{ button.title }}
                            </button>
                        </template>
                        <template v-else>
                            <button type="button" class="primary-button" @click="agree">
                                {{ options.btnAgree }}
                            </button>
                        </template>

                    
                    </div>
                  </div>

            </div>
        </div>
    </transition>



</template>

<script>
export default {
    props: {

    },
    inject: [
        'vocab',
    ],
    data() {
        return {
            isOpen: false,
            title: '',
            message: '',
            agreeCallback: null,
            disagreeCallback: null,
            buttons: [],
            options: {
                btnDisagree: '',
                btnAgree: '',
            },
        };
    },

    created() {
        this.registerGlobalEvents();
    },

    methods: {
        open({
            title = "",
            message = "",
            options = {
                btnDisagree: this.vocab['actions.disagree-btn'],
                btnAgree: this.vocab['actions.agree-btn'],
            },
            buttons = [],
            agree = () => { },
            disagree = () => { }
        }) {

            //console.log('message: ', message);

            this.isOpen = true;

            document.body.style.overflow = 'hidden';

            this.title = title;

            this.message = message;

            this.options = options;

            this.buttons = buttons;

            this.agreeCallback = agree;

            this.disagreeCallback = disagree;

            //console.log('this.agreeCallback: ',this.agreeCallback);
            //console.log('this.disagreeCallback: ',this.disagreeCallback);
        },
        runCallback(callback) {
            this.isOpen = false;
            document.body.style.overflow = 'auto';
            callback();
        },

        disagree() {
            this.isOpen = false;
            document.body.style.overflow = 'auto';
            this.disagreeCallback();
        },

        agree() {
            this.isOpen = false;

            document.body.style.overflow = 'auto';

            this.agreeCallback();
        },

        registerGlobalEvents() {
            //console.log('this.open: ', this.open);
            this.$emitter.on('open-confirm-modal', this.open);
        },
    }
};
</script>