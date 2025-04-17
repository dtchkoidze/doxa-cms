<template>
    <div class="flex items-center justify-between">
        <label class="block mb-1 text-sm font-medium" for="tooltip">
            {{ field.title }}
            <span v-if="isRequired()" class="text-red-500">*</span>
        </label>

        <Tooltip class="ml-2" bg="dark" size="md" position="left" v-if="field.rule">
            <div class="text-sm text-gray-200">{{ field.rule }}</div>
        </Tooltip>
    </div>
</template>

<script>
import Tooltip from "../../components/Tooltip.vue";

export default {
    props: {
        field: Object,
    }, 
    components: {
        Tooltip,
    },
    methods: {
        isRequired() {
            if(this.field.required){
                return true;
            }
            if(this.field.validation_rules){
                for (const [key, value] of Object.entries(this.field.validation_rules)) {
                    if(value.includes('required')) return true;
                }
                return false;
            }
        },
    },
};
</script>
