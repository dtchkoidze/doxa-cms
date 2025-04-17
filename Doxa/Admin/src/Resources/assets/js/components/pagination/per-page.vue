<template>
    <dropdown>
        <template #toggle>
            <span>{{ per_page }}</span>
        </template>
        <template #options>
            <button v-for="option in options" :key="option"
                class="flex items-center px-3 py-1 cursor-pointer hover:bg-gray-50 hover:dark:bg-gray-700/20"
                :class="option === per_page && 'text-violet-500'"
                @click="$parent.changePerPageOption(option)"
            >
                <svg class="mr-2 fill-current shrink-0 text-violet-500"
                    :class="option !== per_page && 'invisible'" width="12" height="9" viewBox="0 0 12 9">
                    <path
                        d="M10.28.28L3.989 6.575 1.695 4.28A1 1 0 00.28 5.695l3 3a1 1 0 001.414 0l7-7A1 1 0 0010.28.28z" />
                </svg>
                <span>{{ option }}</span>
            </button>
        </template>
    </dropdown>
</template>

<script>
import dropdown from "../../components/dropdown/DropdownClassic.vue";

export default {
    props: [
        'per_page',
        'options',
    ],
    components: {
        dropdown,
    },
};
</script>
