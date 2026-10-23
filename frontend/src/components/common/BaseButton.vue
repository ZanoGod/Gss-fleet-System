<script setup lang="ts">
withDefaults(defineProps<{ variant?: 'gold' | 'outline' | 'danger'; disabled?: boolean; type?: 'button' | 'submit' | 'reset' }>(), { variant: 'outline', disabled: false, type: 'button' })
</script>
<template><button :type="type" :disabled="disabled" :class="variant==='gold'?'btn-gold':variant==='danger'?'btn btn-danger':'btn-outline-warm'"><slot /></button></template>