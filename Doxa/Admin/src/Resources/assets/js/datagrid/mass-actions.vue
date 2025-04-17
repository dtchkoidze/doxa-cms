<template>
    <div>
        <dropdown>
            <template #toggle>
                <span>{{ vocab['toolbar.mass-actions.select-action'] }}</span>
            </template>
            <template #options>
                <button v-for="action in mass_actions" :key="action.action"
                    class="flex items-center px-3 py-1 cursor-pointer hover:bg-gray-50 hover:dark:bg-gray-700/20"
                    @click="$parent.performMassAction(action)"
                >
                    <span>{{ action.title }}</span>
                </button>
            </template>
        </dropdown>
    </div>
</template>

<script>
import dropdown from "../components/dropdown/DropdownClassic.vue";

export default {
    inject: ["vocab"],
    props: ["mass_actions"],
    components: {
        dropdown,
    },
};
</script>
