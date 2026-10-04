<template>
  <thead :class="customClass">
    <slot />
  </thead>
</template>
<script setup lang="ts">
defineProps<{ customClass?: string }>();
</script>
