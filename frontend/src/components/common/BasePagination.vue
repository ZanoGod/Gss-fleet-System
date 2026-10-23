<script setup lang="ts">
import { computed } from 'vue'
const props=defineProps<{ page:number; perPage:number; total:number; disabled?:boolean }>()
const emit=defineEmits<{ 'update:page':[page:number] }>()
const pages=computed(()=>Math.max(1,Math.ceil(props.total/props.perPage)))
</script>
<template><div class="gss-pagination"><span>{{ total ? ((page-1)*perPage+1)+'–'+Math.min(page*perPage,total)+' of '+total : 'No results' }}</span><div><button class="btn-outline-warm" :disabled="disabled || page<=1" @click="emit('update:page',page-1)">Previous</button><button class="btn-outline-warm" :disabled="disabled || page>=pages" @click="emit('update:page',page+1)">Next</button></div></div></template>
<style scoped>.gss-pagination,.gss-pagination div{display:flex;align-items:center;justify-content:space-between;gap:.5rem}.gss-pagination{color:var(--muted);font-size:.85rem}.gss-pagination button{padding:.4rem .7rem}</style>