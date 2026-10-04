<template>
  <tbody :class="customClass">
    <slot />
  </tbody>
</template>
<script setup lang="ts">
defineProps<{ customClass?: string }>();
</script>
