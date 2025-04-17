<template>
    <label class="flex items-center">
        <input type="checkbox" class="form-checkbox" :id="params.field.key" :checked="Boolean(value)"
            @change="updateValue($event.target.checked)">

        <span class="ml-2 text-sm dark:text-gray-300">
            {{ params.field.title }}
            <span v-if="params.field.required" class="ml-1 text-xs text-blue-600 dark:text-blue-300">
                {{ params.field.required }}
            </span>
        </span>
    </label>

    <span v-if="params.field.rule" class="text-xs text-blue-600 dark:text-blue-300">
        {{ params.field.rule }}
    </span>

    <slot name="error"></slot>
</template>

<script>
export default {
    props: ['params', 'value'],
    emits: ['updateValue'],
    methods: {
        updateValue(new_value) {
            this.$emit('updateValue', new_value);
        }
    }
};
</script>
