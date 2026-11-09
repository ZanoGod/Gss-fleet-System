<template>
  <svg
    class="classic-loader"
    viewBox="0 0 24 24"
    fill="none"
    aria-hidden="true"
    focusable="false"
  >
    <g stroke="currentColor" stroke-linecap="round" stroke-width="2.4">
      <line
        v-for="spoke in 12"
        :key="spoke"
        x1="12"
        y1="2.2"
        x2="12"
        y2="4.7"
        :transform="`rotate(${(spoke - 1) * 30} 12 12)`"
        :opacity="spoke / 12"
      />
    </g>
  </svg>
</template>
