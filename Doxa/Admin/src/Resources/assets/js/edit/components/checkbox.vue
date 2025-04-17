<template>
    <label class="flex items-center">
        <input type="checkbox" class="form-checkbox" :id="params.dotted_name" value="1" :checked="value" @change="value = !value">
        <span class="ml-2 text-sm">
            {{ params.field.title }}
            <span v-if="params.field.required" class="ml-1 text-xs text-blue-600 dark:text-blue-300">{{ params.field.required }}</span>
        </span>
    </label>
    <span v-if="params.field.rule" class="text-xs text-blue-600 dark:text-blue-300">{{ params.field.rule }}</span>
    <slot name="error"></slot>
</template>

<script>
export default {
    props: ['_value', 'params'],
    data() {
       return {
            value: this._value ? 1 : 0
       }
    },
    updated() {
        // console.log('updated: this.value: ', this.value);
        this.$parent.updateData(this.value ? 1 : 0);
    },
    mounted() {
        console.log("This parent: ", this.$parent);
    }
};
</script>
