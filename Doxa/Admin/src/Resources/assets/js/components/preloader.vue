<template>
    <transition
        tag="div"
        name="modal-overlay"
        enter-class="duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-class="duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            class="fixed inset-0 z-[10001] bg-gray-500 bg-opacity-50 transition-opacity"
        ></div>
    </transition>

    <transition
        tag="div"
        name="modal-content"
        enter-class="duration-300 ease-out"
        enter-from-class="translate-y-4 opacity-0 md:translate-y-0 md:scale-95"
        enter-to-class="translate-y-0 opacity-100 md:scale-100"
        leave-class="duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100 md:scale-100"
        leave-to-class="translate-y-4 opacity-0 md:translate-y-0 md:scale-95"
    >
        <div
            class="fixed inset-0 z-[10002] transform overflow-y-auto transition"
        >
            <div class="flex items-end justify-center min-h-full p-4 sm:items-center sm:p-0">
                <div class="w-[100px]">
                    <svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 300 150'><path fill='none' stroke='#FFFFFF' stroke-width='15' stroke-linecap='round' stroke-dasharray='300 385' stroke-dashoffset='0' d='M275 75c0 31-27 50-50 50-58 0-92-100-150-100-28 0-50 22-50 50s23 50 50 50c58 0 92-100 150-100 24 0 50 19 50 50Z'><animate attributeName='stroke-dashoffset' calcMode='spline' dur='2' values='685;-685' keySplines='0 0 1 1' repeatCount='indefinite'></animate></path></svg>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
export default {

};
</script>