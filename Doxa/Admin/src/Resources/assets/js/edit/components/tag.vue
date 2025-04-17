<template>

    <div class="relative">
        <slot name="label"></slot>
        <input 
            type="text" 
            class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
            @keyup="searchTag($event)"
        >

        <div class="flex flex-wrap gap-2 mt-4 mb-4" v-if="tags.length">
            <p
                class="flex items-center px-2 py-1 font-semibold text-white bg-gray-600 rounded"
                v-for="tag in tags"
            >
                <span v-text="tag.tag"></span>

                <span
                    class="icon-cross cursor-pointer text-lg text-white ltr:ml-1.5 rtl:mr-1.5"
                    @click="removeTag(tag.id)"
                >
                </span>
            </p>
        </div>

        <div 
            v-if="search.length"
            class="absolute z-10 w-full rounded bg-white shadow-[0px_8px_10px_0px_rgba(0,0,0,0.20),0px_6px_30px_0px_rgba(0,0,0,0.12),0px_16px_24px_0px_rgba(0,0,0,0.14)] dark:bg-gray-900" 
            style="min-width: 310px; top: 78px; left: 0px;" 
            tag="div"
        >
            <ul>
                <li 
                    v-for="tag in search" 
                    class="flex items-center justify-between px-5 py-2 text-sm text-gray-600 cursor-pointer hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-950"
                    @click="attachTag(tag)"
                >
                    {{ tag.tag }}
                    <span v-if="tag.exists" class="text-gray-400 dark:text-gray-500">exists</span>
                </li>
                
            </ul>
        </div>

        <slot name="error"></slot>
    </div>
    
</template>

<script>
export default {
    //@keyup.enter="addTag($event)"
    props: ['_value', 'params'],
    data() {
       return {
            value: this._value,
            tags: this._value,
            search: [],
            loading: false,
       }
    },
    updated() {
        this.$parent.updateData(this.tags);
        console.log('this.tags: ',this.tags);
    },
    methods: {

        attachTag(tag) {
            if(tag.exists){
                this.$emitter.emit('add-flash', { type: 'warning', message: 'Tag already attached' });
                return false;
            }
            this.tags.push(tag);
            this.search = [];
        },
        searchTag(event) {

            let val = event.target.value.trim();
            if(val.length < 3){
                this.search = [];
                return;
            }

            if(event.code == 'Enter'){
                if(this.search.length){
                    return false;
                }  

                if(this.isTagExists(val)){
                    this.$emitter.emit('add-flash', { type: 'warning', message: 'Tag already exists' });
                    return false;
                }
                
                let tag = {
                    tag: val,
                    id: 0,
                }

                this.tags.push(tag);
                event.target.value = '';
            }

            let params = {
                str: val,
                column: 'tag',
                action: 'search',
            }  

            axios.get('/admin/tag/free_action/'+this.$parent.getId(), {params: params})
                .then(response => {
                    if(!response.data ?. list?. length){
                        this.search = [];
                    }
                    this.search = response.data.list;
                    if(this.tags.length){
                        for(var i=0; i<this.search.length; i++){
                            var sr = this.search[i];
                            for(var j=0; j<this.tags.length; j++){
                                var tg = this.tags[j];
                                if(sr.id == tg.id){
                                    this.search[i].exists = true;
                                }
                            }
                        }
                    }

                })
                .catch((error) => {
                    // this.loading = false;
                    // this.$emit('add-flash', { type: 'error', message: error.message });
                });
        },
        removeTag(id) {
            this.tags = this.tags.filter(tag => tag.id !== id);
        },
        isTagExists(str) {
            if(this.tags.length){
                for(var i=0; i<this.tags.length; i++){
                    if(str == this.tags[i].tag){
                        return true;
                    }   
                }       
            }
            return false;
        },

    }
};
</script>