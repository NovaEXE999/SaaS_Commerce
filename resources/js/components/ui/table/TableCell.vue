<template>
  <td :class="customClass">
    <slot />
  </td>
</template>
<script setup lang="ts">
defineProps<{ customClass?: string }>();
</script>
