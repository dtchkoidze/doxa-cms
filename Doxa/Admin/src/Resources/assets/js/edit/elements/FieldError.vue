<template>
    <div class="mt-1 text-xs text-red-500">
        {{ error.join(', ') }}  
    </div> 
</template>

<script>
export default {
    props: ['error'],    
};
</script>
