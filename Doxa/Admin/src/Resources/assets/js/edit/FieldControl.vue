<template>
    <component :is="getFieldControl()" :_value="getValue()" :params="getParams()" :key="dotted_name">
        <template #label>
            <FieldLabel :field="field" />
        </template>
        <template #error v-if="getError()">
            <FieldError :error="getError()" />
        </template>
    </component>         
</template>



<script>

import FieldLabel from "./elements/FieldLabel.vue";
import FieldError from "./elements/FieldError.vue";

import InputControl from "./components/input.vue"; // OK
import TextareaControl from "./components/textarea.vue"; // OK
import TinyControl from "./components/tiny.vue"; // OK
import CheckboxControl from "./components/checkbox.vue"; // OK
import ImgControl from "./components/img.vue"; // OK
import RelatedControl from "./components/related.vue"; // OK
import TagControl from "./components/tag.vue";
import DatetimeControl from "./components/datetime.vue";

export default {
    data() {
       return {
            set: this._set,
            dotted_name: '',
       }
    },
    props: {
        channel_id: '',
        locale_id: '',
        fkey: String,
        _set: Object,
        field: Object
    },
    components: {
        FieldLabel,
        FieldError,

        InputControl,
        TextareaControl,
        CheckboxControl,
        ImgControl,
        TinyControl,
        RelatedControl,
        TagControl,
        DatetimeControl,
    },
    updated() {

    },
    created() {
        this.dotted_name = this.getFieldDottedName();
    },
    methods: {
        getParams() {
            return {
                dotted_name: this.dotted_name,
                field: this.field,
            }
        },
        getFieldControl() {
            return this.field.control.charAt(0).toUpperCase() + this.field.control.slice(1)+'Control';
        },
        getValue() {
            //console.log(this.field.key, 'this.locale_id', this.locale_id, 'this.channel_id', this.channel_id);
            //return '';
            return this.locale_id && typeof this.channel_id !== 'undefined' ? this.set.data.variation[this.channel_id][this.locale_id][this.fkey] : this.set.data[this.fkey];
        },
        getId() {
            return this.set.item.id;
        },
        getFieldDottedName(){
            return (this.channel_id && this.locale_id) ? 'variation.'+this.channel_id+'.'+this.locale_id+'.'+this.fkey : this.fkey;
        },

        updateData(value){
            //console.log('this.fkey: ',this.fkey,' value: ',value);
            if(this.channel_id || this.locale_id) {
                this.set.data.variation[this.channel_id][this.locale_id][this.fkey] = value;
            } else {
                
                this.set.data[this.fkey] = value;
            }
        },
        getError(){
            if(this.set.errors ?. [this.dotted_name]) {
                return this.set.errors[this.dotted_name];
            }
            return '';
        },
 
    }
};
</script>