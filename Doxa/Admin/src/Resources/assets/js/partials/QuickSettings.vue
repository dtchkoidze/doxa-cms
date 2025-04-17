<template>
    <div class="mt-6 border-t border-gray-200 dark:border-gray-700/60 pt-4 ">
        <div class="flex items-center justify-between cursor-pointer p-3 rounded-lg hover:bg-gray-100  text-gray-500/80 dark:text-gray-400/80 hover:dark:text-gray-800/80 hover:text-gray-800/80 transition-all duration-300 ease-in-out"
            @click="handleExpand">
            <div class="flex items-center space-x-2">
                <span class=" font-medium">Quick
                    Settings</span>
            </div>
            <span class="text-gray-500">
                <i :class="is_expanded ? 'fas fa-chevron-up rotate-180' : 'fas fa-chevron-down'"
                    class="transition-transform duration-300 ease-in-out"></i>
            </span>
        </div>

        <transition enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 max-h-0 overflow-hidden" enter-to-class="opacity-100 max-h-24 overflow-hidden"
            leave-active-class="transition-all duration-300 ease-in"
            leave-from-class="opacity-100 max-h-24 overflow-hidden" leave-to-class="opacity-0 max-h-0 overflow-hidden">
            <div v-if="is_expanded" class="mx-3 mt-3 space-y-3 text-gray-500/80 dark:text-gray-400/80">
                <label for="exit_on_save" class="flex items-center justify-between">
                    <span class=" ">Enable Exit On Save</span>
                    <input id="exit_on_save" type="checkbox"
                        class="form-checkbox text-sky-900 rounded-md h-5 w-5 transition-colors duration-200"
                        v-model="exit_on_save" />
                </label>
            </div>
        </transition>
    </div>
</template>

<script>
export default {
    data() {
        return {
            is_expanded: false,
            exit_on_save: false,
        }
    },
    watch: {
        exit_on_save: function () {
            localStorage.setItem('exit_on_save', this.exit_on_save);
        },
        is_expanded: function () {
            localStorage.setItem('quick_settings_expanded', this.is_expanded);
        },
    },
    methods: {
        handleExpand() {
            this.is_expanded = !this.is_expanded;
        },
    },
    created() {
        this.is_expanded = localStorage.getItem('quick_settings_expanded') === 'true';
        this.exit_on_save = localStorage.getItem('exit_on_save') === 'true';
    }
}
</script>
