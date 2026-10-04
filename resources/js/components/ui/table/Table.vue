<template>
  <table class="w-full caption-bottom text-sm" :class="customClass">
    <slot />
  </table>
</template>
<script setup lang="ts">
defineProps<{ customClass?: string }>();
</script>
