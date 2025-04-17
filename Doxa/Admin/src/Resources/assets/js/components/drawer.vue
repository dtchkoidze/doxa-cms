<template>
    <div>
        <!-- Toggler -->
        <div @click="open">
            <slot name="toggle" @click="open"> </slot>
        </div>

        <!-- Overlay -->
        <transition
            tag="div"
            name="drawer-overlay"
            enter-class="duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-class="duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                class="fixed inset-0 z-[10001] bg-gray-500 bg-opacity-50 transition-opacity"
                v-show="isOpen"
            ></div>
        </transition>

        <!-- Content -->
        <transition
            tag="div"
            name="drawer"
            :enter-from-class="enterFromLeaveToClasses"
            enter-active-class="transition duration-200 ease-in-out transform"
            enter-to-class="translate-x-0"
            leave-from-class="translate-x-0"
            leave-active-class="transition duration-200 ease-in-out transform"
            :leave-to-class="enterFromLeaveToClasses"
        >
            <div
                class="fixed z-[10002] bg-white dark:bg-gray-900 max-sm:!w-full"
                :class="{
                    'inset-x-0 top-0': position == 'top',
                    'inset-x-0 bottom-0': position == 'bottom',
                    'inset-y-0 ltr:right-0 rtl:left-0': position == 'right',
                    'inset-y-0 ltr:left-0 rtl:right-0': position == 'left',
                }"
                :style="'width:' + width"
                v-if="isOpen"
            >
                <div
                    class="w-full h-full overflow-auto bg-white pointer-events-auto dark:bg-gray-800"
                >
                    <div class="flex flex-col w-full h-full">
                        <div class="flex-1 min-w-0 min-h-0 overflow-auto">
                            <div class="flex flex-col h-full">
                                <!-- Header Slot-->
                                <div
                                    class="grid gap-y-2.5 border-b p-3 dark:border-gray-700 max-sm:px-4"
                                >
                                    <slot name="header" :close="close"> </slot>

                                    <div
                                        class="absolute top-3 ltr:right-3 rtl:left-3"
                                    >
                                        <span
                                            class="text-3xl cursor-pointer icon-cross hover:rounded-md hover:bg-gray-100 dark:hover:bg-gray-950"
                                            @click="close"
                                        >
                                            <i class="fa-solid fa-xmark"></i>
                                        </span>
                                    </div>
                                </div>

                                <!-- Content Slot -->
                                <slot name="content"></slot>

                                <!-- Footer Slot -->
                                <slot name="footer"></slot>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script>
export default {
    props: {
        isActive: {
            type: Boolean,
            default: false,
        },
        position: {
            type: String,
            default: "right",
        },
        width: {
            type: String,
            default: "300px",
        },
    },
    data() {
        return {
            isOpen: this.isActive,
        };
    },
    watch: {
        isActive: function (newVal, oldVal) {
            this.isOpen = newVal;
        },
    },
    updated() {},
    computed: {
        enterFromLeaveToClasses() {
            if (this.position == "top") {
                return "-translate-y-full";
            } else if (this.position == "bottom") {
                return "translate-y-full";
            } else if (this.position == "left") {
                return "ltr:-translate-x-full rtl:translate-x-full";
            } else if (this.position == "right") {
                return "ltr:translate-x-full rtl:-translate-x-full";
            }
        },
    },
    methods: {
        toggle() {
            this.isOpen = !this.isOpen;

            if (this.isOpen) {
                document.body.style.overflow = "hidden";
            } else {
                document.body.style.overflow = "scroll";
            }

            this.$emit("toggle", { isActive: this.isOpen });
        },

        open() {
            this.isOpen = true;

            document.body.style.overflow = "hidden";

            this.$emit("open", { isActive: this.isOpen });
        },

        close() {
            this.isOpen = false;

            document.body.style.overflow = "auto";

            this.$emit("close", { isActive: this.isOpen });
        },
    },
};
</script>
