<template>
    <div class="relative bottom-left">
        
        <div
            class="flex select-none"
            ref="toggleBlock"
            @click="toggle()"
        >
            <slot name="toggle">Toggle</slot>
        </div>

        <transition
            tag="div"
            name="dropdown"
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <div
                class="absolute z-10 w-max rounded bg-white shadow-[0px_8px_10px_0px_rgba(0,0,0,0.20),0px_6px_30px_0px_rgba(0,0,0,0.12),0px_16px_24px_0px_rgba(0,0,0,0.14)] dark:bg-gray-900"
                :style="positionStyles"
                v-show="isActive"
            >
                <div v-show="$slots.content" class="p-5">
                    <slot name="content"></slot>
                </div>

                <ul v-show="$slots.menu">
                    <slot name="menu"></slot>
                </ul>

            </div>
        </transition>

    </div>
</template>

<script>
export default {
    props: {
        position: String,

        closeOnClick: {
            type: Boolean,
            required: false,
            default: true
        },
    },
    data() {
        return {
            toggleBlockWidth: 0,
            toggleBlockHeight: 0,
            isActive: false,
        }
    },
    created() {
        window.addEventListener('click', this.handleFocusOut);
    },

    mounted() {
        this.toggleBlockWidth = this.$refs.toggleBlock.clientWidth;
        this.toggleBlockHeight = this.$refs.toggleBlock.clientHeight;
        // console.log("this.closeOnClick", this.closeOnClick);

        console.log('mounted this.toggleBlockWidth: ', this.toggleBlockWidth);
        console.log('mounted this.toggleBlockHeight: ', this.toggleBlockHeight);
    },

    beforeDestroy() {
        window.removeEventListener('click', this.handleFocusOut);
    }, 
    computed: {
        positionStyles() {
            switch (this.position) {
                case 'bottom-left':
                    return [
                        `min-width: ${this.toggleBlockWidth}px`,
                        `top: ${this.toggleBlockHeight}px`,
                        'left: 0',
                    ];

                case 'bottom-right':
                    return [
                        `min-width: ${this.toggleBlockWidth}px`,
                        `top: ${this.toggleBlockHeight}px`,
                        'right: 0',
                    ];

                case 'top-left':
                    return [
                        `min-width: ${this.toggleBlockWidth}px`,
                        `bottom: ${this.toggleBlockHeight*2}px`,
                        'left: 0',
                    ];

                case 'top-right':
                    return [
                        `min-width: ${this.toggleBlockWidth}px`,
                        `bottom: ${this.toggleBlockHeight*2}px`,
                        'right: 0',
                    ];

                default:
                    return [
                        `min-width: ${this.toggleBlockWidth}px`,
                        `top: ${this.toggleBlockHeight}px`,
                        'left: 0',
                    ];
            }
        },
    },

    methods: {
        toggle() {
            this.isActive = ! this.isActive;
        },

        handleFocusOut(e) {
            if (! this.$el.contains(e.target) || (this.closeOnClick && this.$el.children[1].contains(e.target))) {
                this.isActive = false;
            }
        },

        
    },           
};
</script>