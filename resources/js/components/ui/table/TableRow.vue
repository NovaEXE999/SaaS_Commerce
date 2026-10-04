<template>
  <tr :class="customClass">
    <slot />
  </tr>
</template>
<script setup lang="ts">
defineProps<{ customClass?: string }>();
</script>
