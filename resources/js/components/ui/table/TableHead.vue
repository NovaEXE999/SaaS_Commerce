<template>
  <th :class="customClass">
    <slot />
  </th>
</template>
<script setup lang="ts">
defineProps<{ customClass?: string }>();
</script>
